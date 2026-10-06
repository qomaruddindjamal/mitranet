"""
MitraNet Transaction & Recovery Data Models (Pydantic v2).
Phase 1F: State Machine, Snapshot Models, Lock Metadata, and Audit Records.
"""

from enum import Enum
from typing import Dict, List, Optional, Any
from datetime import datetime, timezone
from pydantic import BaseModel, Field


class TransactionState(str, Enum):
    """Explicit Transaction State Machine (Phase 1F)."""
    IDLE = "IDLE"
    PREPARING = "PREPARING"
    VALIDATING = "VALIDATING"
    SNAPSHOTTING = "SNAPSHOTTING"
    APPLYING = "APPLYING"
    VERIFYING = "VERIFYING"
    COMMITTING = "COMMITTING"
    COMMITTED = "COMMITTED"
    ROLLING_BACK = "ROLLING_BACK"
    ROLLED_BACK = "ROLLED_BACK"
    FAILED = "FAILED"
    RECOVERY_REQUIRED = "RECOVERY_REQUIRED"


class NetworkStateSnapshot(BaseModel):
    """
    Complete physical and logical network state snapshot.
    Used for verifying pre-transaction state, calculating deltas, and atomic rollback.
    """
    snapshot_id: str
    timestamp: str = Field(default_factory=lambda: datetime.now(timezone.utc).isoformat())
    transaction_id: str
    state_hash: str
    
    # Live Network Links & Interface State
    interfaces: List[Dict[str, Any]] = Field(default_factory=list)
    # Master / Slave relationships (bridges, bonds, vrfs)
    slaves: Dict[str, List[str]] = Field(default_factory=dict)
    # Addresses
    addresses: Dict[str, List[str]] = Field(default_factory=dict)
    # Routes
    routes_v4: List[Dict[str, Any]] = Field(default_factory=list)
    routes_v6: List[Dict[str, Any]] = Field(default_factory=list)
    # Specific Virtual Devices
    vlans: List[Dict[str, Any]] = Field(default_factory=list)
    bridges: List[Dict[str, Any]] = Field(default_factory=list)
    bonds: List[Dict[str, Any]] = Field(default_factory=list)
    vrfs: List[Dict[str, Any]] = Field(default_factory=list)
    
    # Configuration State (raw dict or model dump)
    candidate_config: Optional[Dict[str, Any]] = None
    running_config: Optional[Dict[str, Any]] = None


class NetworkOperation(BaseModel):
    """An individual atomic or step-wise network operation in a transaction."""
    op_id: str
    op_type: str  # e.g., "create_vrf", "delete_vlan", "set_address", "add_route", etc.
    target: str
    params: Dict[str, Any] = Field(default_factory=dict)
    inverse_op: Optional[str] = None
    inverse_params: Dict[str, Any] = Field(default_factory=dict)
    executed: bool = False
    success: bool = False
    error: Optional[str] = None


class TransactionRecord(BaseModel):
    """Persistent transaction metadata for audit trails, crashes, and startup recovery."""
    transaction_id: str
    state: TransactionState = TransactionState.IDLE
    created_at: str = Field(default_factory=lambda: datetime.now(timezone.utc).isoformat())
    started_at: Optional[str] = None
    completed_at: Optional[str] = None
    candidate_version: int = 1
    running_version: int = 1
    snapshot_id: Optional[str] = None
    operation_count: int = 0
    applied_operation_count: int = 0
    rollback_operation_count: int = 0
    operations: List[NetworkOperation] = Field(default_factory=list)
    error: Optional[str] = None
    recovery_required: bool = False


class TransactionLockInfo(BaseModel):
    """Lock metadata preventing concurrent destructive configuration applications."""
    lock_id: str
    transaction_id: str
    acquired_at: str
    pid: int
    hostname: str
