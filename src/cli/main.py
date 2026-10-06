"""
MitraNet Unified Command Line Interface (CLI).
Provides operations for config validation, pfSense migration, commit, rollback,
and dynamic Linux kernel network interface discovery & state (Phase 1A).
"""

import sys
import os
import json
from mitranet.core.version import MITRANET_VERSION
from mitranet.core.config.loader import ConfigLoader, ConfigWriter
from mitranet.core.config.validator import ConfigValidator
from mitranet.core.config.transaction import ConfigTransactionManager
from mitranet.core.migration.exporter import MigrationExporter
from mitranet.core.network.discovery import InterfaceDiscoveryService
from mitranet.core.network.exceptions import InterfaceNotFoundError, BackendExecutionError


def print_banner():
    print("=" * 65)
    print(f"       MITRANET NETWORK OPERATING SYSTEM v{MITRANET_VERSION} (Debian Core)")
    print("       Carrier-Grade Firewall, Routing & Hardware NOS")
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


def main():
    if len(sys.argv) < 2:
        print_banner()
        print("\nUsage: mitranet <command> <subcommand> [options]")
        print("\nCommands:")
        print("  interface list [--json]         List discovered network interfaces")
        print("  interface show <name> [--json]  Show operational details of an interface")
        print("  config validate <config.json>   Validate configuration file")
        print("  config import-pfsense <xml>     Migrate pfSense config.xml to MitraNet JSON")
        print("  config show                     Display current running configuration")
        print("  config commit [note]            Commit candidate configuration to running")
        print("  config rollback [snapshot_id]   Rollback to previous snapshot")
        sys.exit(0)

    category = sys.argv[1].lower()
    if category == "interface":
        if len(sys.argv) < 3:
            print("Specify an interface subcommand: 'list' or 'show'")
            sys.exit(1)
        subcmd = sys.argv[2].lower()
        subargs = sys.argv[3:]
        if subcmd == "list":
            cmd_interface_list(subargs)
        elif subcmd == "show":
            cmd_interface_show(subargs)
        else:
            print(f"Unknown interface subcommand: {subcmd}")
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
