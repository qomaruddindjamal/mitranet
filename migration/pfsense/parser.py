"""
pfSense config.xml to MitraNet Migration Ingestion Engine.
Parses pfSense configuration, normalizes BSD interfaces, maps firewall rules,
NAT tables, DHCP, DNS, and produces a validated MitraNet configuration.
"""

import xml.etree.ElementTree as ET
from typing import Dict, List, Tuple
from mitranet.src.config.models import (
    MitraNetMasterConfig,
    SystemConfig,
    InterfaceConfig,
    InterfaceAddress,
    FirewallRule,
    OutboundNatRule,
    DHCPSubnet,
    DNSConfig,
)


class PfSenseMigrationEngine:
    def __init__(self, interface_mapping: Dict[str, str] = None):
        # Default mapping for typical BSD network interfaces to Linux interfaces
        self.interface_mapping = interface_mapping or {
            "em0": "eth0",
            "em1": "eth1",
            "igb0": "eth0",
            "igb1": "eth1",
            "vtnet0": "eth0",
            "vtnet1": "eth1",
        }

    def map_interface_name(self, bsd_if: str) -> str:
        return self.interface_mapping.get(bsd_if, bsd_if)

    def parse_xml_string(self, xml_content: str) -> Tuple[MitraNetMasterConfig, List[str]]:
        root = ET.fromstring(xml_content)
        warnings: List[str] = []
        master = MitraNetMasterConfig()

        # 1. System section
        system_elem = root.find("system")
        if system_elem is not None:
            sys_cfg = SystemConfig()
            hostname = system_elem.findtext("hostname")
            if hostname:
                sys_cfg.hostname = hostname
            domain = system_elem.findtext("domain")
            if domain:
                sys_cfg.domain = domain
            timeservers = system_elem.findtext("timeservers")
            if timeservers:
                sys_cfg.timeservers = [ts.strip() for ts in timeservers.split()]
            master.system = sys_cfg

        # 2. Interfaces section
        ifaces_elem = root.find("interfaces")
        if ifaces_elem is not None:
            parsed_ifaces: List[InterfaceConfig] = []
            role_map = {"wan": "wan", "lan": "lan"}

            for if_tag in ifaces_elem:
                tag_name = if_tag.tag
                bsd_dev = if_tag.findtext("if") or tag_name
                linux_dev = self.map_interface_name(bsd_dev)
                role = role_map.get(tag_name, "opt")
                descr = if_tag.findtext("descr") or tag_name.upper()
                enabled = if_tag.find("enable") is not None

                addresses: List[InterfaceAddress] = []
                ipaddr = if_tag.findtext("ipaddr")
                subnet = if_tag.findtext("subnet")
                dhcp_client = False

                if ipaddr == "dhcp":
                    dhcp_client = True
                elif ipaddr and subnet:
                    try:
                        addresses.append(InterfaceAddress(ip=ipaddr, prefix_length=int(subnet)))
                    except Exception as e:
                        warnings.append(f"Invalid IP configuration on interface {tag_name}: {e}")

                parsed_ifaces.append(
                    InterfaceConfig(
                        name=linux_dev,
                        description=descr,
                        enabled=enabled,
                        role=role,
                        type="ethernet",
                        addresses=addresses,
                        dhcp_client=dhcp_client,
                    )
                )
            master.interfaces = parsed_ifaces

        # 3. Firewall rules (<filter>)
        filter_elem = root.find("filter")
        if filter_elem is not None:
            rules: List[FirewallRule] = []
            for idx, r_elem in enumerate(filter_elem.findall("rule")):
                rule_type = r_elem.findtext("type") or "pass"
                action = "accept" if rule_type == "pass" else "drop"
                descr = r_elem.findtext("descr") or f"Imported pfSense rule {idx + 1}"
                tracker = r_elem.findtext("tracker") or f"pfsense_{idx + 1}"
                if_role = r_elem.findtext("interface") or "any"
                linux_if = self.map_interface_name(if_role)
                proto = r_elem.findtext("protocol") or "any"

                # Parse destination
                dest = "any"
                dest_elem = r_elem.find("destination")
                if dest_elem is not None:
                    if dest_elem.find("any") is not None:
                        dest = "any"
                    elif dest_elem.findtext("network"):
                        dest = dest_elem.findtext("network")
                    elif dest_elem.findtext("address"):
                        dest = dest_elem.findtext("address")

                rules.append(
                    FirewallRule(
                        id=tracker,
                        description=descr,
                        enabled=True,
                        action=action,
                        interface=linux_if if linux_if != "lan" and linux_if != "wan" else "any",
                        protocol="any" if proto not in ["tcp", "udp", "icmp"] else proto,
                        destination=dest,
                    )
                )
            master.firewall_rules = rules

        # 4. Outbound NAT (<nat><outbound>)
        nat_elem = root.find("nat")
        if nat_elem is not None:
            outbound_elem = nat_elem.find("outbound")
            if outbound_elem is not None:
                mode = outbound_elem.findtext("mode") or "automatic"
                if mode in ["automatic", "hybrid"]:
                    # Create default masquerade on WAN
                    wan_if = next((i.name for i in master.interfaces if i.role == "wan"), "eth0")
                    master.nat_outbound.append(
                        OutboundNatRule(
                            description="pfSense automatic outbound masquerade",
                            interface=wan_if,
                            source="192.168.0.0/16",
                            mode="masquerade",
                        )
                    )

        # 5. DHCP Server (<dhcpd>)
        dhcpd_elem = root.find("dhcpd")
        if dhcpd_elem is not None:
            dhcp_subnets: List[DHCPSubnet] = []
            for if_block in dhcpd_elem:
                tag = if_block.tag
                if if_block.find("enable") is not None:
                    range_elem = if_block.find("range")
                    if range_elem is not None:
                        start_ip = range_elem.findtext("from") or ""
                        end_ip = range_elem.findtext("to") or ""
                        matched_if = next((i for i in master.interfaces if i.role == tag), None)
                        if matched_if and matched_if.addresses:
                            gw = matched_if.addresses[0].ip
                            net_prefix = matched_if.addresses[0].prefix_length
                            # Estimate subnet CIDR
                            subnet_str = f"{gw.rsplit('.', 1)[0]}.0/{net_prefix}"
                            dhcp_subnets.append(
                                DHCPSubnet(
                                    interface=matched_if.name,
                                    enabled=True,
                                    subnet=subnet_str,
                                    range_start=start_ip,
                                    range_end=end_ip,
                                    gateway=gw,
                                )
                            )
            master.dhcp_servers = dhcp_subnets

        # 6. Unbound DNS Resolver (<unbound>)
        unbound_elem = root.find("unbound")
        if unbound_elem is not None:
            dns_cfg = DNSConfig()
            dns_cfg.enabled = unbound_elem.find("enable") is not None
            dns_cfg.dnssec = unbound_elem.find("dnssec") is not None
            master.dns = dns_cfg

        return master, warnings
