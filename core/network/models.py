"""
MitraNet Network Runtime State Models (Pydantic v2).
Represents actual dynamic kernel network state discovered from Linux subsystem.
"""

from typing import List, Optional, Literal, Dict, Any
from pydantic import BaseModel, Field


class InterfaceStatistics(BaseModel):
    rx_bytes: int = Field(default=0, ge=0)
    rx_packets: int = Field(default=0, ge=0)
    rx_errors: int = Field(default=0, ge=0)
    rx_dropped: int = Field(default=0, ge=0)
    tx_bytes: int = Field(default=0, ge=0)
    tx_packets: int = Field(default=0, ge=0)
    tx_errors: int = Field(default=0, ge=0)
    tx_dropped: int = Field(default=0, ge=0)


class NetworkInterfaceState(BaseModel):
    name: str = Field(..., description="Linux network device name, e.g. eth0, ens18, lo")
    index: int = Field(..., ge=1, description="Kernel interface ifindex")
    type: str = Field(default="unknown", description="Interface link type, e.g. ether, loopback, bridge, vlan")
    mac_address: Optional[str] = Field(default=None, description="Hardware MAC address if applicable")
    mtu: int = Field(default=1500, ge=68, description="Interface Maximum Transmission Unit")
    admin_state: Literal["UP", "DOWN", "UNKNOWN"] = Field(default="DOWN", description="Administrative state (flags)")
    oper_state: Literal["UP", "DOWN", "UNKNOWN", "DORMANT", "LOWERLAYERDOWN"] = Field(
        default="UNKNOWN", description="Operational link state"
    )
    carrier: Optional[bool] = Field(default=None, description="Physical carrier presence")
    flags: List[str] = Field(default_factory=list, description="Kernel link flags e.g. UP, BROADCAST, MULTICAST")
    ipv4_addresses: List[str] = Field(default_factory=list, description="List of IPv4 addresses with CIDR prefix")
    ipv6_addresses: List[str] = Field(default_factory=list, description="List of IPv6 addresses with CIDR prefix")
    statistics: InterfaceStatistics = Field(default_factory=InterfaceStatistics)
    parent_device: Optional[str] = Field(default=None, description="Parent interface if VLAN or slave")
    vlan_id: Optional[int] = Field(default=None, ge=1, le=4094, description="VLAN ID if 802.1Q subinterface")


class RouteState(BaseModel):
    """
    Represents an active routing table entry discovered from the Linux kernel.
    """
    family: Literal["inet", "inet6"] = Field(..., description="Address family: inet (IPv4) or inet6 (IPv6)")
    destination: str = Field(..., description="Destination CIDR prefix, e.g. 192.0.2.0/24, 0.0.0.0/0, ::/0")
    gateway: Optional[str] = Field(default=None, description="Next-hop gateway IP if applicable")
    interface: Optional[str] = Field(default=None, description="Egress network interface device name")
    table: int = Field(default=254, description="Routing table ID (254=main, 255=local, etc.)")
    metric: Optional[int] = Field(default=None, ge=0, description="Route priority/metric")
    protocol: Optional[str] = Field(default=None, description="Routing protocol, e.g. kernel, boot, static, dhcp, ra")
    scope: Optional[str] = Field(default=None, description="Route scope, e.g. global, link, host")
    type: Optional[str] = Field(default="unicast", description="Route type, e.g. unicast, local, broadcast, blackhole")
    prefsrc: Optional[str] = Field(default=None, description="Preferred source IP address for outgoing traffic")
    flags: List[str] = Field(default_factory=list, description="Route flags from kernel")

    @property
    def is_default(self) -> bool:
        return self.destination in ("0.0.0.0/0", "::/0", "default")


class VlanState(BaseModel):
    """
    Represents an active 802.1Q VLAN interface in Linux kernel.
    """
    name: str = Field(..., description="VLAN interface name, e.g. enp0s8.100, vlan100")
    parent: str = Field(..., description="Parent/underlying interface device name, e.g. enp0s8")
    vlan_id: int = Field(..., ge=1, le=4094, description="802.1Q VLAN ID (1-4094)")
    protocol: str = Field(default="802.1Q", description="VLAN encapsulation protocol (802.1Q or 802.1ad)")
    mtu: int = Field(default=1500, ge=68, description="Interface MTU")
    admin_state: Literal["UP", "DOWN", "UNKNOWN"] = Field(default="DOWN", description="Administrative state")
    oper_state: Literal["UP", "DOWN", "UNKNOWN", "DORMANT", "LOWERLAYERDOWN"] = Field(
        default="UNKNOWN", description="Operational link state"
    )
    mac_address: Optional[str] = Field(default=None, description="MAC address")
    ipv4_addresses: List[str] = Field(default_factory=list, description="IPv4 addresses with CIDR")
    ipv6_addresses: List[str] = Field(default_factory=list, description="IPv6 addresses with CIDR")


class BridgePortState(BaseModel):
    """
    Represents a member interface port inside a Linux bridge.
    """
    interface: str = Field(..., description="Slave/member interface device name")
    state: str = Field(default="disabled", description="Bridge port state (forwarding, disabled, blocking, etc.)")
    priority: Optional[int] = Field(default=None, description="STP port priority")
    cost: Optional[int] = Field(default=None, description="STP path cost")


class BridgeState(BaseModel):
    """
    Represents a Linux Bridge network device.
    """
    name: str = Field(..., description="Bridge interface name, e.g. br0")
    admin_state: Literal["UP", "DOWN", "UNKNOWN"] = Field(default="DOWN", description="Administrative state")
    oper_state: Literal["UP", "DOWN", "UNKNOWN", "DORMANT", "LOWERLAYERDOWN"] = Field(
        default="UNKNOWN", description="Operational link state"
    )
    mac_address: Optional[str] = Field(default=None, description="Bridge MAC address")
    mtu: int = Field(default=1500, ge=68, description="Bridge MTU")
    stp_enabled: bool = Field(default=False, description="Whether Spanning Tree Protocol (STP) is active")
    ports: List[BridgePortState] = Field(default_factory=list, description="Attached member ports")
    ipv4_addresses: List[str] = Field(default_factory=list, description="IPv4 addresses with CIDR")
    ipv6_addresses: List[str] = Field(default_factory=list, description="IPv6 addresses with CIDR")


class BondSlaveState(BaseModel):
    """
    Represents a slave member interface attached to a Linux bond.
    """
    interface: str = Field(..., description="Slave member interface device name")
    mii_status: Optional[str] = Field(default=None, description="MII carrier status: up, down")
    link_failure_count: Optional[int] = Field(default=0, description="Link failure counter")
    perm_hwaddr: Optional[str] = Field(default=None, description="Permanent hardware MAC address")


class BondState(BaseModel):
    """
    Represents a Linux Bonding master network device.
    """
    name: str = Field(..., description="Bond interface name, e.g. bond0")
    mode: str = Field(..., description="Bonding mode, e.g. active-backup, 802.3ad, balance-rr, balance-xor")
    admin_state: Literal["UP", "DOWN", "UNKNOWN"] = Field(default="DOWN", description="Administrative state")
    oper_state: Literal["UP", "DOWN", "UNKNOWN", "DORMANT", "LOWERLAYERDOWN"] = Field(
        default="UNKNOWN", description="Operational link state"
    )
    mac_address: Optional[str] = Field(default=None, description="Bond MAC address")
    mtu: int = Field(default=1500, ge=68, description="Bond MTU")
    miimon: Optional[int] = Field(default=None, description="MII link monitoring interval in ms")
    slaves: List[BondSlaveState] = Field(default_factory=list, description="Active slave member interfaces")
    active_slave: Optional[str] = Field(default=None, description="Currently active slave if active-backup mode")
    lacp_rate: Optional[str] = Field(default=None, description="LACP rate (slow or fast) if 802.3ad mode")
    lacp_active: Optional[str] = Field(default=None, description="LACP active mode (on or off) if 802.3ad mode")
    ad_actor_system: Optional[str] = Field(default=None, description="802.3ad actor system ID MAC")
    ipv4_addresses: List[str] = Field(default_factory=list, description="IPv4 addresses with CIDR")
    ipv6_addresses: List[str] = Field(default_factory=list, description="IPv6 addresses with CIDR")


class VRFState(BaseModel):
    """
    Represents an active Linux Virtual Routing and Forwarding (VRF) device and domain.
    """
    name: str = Field(..., description="VRF device name, e.g. vrf-blue, vrf100")
    table: int = Field(..., ge=1, le=4294967295, description="Associated routing table ID")
    admin_state: Literal["UP", "DOWN", "UNKNOWN"] = Field(default="DOWN", description="Administrative state")
    oper_state: Literal["UP", "DOWN", "UNKNOWN", "DORMANT", "LOWERLAYERDOWN"] = Field(
        default="UNKNOWN", description="Operational link state"
    )
    mac_address: Optional[str] = Field(default=None, description="VRF device MAC address")
    interfaces: List[str] = Field(default_factory=list, description="Member network interfaces assigned to this VRF")
    routes_count: Optional[int] = Field(default=None, description="Number of active routes in this VRF table")



