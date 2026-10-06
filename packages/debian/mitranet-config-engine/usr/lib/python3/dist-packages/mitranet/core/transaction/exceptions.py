"""
Transaction & Recovery Exception Definitions (Phase 1F).
"""

from mitranet.core.network.exceptions import NetworkError


class TransactionEngineError(NetworkError):
    """Base exception for all Transaction Engine operations."""
    pass


class TransactionLockError(TransactionEngineError):
    """Raised when transaction lock cannot be acquired or is held by another process."""
    pass


class InvalidStateTransitionError(TransactionEngineError):
    """Raised when an invalid state transition is attempted in the state machine."""
    pass


class SnapshotIntegrityError(TransactionEngineError):
    """Raised when a snapshot is corrupt, hash mismatches, or lacks restoration data."""
    pass


class TransactionApplyError(TransactionEngineError):
    """Raised when forward execution of a transaction plan fails."""
    pass


class RollbackExecutionError(TransactionEngineError):
    """Raised when rollback of operations fails, requiring manual or startup recovery."""
    pass


class RecoveryRequiredError(TransactionEngineError):
    """Raised when transaction engine enters RECOVERY_REQUIRED state."""
    pass
