"""
MitraNet Configuration Exceptions.
"""

class ConfigError(Exception):
    """Base exception for all MitraNet configuration errors."""
    pass

class ValidationError(ConfigError):
    """Raised when configuration fails schema or semantic validation."""
    pass

class SchemaVersionError(ConfigError):
    """Raised when configuration schema version is unsupported."""
    pass

class TransactionError(ConfigError):
    """Raised when an atomic commit or rollback transaction fails."""
    pass
