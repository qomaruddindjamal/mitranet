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

