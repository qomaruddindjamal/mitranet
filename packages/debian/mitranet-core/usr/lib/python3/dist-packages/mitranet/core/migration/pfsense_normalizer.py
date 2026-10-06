"""
pfSense Configuration Value Normalizer.
Converts BSD/pfSense strings, empty elements, and flags into clean typed representations.
"""

from typing import Any, Dict, List, Optional


class PfSenseNormalizer:
    @classmethod
    def to_bool(cls, val: Any) -> bool:
        if isinstance(val, bool):
            return val
        if val is None or val == "":
            return False
        if str(val).lower() in ["1", "yes", "true", "enable", "enabled", "on"]:
            return True
        return False

    @classmethod
    def to_int(cls, val: Any, default: Optional[int] = None) -> Optional[int]:
        if val is None or val == "":
            return default
        try:
            return int(str(val).strip())
        except (ValueError, TypeError):
            return default

    @classmethod
    def to_list(cls, val: Any) -> List[Any]:
        if val is None or val == "":
            return []
        if isinstance(val, list):
            return val
        return [val]

    @classmethod
    def clean_str(cls, val: Any) -> str:
        if val is None:
            return ""
        return str(val).strip()
