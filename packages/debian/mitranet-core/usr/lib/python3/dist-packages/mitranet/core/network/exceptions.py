"""
Network Exception Definitions.
"""

class NetworkError(Exception):
    """Base exception for all MitraNet network operations."""
    pass

class InterfaceNotFoundError(NetworkError):
    """Raised when an interface is not found in the kernel."""
    pass

class BackendExecutionError(NetworkError):
    """Raised when the Linux network backend encounters an execution error."""
    pass

class DataParsingError(NetworkError):
    """Raised when kernel network data cannot be parsed."""
    pass


class NetworkValidationError(NetworkError):
    """Raised when interface configuration inputs fail validation."""
    pass


class NetworkSecurityError(NetworkValidationError):
    """Raised when input parameters trigger security policies (e.g., shell injection)."""
    pass


class SafetyConstraintViolationError(NetworkError):
    """Raised when an operation would violate system safety constraints (e.g. disabling loopback)."""
    pass


class VerificationFailureError(NetworkError):
    """Raised when post-write state verification does not match desired state."""
    pass


class RouteNotFoundError(NetworkError):
    """Raised when an operation targets a non-existent routing entry."""
    pass


class RouteAlreadyExistsError(NetworkError):
    """Raised when attempting to add an identical route that already exists."""
    pass


class ProtectedRouteError(SafetyConstraintViolationError):
    """Raised when attempting to modify or delete a protected route (e.g., active management default route)."""
    pass


class DeviceAlreadyExistsError(NetworkError):
    """Raised when attempting to create a device (VLAN, bridge, bond) that already exists."""
    pass


class DeviceNotFoundError(NetworkError):
    """Raised when a target device (VLAN, bridge, bond) does not exist."""
    pass


class UnsupportedBondModeError(NetworkValidationError):
    """Raised when a requested bonding mode is not supported by kernel/system."""
    pass


