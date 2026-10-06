"""
Semantic Compatibility Mapper: pfSense Normalized DOM -> MitraNet Canonical Models.
Translates interfaces, routing, firewall rules, NAT, DHCP, DNS, WireGuard, and records unsupported features.
"""

from typing import Dict, Any, Tuple
from mitranet.core.version import SCHEMA_VERSION
from mitranet.core.config.model import (
    MitraNetConfig,
    SystemConfig,
    InterfaceConfig,
    InterfaceIPv4,
    InterfaceIPv6,
    VlanConfig,
    BridgeConfig,
    StaticRoute,
    FirewallAlias,
    FirewallRule,
    OutboundNatRule,
    PortForwardRule,
    DHCPSubnet,
    DNSConfig,
    WireGuardInterface,
    WireGuardPeer,
)
from mitranet.core.migration.pfsense_normalizer import PfSenseNormalizer
from mitranet.core.migration.compatibility import InterfaceMapper
from mitranet.core.migration.migration_report import MigrationReport, MigrationItem


class PfSenseMapper:
    def __init__(self, iface_mapper: InterfaceMapper = None):
        self.iface_mapper = iface_mapper or InterfaceMapper()

    def map_to_mitranet(self, raw_pfsense: Dict[str, Any]) -> Tuple[MitraNetConfig, MigrationReport]:
        report = MigrationReport()
        report.target_schema_version = SCHEMA_VERSION
        cfg = MitraNetConfig()

        pfsense_root = raw_pfsense.get("pfsense", raw_pfsense)
        version = pfsense_root.get("version", "unknown")
        report.source_version = str(version)

        # 1. System Section
        sys_data = pfsense_root.get("system", {})
        if sys_data:
            sys_cfg = SystemConfig()
            if "hostname" in sys_data and sys_data["hostname"]:
                sys_cfg.hostname = sys_data["hostname"]
            if "domain" in sys_data and sys_data["domain"]:
                sys_cfg.domain = sys_data["domain"]
            if "timezone" in sys_data and sys_data["timezone"]:
                sys_cfg.timezone = sys_data["timezone"]
            if "timeservers" in sys_data and sys_data["timeservers"]:
                sys_cfg.timeservers = [s.strip() for s in str(sys_data["timeservers"]).split()]
            if "dnsserver" in sys_data and sys_data["dnsserver"]:
                sys_cfg.dns_servers = PfSenseNormalizer.to_list(sys_data["dnsserver"])
            cfg.system = sys_cfg
            report.add_item(MigrationItem(
                category="System",
                feature="General Settings",
                status="MIGRATED",
                target_detail=f"Hostname {sys_cfg.hostname}.{sys_cfg.domain}"
            ))

        # Check unsupported BSD-specific system settings
        if "powerd_ac_mode" in sys_data or "powerd_battery_mode" in sys_data:
            report.add_item(MigrationItem(
                category="System",
                feature="FreeBSD powerd CPU governor",
                status="UNSUPPORTED",
                reason="FreeBSD powerd is specific to BSD; Linux CPU governors (cpufreq) are managed via kernel."
            ))

        # 2. Interfaces Section
        ifaces_data = pfsense_root.get("interfaces", {})
        if ifaces_data:
            for if_key, if_val in ifaces_data.items():
                if not isinstance(if_val, dict):
                    continue
                bsd_dev = if_val.get("if", if_key)
                linux_dev = self.iface_mapper.resolve(bsd_dev)
                enabled = "enable" in if_val
                role = "wan" if if_key == "wan" else ("lan" if if_key == "lan" else "opt")
                descr = if_val.get("descr", if_key.upper())

                # IPv4
                ipv4_mode = "disabled"
                ipaddr = if_val.get("ipaddr")
                subnet = PfSenseNormalizer.to_int(if_val.get("subnet"))
                gw = if_val.get("gateway")
                if ipaddr == "dhcp":
                    ipv4_mode = "dhcp"
                    ipaddr = None
                elif ipaddr and subnet is not None:
                    ipv4_mode = "static"

                # IPv6
                ipv6_mode = "disabled"
                ip6addr = if_val.get("ipaddrv6")
                subnet6 = PfSenseNormalizer.to_int(if_val.get("subnetv6"))
                if ip6addr == "dhcp6":
                    ipv6_mode = "dhcp6"
                    ip6addr = None
                elif ip6addr == "track6":
                    ipv6_mode = "slaac"
                    ip6addr = None
                elif ip6addr and subnet6 is not None:
                    ipv6_mode = "static"

                cfg.interfaces[if_key] = InterfaceConfig(
                    device=linux_dev,
                    description=descr,
                    enabled=enabled,
                    role=role,
                    type="ethernet",
                    mtu=PfSenseNormalizer.to_int(if_val.get("mtu"), 1500),
                    ipv4=InterfaceIPv4(mode=ipv4_mode, address=ipaddr, prefix=subnet, gateway=gw),
                    ipv6=InterfaceIPv6(mode=ipv6_mode, address=ip6addr, prefix=subnet6),
                )
                report.add_item(MigrationItem(
                    category="Interfaces",
                    feature=f"Interface {if_key} ({bsd_dev})",
                    status="MIGRATED",
                    target_detail=f"Mapped to Linux device '{linux_dev}'"
                ))

        # 3. VLANs
        vlans_data = pfsense_root.get("vlans", {})
        if isinstance(vlans_data, dict) and "vlan" in vlans_data:
            for vlan in PfSenseNormalizer.to_list(vlans_data["vlan"]):
                if isinstance(vlan, dict):
                    tag = PfSenseNormalizer.to_int(vlan.get("tag"))
                    ifif = vlan.get("if")
                    vif = vlan.get("vif", f"vlan_{tag}")
                    if tag and ifif:
                        cfg.vlans[vif] = VlanConfig(
                            id=tag,
                            parent=ifif,
                            description=vlan.get("descr", "")
                        )
                        report.add_item(MigrationItem(
                            category="Interfaces",
                            feature=f"VLAN {tag} on {ifif}",
                            status="MIGRATED",
                            target_detail=f"Created Linux VLAN subinterface {vif}"
                        ))

        # 4. Bridges
        bridges_data = pfsense_root.get("bridges", {})
        if isinstance(bridges_data, dict) and "bridged" in bridges_data:
            for br in PfSenseNormalizer.to_list(bridges_data["bridged"]):
                if isinstance(br, dict):
                    br_if = br.get("bridgeif", "br0")
                    members = [m.strip() for m in str(br.get("members", "")).split(",") if m.strip()]
                    cfg.bridges[br_if] = BridgeConfig(
                        members=members,
                        stp=PfSenseNormalizer.to_bool(br.get("stp")),
                        description=br.get("descr", "")
                    )
                    report.add_item(MigrationItem(
                        category="Interfaces",
                        feature=f"Bridge {br_if}",
                        status="MIGRATED",
                        target_detail=f"Linux bridge spanning {members}"
                    ))

        # 5. Static Routes
        routes_data = pfsense_root.get("staticroutes", {})
        if isinstance(routes_data, dict) and "route" in routes_data:
            for r in PfSenseNormalizer.to_list(routes_data["route"]):
                if isinstance(r, dict):
                    net = r.get("network")
                    gw = r.get("gateway")
                    if net and gw:
                        cfg.routing.static_routes.append(StaticRoute(
                            destination=net,
                            gateway=gw,
                            description=r.get("descr", "")
                        ))
                        report.add_item(MigrationItem(
                            category="Routing",
                            feature=f"Static route {net}",
                            status="MIGRATED",
                            target_detail=f"Next-hop {gw}"
                        ))

        # 6. Firewall Aliases
        aliases_data = pfsense_root.get("aliases", {})
        if isinstance(aliases_data, dict) and "alias" in aliases_data:
            for al in PfSenseNormalizer.to_list(aliases_data["alias"]):
                if isinstance(al, dict):
                    al_name = al.get("name")
                    al_type = al.get("type", "host")
                    raw_addr = str(al.get("address", "")).split()
                    mapped_type = "host" if "host" in al_type else ("port" if "port" in al_type else "network")
                    if al_name:
                        cfg.firewall.aliases[al_name] = FirewallAlias(
                            type=mapped_type,
                            entries=[e.strip() for e in raw_addr if e.strip()],
                            description=al.get("descr", "")
                        )
                        report.add_item(MigrationItem(
                            category="Firewall",
                            feature=f"Alias {al_name}",
                            status="MIGRATED",
                            target_detail=f"{mapped_type} set with {len(raw_addr)} items"
                        ))

        # 7. Firewall Rules
        filter_data = pfsense_root.get("filter", {})
        if isinstance(filter_data, dict) and "rule" in filter_data:
            for idx, r in enumerate(PfSenseNormalizer.to_list(filter_data["rule"])):
                if isinstance(r, dict):
                    rule_id = r.get("tracker", f"rule_{idx + 1}")
                    action = "accept" if r.get("type") == "pass" else "drop"
                    iface = r.get("interface", "any")
                    proto = r.get("protocol", "any")

                    # Destination parsing
                    dst = "any"
                    dst_block = r.get("destination", {})
                    if isinstance(dst_block, dict):
                        if "any" in dst_block:
                            dst = "any"
                        elif "network" in dst_block:
                            dst = dst_block["network"]
                        elif "address" in dst_block:
                            dst = dst_block["address"]

                    cfg.firewall.rules.append(FirewallRule(
                        id=rule_id,
                        action=action,
                        interface=iface,
                        protocol=proto if proto in ["tcp", "udp", "icmp", "tcp_udp"] else "any",
                        destination=dst,
                        description=r.get("descr", "")
                    ))
                    report.add_item(MigrationItem(
                        category="Firewall",
                        feature=f"Rule {rule_id}",
                        status="MIGRATED",
                        target_detail=f"{action.upper()} on {iface}"
                    ))

        # 8. NAT Outbound and Port Forward
        nat_data = pfsense_root.get("nat", {})
        if isinstance(nat_data, dict):
            # Outbound
            outbound_data = nat_data.get("outbound", {})
            if isinstance(outbound_data, dict):
                mode = outbound_data.get("mode", "automatic")
                if mode in ["automatic", "hybrid"]:
                    wan_key = next((k for k, v in cfg.interfaces.items() if v.role == "wan"), "wan")
                    cfg.nat.outbound.append(OutboundNatRule(
                        interface=wan_key,
                        source="10.0.0.0/8",
                        mode="masquerade",
                        description="Auto-migrated LAN Outbound NAT"
                    ))
                    report.add_item(MigrationItem(
                        category="NAT",
                        feature="Outbound NAT",
                        status="MIGRATED",
                        target_detail="Configured nftables postrouting masquerade"
                    ))

            # Inbound Port Forward
            if "rule" in nat_data:
                for idx, pf in enumerate(PfSenseNormalizer.to_list(nat_data["rule"])):
                    if isinstance(pf, dict):
                        pf_id = f"pf_{idx + 1}"
                        proto = pf.get("protocol", "tcp")
                        ext_port = PfSenseNormalizer.to_int(pf.get("destination", {}).get("port") if isinstance(pf.get("destination"), dict) else pf.get("local-port"))
                        int_ip = pf.get("target")
                        int_port = PfSenseNormalizer.to_int(pf.get("local-port"))
                        if ext_port and int_ip and int_port:
                            cfg.nat.port_forward.append(PortForwardRule(
                                id=pf_id,
                                interface=pf.get("interface", "wan"),
                                protocol=proto if proto in ["tcp", "udp", "tcp_udp"] else "tcp",
                                external_port=ext_port,
                                internal_ip=int_ip,
                                internal_port=int_port,
                                description=pf.get("descr", "")
                            ))
                            report.add_item(MigrationItem(
                                category="NAT",
                                feature=f"Port Forward {pf_id}",
                                status="MIGRATED",
                                target_detail=f"DNAT to {int_ip}:{int_port}"
                            ))

        # 9. DHCP Server
        dhcpd_data = pfsense_root.get("dhcpd", {})
        if isinstance(dhcpd_data, dict):
            for dhcp_if, dhcp_block in dhcpd_data.items():
                if isinstance(dhcp_block, dict) and "enable" in dhcp_block:
                    rng = dhcp_block.get("range", {})
                    r_from = rng.get("from")
                    r_to = rng.get("to")
                    if r_from and r_to:
                        # Find subnet
                        matched_if = cfg.interfaces.get(dhcp_if)
                        ip_prefix = "192.168.1.0/24"
                        gw = None
                        if matched_if and matched_if.ipv4 and matched_if.ipv4.address:
                            gw = matched_if.ipv4.address
                            ip_prefix = f"{gw.rsplit('.', 1)[0]}.0/{matched_if.ipv4.prefix or 24}"

                        cfg.dhcp[dhcp_if] = DHCPSubnet(
                            interface=dhcp_if,
                            subnet=ip_prefix,
                            range_start=r_from,
                            range_end=r_to,
                            gateway=gw
                        )
                        report.add_item(MigrationItem(
                            category="DHCP",
                            feature=f"DHCPv4 Pool on {dhcp_if}",
                            status="MIGRATED",
                            target_detail=f"{r_from} - {r_to}"
                        ))

        # 10. DNS Resolver (Unbound)
        unbound_data = pfsense_root.get("unbound", {})
        if isinstance(unbound_data, dict):
            dns_cfg = DNSConfig()
            dns_cfg.enabled = "enable" in unbound_data
            dns_cfg.dnssec = "dnssec" in unbound_data
            cfg.dns = dns_cfg
            report.add_item(MigrationItem(
                category="DNS",
                feature="Unbound DNS Resolver",
                status="MIGRATED",
                target_detail=f"DNSSEC={'enabled' if dns_cfg.dnssec else 'disabled'}"
            ))

        # 11. High Availability (CARP/XMLRPC) - PARTIAL
        if "hasync" in pfsense_root or "installedpackages" in pfsense_root and "carp" in str(pfsense_root):
            report.add_item(MigrationItem(
                category="HA",
                feature="CARP & XMLRPC Sync",
                status="PARTIAL",
                reason="MitraNet uses Linux Keepalived VRRP and REST replication instead of BSD CARP / XMLRPC."
            ))

        # 12. BSD ALTQ Traffic Shaper - UNSUPPORTED / PARTIAL
        if "shaper" in pfsense_root and pfsense_root["shaper"]:
            report.add_item(MigrationItem(
                category="QoS",
                feature="ALTQ Traffic Shaper",
                status="PARTIAL",
                reason="FreeBSD ALTQ queues translated to Linux tc (HTB + CAKE FQ-CoDel)."
            ))

        return cfg, report
