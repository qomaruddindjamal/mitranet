"""
MitraNet Firewall Exception Classes.
Phase 3A: Structured error handling for validator, compiler, planner, and backend.
"""

class FirewallError(Exception):
    """Base exception for all MitraNet firewall errors."""
    pass


class FirewallValidationError(FirewallError):
    """Raised when firewall rule, zone, or policy fails structural or semantic validation."""
    pass


class FirewallSecurityViolationError(FirewallError):
    """Raised when an anti-lockout constraint is violated or dangerous rule is submitted."""
    pass


class FirewallCompilationError(FirewallError):
    """Raised when firewall rules cannot be compiled into valid nftables syntax."""
    pass


class FirewallBackendError(FirewallError):
    """Raised when executing nftables or kernel netfilter operations fails."""
    pass


class FirewallRollbackError(FirewallError):
    """Raised when rollback of a firewall ruleset transaction fails."""
    pass


class FirewallSnapshotError(FirewallError):
    """Raised when capturing or verifying a firewall ruleset snapshot fails."""
    pass
