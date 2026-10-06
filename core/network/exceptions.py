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
