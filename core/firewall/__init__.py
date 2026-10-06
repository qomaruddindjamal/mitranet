"""
MitraNet Native Firewall Subsystem Package.
Phase 3A: Production-grade Linux Netfilter/nftables Firewall Core.
"""

from mitranet.core.firewall.models import (
    FirewallAction,
    FirewallFamily,
    FirewallProtocol,
    FirewallDirection,
    FirewallConntrackState,
    FirewallZone,
    FirewallRule,
    FirewallPolicy,
    FirewallTableConfig,
    FirewallRuleCounter,
    FirewallState,
)
from mitranet.core.firewall.errors import (
    FirewallError,
    FirewallValidationError,
    FirewallSecurityViolationError,
    FirewallCompilationError,
    FirewallBackendError,
    FirewallRollbackError,
    FirewallSnapshotError,
)
from mitranet.core.firewall.validator import FirewallValidator
from mitranet.core.firewall.compiler import NftablesCompiler
from mitranet.core.firewall.planner import (
    FirewallPlanner,
    FirewallOperation,
    FirewallOperationType,
)
from mitranet.core.firewall.backend import NftablesBackend
from mitranet.core.firewall.snapshot import FirewallSnapshot, FirewallSnapshotManager
from mitranet.core.firewall.engine import FirewallTransactionEngine

__all__ = [
    "FirewallAction",
    "FirewallFamily",
    "FirewallProtocol",
    "FirewallDirection",
    "FirewallConntrackState",
    "FirewallZone",
    "FirewallRule",
    "FirewallPolicy",
    "FirewallTableConfig",
    "FirewallRuleCounter",
    "FirewallState",
    "FirewallError",
    "FirewallValidationError",
    "FirewallSecurityViolationError",
    "FirewallCompilationError",
    "FirewallBackendError",
    "FirewallRollbackError",
    "FirewallSnapshotError",
    "FirewallValidator",
    "NftablesCompiler",
    "FirewallPlanner",
    "FirewallOperation",
    "FirewallOperationType",
    "NftablesBackend",
    "FirewallSnapshot",
    "FirewallSnapshotManager",
    "FirewallTransactionEngine",
]
