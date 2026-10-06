"""
MitraNet Firewall Data Models (Pydantic v2).
Phase 3A: Production-grade native Linux Firewall models.
Supports nftables inet table, input/forward/output chains, stateful inspection,
rule priority, zones, logging, counters, and anti-lockout safety constraints.
"""

from enum import Enum
from typing import Dict, List, Optional, Set
from pydantic import BaseModel, Field, field_validator


class FirewallAction(str, Enum):
    ACCEPT = "accept"
    DROP = "drop"
    REJECT = "reject"


class FirewallFamily(str, Enum):
    INET = "inet"
    IPV4 = "ipv4"
    IPV6 = "ipv6"


class FirewallProtocol(str, Enum):
    ANY = "any"
    TCP = "tcp"
    UDP = "udp"
    TCP_UDP = "tcp_udp"
    ICMP = "icmp"
    ICMPV6 = "icmpv6"
    ESP = "esp"
    AH = "ah"
    GRE = "gre"
    IGMP = "igmp"


class FirewallDirection(str, Enum):
    IN = "in"
    OUT = "out"
    FORWARD = "forward"


class FirewallConntrackState(str, Enum):
    NEW = "new"
    ESTABLISHED = "established"
    RELATED = "related"
    INVALID = "invalid"


class FirewallZone(BaseModel):
    """Network security zone mapping to physical or virtual interfaces."""
    name: str = Field(..., description="Unique zone name (e.g., WAN, LAN, DMZ, MGMT, VPN)")
    interfaces: List[str] = Field(default_factory=list, description="Associated network interfaces")
    description: str = Field(default="", description="Zone description")
    is_management: bool = Field(default=False, description="Whether this zone carries critical management traffic")

    @field_validator("name")
    @classmethod
    def validate_name(cls, v: str) -> str:
        v = v.strip()
        if not v:
            raise ValueError("Zone name cannot be empty")
        if any(c in v for c in ";;|&`$()\\\"'\n\r\t "):
            raise ValueError(f"Zone name contains forbidden characters: {v}")
        return v


class FirewallRule(BaseModel):
    """
    Firewall Filter Rule definition.
    Compiles deterministically to Linux nftables rules.
    """
    id: str = Field(..., description="Unique identifier for the rule (alphanumeric + underscore/hyphen)")
    enabled: bool = Field(default=True, description="Rule enabled status")
    description: str = Field(default="", description="Rule human-readable description")
    family: FirewallFamily = Field(default=FirewallFamily.INET, description="Protocol family (inet, ipv4, ipv6)")
    direction: FirewallDirection = Field(default=FirewallDirection.IN, description="Direction or chain association")
    chain: Optional[str] = Field(default=None, description="Explicit nftables chain (input, forward, output, or zone chain)")
    zone: Optional[str] = Field(default=None, description="Source or destination zone context")
    interface: str = Field(default="any", description="In-interface or out-interface filter (or 'any')")
    out_interface: Optional[str] = Field(default=None, description="Egress interface filter for forward/output chains")
    
    # Matching criteria
    protocol: FirewallProtocol = Field(default=FirewallProtocol.ANY, description="Transport protocol")
    source: str = Field(default="any", description="Source IP, CIDR, alias name, or 'any'")
    source_ports: List[str] = Field(default_factory=list, description="Source ports or port ranges")
    destination: str = Field(default="any", description="Destination IP, CIDR, alias name, or 'any'")
    destination_ports: List[str] = Field(default_factory=list, description="Destination ports or port ranges")
    states: List[FirewallConntrackState] = Field(default_factory=list, description="Conntrack states to match (e.g., [new, established])")
    
    # Verdict & Actions
    action: FirewallAction = Field(default=FirewallAction.ACCEPT, description="Verdict: accept, drop, reject")
    log: bool = Field(default=False, description="Enable kernel netfilter logging")
    log_prefix: Optional[str] = Field(default=None, description="Prefix for kernel log entries")
    counter: bool = Field(default=True, description="Maintain packet and byte counters in nftables")
    priority: int = Field(default=100, ge=1, le=65535, description="Rule ordering priority (lower number = higher priority)")

    @field_validator("id")
    @classmethod
    def validate_id(cls, v: str) -> str:
        v = v.strip()
        if not v:
            raise ValueError("Rule ID cannot be empty")
        if any(c in v for c in ";;|&`$()\\\"'\n\r\t "):
            raise ValueError(f"Rule ID contains invalid or dangerous characters: {v}")
        return v

    @field_validator("log_prefix")
    @classmethod
    def validate_log_prefix(cls, v: Optional[str]) -> Optional[str]:
        if v is None:
            return None
        v = v.strip("\r\n")
        if any(c in v for c in ";|&`$()\\\"'\n\r"):
            raise ValueError("Log prefix contains illegal characters")
        if len(v) > 64:
            raise ValueError("Log prefix exceeds maximum 64 characters")
        return v



class FirewallPolicy(BaseModel):
    """Default policy configuration for standard base chains."""
    input_default: FirewallAction = Field(default=FirewallAction.DROP, description="Default verdict for input chain")
    forward_default: FirewallAction = Field(default=FirewallAction.DROP, description="Default verdict for forward chain")
    output_default: FirewallAction = Field(default=FirewallAction.ACCEPT, description="Default verdict for output chain")
    established_related_accept: bool = Field(default=True, description="Automatically accept established,related conntrack states")
    invalid_drop: bool = Field(default=True, description="Automatically drop invalid conntrack states")
    loopback_accept: bool = Field(default=True, description="Explicitly accept all traffic on loopback (lo) interface")
    anti_lockout_enabled: bool = Field(default=True, description="Guarantee management interface SSH and local CLI reachability")
    management_interfaces: List[str] = Field(default_factory=lambda: ["enp0s3"], description="Interfaces protected by anti-lockout")
    management_ports: List[str] = Field(default_factory=lambda: ["22"], description="Ports protected by anti-lockout")


class FirewallTableConfig(BaseModel):
    """Complete MitraNet Firewall specification."""
    name: str = Field(default="mitranet", description="nftables table name")
    family: FirewallFamily = Field(default=FirewallFamily.INET, description="nftables table family (default inet)")
    policy: FirewallPolicy = Field(default_factory=FirewallPolicy, description="Default security policies")
    zones: Dict[str, FirewallZone] = Field(default_factory=dict, description="Configured security zones")
    rules: List[FirewallRule] = Field(default_factory=list, description="Firewall rule ordered list")


class FirewallRuleCounter(BaseModel):
    """Runtime counter metric for a specific rule or chain."""
    rule_id: str
    packets: int = 0
    bytes: int = 0


class FirewallState(BaseModel):
    """Runtime status of the MitraNet firewall engine."""
    active: bool = False
    table_name: str = "mitranet"
    table_family: str = "inet"
    rule_count: int = 0
    chains: List[str] = Field(default_factory=list)
    ruleset_hash: Optional[str] = None
    last_applied: Optional[str] = None
    counters: Dict[str, FirewallRuleCounter] = Field(default_factory=dict)
