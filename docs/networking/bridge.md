# MitraNet Linux Bridge Architecture & Implementation (Phase 1D)

## Overview
MitraNet implements native Linux Bridge switching and member port attachment through `iproute2`.

## Linux Implementation
- Bridge creation: `ip link add name <name> type bridge`
- Port attachment: `ip link set dev <port> master <bridge>`
- Port detachment: `ip link set dev <port> nomaster`
- Discovery: `ip -d -j link show` parsing `info_kind: bridge` and `info_slave_kind: bridge`.

## Protection & Safety
- Loopback `lo` is forbidden as a bridge or bridge port.
- Active management interfaces (carrying management IP/subnet) are strictly protected and cannot be bridged.
- Rejection of malicious command injections.

## CLI Commands
```bash
# List bridges
mitranet bridge list
mitranet bridge list --json

# Show bridge details and member ports
mitranet bridge show <name>

# Create bridge
mitranet bridge create <name>

# Manage member ports
mitranet bridge port add <bridge> <interface>
mitranet bridge port remove <bridge> <interface>

# Delete bridge
mitranet bridge delete <name>
```
