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


