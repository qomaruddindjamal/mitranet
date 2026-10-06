"""
Hardware Device Mapping Abstraction for Interface Migration.
"""

from typing import Dict, Optional


class InterfaceMapper:
    """Maps BSD physical names (em0, igb0, vtnet0) to Linux netdevices."""
    def __init__(self, custom_mapping: Optional[Dict[str, str]] = None):
        self.mapping = custom_mapping or {
            "em0": "eth0",
            "em1": "eth1",
            "igb0": "eth0",
            "igb1": "eth1",
            "ix0": "eth0",
            "ix1": "eth1",
            "vtnet0": "eth0",
            "vtnet1": "eth1",
            "re0": "eth0",
            "re1": "eth1",
        }

    def resolve(self, bsd_device: str) -> str:
        dev = bsd_device.strip()
        return self.mapping.get(dev, dev)
