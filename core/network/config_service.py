"""
MitraNet Interface Configuration Service (Phase 1B).
Manages interface state transitions, MTU, MAC, and IP configuration with:
- Pre-write semantic and security validation
- Capture old state
- Apply via Linux backend
- Discover and verify actual kernel state
- Enforce loopback safety protections
"""

import logging
from typing import Optional
from mitranet.core.network.models import NetworkInterfaceState
from mitranet.core.network.backend import NetworkBackend, LinuxNetworkBackend
from mitranet.core.network.discovery import InterfaceDiscoveryService
from mitranet.core.network.validator import InterfaceConfigValidator
from mitranet.core.network.exceptions import (
    NetworkError,
    InterfaceNotFoundError,
    NetworkValidationError,
    SafetyConstraintViolationError,
    VerificationFailureError,
)

logger = logging.getLogger("mitranet.network.config")


class InterfaceConfigurationService:
    def __init__(
        self,
        backend: Optional[NetworkBackend] = None,
        discovery: Optional[InterfaceDiscoveryService] = None,
    ):
        self.backend = backend or LinuxNetworkBackend()
        self.discovery = discovery or InterfaceDiscoveryService(backend=self.backend)

    def set_interface_up(self, iface_name: str) -> NetworkInterfaceState:
        """Sets administrative link state to UP and verifies."""
        return self.set_interface_state(iface_name, state_up=True)

    def set_interface_down(self, iface_name: str) -> NetworkInterfaceState:
        """Sets administrative link state to DOWN and verifies."""
        return self.set_interface_state(iface_name, state_up=False)

    def set_interface_state(self, iface_name: str, state_up: bool) -> NetworkInterfaceState:
        """Sets administrative link state to UP or DOWN and verifies kernel state."""
        valid_name = InterfaceConfigValidator.validate_interface_name(iface_name)

        # Loopback safety protection
        if valid_name == "lo" and not state_up:
            raise SafetyConstraintViolationError("Safety violation: Disabling loopback interface 'lo' is forbidden.")

        # Ensure interface exists
        current = self.discovery.get_interface(valid_name)

        target_str = "UP" if state_up else "DOWN"
        logger.info("Setting interface %s admin state to %s", valid_name, target_str)

        if state_up:
            self.backend.set_interface_up(valid_name)
        else:
            self.backend.set_interface_down(valid_name)

        # Verify actual state after apply
        verified = self.discovery.get_interface(valid_name)
        if verified.admin_state != target_str:
            raise VerificationFailureError(
                f"State verification failed for {valid_name}: expected admin_state {target_str}, got {verified.admin_state}."
            )
        return verified

    def set_mtu(self, iface_name: str, mtu: int) -> NetworkInterfaceState:
        """Configures MTU and verifies in kernel."""
        valid_name = InterfaceConfigValidator.validate_interface_name(iface_name)
        valid_mtu = InterfaceConfigValidator.validate_mtu(mtu)

        # Ensure interface exists
        self.discovery.get_interface(valid_name)

        logger.info("Setting interface %s MTU to %d", valid_name, valid_mtu)
        self.backend.set_mtu(valid_name, valid_mtu)

        verified = self.discovery.get_interface(valid_name)
        if verified.mtu != valid_mtu:
            raise VerificationFailureError(
                f"MTU verification failed for {valid_name}: expected MTU {valid_mtu}, got {verified.mtu}."
            )
        return verified

    def set_mac(self, iface_name: str, mac: str) -> NetworkInterfaceState:
        """Alias for set_mac_address."""
        return self.set_mac_address(iface_name, mac)

    def set_mac_address(self, iface_name: str, mac: str) -> NetworkInterfaceState:
        """Configures MAC address and verifies in kernel."""
        valid_name = InterfaceConfigValidator.validate_interface_name(iface_name)
        valid_mac = InterfaceConfigValidator.validate_mac_address(mac)

        if valid_name == "lo":
            raise SafetyConstraintViolationError("Safety violation: Changing MAC address on loopback 'lo' is forbidden.")

        self.discovery.get_interface(valid_name)

        logger.info("Setting interface %s MAC to %s", valid_name, valid_mac)
        self.backend.set_mac_address(valid_name, valid_mac)

        verified = self.discovery.get_interface(valid_name)
        if (verified.mac_address or "").lower() != valid_mac.lower():
            raise VerificationFailureError(
                f"MAC verification failed for {valid_name}: expected MAC {valid_mac}, got {verified.mac_address}."
            )
        return verified

    def add_address(self, iface_name: str, cidr: str) -> NetworkInterfaceState:
        """Adds IPv4 or IPv6 address with CIDR prefix to interface and verifies in kernel."""
        valid_name = InterfaceConfigValidator.validate_interface_name(iface_name)
        ip_obj = InterfaceConfigValidator.validate_ip_address(cidr)
        normalized_cidr = str(ip_obj)

        current = self.discovery.get_interface(valid_name)
        existing = current.ipv4_addresses if ip_obj.version == 4 else current.ipv6_addresses

        if normalized_cidr in existing:
            raise NetworkValidationError(f"Address {normalized_cidr} is already assigned to interface {valid_name}.")

        logger.info("Adding IP address %s to interface %s", normalized_cidr, valid_name)
        self.backend.add_address(valid_name, normalized_cidr)

        verified = self.discovery.get_interface(valid_name)
        updated = verified.ipv4_addresses if ip_obj.version == 4 else verified.ipv6_addresses
        if normalized_cidr not in updated:
            raise VerificationFailureError(
                f"Address verification failed: {normalized_cidr} not found on {valid_name} after addition."
            )
        return verified

    def remove_address(self, iface_name: str, cidr: str) -> NetworkInterfaceState:
        """Removes IPv4 or IPv6 address from interface and verifies absence in kernel."""
        valid_name = InterfaceConfigValidator.validate_interface_name(iface_name)
        ip_obj = InterfaceConfigValidator.validate_ip_address(cidr)
        normalized_cidr = str(ip_obj)

        # Loopback safety protection
        if valid_name == "lo" and normalized_cidr in ["127.0.0.1/8", "::1/128"]:
            raise SafetyConstraintViolationError(f"Safety violation: Removing essential loopback address {normalized_cidr} is forbidden.")

        current = self.discovery.get_interface(valid_name)
        existing = current.ipv4_addresses if ip_obj.version == 4 else current.ipv6_addresses
        if normalized_cidr not in existing:
            raise NetworkValidationError(f"Address {normalized_cidr} is not assigned to interface {valid_name}.")

        logger.info("Removing IP address %s from interface %s", normalized_cidr, valid_name)
        self.backend.remove_address(valid_name, normalized_cidr)

        verified = self.discovery.get_interface(valid_name)
        updated = verified.ipv4_addresses if ip_obj.version == 4 else verified.ipv6_addresses
        if normalized_cidr in updated:
            raise VerificationFailureError(
                f"Removal verification failed: {normalized_cidr} still present on {valid_name} after deletion."
            )
        return verified
