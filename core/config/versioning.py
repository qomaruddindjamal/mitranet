"""
MitraNet Configuration Versioning and Migration Management.
"""

from typing import Dict, Any
from mitranet.core.config.exceptions import SchemaVersionError


SUPPORTED_SCHEMA_VERSIONS = ["1.0"]


class ConfigVersionManager:
    @classmethod
    def validate_version(cls, data: Dict[str, Any]) -> str:
        version = data.get("schema_version")
        if not version:
            raise SchemaVersionError("Missing 'schema_version' in configuration.")
        if str(version) not in SUPPORTED_SCHEMA_VERSIONS:
            raise SchemaVersionError(f"Unsupported schema_version '{version}'. Supported: {SUPPORTED_SCHEMA_VERSIONS}")
        return str(version)

    @classmethod
    def upgrade_if_needed(cls, data: Dict[str, Any]) -> Dict[str, Any]:
        """Hook for executing version migrations (e.g. v1.0 -> v2.0)."""
        version = cls.validate_version(data)
        if version == "1.0":
            return data
        return data
