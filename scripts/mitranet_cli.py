#!/usr/bin/env python3
"""
MitraNet Command Line Interface (CLI)
Project: MitraNet
Code OS: Rinjani
Foundation: MitraOS 1.0.0
Architecture: amd64

Provides the unified, structured command hierarchy for network and system management,
consuming the shared Configuration Engine.
"""

import os
import sys
import json
import argparse
from typing import List

# Ensure api directory is in python path
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "api")))
from config_engine import ConfigurationEngine, LockError

def format_output(data, as_json=False):
    if as_json:
        print(json.dumps(data, indent=2))
    else:
        if isinstance(data, dict):
            for k, v in data.items():
                print(f"{k}: {v}")
        elif isinstance(data, list):
            for item in data:
                print(item)
        else:
            print(data)

def main():
    parser = argparse.ArgumentParser(prog="mitranet", description="MitraNet Network Control CLI")
    parser.add_argument("--json", action="store_true", help="Output machine-readable JSON format")
    subparsers = parser.add_subparsers(dest="subcommand", help="Management domain")

    # 1. interface
    p_iface = subparsers.add_parser("interface", help="Network interface operations")
    p_iface_sub = p_iface.add_subparsers(dest="action")
    p_iface_sub.add_parser("list", help="List all configured interfaces")
    p_iface_show = p_iface_sub.add_parser("show", help="Show specific interface details")
    p_iface_show.add_argument("name", help="Interface name (e.g. eth0)")

    # 2. address
    p_addr = subparsers.add_parser("address", help="IP address operations")
    p_addr_sub = p_addr.add_subparsers(dest="action")
    p_addr_sub.add_parser("list", help="List all configured IP addresses")
    p_addr_add = p_addr_sub.add_parser("add", help="Add address to interface")
    p_addr_add.add_argument("name", help="Address key (e.g. eth0_v4)")
    p_addr_add.add_argument("interface", help="Target interface")
    p_addr_add.add_argument("ip", help="IP Address")
    p_addr_add.add_argument("prefix", type=int, help="Subnet prefix length (e.g. 24)")

    # 3. route
    p_route = subparsers.add_parser("route", help="Routing table operations")
    p_route_sub = p_route.add_subparsers(dest="action")
    p_route_sub.add_parser("list", help="List configured static routes")

    # 4. config
    p_cfg = subparsers.add_parser("config", help="Configuration transaction management")
    p_cfg_sub = p_cfg.add_subparsers(dest="action")
    p_cfg_sub.add_parser("show", help="Show active running configuration")
    p_cfg_sub.add_parser("diff", help="Show candidate differences against running")
    p_cfg_sub.add_parser("validate", help="Validate current candidate configuration")
    p_cfg_apply = p_cfg_sub.add_parser("apply", help="Apply candidate configuration changes")
    p_cfg_apply.add_argument("--dry-run", action="store_true", help="Simulate apply without persisting")
    p_cfg_sub.add_parser("rollback", help="Rollback to previous committed backup")

    # 5. system
    p_sys = subparsers.add_parser("system", help="System platform information")
    p_sys_sub = p_sys.add_subparsers(dest="action")
    p_sys_sub.add_parser("info", help="Display MitraNet release identity")

    args = parser.parse_args()

    engine = ConfigurationEngine()

    if not args.subcommand:
        parser.print_help()
        sys.exit(0)

    # Dispatch logic
    if args.subcommand == "interface":
        if args.action == "list":
            ifaces = engine.get_running_config().get("interfaces", {})
            format_output(ifaces, args.json)
        elif args.action == "show":
            ifaces = engine.get_running_config().get("interfaces", {})
            if args.name in ifaces:
                format_output(ifaces[args.name], args.json)
            else:
                print(f"Error: Interface {args.name} not found", file=sys.stderr)
                sys.exit(1)
        else:
            p_iface.print_help()

    elif args.subcommand == "address":
        if args.action == "list":
            addrs = engine.get_running_config().get("addresses", {})
            format_output(addrs, args.json)
        elif args.action == "add":
            engine.set_candidate_value("addresses", args.name, {
                "interface": args.interface,
                "ip": args.ip,
                "prefix": args.prefix,
                "family": "ipv4" if "." in args.ip else "ipv6"
            })
            ok, msg, errs = engine.apply_and_commit(actor="cli")
            if ok:
                format_output({"status": "SUCCESS", "message": msg}, args.json)
            else:
                format_output({"status": "ERROR", "message": msg, "errors": errs}, args.json)
                sys.exit(1)
        else:
            p_addr.print_help()

    elif args.subcommand == "route":
        if args.action == "list":
            routes = engine.get_running_config().get("routes", {})
            format_output(routes, args.json)
        else:
            p_route.print_help()

    elif args.subcommand == "config":
        if args.action == "show":
            format_output(engine.get_running_config(), args.json)
        elif args.action == "diff":
            diff = engine.compute_diff()
            format_output(diff if diff else ["Configuration matches running state (no changes)"], args.json)
        elif args.action == "validate":
            errs = engine.validate()
            if not errs:
                format_output({"status": "VALID", "errors": []}, args.json)
            else:
                format_output({"status": "INVALID", "errors": errs}, args.json)
                sys.exit(1)
        elif args.action == "apply":
            ok, msg, errs = engine.apply_and_commit(actor="cli", dry_run=args.dry_run)
            if ok:
                format_output({"status": "SUCCESS", "message": msg}, args.json)
            else:
                format_output({"status": "ERROR", "message": msg, "errors": errs}, args.json)
                sys.exit(1)
        elif args.action == "rollback":
            ok, msg = engine.rollback()
            if ok:
                format_output({"status": "SUCCESS", "message": msg}, args.json)
            else:
                format_output({"status": "ERROR", "message": msg}, args.json)
                sys.exit(1)
        else:
            p_cfg.print_help()

    elif args.subcommand == "system":
        if args.action == "info":
            info = {
                "project": "MitraNet",
                "version": "0.1.0-dev",
                "foundation": "MitraOS 1.0.0",
                "code_os": "Rinjani",
                "architecture": "amd64",
                "interface": "CLI + TUI + Web UI"
            }
            format_output(info, args.json)
        else:
            p_sys.print_help()

if __name__ == "__main__":
    main()
