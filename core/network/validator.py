"""
MitraNet Interface Configuration Validator.
Enforces strict security checks, validates names against shell injection,
and checks MAC/MTU/IP format boundaries.
"""

import re
import ipaddress
from typing import Union
from mitranet.core.network.exceptions import NetworkValidationError, NetworkSecurityError


# Strict Linux network device name regex (alphanumeric, dots, underscores, dashes, max 15 chars)
IFACE_NAME_REGEX = re.compile(r"^[a-zA-Z0-9_.-]{1,15}$")
MAC_REGEX = re.compile(r"^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$")


class InterfaceConfigValidator:
    """Security and semantic validation for network interface parameters."""

    @classmethod
    def validate_interface_name(cls, name: str) -> str:
        if not name or not isinstance(name, str):
            raise NetworkValidationError("Interface name must be a non-empty string.")
        name = name.strip()
        # Security: protect against shell metacharacters and command injection
        if any(c in name for c in [";", "&", "|", "`", "$", "(", ")", "<", ">", "\n", "\r", " "]):
            raise NetworkSecurityError(f"Malicious interface name rejected: '{name}'.")
        if not IFACE_NAME_REGEX.match(name):
            raise NetworkValidationError(f"Invalid Linux interface name format: '{name}'. Must be 1-15 alphanumeric/._- characters.")
        return name

    @classmethod
    def validate_mtu(cls, mtu: int) -> int:
        try:
            mtu_int = int(mtu)
        except (ValueError, TypeError):
            raise NetworkValidationError(f"MTU must be an integer, got: {mtu}")
        if mtu_int < 68 or mtu_int > 9216:
            raise NetworkValidationError(f"MTU {mtu_int} out of valid range (68 - 9216).")
        return mtu_int

    @classmethod
    def validate_mac_address(cls, mac: str) -> str:
        if not mac or not isinstance(mac, str):
            raise NetworkValidationError("MAC address must be a non-empty string.")
        mac = mac.strip()
        if not MAC_REGEX.match(mac):
            raise NetworkValidationError(f"Invalid MAC address format: '{mac}'. Expected format: XX:XX:XX:XX:XX:XX")
        return mac.lower()

    @classmethod
    def validate_ip_address(cls, cidr: str) -> Union[ipaddress.IPv4Interface, ipaddress.IPv6Interface]:
        if not cidr or not isinstance(cidr, str):
            raise NetworkValidationError("IP address must be a non-empty string in CIDR notation.")
        cidr = cidr.strip()
        if "/" not in cidr:
            raise NetworkValidationError(f"IP address '{cidr}' must include a prefix length in CIDR notation (e.g. /24 or /64).")
        try:
            if ":" in cidr:
                return ipaddress.IPv6Interface(cidr)
            else:
                return ipaddress.IPv4Interface(cidr)
        except ValueError as e:
            raise NetworkValidationError(f"Invalid IP/CIDR address format '{cidr}': {e}")
