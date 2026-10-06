"""
MitraNet Core Configuration Data Models (Pydantic v2 compatible).
Defines the single-source-of-truth configuration schema for MitraNet Network Operating System.
"""

from typing import List, Optional, Dict, Literal
from pydantic import BaseModel, Field


class SystemConfig(BaseModel):
    hostname: str = Field(default="mitranet", description="System hostname")
    domain: str = Field(default="home.arpa", description="System search domain")
    timezone: str = Field(default="UTC", description="System timezone")
    timeservers: List[str] = Field(
        default_factory=lambda: ["pool.ntp.org"],
        description="NTP time servers"
    )
    dns_servers: List[str] = Field(
        default_factory=lambda: ["1.1.1.1", "8.8.8.8"],
        description="Upstream system DNS resolvers"
    )


class InterfaceAddress(BaseModel):
    ip: str = Field(..., description="IPv4 or IPv6 address")
    prefix_length: int = Field(..., ge=0, le=128, description="CIDR prefix length")


class InterfaceConfig(BaseModel):
    name: str = Field(..., description="Physical or logical interface name (e.g. eth0, vlan10)")
    description: str = Field(default="", description="Descriptive label")
    enabled: bool = Field(default=True, description="Interface administrative status")
    role: Literal["wan", "lan", "opt", "management"] = Field(
        default="lan", description="MitraNet interface role"
    )
    type: Literal["ethernet", "vlan", "bridge", "bond", "wireguard", "loopback"] = Field(
        default="ethernet", description="Interface type"
    )
    addresses: List[InterfaceAddress] = Field(default_factory=list, description="Static IP assignments")
    dhcp_client: bool = Field(default=False, description="Enable DHCP client on interface")
    parent: Optional[str] = Field(default=None, description="Parent interface for VLANs")
    vlan_id: Optional[int] = Field(default=None, ge=1, le=4094, description="802.1Q VLAN Tag")
    bridge_members: Optional[List[str]] = Field(default=None, description="Slave ports for bridge interface")
    mtu: int = Field(default=1500, ge=68, le=9216, description="Interface MTU")


class StaticRoute(BaseModel):
    destination: str = Field(..., description="Destination CIDR network (e.g. 10.0.0.0/8)")
    gateway: str = Field(..., description="Next-hop gateway IP address")
    interface: Optional[str] = Field(default=None, description="Egress interface")
    metric: int = Field(default=1, ge=0, description="Route metric / administrative distance")
    description: str = Field(default="", description="Route note")


class FirewallAlias(BaseModel):
    name: str = Field(..., description="Unique alias name (alphanumeric/underscore)")
    type: Literal["host", "network", "port"] = Field(..., description="Alias type")
    entries: List[str] = Field(default_factory=list, description="List of IPs, CIDRs, or ports")
    description: str = Field(default="", description="Alias description")


class FirewallRule(BaseModel):
    id: str = Field(..., description="Unique rule ID or tracker")
    description: str = Field(default="", description="Rule description")
    enabled: bool = Field(default=True, description="Rule active status")
    action: Literal["accept", "drop", "reject"] = Field(default="accept", description="Packet verdict")
    direction: Literal["in", "out"] = Field(default="in", description="Traffic direction")
    interface: str = Field(..., description="Matching interface or 'any'")
    ip_version: Literal["ipv4", "ipv6", "both"] = Field(default="ipv4", description="IP version")
    protocol: Literal["any", "tcp", "udp", "icmp", "tcp_udp"] = Field(default="any", description="Transport protocol")
    source: str = Field(default="any", description="Source IP, CIDR, or Alias name")
    source_port: Optional[str] = Field(default=None, description="Source port or range")
    destination: str = Field(default="any", description="Destination IP, CIDR, or Alias name")
    destination_port: Optional[str] = Field(default=None, description="Destination port or range")
    log: bool = Field(default=False, description="Enable logging for matches")


class PortForwardRule(BaseModel):
    id: str = Field(..., description="Unique rule ID")
    description: str = Field(default="", description="Port forward description")
    enabled: bool = Field(default=True, description="Rule active status")
    interface: str = Field(default="eth0", description="Ingress WAN interface")
    protocol: Literal["tcp", "udp", "tcp_udp"] = Field(default="tcp", description="Protocol")
    external_port: int = Field(..., ge=1, le=65535, description="External inbound listening port")
    internal_ip: str = Field(..., description="Internal target host IP")
    internal_port: int = Field(..., ge=1, le=65535, description="Internal target port")


class OutboundNatRule(BaseModel):
    description: str = Field(default="", description="Outbound NAT rule note")
    enabled: bool = Field(default=True, description="Rule active status")
    interface: str = Field(..., description="Egress WAN interface")
    source: str = Field(..., description="Matching source network (e.g. 192.168.1.0/24)")
    mode: Literal["masquerade", "snat"] = Field(default="masquerade", description="NAT method")
    target_ip: Optional[str] = Field(default=None, description="Explicit static SNAT IP")


class DHCPSubnet(BaseModel):
    interface: str = Field(..., description="Serving interface name")
    enabled: bool = Field(default=True, description="Service status")
    subnet: str = Field(..., description="Subnet CIDR (e.g. 192.168.1.0/24)")
    range_start: str = Field(..., description="Pool allocation start IP")
    range_end: str = Field(..., description="Pool allocation end IP")
    gateway: Optional[str] = Field(default=None, description="Default router IP")
    dns_servers: List[str] = Field(default_factory=list, description="DNS servers to offer")
    lease_time_seconds: int = Field(default=7200, ge=60, description="Lease duration in seconds")
    static_reservations: Dict[str, str] = Field(
        default_factory=dict,
        description="MAC address -> IP reservations mapping"
    )


class DNSConfig(BaseModel):
    enabled: bool = Field(default=True, description="DNS resolver status")
    dnssec: bool = Field(default=True, description="Enable DNSSEC validation")
    listen_port: int = Field(default=53, ge=1, le=65535, description="DNS listening port")
    host_overrides: Dict[str, str] = Field(
        default_factory=dict,
        description="Local hostname -> IP address mapping"
    )
    upstream_forwarders: List[str] = Field(
        default_factory=lambda: ["1.1.1.1", "8.8.8.8"],
        description="Upstream recursive forwarders"
    )


class WireGuardPeer(BaseModel):
    public_key: str = Field(..., description="Peer WireGuard public key")
    allowed_ips: List[str] = Field(default_factory=list, description="Routed IP networks")
    endpoint: Optional[str] = Field(default=None, description="Peer host:port endpoint")
    persistent_keepalive: int = Field(default=25, ge=0, description="Keepalive interval in seconds")


class WireGuardInterface(BaseModel):
    name: str = Field(default="wg0", description="WireGuard device name")
    enabled: bool = Field(default=True, description="Interface status")
    private_key: str = Field(..., description="Base64 encoded private key")
    listen_port: int = Field(default=51820, ge=1, le=65535, description="UDP listening port")
    addresses: List[InterfaceAddress] = Field(default_factory=list, description="Tunnel IP addresses")
    peers: List[WireGuardPeer] = Field(default_factory=list, description="Configured remote peers")


class MitraNetMasterConfig(BaseModel):
    version: str = Field(default="1.0.0", description="MitraNet configuration schema version")
    system: SystemConfig = Field(default_factory=SystemConfig)
    interfaces: List[InterfaceConfig] = Field(default_factory=list)
    routes: List[StaticRoute] = Field(default_factory=list)
    firewall_aliases: List[FirewallAlias] = Field(default_factory=list)
    firewall_rules: List[FirewallRule] = Field(default_factory=list)
    nat_port_forwards: List[PortForwardRule] = Field(default_factory=list)
    nat_outbound: List[OutboundNatRule] = Field(default_factory=list)
    dhcp_servers: List[DHCPSubnet] = Field(default_factory=list)
    dns: DNSConfig = Field(default_factory=DNSConfig)
    wireguard_tunnels: List[WireGuardInterface] = Field(default_factory=list)
