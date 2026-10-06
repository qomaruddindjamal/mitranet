"""
MitraNet Canonical Configuration Data Models (Pydantic v2).
Defines the canonical, single-source-of-truth object model for persistent configuration.
"""

from typing import Dict, List, Optional, Literal, Any
from pydantic import BaseModel, Field, field_validator
from mitranet.core.version import SCHEMA_VERSION


class SystemConfig(BaseModel):
    hostname: str = Field(default="mitranet", min_length=1, max_length=63)
    domain: str = Field(default="home.arpa", min_length=1)
    timezone: str = Field(default="UTC")
    timeservers: List[str] = Field(default_factory=lambda: ["pool.ntp.org"])
    dns_servers: List[str] = Field(default_factory=lambda: ["1.1.1.1", "8.8.8.8"])


class InterfaceIPv4(BaseModel):
    mode: Literal["static", "dhcp", "disabled"] = Field(default="static")
    address: Optional[str] = Field(default=None)
    prefix: Optional[int] = Field(default=None, ge=0, le=32)
    gateway: Optional[str] = Field(default=None)


class InterfaceIPv6(BaseModel):
    mode: Literal["static", "slaac", "dhcp6", "disabled"] = Field(default="disabled")
    address: Optional[str] = Field(default=None)
    prefix: Optional[int] = Field(default=None, ge=0, le=128)
    gateway: Optional[str] = Field(default=None)


class InterfaceConfig(BaseModel):
    device: str = Field(..., description="Physical or OS netdevice name (e.g. eth0, enp1s0)")
    description: str = Field(default="")
    enabled: bool = Field(default=True)
    role: Literal["wan", "lan", "opt", "management"] = Field(default="lan")
    type: Literal["ethernet", "vlan", "bridge", "bond", "loopback", "wireguard", "vrf"] = Field(default="ethernet")
    mtu: int = Field(default=1500, ge=68, le=9216)
    mac: Optional[str] = Field(default=None)
    ipv4: Optional[InterfaceIPv4] = Field(default_factory=InterfaceIPv4)
    ipv6: Optional[InterfaceIPv6] = Field(default_factory=InterfaceIPv6)


class VlanConfig(BaseModel):
    id: int = Field(..., ge=1, le=4094)
    parent: str = Field(..., description="Parent interface key (e.g. lan, wan)")
    description: str = Field(default="")


class BridgeConfig(BaseModel):
    members: List[str] = Field(default_factory=list, description="List of interface keys")
    stp: bool = Field(default=True)
    description: str = Field(default="")


class BondConfig(BaseModel):
    slaves: List[str] = Field(..., min_length=1)
    mode: Literal["802.3ad", "active-backup", "balance-rr"] = Field(default="802.3ad")
    description: str = Field(default="")


class VrfConfig(BaseModel):
    table_id: int = Field(..., ge=1, le=65535)
    interfaces: List[str] = Field(default_factory=list)


class StaticRoute(BaseModel):
    destination: str = Field(..., description="Destination CIDR (e.g. 10.0.0.0/8)")
    gateway: str = Field(..., description="Next-hop IP")
    interface: Optional[str] = Field(default=None)
    metric: int = Field(default=1, ge=1)
    description: str = Field(default="")


class GatewayConfig(BaseModel):
    address: str
    interface: str
    monitor: bool = Field(default=True)
    weight: int = Field(default=1, ge=1)


class RoutingConfig(BaseModel):
    static_routes: List[StaticRoute] = Field(default_factory=list)
    gateways: Dict[str, GatewayConfig] = Field(default_factory=dict)


class FirewallAlias(BaseModel):
    type: Literal["host", "network", "port"] = Field(...)
    entries: List[str] = Field(default_factory=list)
    description: str = Field(default="")


class FirewallRule(BaseModel):
    id: str = Field(...)
    enabled: bool = Field(default=True)
    action: Literal["accept", "drop", "reject"] = Field(default="accept")
    direction: Literal["in", "out"] = Field(default="in")
    interface: str = Field(default="any")
    protocol: Literal["any", "tcp", "udp", "icmp", "tcp_udp"] = Field(default="any")
    source: str = Field(default="any")
    source_port: Optional[str] = Field(default=None)
    destination: str = Field(default="any")
    destination_port: Optional[str] = Field(default=None)
    log: bool = Field(default=False)
    description: str = Field(default="")


class FirewallConfig(BaseModel):
    aliases: Dict[str, FirewallAlias] = Field(default_factory=dict)
    rules: List[FirewallRule] = Field(default_factory=list)


class OutboundNatRule(BaseModel):
    interface: str = Field(..., description="Egress WAN interface name or key")
    source: str = Field(..., description="Source subnet or alias")
    mode: Literal["masquerade", "snat"] = Field(default="masquerade")
    target_ip: Optional[str] = Field(default=None)
    description: str = Field(default="")


class PortForwardRule(BaseModel):
    id: str = Field(...)
    interface: str = Field(default="wan")
    protocol: Literal["tcp", "udp", "tcp_udp"] = Field(default="tcp")
    external_port: int = Field(..., ge=1, le=65535)
    internal_ip: str = Field(...)
    internal_port: int = Field(..., ge=1, le=65535)
    enabled: bool = Field(default=True)
    description: str = Field(default="")


class NatConfig(BaseModel):
    outbound: List[OutboundNatRule] = Field(default_factory=list)
    port_forward: List[PortForwardRule] = Field(default_factory=list)


class DHCPSubnet(BaseModel):
    interface: str = Field(...)
    enabled: bool = Field(default=True)
    subnet: str = Field(...)
    range_start: str = Field(...)
    range_end: str = Field(...)
    gateway: Optional[str] = Field(default=None)
    dns_servers: List[str] = Field(default_factory=list)
    lease_time: int = Field(default=7200, ge=60)
    static_mappings: Dict[str, str] = Field(default_factory=dict)


class DNSConfig(BaseModel):
    enabled: bool = Field(default=True)
    dnssec: bool = Field(default=True)
    host_overrides: Dict[str, str] = Field(default_factory=dict)
    upstream_forwarders: List[str] = Field(default_factory=lambda: ["1.1.1.1", "8.8.8.8"])


class WireGuardPeer(BaseModel):
    public_key: str
    allowed_ips: List[str] = Field(default_factory=list)
    endpoint: Optional[str] = Field(default=None)


class WireGuardInterface(BaseModel):
    enabled: bool = Field(default=True)
    private_key: str
    listen_port: int = Field(default=51820, ge=1, le=65535)
    address: str
    peers: List[WireGuardPeer] = Field(default_factory=list)


class VpnConfig(BaseModel):
    wireguard: Dict[str, WireGuardInterface] = Field(default_factory=dict)
    ipsec: Dict[str, Any] = Field(default_factory=dict)
    openvpn: Dict[str, Any] = Field(default_factory=dict)


class MitraNetConfig(BaseModel):
    schema_version: str = Field(default=SCHEMA_VERSION)
    config_version: int = Field(default=1, ge=1)
    system: SystemConfig = Field(default_factory=SystemConfig)
    interfaces: Dict[str, InterfaceConfig] = Field(default_factory=dict)
    vlans: Dict[str, VlanConfig] = Field(default_factory=dict)
    bridges: Dict[str, BridgeConfig] = Field(default_factory=dict)
    bonds: Dict[str, BondConfig] = Field(default_factory=dict)
    vrfs: Dict[str, VrfConfig] = Field(default_factory=dict)
    routing: RoutingConfig = Field(default_factory=RoutingConfig)
    firewall: FirewallConfig = Field(default_factory=FirewallConfig)
    nat: NatConfig = Field(default_factory=NatConfig)
    dhcp: Dict[str, DHCPSubnet] = Field(default_factory=dict)
    dns: DNSConfig = Field(default_factory=DNSConfig)
    vpn: VpnConfig = Field(default_factory=VpnConfig)
    qos: Dict[str, Any] = Field(default_factory=dict)
    ha: Dict[str, Any] = Field(default_factory=dict)
    services: Dict[str, Any] = Field(default_factory=dict)
    users: Dict[str, Any] = Field(default_factory=dict)
    certificates: Dict[str, Any] = Field(default_factory=dict)
