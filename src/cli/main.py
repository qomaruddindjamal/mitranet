"""
MitraNet Unified Command Line Interface (CLI).
Provides operations for config validation, pfSense migration, commit, rollback,
and dynamic Linux kernel network interface discovery & state (Phase 1A).
"""

import sys
import os
import json
from mitranet.core.version import MITRANET_VERSION, CODENAME, PRETTY_NAME
from mitranet.core.config.loader import ConfigLoader, ConfigWriter
from mitranet.core.config.validator import ConfigValidator
from mitranet.core.config.transaction import ConfigTransactionManager
from mitranet.core.migration.exporter import MigrationExporter
from mitranet.core.network.discovery import InterfaceDiscoveryService
from mitranet.core.network.config_service import InterfaceConfigurationService
from mitranet.core.network.routing_discovery import RouteDiscoveryService
from mitranet.core.network.routing_service import RouteConfigurationService
from mitranet.core.network.vlan import VlanService
from mitranet.core.network.bridge import BridgeService
from mitranet.core.network.bonding import BondService
from mitranet.core.network.exceptions import (
    InterfaceNotFoundError,
    BackendExecutionError,
    NetworkValidationError,
    NetworkSecurityError,
    SafetyConstraintViolationError,
    VerificationFailureError,
    RouteNotFoundError,
    RouteAlreadyExistsError,
    ProtectedRouteError,
    DeviceAlreadyExistsError,
    DeviceNotFoundError,
    UnsupportedBondModeError,
)



def print_banner():
    print("=" * 65)
    print(f"       {PRETTY_NAME.upper()} (Debian Core)")
    print(f"       Carrier-Grade Firewall, Routing & Hardware NOS")
    print("=" * 65)


def cmd_config_validate(args):
    if not args:
        print("Usage: mitranet config validate <path-to-config.json>")
        sys.exit(1)
    filepath = args[0]
    try:
        cfg = ConfigLoader.load_from_file(filepath)
        errors = ConfigValidator.validate(cfg)
        if errors:
            print(f"[FAIL] Configuration validation failed:")
            for e in errors:
                print(f"  - {e}")
            sys.exit(1)
        else:
            print(f"[PASS] Configuration '{filepath}' is valid.")
    except Exception as e:
        print(f"[ERROR] {e}")
        sys.exit(1)


def cmd_config_import_pfsense(args):
    if not args:
        print("Usage: mitranet config import-pfsense <pfsense-config.xml> [output-config.json]")
        sys.exit(1)
    xml_path = args[0]
    out_json = args[1] if len(args) > 1 else "C:/mitranet/tests/fixtures/pfsense/pfsense-migrated.json"
    out_rep_json = out_json.replace(".json", "-report.json")
    out_rep_md = out_json.replace(".json", "-report.md")

    try:
        cfg, report = MigrationExporter.migrate_pfsense_file(
            xml_filepath=xml_path,
            output_json_path=out_json,
            output_report_json_path=out_rep_json,
            output_report_md_path=out_rep_md
        )
        print("\nMigration completed successfully!")
        print("-" * 40)
        print(f"Migrated       : {report.summary.get('migrated', 0)}")
        print(f"Partial        : {report.summary.get('partial', 0)}")
        print(f"Unsupported    : {report.summary.get('unsupported', 0)}")
        print(f"Failed         : {report.summary.get('failed', 0)}")
        print("-" * 40)
        print("Artifacts generated:")
        print(f"  Configuration : {out_json}")
        print(f"  Report (JSON) : {out_rep_json}")
        print(f"  Report (MD)   : {out_rep_md}")
    except Exception as e:
        print(f"[ERROR] Migration failed: {e}")
        sys.exit(1)


def cmd_config_show(args):
    mgr = ConfigTransactionManager()
    cfg = mgr.get_running()
    print(cfg.model_dump_json(indent=2))


def cmd_config_commit(args):
    note = " ".join(args) if args else "Commit via CLI"
    mgr = ConfigTransactionManager()
    try:
        success, msg = mgr.commit(note)
        print(f"[{'PASS' if success else 'FAIL'}] {msg}")
    except Exception as e:
        print(f"[ERROR] {e}")
        sys.exit(1)


def cmd_config_rollback(args):
    snap_id = args[0] if args else None
    mgr = ConfigTransactionManager()
    try:
        success, msg = mgr.rollback(snap_id)
        print(f"[{'PASS' if success else 'FAIL'}] {msg}")
    except Exception as e:
        print(f"[ERROR] {e}")
        sys.exit(1)


# ==========================================
# Phase 1A: Network Interface Discovery CLI
# ==========================================

def cmd_interface_list(args, service: InterfaceDiscoveryService = None):
    service = service or InterfaceDiscoveryService()
    as_json = "--json" in args

    try:
        ifaces = service.discover_interfaces()
    except BackendExecutionError as e:
        print(f"[ERROR] {e}")
        sys.exit(1)

    if as_json:
        data = [i.model_dump() for i in ifaces]
        print(json.dumps(data, indent=2))
        return

    # Table format
    print(f"{'INTERFACE':<12} {'TYPE':<10} {'ADMIN':<7} {'OPER':<8} {'CARRIER':<9} {'MTU':<7} {'MAC'}")
    print("-" * 75)
    for i in ifaces:
        carrier_str = "UP" if i.carrier is True else ("DOWN" if i.carrier is False else "-")
        mac_str = i.mac_address or "-"
        print(f"{i.name:<12} {i.type:<10} {i.admin_state:<7} {i.oper_state:<8} {carrier_str:<9} {i.mtu:<7} {mac_str}")


def cmd_interface_show(args, service: InterfaceDiscoveryService = None):
    service = service or InterfaceDiscoveryService()
    as_json = "--json" in args
    clean_args = [a for a in args if a != "--json"]

    if not clean_args:
        print("Usage: mitranet interface show <name> [--json]")
        sys.exit(1)

    ifname = clean_args[0]
    try:
        iface = service.get_interface(ifname)
    except InterfaceNotFoundError:
        print(f"Interface '{ifname}' not found.")
        sys.exit(1)
    except BackendExecutionError as e:
        print(f"[ERROR] {e}")
        sys.exit(1)

    if as_json:
        print(iface.model_dump_json(indent=2))
        return

    carrier_str = "UP" if iface.carrier is True else ("DOWN" if iface.carrier is False else "Unknown / N/A")
    ipv4_str = ", ".join(iface.ipv4_addresses) if iface.ipv4_addresses else "None"
    ipv6_str = ", ".join(iface.ipv6_addresses) if iface.ipv6_addresses else "None"
    flags_str = ", ".join(iface.flags) if iface.flags else "None"

    print(f"Interface: {iface.name}")
    print(f"  Index               : {iface.index}")
    print(f"  Type                : {iface.type}")
    print(f"  MAC Address         : {iface.mac_address or 'None'}")
    print(f"  MTU                 : {iface.mtu}")
    print(f"  Administrative State: {iface.admin_state}")
    print(f"  Operational State   : {iface.oper_state}")
    print(f"  Physical Carrier    : {carrier_str}")
    print(f"  Flags               : {flags_str}")
    if iface.parent_device:
        print(f"  Parent Device       : {iface.parent_device}")
    print(f"  IPv4 Addresses      : {ipv4_str}")
    print(f"  IPv6 Addresses      : {ipv6_str}")
    print("  Statistics:")
    print(f"    RX Bytes: {iface.statistics.rx_bytes:<12} Packets: {iface.statistics.rx_packets:<8} Errors: {iface.statistics.rx_errors:<4} Dropped: {iface.statistics.rx_dropped}")
    print(f"    TX Bytes: {iface.statistics.tx_bytes:<12} Packets: {iface.statistics.tx_packets:<8} Errors: {iface.statistics.tx_errors:<4} Dropped: {iface.statistics.tx_dropped}")


def cmd_interface_up(args, service: InterfaceConfigurationService = None):
    service = service or InterfaceConfigurationService()
    if not args:
        print("Usage: mitranet interface up <interface>")
        sys.exit(1)
    ifname = args[0]
    try:
        iface = service.set_interface_up(ifname)
        print(f"[SUCCESS] Interface '{ifname}' administrative state is now UP (oper_state={iface.oper_state}).")
    except (NetworkValidationError, NetworkSecurityError, SafetyConstraintViolationError) as e:
        print(f"[VALIDATION ERROR] {e}")
        sys.exit(1)
    except (InterfaceNotFoundError, VerificationFailureError, BackendExecutionError) as e:
        print(f"[ERROR] {e}")
        sys.exit(1)


def cmd_interface_down(args, service: InterfaceConfigurationService = None):
    service = service or InterfaceConfigurationService()
    if not args:
        print("Usage: mitranet interface down <interface>")
        sys.exit(1)
    ifname = args[0]
    try:
        iface = service.set_interface_down(ifname)
        print(f"[SUCCESS] Interface '{ifname}' administrative state is now DOWN (oper_state={iface.oper_state}).")
    except (NetworkValidationError, NetworkSecurityError, SafetyConstraintViolationError) as e:
        print(f"[VALIDATION ERROR] {e}")
        sys.exit(1)
    except (InterfaceNotFoundError, VerificationFailureError, BackendExecutionError) as e:
        print(f"[ERROR] {e}")
        sys.exit(1)


def cmd_interface_set(args, service: InterfaceConfigurationService = None):
    service = service or InterfaceConfigurationService()
    if len(args) < 3:
        print("Usage: mitranet interface set <interface> mtu <value>")
        print("       mitranet interface set <interface> mac <address>")
        sys.exit(1)
    ifname = args[0]
    prop = args[1].lower()
    val = args[2]

    try:
        if prop == "mtu":
            iface = service.set_mtu(ifname, val)
            print(f"[SUCCESS] Interface '{ifname}' MTU set to {iface.mtu}.")
        elif prop in ("mac", "address"):
            iface = service.set_mac(ifname, val)
            print(f"[SUCCESS] Interface '{ifname}' MAC address set to {iface.mac_address}.")
        else:
            print(f"Unknown property '{prop}'. Supported: mtu, mac")
            sys.exit(1)
    except (NetworkValidationError, NetworkSecurityError, SafetyConstraintViolationError) as e:
        print(f"[VALIDATION ERROR] {e}")
        sys.exit(1)
    except (InterfaceNotFoundError, VerificationFailureError, BackendExecutionError) as e:
        print(f"[ERROR] {e}")
        sys.exit(1)


def cmd_interface_address(args, service: InterfaceConfigurationService = None):
    service = service or InterfaceConfigurationService()
    if len(args) < 3:
        print("Usage: mitranet interface address add <interface> <CIDR>")
        print("       mitranet interface address remove <interface> <CIDR>")
        sys.exit(1)
    action = args[0].lower()
    ifname = args[1]
    cidr = args[2]

    try:
        if action == "add":
            iface = service.add_address(ifname, cidr)
            print(f"[SUCCESS] Address {cidr} added to interface '{ifname}'.")
        elif action in ("remove", "del", "delete"):
            iface = service.remove_address(ifname, cidr)
            print(f"[SUCCESS] Address {cidr} removed from interface '{ifname}'.")
        else:
            print(f"Unknown address action '{action}'. Supported: add, remove")
            sys.exit(1)
    except (NetworkValidationError, NetworkSecurityError, SafetyConstraintViolationError) as e:
        print(f"[VALIDATION ERROR] {e}")
        sys.exit(1)
    except (InterfaceNotFoundError, VerificationFailureError, BackendExecutionError) as e:
        print(f"[ERROR] {e}")
        sys.exit(1)


# =========================================================================
# Phase 1C: Routing Core CLI Commands
# =========================================================================

def cmd_route_list(args, discovery: RouteDiscoveryService = None):
    discovery = discovery or RouteDiscoveryService()
    as_json = "--json" in args
    family = None
    table = 254

    i = 0
    while i < len(args):
        a = args[i].lower()
        if a == "--ipv4":
            family = "inet"
        elif a == "--ipv6":
            family = "inet6"
        elif a == "--table" and i + 1 < len(args):
            try:
                table = int(args[i + 1])
            except ValueError:
                table = 254
            i += 1
        i += 1

    routes = discovery.get_routes(family=family, table=table)

    if as_json:
        data = [r.model_dump() for r in routes]
        print(json.dumps(data, indent=2))
    else:
        print(f"{'DESTINATION':<28} {'GATEWAY':<20} {'INTERFACE':<10} {'METRIC':<8} {'TABLE':<6} {'FAMILY':<6}")
        print("-" * 84)
        for r in routes:
            gw_str = r.gateway or "*"
            dev_str = r.interface or "*"
            metric_str = str(r.metric) if r.metric is not None else "-"
            print(f"{r.destination:<28} {gw_str:<20} {dev_str:<10} {metric_str:<8} {r.table:<6} {r.family:<6}")


def cmd_route_show(args, discovery: RouteDiscoveryService = None):
    discovery = discovery or RouteDiscoveryService()
    if not args or args[0].startswith("--"):
        print("Usage: mitranet route show <destination> [--json] [--table <table>]")
        sys.exit(1)
    target_dest = args[0]
    as_json = "--json" in args
    table = 254

    for i, a in enumerate(args):
        if a.lower() == "--table" and i + 1 < len(args):
            try:
                table = int(args[i + 1])
            except ValueError:
                table = 254

    routes = discovery.get_routes(table=table)
    matched = [r for r in routes if r.destination == target_dest or (r.is_default and target_dest in ("default", "0.0.0.0/0", "::/0"))]

    if not matched:
        print(f"[ERROR] Route to '{target_dest}' not found in routing table {table}.")
        sys.exit(1)

    if as_json:
        print(json.dumps([r.model_dump() for r in matched], indent=2))
    else:
        for r in matched:
            print(f"Route: {r.destination}")
            print(f"  Family    : {r.family}")
            print(f"  Gateway   : {r.gateway or 'Direct / On-link'}")
            print(f"  Interface : {r.interface or 'N/A'}")
            print(f"  Metric    : {r.metric if r.metric is not None else 'Default'}")
            print(f"  Table     : {r.table}")
            print(f"  Protocol  : {r.protocol or 'N/A'}")
            print(f"  Scope     : {r.scope or 'N/A'}")
            print(f"  Type      : {r.type or 'unicast'}")


def cmd_route_add(args, service: RouteConfigurationService = None):
    service = service or RouteConfigurationService()
    if not args:
        print("Usage: mitranet route add <destination> [via <gateway>] [dev <interface>] [metric <metric>] [table <table>]")
        sys.exit(1)

    dest = args[0]
    gateway = None
    interface = None
    metric = None
    table = 254

    i = 1
    while i < len(args):
        token = args[i].lower()
        if token in ("via", "gw", "gateway") and i + 1 < len(args):
            gateway = args[i + 1]
            i += 1
        elif token in ("dev", "iface", "interface") and i + 1 < len(args):
            interface = args[i + 1]
            i += 1
        elif token == "metric" and i + 1 < len(args):
            try:
                metric = int(args[i + 1])
            except ValueError:
                print(f"[ERROR] Invalid metric value: {args[i + 1]}")
                sys.exit(1)
            i += 1
        elif token == "table" and i + 1 < len(args):
            try:
                table = int(args[i + 1])
            except ValueError:
                table = args[i + 1]
            i += 1
        i += 1

    try:
        r = service.add_route(destination=dest, gateway=gateway, interface=interface, metric=metric, table=table)
        print(f"[SUCCESS] Route to {r.destination} via {r.gateway or 'dev'} {r.interface or ''} added (table {r.table}).")
    except (NetworkValidationError, NetworkSecurityError, SafetyConstraintViolationError, ProtectedRouteError, RouteAlreadyExistsError) as e:
        print(f"[VALIDATION ERROR] {e}")
        sys.exit(1)
    except (VerificationFailureError, BackendExecutionError, InterfaceNotFoundError) as e:
        print(f"[ERROR] {e}")
        sys.exit(1)


def cmd_route_remove(args, service: RouteConfigurationService = None):
    service = service or RouteConfigurationService()
    if not args:
        print("Usage: mitranet route remove <destination> [via <gateway>] [dev <interface>] [table <table>]")
        sys.exit(1)

    dest = args[0]
    gateway = None
    interface = None
    table = 254

    i = 1
    while i < len(args):
        token = args[i].lower()
        if token in ("via", "gw", "gateway") and i + 1 < len(args):
            gateway = args[i + 1]
            i += 1
        elif token in ("dev", "iface", "interface") and i + 1 < len(args):
            interface = args[i + 1]
            i += 1
        elif token == "table" and i + 1 < len(args):
            try:
                table = int(args[i + 1])
            except ValueError:
                table = args[i + 1]
            i += 1
        i += 1

    try:
        service.remove_route(destination=dest, gateway=gateway, interface=interface, table=table)
        print(f"[SUCCESS] Route to {dest} removed (table {table}).")
    except (NetworkValidationError, NetworkSecurityError, SafetyConstraintViolationError, ProtectedRouteError, RouteNotFoundError) as e:
        print(f"[VALIDATION ERROR] {e}")
        sys.exit(1)
    except (VerificationFailureError, BackendExecutionError) as e:
        print(f"[ERROR] {e}")
        sys.exit(1)


# =========================================================================
# VLAN Subcommands (Phase 1D)
# =========================================================================

def cmd_vlan_list(args, service: VlanService = None):
    service = service or VlanService()
    as_json = "--json" in args
    try:
        vlans = service.discover_vlans()
        if as_json:
            print(json.dumps([v.model_dump() for v in vlans], indent=2))
        else:
            if not vlans:
                print("No 802.1Q VLAN interfaces found.")
                return
            header = f"{'VLAN NAME':<18} {'PARENT':<12} {'VLAN ID':<10} {'STATE':<8} {'MTU':<8} {'IP ADDRESSES'}"
            print(header)
            print("-" * 75)
            for v in vlans:
                ips = ", ".join(v.ipv4_addresses + v.ipv6_addresses) or "-"
                print(f"{v.name:<18} {v.parent:<12} {v.vlan_id:<10} {v.admin_state:<8} {v.mtu:<8} {ips}")
    except Exception as e:
        print(f"[ERROR] Failed to discover VLANs: {e}")
        sys.exit(1)


def cmd_vlan_show(args, service: VlanService = None):
    service = service or VlanService()
    if not args:
        print("Usage: mitranet vlan show <name> [--json]")
        sys.exit(1)
    name = args[0]
    as_json = "--json" in args
    try:
        v = service.get_vlan(name)
        if as_json:
            print(json.dumps(v.model_dump(), indent=2))
        else:
            print(f"VLAN Interface: {v.name}")
            print(f"  Parent Interface : {v.parent}")
            print(f"  VLAN ID          : {v.vlan_id}")
            print(f"  Protocol         : {v.protocol}")
            print(f"  Admin State      : {v.admin_state}")
            print(f"  Operational State: {v.oper_state}")
            print(f"  MTU              : {v.mtu}")
            print(f"  MAC Address      : {v.mac_address or '-'}")
            print(f"  IPv4 Addresses   : {', '.join(v.ipv4_addresses) or '-'}")
            print(f"  IPv6 Addresses   : {', '.join(v.ipv6_addresses) or '-'}")
    except (DeviceNotFoundError, NetworkValidationError, NetworkSecurityError) as e:
        print(f"[ERROR] {e}")
        sys.exit(1)


def cmd_vlan_create(args, service: VlanService = None):
    service = service or VlanService()
    if len(args) < 3:
        print("Usage: mitranet vlan create <name> <parent> <vlan_id>")
        sys.exit(1)
    name, parent, vlan_id = args[0], args[1], args[2]
    try:
        v = service.create_vlan(name=name, parent=parent, vlan_id=int(vlan_id))
        print(f"[SUCCESS] VLAN '{v.name}' (id={v.vlan_id}, parent={v.parent}) created successfully.")
    except (NetworkValidationError, NetworkSecurityError, DeviceAlreadyExistsError) as e:
        print(f"[VALIDATION ERROR] {e}")
        sys.exit(1)
    except (VerificationFailureError, BackendExecutionError, InterfaceNotFoundError) as e:
        print(f"[ERROR] {e}")
        sys.exit(1)


def cmd_vlan_delete(args, service: VlanService = None):
    service = service or VlanService()
    if not args:
        print("Usage: mitranet vlan delete <name>")
        sys.exit(1)
    name = args[0]
    try:
        service.delete_vlan(name)
        print(f"[SUCCESS] VLAN '{name}' deleted successfully.")
    except (NetworkValidationError, NetworkSecurityError, DeviceNotFoundError) as e:
        print(f"[VALIDATION ERROR] {e}")
        sys.exit(1)
    except (VerificationFailureError, BackendExecutionError) as e:
        print(f"[ERROR] {e}")
        sys.exit(1)


def cmd_vlan_up(args, service: VlanService = None):
    service = service or VlanService()
    if not args:
        print("Usage: mitranet vlan up <name>")
        sys.exit(1)
    name = args[0]
    try:
        service.set_vlan_up(name)
        print(f"[SUCCESS] VLAN '{name}' set UP.")
    except Exception as e:
        print(f"[ERROR] {e}")
        sys.exit(1)


def cmd_vlan_down(args, service: VlanService = None):
    service = service or VlanService()
    if not args:
        print("Usage: mitranet vlan down <name>")
        sys.exit(1)
    name = args[0]
    try:
        service.set_vlan_down(name)
        print(f"[SUCCESS] VLAN '{name}' set DOWN.")
    except Exception as e:
        print(f"[ERROR] {e}")
        sys.exit(1)


# =========================================================================
# Linux Bridge Subcommands (Phase 1D)
# =========================================================================

def cmd_bridge_list(args, service: BridgeService = None):
    service = service or BridgeService()
    as_json = "--json" in args
    try:
        bridges = service.discover_bridges()
        if as_json:
            print(json.dumps([b.model_dump() for b in bridges], indent=2))
        else:
            if not bridges:
                print("No Linux bridges found.")
                return
            header = f"{'BRIDGE NAME':<18} {'ADMIN':<8} {'OPER':<8} {'STP':<6} {'PORTS':<20} {'IP ADDRESSES'}"
            print(header)
            print("-" * 75)
            for b in bridges:
                ports_str = ", ".join(p.interface for p in b.ports) or "-"
                ips = ", ".join(b.ipv4_addresses + b.ipv6_addresses) or "-"
                print(f"{b.name:<18} {b.admin_state:<8} {b.oper_state:<8} {str(b.stp_enabled):<6} {ports_str:<20} {ips}")
    except Exception as e:
        print(f"[ERROR] Failed to discover bridges: {e}")
        sys.exit(1)


def cmd_bridge_show(args, service: BridgeService = None):
    service = service or BridgeService()
    if not args:
        print("Usage: mitranet bridge show <name> [--json]")
        sys.exit(1)
    name = args[0]
    as_json = "--json" in args
    try:
        b = service.get_bridge(name)
        if as_json:
            print(json.dumps(b.model_dump(), indent=2))
        else:
            print(f"Linux Bridge: {b.name}")
            print(f"  Admin State      : {b.admin_state}")
            print(f"  Operational State: {b.oper_state}")
            print(f"  STP Enabled      : {b.stp_enabled}")
            print(f"  MAC Address      : {b.mac_address or '-'}")
            print(f"  MTU              : {b.mtu}")
            ports_summary = ", ".join(f"{p.interface} ({p.state})" for p in b.ports) or "-"
            print(f"  Member Ports     : {ports_summary}")
            print(f"  IPv4 Addresses   : {', '.join(b.ipv4_addresses) or '-'}")
            print(f"  IPv6 Addresses   : {', '.join(b.ipv6_addresses) or '-'}")
    except (DeviceNotFoundError, NetworkValidationError, NetworkSecurityError) as e:
        print(f"[ERROR] {e}")
        sys.exit(1)


def cmd_bridge_create(args, service: BridgeService = None):
    service = service or BridgeService()
    if not args:
        print("Usage: mitranet bridge create <name>")
        sys.exit(1)
    name = args[0]
    try:
        b = service.create_bridge(name)
        print(f"[SUCCESS] Bridge '{b.name}' created successfully.")
    except (NetworkValidationError, NetworkSecurityError, DeviceAlreadyExistsError) as e:
        print(f"[VALIDATION ERROR] {e}")
        sys.exit(1)
    except (VerificationFailureError, BackendExecutionError) as e:
        print(f"[ERROR] {e}")
        sys.exit(1)


def cmd_bridge_delete(args, service: BridgeService = None):
    service = service or BridgeService()
    if not args:
        print("Usage: mitranet bridge delete <name>")
        sys.exit(1)
    name = args[0]
    try:
        service.delete_bridge(name)
        print(f"[SUCCESS] Bridge '{name}' deleted successfully.")
    except (NetworkValidationError, NetworkSecurityError, DeviceNotFoundError) as e:
        print(f"[VALIDATION ERROR] {e}")
        sys.exit(1)
    except (VerificationFailureError, BackendExecutionError) as e:
        print(f"[ERROR] {e}")
        sys.exit(1)


def cmd_bridge_port(args, service: BridgeService = None):
    service = service or BridgeService()
    if len(args) < 3:
        print("Usage: mitranet bridge port add|remove <bridge> <interface>")
        sys.exit(1)
    action = args[0].lower()
    bname = args[1]
    iface = args[2]

    try:
        if action == "add":
            service.add_port(bname, iface)
            print(f"[SUCCESS] Interface '{iface}' added to bridge '{bname}'.")
        elif action in ("remove", "del", "delete"):
            service.remove_port(bname, iface)
            print(f"[SUCCESS] Interface '{iface}' removed from bridge '{bname}'.")
        else:
            print(f"Unknown bridge port action '{action}'. Use 'add' or 'remove'.")
            sys.exit(1)
    except (NetworkValidationError, NetworkSecurityError, SafetyConstraintViolationError, DeviceAlreadyExistsError, DeviceNotFoundError, InterfaceNotFoundError) as e:
        print(f"[VALIDATION ERROR] {e}")
        sys.exit(1)
    except (VerificationFailureError, BackendExecutionError) as e:
        print(f"[ERROR] {e}")
        sys.exit(1)


# =========================================================================
# Linux Bonding Subcommands (Phase 1D)
# =========================================================================

def cmd_bond_list(args, service: BondService = None):
    service = service or BondService()
    as_json = "--json" in args
    try:
        bonds = service.discover_bonds()
        if as_json:
            print(json.dumps([b.model_dump() for b in bonds], indent=2))
        else:
            if not bonds:
                print("No Linux bonding interfaces found.")
                return
            header = f"{'BOND NAME':<16} {'MODE':<16} {'ADMIN':<8} {'OPER':<8} {'SLAVES':<20} {'ACTIVE SLAVE'}"
            print(header)
            print("-" * 78)
            for b in bonds:
                slaves_str = ", ".join(s.interface for s in b.slaves) or "-"
                print(f"{b.name:<16} {b.mode:<16} {b.admin_state:<8} {b.oper_state:<8} {slaves_str:<20} {b.active_slave or '-'}")
    except Exception as e:
        print(f"[ERROR] Failed to discover bonds: {e}")
        sys.exit(1)


def cmd_bond_show(args, service: BondService = None):
    service = service or BondService()
    if not args:
        print("Usage: mitranet bond show <name> [--json]")
        sys.exit(1)
    name = args[0]
    as_json = "--json" in args
    try:
        b = service.get_bond(name)
        if as_json:
            print(json.dumps(b.model_dump(), indent=2))
        else:
            print(f"Linux Bond: {b.name}")
            print(f"  Bonding Mode     : {b.mode}")
            print(f"  Admin State      : {b.admin_state}")
            print(f"  Operational State: {b.oper_state}")
            print(f"  MAC Address      : {b.mac_address or '-'}")
            print(f"  MTU              : {b.mtu}")
            print(f"  MII Monitoring   : {b.miimon or '-'} ms")
            print(f"  Active Slave     : {b.active_slave or '-'}")
            slaves_detail = ", ".join(f"{s.interface} (mii={s.mii_status or 'unknown'})" for s in b.slaves) or "-"
            print(f"  Slave Members    : {slaves_detail}")
            if b.mode in ("802.3ad", "4"):
                print(f"  LACP Rate        : {b.lacp_rate or '-'}")
                print(f"  LACP Active      : {b.lacp_active or '-'}")
                print(f"  802.3ad Actor Sys: {b.ad_actor_system or '-'}")
            print(f"  IPv4 Addresses   : {', '.join(b.ipv4_addresses) or '-'}")
            print(f"  IPv6 Addresses   : {', '.join(b.ipv6_addresses) or '-'}")
    except (DeviceNotFoundError, NetworkValidationError, NetworkSecurityError) as e:
        print(f"[ERROR] {e}")
        sys.exit(1)


def cmd_bond_create(args, service: BondService = None):
    service = service or BondService()
    if len(args) < 2:
        print("Usage: mitranet bond create <name> <mode>")
        sys.exit(1)
    name, mode = args[0], args[1]
    try:
        b = service.create_bond(name, mode=mode)
        print(f"[SUCCESS] Bond '{b.name}' (mode={b.mode}) created successfully.")
    except (NetworkValidationError, NetworkSecurityError, UnsupportedBondModeError, DeviceAlreadyExistsError) as e:
        print(f"[VALIDATION ERROR] {e}")
        sys.exit(1)
    except (VerificationFailureError, BackendExecutionError) as e:
        print(f"[ERROR] {e}")
        sys.exit(1)


def cmd_bond_delete(args, service: BondService = None):
    service = service or BondService()
    if not args:
        print("Usage: mitranet bond delete <name>")
        sys.exit(1)
    name = args[0]
    try:
        service.delete_bond(name)
        print(f"[SUCCESS] Bond '{name}' deleted successfully.")
    except (NetworkValidationError, NetworkSecurityError, DeviceNotFoundError) as e:
        print(f"[VALIDATION ERROR] {e}")
        sys.exit(1)
    except (VerificationFailureError, BackendExecutionError) as e:
        print(f"[ERROR] {e}")
        sys.exit(1)


def cmd_bond_slave(args, service: BondService = None):
    service = service or BondService()
    if len(args) < 3:
        print("Usage: mitranet bond slave add|remove <bond> <interface>")
        sys.exit(1)
    action = args[0].lower()
    bname = args[1]
    iface = args[2]

    try:
        if action == "add":
            service.add_slave(bname, iface)
            print(f"[SUCCESS] Slave '{iface}' added to bond '{bname}'.")
        elif action in ("remove", "del", "delete"):
            service.remove_slave(bname, iface)
            print(f"[SUCCESS] Slave '{iface}' removed from bond '{bname}'.")
        else:
            print(f"Unknown bond slave action '{action}'. Use 'add' or 'remove'.")
            sys.exit(1)
    except (NetworkValidationError, NetworkSecurityError, SafetyConstraintViolationError, DeviceAlreadyExistsError, DeviceNotFoundError, InterfaceNotFoundError) as e:
        print(f"[VALIDATION ERROR] {e}")
        sys.exit(1)
    except (VerificationFailureError, BackendExecutionError) as e:
        print(f"[ERROR] {e}")
        sys.exit(1)



def main():
    if len(sys.argv) < 2 or sys.argv[1].lower() in ["--help", "-h", "help"]:
        print_banner()
        print("\nUsage: mitranet <command> <subcommand> [options]")
        print("\nCommands:")
        print("  interface list [--json]                   List discovered network interfaces")
        print("  interface show <name> [--json]            Show operational details of an interface")
        print("  interface up <name>                       Set interface administrative state UP")
        print("  interface down <name>                     Set interface administrative state DOWN")
        print("  interface set <name> mtu <value>          Set interface MTU")
        print("  interface set <name> mac <mac_address>    Set interface MAC address")
        print("  interface address add <name> <CIDR>       Add IPv4/IPv6 address to interface")
        print("  interface address remove <name> <CIDR>    Remove IPv4/IPv6 address from interface")
        print("  route list [--json] [--ipv4|--ipv6]       List discovered kernel routing table entries")
        print("  route show <destination> [--json]         Show routing details for a destination")
        print("  route add <dest> [via <gw>] [dev <if>]    Add static route with verification")
        print("  route remove <dest> [via <gw>] [dev <if>] Remove static route from kernel")
        print("  vlan list [--json]                        List 802.1Q VLAN interfaces")
        print("  vlan show <name> [--json]                 Show details of a VLAN interface")
        print("  vlan create <name> <parent> <vlan_id>     Create 802.1Q VLAN interface")
        print("  vlan delete <name>                        Delete VLAN interface")
        print("  bridge list [--json]                      List Linux bridge interfaces")
        print("  bridge show <name> [--json]               Show bridge and member ports")
        print("  bridge create <name>                      Create Linux bridge")
        print("  bridge delete <name>                      Delete Linux bridge")
        print("  bridge port add|remove <br> <iface>       Attach or detach bridge port")
        print("  bond list [--json]                        List bonding interfaces")
        print("  bond show <name> [--json]                 Show bond mode and slaves")
        print("  bond create <name> <mode>                 Create bond (e.g. active-backup, 802.3ad)")
        print("  bond delete <name>                        Delete bonding interface")
        print("  bond slave add|remove <bond> <iface>      Attach or detach slave interface")
        print("  config validate <config.json>             Validate configuration file")
        print("  config import-pfsense <xml>               Migrate pfSense config.xml to MitraNet JSON")
        print("  config show                               Display current running configuration")
        print("  config commit [note]                      Commit candidate configuration to running")
        print("  config rollback [snapshot_id]             Rollback to previous snapshot")
        print("  version                                   Show OS and MitraNet version")
        sys.exit(0)

    category = sys.argv[1].lower()
    if category in ["--version", "-v", "version"]:
        print(f"{PRETTY_NAME} (OS: MitraNet, Codename: {CODENAME}, Release: {MITRANET_VERSION})")
        sys.exit(0)

    if category == "interface":
        if len(sys.argv) < 3:
            print("Specify an interface subcommand: 'list', 'show', 'up', 'down', 'set', 'address'")
            sys.exit(1)
        subcmd = sys.argv[2].lower()
        subargs = sys.argv[3:]
        if subcmd == "list":
            cmd_interface_list(subargs)
        elif subcmd == "show":
            cmd_interface_show(subargs)
        elif subcmd == "up":
            cmd_interface_up(subargs)
        elif subcmd == "down":
            cmd_interface_down(subargs)
        elif subcmd == "set":
            cmd_interface_set(subargs)
        elif subcmd == "address":
            cmd_interface_address(subargs)
        else:
            print(f"Unknown interface subcommand: {subcmd}")
            sys.exit(1)

    elif category == "route":
        if len(sys.argv) < 3:
            print("Specify a route subcommand: 'list', 'show', 'add', 'remove'")
            sys.exit(1)
        subcmd = sys.argv[2].lower()
        subargs = sys.argv[3:]
        if subcmd == "list":
            cmd_route_list(subargs)
        elif subcmd == "show":
            cmd_route_show(subargs)
        elif subcmd == "add":
            cmd_route_add(subargs)
        elif subcmd in ("remove", "del", "delete"):
            cmd_route_remove(subargs)
        else:
            print(f"Unknown route subcommand: {subcmd}")
            sys.exit(1)

    elif category == "vlan":
        if len(sys.argv) < 3:
            print("Specify a vlan subcommand: 'list', 'show', 'create', 'delete', 'up', 'down'")
            sys.exit(1)
        subcmd = sys.argv[2].lower()
        subargs = sys.argv[3:]
        if subcmd == "list":
            cmd_vlan_list(subargs)
        elif subcmd == "show":
            cmd_vlan_show(subargs)
        elif subcmd in ("create", "add"):
            cmd_vlan_create(subargs)
        elif subcmd in ("delete", "del", "remove"):
            cmd_vlan_delete(subargs)
        elif subcmd == "up":
            cmd_vlan_up(subargs)
        elif subcmd == "down":
            cmd_vlan_down(subargs)
        else:
            print(f"Unknown vlan subcommand: {subcmd}")
            sys.exit(1)

    elif category == "bridge":
        if len(sys.argv) < 3:
            print("Specify a bridge subcommand: 'list', 'show', 'create', 'delete', 'port'")
            sys.exit(1)
        subcmd = sys.argv[2].lower()
        subargs = sys.argv[3:]
        if subcmd == "list":
            cmd_bridge_list(subargs)
        elif subcmd == "show":
            cmd_bridge_show(subargs)
        elif subcmd in ("create", "add"):
            cmd_bridge_create(subargs)
        elif subcmd in ("delete", "del", "remove"):
            cmd_bridge_delete(subargs)
        elif subcmd == "port":
            cmd_bridge_port(subargs)
        else:
            print(f"Unknown bridge subcommand: {subcmd}")
            sys.exit(1)

    elif category == "bond":
        if len(sys.argv) < 3:
            print("Specify a bond subcommand: 'list', 'show', 'create', 'delete', 'slave'")
            sys.exit(1)
        subcmd = sys.argv[2].lower()
        subargs = sys.argv[3:]
        if subcmd == "list":
            cmd_bond_list(subargs)
        elif subcmd == "show":
            cmd_bond_show(subargs)
        elif subcmd in ("create", "add"):
            cmd_bond_create(subargs)
        elif subcmd in ("delete", "del", "remove"):
            cmd_bond_delete(subargs)
        elif subcmd == "slave":
            cmd_bond_slave(subargs)
        else:
            print(f"Unknown bond subcommand: {subcmd}")
            sys.exit(1)

    elif category == "config":
        if len(sys.argv) < 3:
            print("Specify a config subcommand (validate, import-pfsense, show, commit, rollback)")
            sys.exit(1)
        subcmd = sys.argv[2].lower()
        subargs = sys.argv[3:]
        if subcmd == "validate":
            cmd_config_validate(subargs)
        elif subcmd == "import-pfsense":
            cmd_config_import_pfsense(subargs)
        elif subcmd == "show":
            cmd_config_show(subargs)
        elif subcmd == "commit":
            cmd_config_commit(subargs)
        elif subcmd == "rollback":
            cmd_config_rollback(subargs)
        else:
            print(f"Unknown config subcommand: {subcmd}")
            sys.exit(1)
    else:
        print(f"Unknown command: {category}")
        sys.exit(1)


if __name__ == "__main__":
    main()


