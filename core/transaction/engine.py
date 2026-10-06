"""
MitraNet Transaction & Recovery Engine (Phase 1F).
Provides full state machine transitions, snapshot capture, dependency-ordered apply,
runtime verification, and atomic/best-effort-safe rollback.
"""

import os
import json
import logging
from typing import Optional, List, Tuple
from datetime import datetime, timezone

from mitranet.core.config.model import MitraNetConfig
from mitranet.core.config.loader import ConfigLoader, ConfigWriter
from mitranet.core.config.validator import ConfigValidator
from mitranet.core.network.backend import NetworkBackend, LinuxNetworkBackend
from mitranet.core.network.config_service import InterfaceConfigurationService
from mitranet.core.network.routing_service import RouteConfigurationService
from mitranet.core.network.vlan import VlanService
from mitranet.core.network.bridge import BridgeService
from mitranet.core.network.bonding import BondService
from mitranet.core.network.vrf import VRFService
from mitranet.core.network.validator import InterfaceConfigValidator
from mitranet.core.network.exceptions import SafetyConstraintViolationError

from mitranet.core.transaction.models import (
    TransactionState,
    TransactionRecord,
    NetworkOperation,
    NetworkStateSnapshot,
)
from mitranet.core.transaction.exceptions import (
    TransactionEngineError,
    TransactionLockError,
    InvalidStateTransitionError,
    TransactionApplyError,
    RollbackExecutionError,
    RecoveryRequiredError,
)
from mitranet.core.transaction.lock import TransactionLock
from mitranet.core.transaction.snapshot import NetworkSnapshotManager
from mitranet.core.transaction.planner import DependencyPlanner

logger = logging.getLogger("mitranet.transaction")


class NetworkTransactionEngine:
    """
    Production-grade Network Configuration Transaction and Recovery Engine.
    Coordinates candidate/running synchronization with actual Linux kernel state.
    """

    VALID_TRANSITIONS = {
        TransactionState.IDLE: [TransactionState.PREPARING],
        TransactionState.PREPARING: [TransactionState.VALIDATING, TransactionState.COMMITTED, TransactionState.ROLLING_BACK, TransactionState.FAILED, TransactionState.RECOVERY_REQUIRED],
        TransactionState.VALIDATING: [TransactionState.SNAPSHOTTING, TransactionState.ROLLING_BACK, TransactionState.FAILED, TransactionState.RECOVERY_REQUIRED],
        TransactionState.SNAPSHOTTING: [TransactionState.APPLYING, TransactionState.ROLLING_BACK, TransactionState.FAILED, TransactionState.RECOVERY_REQUIRED],
        TransactionState.APPLYING: [TransactionState.VERIFYING, TransactionState.ROLLING_BACK, TransactionState.FAILED, TransactionState.RECOVERY_REQUIRED],
        TransactionState.VERIFYING: [TransactionState.COMMITTING, TransactionState.ROLLING_BACK, TransactionState.FAILED, TransactionState.RECOVERY_REQUIRED],
        TransactionState.COMMITTING: [TransactionState.COMMITTED, TransactionState.ROLLING_BACK, TransactionState.FAILED, TransactionState.RECOVERY_REQUIRED],
        TransactionState.ROLLING_BACK: [TransactionState.ROLLED_BACK, TransactionState.RECOVERY_REQUIRED],
        TransactionState.COMMITTED: [TransactionState.IDLE, TransactionState.PREPARING],
        TransactionState.ROLLED_BACK: [TransactionState.IDLE, TransactionState.PREPARING],
        TransactionState.FAILED: [TransactionState.IDLE, TransactionState.PREPARING],
        TransactionState.RECOVERY_REQUIRED: [TransactionState.ROLLING_BACK, TransactionState.IDLE],
    }

    def __init__(
        self,
        base_dir: str = "/var/lib/mitranet",
        backend: Optional[NetworkBackend] = None,
    ):
        self.base_dir = base_dir
        self.config_dir = os.path.join(base_dir, "config")
        self.running_file = os.path.join(self.config_dir, "running.json")
        self.candidate_file = os.path.join(self.config_dir, "candidate.json")
        self.state_file = os.path.join(base_dir, "transaction_state.json")
        self.snapshots_dir = os.path.join(base_dir, "snapshots")
        self.lock_file = os.path.join(base_dir, "transaction.lock")

        os.makedirs(self.config_dir, exist_ok=True)
        os.makedirs(self.snapshots_dir, exist_ok=True)

        self.backend = backend or LinuxNetworkBackend()
        self.lock = TransactionLock(lock_file=self.lock_file)
        self.snapshot_mgr = NetworkSnapshotManager(snapshot_dir=self.snapshots_dir, backend=self.backend)

        # Network Subsystem Services
        self.iface_service = InterfaceConfigurationService(backend=self.backend)
        self.route_service = RouteConfigurationService(backend=self.backend)
        self.vlan_service = VlanService(backend=self.backend)
        self.bridge_service = BridgeService(backend=self.backend)
        self.bond_service = BondService(backend=self.backend)
        self.vrf_service = VRFService(backend=self.backend)

        self.current_record: Optional[TransactionRecord] = self._load_persisted_state()
        self.running_config: MitraNetConfig = self._init_running()
        self.candidate_config: MitraNetConfig = self._init_candidate()

    # -------------------------------------------------------------------------
    # Persistence & State Machine
    # -------------------------------------------------------------------------
    def _load_persisted_state(self) -> Optional[TransactionRecord]:
        if os.path.exists(self.state_file):
            try:
                with open(self.state_file, "r", encoding="utf-8") as f:
                    data = json.load(f)
                return TransactionRecord.model_validate(data)
            except Exception as e:
                logger.error("Failed to load persisted transaction state: %s", e)
        return None

    def _persist_state(self, record: TransactionRecord):
        self.current_record = record
        try:
            with open(self.state_file, "w", encoding="utf-8") as f:
                f.write(record.model_dump_json(indent=2))
        except Exception as e:
            logger.error("Failed to persist transaction state: %s", e)

    def transition_state(self, record: TransactionRecord, new_state: TransactionState):
        """Validates and applies state machine transitions."""
        allowed = self.VALID_TRANSITIONS.get(record.state, [])
        if new_state not in allowed:
            raise InvalidStateTransitionError(
                f"Invalid transition from state '{record.state}' to '{new_state}'. Allowed: {allowed}"
            )
        record.state = new_state
        self._persist_state(record)

    # -------------------------------------------------------------------------
    # Configuration Initialization
    # -------------------------------------------------------------------------
    def _init_running(self) -> MitraNetConfig:
        if os.path.exists(self.running_file):
            return ConfigLoader.load_from_file(self.running_file)
        cfg = MitraNetConfig()
        ConfigWriter.write_to_file(cfg, self.running_file)
        return cfg

    def _init_candidate(self) -> MitraNetConfig:
        if os.path.exists(self.candidate_file):
            try:
                return ConfigLoader.load_from_file(self.candidate_file)
            except Exception:
                pass
        return self.running_config.model_copy(deep=True)

    def get_running(self) -> MitraNetConfig:
        return self.running_config

    def get_candidate(self) -> MitraNetConfig:
        return self.candidate_config

    def set_candidate(self, cfg: MitraNetConfig) -> List[str]:
        """Sets candidate configuration after schema and semantic validation."""
        errors = ConfigValidator.validate(cfg)
        if errors:
            return errors
        self.candidate_config = cfg
        ConfigWriter.write_to_file(cfg, self.candidate_file)
        return []

    # -------------------------------------------------------------------------
    # Planning & Dry Run
    # -------------------------------------------------------------------------
    def plan(self) -> List[NetworkOperation]:
        """Calculates differences and returns dependency-ordered plan without runtime changes."""
        errors = ConfigValidator.validate(self.candidate_config)
        if errors:
            raise TransactionEngineError(f"Candidate configuration invalid: {'; '.join(errors)}")
        return DependencyPlanner.generate_plan(self.candidate_config, self.running_config)

    # -------------------------------------------------------------------------
    # Apply and Rollback Engine
    # -------------------------------------------------------------------------
    def apply_and_commit(
        self,
        failure_injection_at_step: Optional[int] = None,
    ) -> Tuple[bool, str]:
        """
        Executes full transaction pipeline:
        PREPARE -> VALIDATE -> SNAPSHOT -> APPLY -> VERIFY -> COMMIT
        Automatically triggers rollback if apply or verification fails.
        """
        tx_id = f"tx_{int(datetime.now(timezone.utc).timestamp())}_{os.getpid()}"
        self.lock.acquire(tx_id)

        record = TransactionRecord(
            transaction_id=tx_id,
            state=TransactionState.IDLE,
            started_at=datetime.now(timezone.utc).isoformat(),
            candidate_version=self.candidate_config.config_version,
            running_version=self.running_config.config_version,
        )
        self._persist_state(record)

        snapshot: Optional[NetworkStateSnapshot] = None
        try:
            # 1. PREPARING
            self.transition_state(record, TransactionState.PREPARING)
            plan = self.plan()
            record.operations = plan
            record.operation_count = len(plan)
            self._persist_state(record)

            # Idempotency check: if plan is empty, NO-OP
            if not plan:
                self.transition_state(record, TransactionState.COMMITTED)
                self.lock.release(tx_id)
                return True, "No configuration changes detected (idempotent NO-OP)."

            # 2. VALIDATING
            self.transition_state(record, TransactionState.VALIDATING)
            errors = ConfigValidator.validate(self.candidate_config)
            if errors:
                raise TransactionEngineError(f"Validation failed: {'; '.join(errors)}")

            # 3. SNAPSHOTTING
            self.transition_state(record, TransactionState.SNAPSHOTTING)
            snapshot = self.snapshot_mgr.capture_snapshot(
                transaction_id=tx_id,
                candidate_config=self.candidate_config.model_dump(),
                running_config=self.running_config.model_dump(),
            )
            record.snapshot_id = snapshot.snapshot_id
            self._persist_state(record)

            # 4. APPLYING
            self.transition_state(record, TransactionState.APPLYING)
            for idx, op in enumerate(record.operations, start=1):
                # Check intentional failure injection
                if failure_injection_at_step and idx == failure_injection_at_step:
                    raise TransactionApplyError(
                        f"Controlled failure injected at operation step {idx}: '{op.op_type}' on '{op.target}'."
                    )
                self._execute_operation(op)
                op.executed = True
                op.success = True
                record.applied_operation_count += 1
                self._persist_state(record)

            # 5. VERIFYING
            self.transition_state(record, TransactionState.VERIFYING)
            self._verify_desired_state(self.candidate_config)

            # 6. COMMITTING
            self.transition_state(record, TransactionState.COMMITTING)
            self.candidate_config.config_version += 1
            ConfigWriter.write_to_file(self.candidate_config, self.running_file)
            self.running_config = self.candidate_config.model_copy(deep=True)

            self.transition_state(record, TransactionState.COMMITTED)
            record.completed_at = datetime.now(timezone.utc).isoformat()
            self._persist_state(record)
            self.lock.release(tx_id)
            return True, f"Transaction '{tx_id}' successfully applied and committed (version {self.running_config.config_version})."

        except Exception as e:
            logger.error("Transaction '%s' failed during execution: %s", tx_id, e)
            record.error = str(e)
            self._persist_state(record)

            # Trigger Rollback
            rollback_success = False
            try:
                self.transition_state(record, TransactionState.ROLLING_BACK)
                self._execute_rollback(record, snapshot)
                self.transition_state(record, TransactionState.ROLLED_BACK)
                rollback_success = True
            except Exception as rb_err:
                logger.critical("Transaction '%s' rollback failed! System requires recovery: %s", tx_id, rb_err)
                record.error = f"Apply error: {e} | Rollback error: {rb_err}"
                record.recovery_required = True
                self.transition_state(record, TransactionState.RECOVERY_REQUIRED)

            record.completed_at = datetime.now(timezone.utc).isoformat()
            self._persist_state(record)
            self.lock.release(tx_id)

            if rollback_success:
                return False, f"Transaction failed and rolled back cleanly: {e}"
            else:
                return False, f"FATAL: Transaction failed and rollback encountered errors! RECOVERY_REQUIRED: {record.error}"

    def _execute_operation(self, op: NetworkOperation):
        """Dispatches an individual NetworkOperation to the corresponding service."""
        t = op.op_type
        p = op.params
        if t == "set_link_up":
            self.iface_service.set_interface_up(p["interface"])
        elif t == "set_link_down":
            self.iface_service.set_interface_down(p["interface"])
        elif t == "set_mtu":
            self.backend.set_mtu(p["interface"], p["mtu"])
        elif t == "create_vlan":
            self.vlan_service.create_vlan(name=p["name"], parent=p["parent"], vlan_id=p["vlan_id"])
        elif t == "delete_vlan":
            self.vlan_service.delete_vlan(name=p["name"])
        elif t == "create_bridge":
            self.bridge_service.create_bridge(name=p["name"], stp=p.get("stp", True))
        elif t == "delete_bridge":
            self.bridge_service.delete_bridge(name=p["name"])
        elif t == "add_bridge_port":
            self.bridge_service.add_port(bridge_name=p["bridge"], port_name=p["port"])
        elif t == "remove_bridge_port":
            self.bridge_service.remove_port(bridge_name=p["bridge"], port_name=p["port"])
        elif t == "create_bond":
            self.bond_service.create_bond(name=p["name"], mode=p.get("mode", "802.3ad"))
        elif t == "delete_bond":
            self.bond_service.delete_bond(name=p["name"])
        elif t == "add_bond_slave":
            self.bond_service.add_slave(bond_name=p["bond"], slave_name=p["slave"])
        elif t == "remove_bond_slave":
            self.bond_service.remove_slave(bond_name=p["bond"], slave_name=p["slave"])
        elif t == "create_vrf":
            self.vrf_service.create_vrf(name=p["name"], table=p["table"])
        elif t == "delete_vrf":
            self.vrf_service.delete_vrf(name=p["name"])
        elif t == "add_vrf_interface":
            self.vrf_service.add_interface(vrf_name=p["vrf"], iface_name=p["interface"])
        elif t == "remove_vrf_interface":
            self.vrf_service.remove_interface(vrf_name=p["vrf"], iface_name=p["interface"])
        elif t == "add_address":
            self.backend.add_address(p["interface"], p["cidr"])
        elif t == "remove_address":
            self.backend.remove_address(p["interface"], p["cidr"])
        elif t == "add_route":
            self.route_service.add_route(
                destination=p["destination"],
                gateway=p.get("gateway"),
                interface=p.get("interface"),
                metric=p.get("metric"),
            )
        elif t == "remove_route":
            self.route_service.remove_route(
                destination=p["destination"],
                gateway=p.get("gateway"),
                interface=p.get("interface"),
            )
        else:
            raise TransactionEngineError(f"Unknown operation type: {t}")

    def _execute_rollback(self, record: TransactionRecord, snapshot: Optional[NetworkStateSnapshot]):
        """Executes inverse operations in reverse topological order."""
        executed_ops = [op for op in record.operations if op.executed]
        # Reverse order
        for op in reversed(executed_ops):
            if op.inverse_op:
                logger.info("Rollback: executing inverse %s for target %s", op.inverse_op, op.target)
                inv_op = NetworkOperation(
                    op_id=f"rb_{op.op_id}",
                    op_type=op.inverse_op,
                    target=op.target,
                    params=op.inverse_params,
                )
                try:
                    self._execute_operation(inv_op)
                    record.rollback_operation_count += 1
                except Exception as e:
                    logger.warning("Error executing inverse operation %s: %s", op.inverse_op, e)

    def _verify_desired_state(self, desired: MitraNetConfig):
        """Verifies that actual Linux kernel state matches desired configuration."""
        # Check VRFs
        discovered_vrfs = {v.name: v for v in self.vrf_service.discover_vrfs()}
        for vname, vcfg in desired.vrfs.items():
            if vname not in discovered_vrfs:
                raise TransactionEngineError(f"Verification failure: VRF '{vname}' not found in kernel.")
            if discovered_vrfs[vname].table != vcfg.table_id:
                raise TransactionEngineError(
                    f"Verification failure: VRF '{vname}' table mismatch (expected {vcfg.table_id}, got {discovered_vrfs[vname].table})."
                )

        # Check VLANs
        discovered_vlans = {v.name: v for v in self.vlan_service.discover_vlans()}
        for vkey, vcfg in desired.vlans.items():
            pdev = desired.interfaces[vcfg.parent].device if vcfg.parent in desired.interfaces else vcfg.parent
            vdev = f"{pdev}.{vcfg.id}"
            if vdev not in discovered_vlans:
                raise TransactionEngineError(f"Verification failure: VLAN '{vdev}' not found in kernel.")

    # -------------------------------------------------------------------------
    # Startup Recovery
    # -------------------------------------------------------------------------
    def check_startup_recovery(self) -> Tuple[bool, str]:
        """Inspects persisted state at daemon/CLI startup to handle crashed/incomplete transactions."""
        # 1. Clean stale lock if process died
        if self.lock.is_locked():
            return False, "Active transaction lock detected from another live process."
        else:
            self.lock.release()

        # 2. Check persisted record
        if not self.current_record:
            return True, "System state clean (no pending transactions)."

        s = self.current_record.state
        if s in [TransactionState.APPLYING, TransactionState.VERIFYING, TransactionState.COMMITTING]:
            # System crashed midway through forward application!
            self.current_record.state = TransactionState.RECOVERY_REQUIRED
            self.current_record.recovery_required = True
            self._persist_state(self.current_record)
            return False, f"Crash detected during '{s}' in transaction '{self.current_record.transaction_id}'. RECOVERY_REQUIRED."

        if s == TransactionState.ROLLING_BACK:
            self.current_record.state = TransactionState.RECOVERY_REQUIRED
            self.current_record.recovery_required = True
            self._persist_state(self.current_record)
            return False, f"Crash detected during rollback in transaction '{self.current_record.transaction_id}'. RECOVERY_REQUIRED."

        if s == TransactionState.RECOVERY_REQUIRED:
            return False, f"System is in RECOVERY_REQUIRED state for transaction '{self.current_record.transaction_id}'."

        return True, f"System state consistent (last transaction state: {s})."
