# MitraNet VRF (Virtual Routing and Forwarding) Core (Phase 1E)

## Overview
MitraNet provides carrier-grade native Linux VRF (Virtual Routing and Forwarding) implementation, multi-tenancy, and routing domain isolation on top of the real Linux kernel networking stack.

## Linux Implementation
- Real Linux native VRF device creation via `iproute2` and Netlink:
  ```bash
  ip link add <vrf-name> type vrf table <table-id>
  ip link set dev <interface> master <vrf-name>
  ip link set dev <interface> nomaster
  ip link delete <vrf-name>
  ```
- Subprocess execution strictly uses `shell=False` with parameterized argument lists.
- Kernel discovery queries `ip -d -j link show` for `info_kind == "vrf"` and `info_data.table`.
- Member interfaces are discovered via `master == "<vrf_name>"` and `info_slave_kind == "vrf"`.

## Table ID Validation & Security
- Linux routing table ID range: `1` to `2147483647`.
- Linux reserved tables are explicitly rejected:
  - `0`: unspec / reserved
  - `253`: default
  - `254`: main
  - `255`: local
- Table IDs and VRF names are strictly sanitized against shell injection, command delimiters (`;`, `&&`, `|`), subshells (`$()`, `` ` ``), and path traversal.

## CLI Commands
```bash
# List all active VRF instances
mitranet vrf list
mitranet vrf list --json

# Show details of a specific VRF instance
mitranet vrf show <name>
mitranet vrf show <name> --json

# Create a VRF instance with a designated FIB table ID
mitranet vrf create <name> <table>

# Delete a VRF instance
mitranet vrf delete <name>

# Attach an interface to a VRF
mitranet vrf interface add <vrf> <interface>

# Detach an interface from a VRF
mitranet vrf interface remove <vrf> <interface>
```

## Routing Domain Isolation
- Routes installed in a VRF table (e.g., table 100) are fully isolated from other VRF tables (e.g., table 200) and the default/main routing table (table 254).
- Kernel state is inspected via `ip -j route show table <table>` and `ip -j -6 route show table <table>`.
- Management interfaces (e.g., management IP on 10.0.2.x) and loopback (`lo`) are protected from accidental enslavement to a VRF.

## Transaction Model
Every state modification follows the transaction pipeline:
`VALIDATE -> CAPTURE -> APPLY -> DISCOVER -> VERIFY -> COMMIT RESULT` (with automatic rollback upon failure).

## Environment-Gated Testing Harness
The test harness provides strict preflight capability probing (`VRFEnvironmentProbe`):
1. **OS & Kernel Validation**: Verifies Linux kernel version and operational VRF driver support.
2. **iproute2 Support**: Probes `ip link help vrf` for table ID syntax support.
3. **Privilege Gating**: Enforces `CAP_NET_ADMIN` / root privileges prior to attempting destructive netlink calls.
4. **Management Protection**: Automatically identifies the active management interface (carrying the default route or management address) and protects it from VRF enslavement.
5. **Safe Interface Discovery**: Dynamically discovers candidate interfaces for testing (e.g. `enp0s8`) without hardcoding interface names.
6. **Routing Table Conflict Detection**: Ensures test routing tables are not already in use by VRFs, system services, or Linux reserved table IDs (0, 253, 254, 255).
7. **Baseline Restoration**: Captures full network link and routing table snapshots before test execution and asserts zero leaks after cleanup.
