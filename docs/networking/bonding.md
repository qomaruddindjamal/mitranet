# MitraNet Linux Bonding Architecture & Implementation (Phase 1D)

## Overview
MitraNet implements Linux channel bonding for link aggregation and high availability.

## Supported Modes
- `balance-rr` (Mode 0)
- `active-backup` (Mode 1)
- `balance-xor` (Mode 2)
- `broadcast` (Mode 3)
- `802.3ad` / `lacp` (Mode 4)
- `balance-tlb` (Mode 5)
- `balance-alb` (Mode 6)

## Linux Implementation
- Creation: `ip link add name <name> type bond mode <mode> miimon <ms>`
- Slave attachment: `ip link set dev <slave> master <bond>`
- Slave detachment: `ip link set dev <slave> nomaster`
- Runtime info: Parsed from `ip -d -j link show` and `/proc/net/bonding/<name>`.

## CLI Commands
```bash
# List bonds
mitranet bond list
mitranet bond list --json

# Show bond details
mitranet bond show <name>

# Create bond
mitranet bond create <name> <mode>

# Attach/detach slave interfaces
mitranet bond slave add <bond> <interface>
mitranet bond slave remove <bond> <interface>

# Delete bond
mitranet bond delete <name>
```
