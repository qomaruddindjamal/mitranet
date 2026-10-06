# MitraNet 802.1Q VLAN Architecture & Implementation (Phase 1D)

## Overview
MitraNet provides carrier-grade native Linux 802.1Q VLAN interface creation, discovery, and lifecycle management.

## Linux Implementation
- Backend utilizes `ip link add link <parent> name <name> type vlan id <vlan_id>` via `iproute2`.
- Query and state inspection utilizes `ip -d -j link show`.
- VLAN IDs are constrained to standard IEEE 802.1Q range: 1–4094.
- Parent interface cannot be `lo` (loopback) and must exist in kernel.

## CLI Commands
```bash
# List all active VLANs
mitranet vlan list
mitranet vlan list --json

# Show details of a specific VLAN
mitranet vlan show <name>

# Create 802.1Q VLAN
mitranet vlan create <name> <parent> <vlan_id>

# Set administrative state
mitranet vlan up <name>
mitranet vlan down <name>

# Delete VLAN
mitranet vlan delete <name>
```

## Security & Verification
- All device names and parent interfaces are strictly checked against shell metacharacters.
- Every create operation executes `VALIDATE -> CAPTURE -> APPLY -> DISCOVER -> VERIFY`.
