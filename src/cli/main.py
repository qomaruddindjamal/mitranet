"""
MitraNet Interactive and Scriptable Hierarchical Command Line Interface.
Provides network operators with an expressive shell for state inspection and configuration.
"""

import sys
import json
from mitranet.src.config.engine import ConfigEngine
from mitranet.src.network.firewall import NftablesCompiler


def print_banner():
    print("=" * 60)
    print("       MITRANET NETWORK OPERATING SYSTEM (Debian Core)")
    print("       Carrier-Grade Firewall, Routing & Hardware NOS")
    print("=" * 60)


def show_interfaces(engine: ConfigEngine):
    cfg = engine.get_running_config()
    print("\n--- Network Interfaces ---")
    if not cfg.interfaces:
        print("No interfaces configured.")
        return
    for iface in cfg.interfaces:
        status = "UP" if iface.enabled else "DOWN"
        addrs = ", ".join([f"{a.ip}/{a.prefix_length}" for a in iface.addresses]) or "unassigned"
        dhcp = " (DHCP Client)" if iface.dhcp_client else ""
        print(f"[{iface.name}] {status} Role:{iface.role.upper()} Type:{iface.type} Addr:{addrs}{dhcp}")


def show_firewall(engine: ConfigEngine):
    cfg = engine.get_running_config()
    print("\n--- Firewall Rules ---")
    if not cfg.firewall_rules:
        print("No firewall rules defined.")
        return
    for r in cfg.firewall_rules:
        status = "ENABLED" if r.enabled else "DISABLED"
        print(f"Rule [{r.id}] {r.action.upper()} if:{r.interface} proto:{r.protocol} dst:{r.destination} ({status})")


def show_nftables(engine: ConfigEngine):
    cfg = engine.get_running_config()
    print("\n--- Compiled nftables Output ---")
    print(NftablesCompiler.compile_ruleset(cfg))


def main():
    print_banner()
    engine = ConfigEngine()

    if len(sys.argv) > 1:
        cmd = sys.argv[1].lower()
        if cmd == "interfaces":
            show_interfaces(engine)
        elif cmd == "firewall":
            show_firewall(engine)
        elif cmd == "nftables":
            show_nftables(engine)
        elif cmd == "commit":
            success, msg = engine.commit("CLI Commit")
            print(f"Commit status: {success} - {msg}")
        elif cmd == "rollback":
            success, msg = engine.rollback()
            print(f"Rollback status: {success} - {msg}")
        else:
            print(f"Unknown command: {cmd}")
            print("Available commands: interfaces, firewall, nftables, commit, rollback")
    else:
        print("\nMitraNet CLI Shell Ready.")
        show_interfaces(engine)
        show_firewall(engine)


if __name__ == "__main__":
    main()
