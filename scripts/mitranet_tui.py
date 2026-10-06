#!/usr/bin/env python3
"""
MitraNet Terminal User Interface (TUI) Foundation
Project: MitraNet
Code OS: Rinjani
Foundation: MitraOS 1.0.0
Architecture: amd64

Provides the menu-driven console interface for MitraNet, utilizing the
shared Configuration Engine without duplicating any business or validation logic.
"""

import os
import sys

# Ensure api directory is in python path
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "api")))
from config_engine import ConfigurationEngine

def draw_header():
    print("=" * 60)
    print("           MitraNet 0.1.0-dev — Console Interface            ")
    print("       Foundation: MitraOS 1.0.0 | Code OS: Rinjani          ")
    print("=" * 60)

def main_menu():
    engine = ConfigurationEngine()
    while True:
        draw_header()
        print("1) Interfaces & IP Assignment")
        print("2) Routing & Gateways")
        print("3) Firewall & Packet Filter")
        print("4) System Platform Info")
        print("5) Configuration Diff & Validation")
        print("6) Rollback Previous Change")
        print("0) Exit")
        print("-" * 60)
        choice = input("Select option [0-6]: ").strip()

        if choice == "1":
            print("\n--- Configured Interfaces ---")
            ifaces = engine.get_running_config().get("interfaces", {})
            for k, v in ifaces.items():
                print(f"  {k}: {v.get('description', '')} [MTU: {v.get('mtu')}] (Zone: {v.get('zone')})")
            input("\nPress Enter to return...")

        elif choice == "2":
            print("\n--- Configured Routes ---")
            routes = engine.get_running_config().get("routes", {})
            for k, v in routes.items():
                print(f"  {k}: {v.get('destination')} via {v.get('gateway')} ({v.get('interface')})")
            input("\nPress Enter to return...")

        elif choice == "3":
            print("\n--- Firewall Rules Summary ---")
            rules = engine.get_running_config().get("firewall", {}).get("rules", [])
            print(f"  Active Rules: {len(rules)}")
            for r in rules:
                print(f"  [{r.get('id')}] {r.get('action')} on {r.get('zone')} (proto: {r.get('protocol')})")
            input("\nPress Enter to return...")

        elif choice == "4":
            print("\n--- Platform Information ---")
            print("  Project     : MitraNet")
            print("  Version     : 0.1.0-dev")
            print("  Foundation  : MitraOS 1.0.0")
            print("  Code OS     : Rinjani")
            print("  Architecture: amd64")
            input("\nPress Enter to return...")

        elif choice == "5":
            print("\n--- Configuration Status & Validation ---")
            errs = engine.validate()
            if not errs:
                print("  Status: VALID (0 errors detected)")
            else:
                print(f"  Status: INVALID ({len(errs)} errors detected)")
                for e in errs:
                    print(f"    - [{e['code']}] {e['message']}")
            input("\nPress Enter to return...")

        elif choice == "6":
            print("\n--- Configuration Rollback ---")
            ok, msg = engine.rollback()
            if ok:
                print(f"  Success: {msg}")
            else:
                print(f"  Error: {msg}")
            input("\nPress Enter to return...")

        elif choice == "0":
            print("\nExiting MitraNet Console.")
            break
        else:
            print("\nInvalid choice.")

if __name__ == "__main__":
    main_menu()
