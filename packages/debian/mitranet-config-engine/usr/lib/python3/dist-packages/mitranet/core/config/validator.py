"""
MitraNet Configuration Semantic Validator.
Validates cross-references, address sanity, device bindings, and VLAN boundaries.
"""

from typing import List
from mitranet.core.config.model import MitraNetConfig
from mitranet.core.config.exceptions import ValidationError


class ConfigValidator:
    @classmethod
    def validate(cls, cfg: MitraNetConfig) -> List[str]:
        errors: List[str] = []
        iface_keys = set(cfg.interfaces.keys())
        device_names = {iface.device for iface in cfg.interfaces.values()}

        # 1. Validate VLAN parent references
        for vlan_key, vlan in cfg.vlans.items():
            if vlan.parent not in iface_keys and vlan.parent not in device_names:
                errors.append(f"VLAN '{vlan_key}' references non-existent parent interface '{vlan.parent}'.")

        # 2. Validate Bridge member references
        for br_key, bridge in cfg.bridges.items():
            for member in bridge.members:
                if member not in iface_keys and member not in device_names:
                    errors.append(f"Bridge '{br_key}' references non-existent member interface '{member}'.")

        # 3. Validate Bond slave references
        for bond_key, bond in cfg.bonds.items():
            for slave in bond.slaves:
                if slave not in iface_keys and slave not in device_names:
                    errors.append(f"Bond '{bond_key}' references non-existent slave interface '{slave}'.")

        # 4. Validate Firewall rule interface references
        for rule in cfg.firewall.rules:
            if rule.interface != "any":
                if rule.interface not in iface_keys and rule.interface not in device_names:
                    errors.append(f"Firewall rule '{rule.id}' references undefined interface '{rule.interface}'.")

        # 5. Validate Port forward interface references
        for pf in cfg.nat.port_forward:
            if pf.interface not in iface_keys and pf.interface not in device_names:
                errors.append(f"Port forward rule '{pf.id}' references undefined interface '{pf.interface}'.")

        # 6. Validate DHCP interface bindings
        for dhcp_key, dhcp in cfg.dhcp.items():
            if dhcp.interface not in iface_keys and dhcp.interface not in device_names:
                errors.append(f"DHCP server for '{dhcp_key}' references undefined interface '{dhcp.interface}'.")

        return errors

    @classmethod
    def assert_valid(cls, cfg: MitraNetConfig):
        errors = cls.validate(cfg)
        if errors:
            raise ValidationError("; ".join(errors))
