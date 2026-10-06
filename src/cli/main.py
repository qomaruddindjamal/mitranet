"""
MitraNet Unified Command Line Interface (CLI).
Provides operations for config validation, pfSense migration, export, commit, rollback, and inspection.
"""

import sys
import os
import json
from mitranet.core.config.loader import ConfigLoader, ConfigWriter
from mitranet.core.config.validator import ConfigValidator
from mitranet.core.config.transaction import ConfigTransactionManager
from mitranet.core.migration.exporter import MigrationExporter


def print_banner():
    print("=" * 65)
    print("       MITRANET NETWORK OPERATING SYSTEM (Debian Core)")
    print("       Carrier-Grade Firewall, Routing & Hardware NOS")
    print("=" * 65)


def cmd_validate(args):
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


def cmd_import_pfsense(args):
    if not args:
        print("Usage: mitranet config import-pfsense <pfsense-config.xml> [output-config.json]")
        sys.exit(1)
    xml_path = args[0]
    out_json = args[1] if len(args) > 1 else "C:/mitranet/examples/pfsense-migrated.json"
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


def cmd_show(args):
    mgr = ConfigTransactionManager()
    cfg = mgr.get_running()
    print(cfg.model_dump_json(indent=2))


def cmd_commit(args):
    note = " ".join(args) if args else "Commit via CLI"
    mgr = ConfigTransactionManager()
    try:
        success, msg = mgr.commit(note)
        print(f"[{'PASS' if success else 'FAIL'}] {msg}")
    except Exception as e:
        print(f"[ERROR] {e}")
        sys.exit(1)


def cmd_rollback(args):
    snap_id = args[0] if args else None
    mgr = ConfigTransactionManager()
    try:
        success, msg = mgr.rollback(snap_id)
        print(f"[{'PASS' if success else 'FAIL'}] {msg}")
    except Exception as e:
        print(f"[ERROR] {e}")
        sys.exit(1)


def main():
    if len(sys.argv) < 2:
        print_banner()
        print("\nUsage: mitranet config <subcommand> [options]")
        print("\nSubcommands:")
        print("  validate <config.json>          Validate configuration file")
        print("  import-pfsense <config.xml>     Migrate pfSense config.xml to MitraNet JSON")
        print("  show                            Display current running configuration")
        print("  commit [note]                   Commit candidate configuration to running")
        print("  rollback [snapshot_id]          Rollback to previous snapshot")
        sys.exit(0)

    category = sys.argv[1].lower()
    if category == "config":
        if len(sys.argv) < 3:
            print("Specify a config subcommand (validate, import-pfsense, show, commit, rollback)")
            sys.exit(1)
        subcmd = sys.argv[2].lower()
        subargs = sys.argv[3:]
        if subcmd == "validate":
            cmd_validate(subargs)
        elif subcmd == "import-pfsense":
            cmd_import_pfsense(subargs)
        elif subcmd == "show":
            cmd_show(subargs)
        elif subcmd == "commit":
            cmd_commit(subargs)
        elif subcmd == "rollback":
            cmd_rollback(subargs)
        else:
            print(f"Unknown config subcommand: {subcmd}")
            sys.exit(1)
    else:
        print(f"Unknown command: {category}")
        sys.exit(1)


if __name__ == "__main__":
    main()
