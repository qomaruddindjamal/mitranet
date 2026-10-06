# MITRANET — CLI COMMAND REFERENCE

**Official Identity:** MitraNet 0.1.0-dev (Code OS: Rinjani)

## Global Flags
- `--json`: Output results in structured JSON format.

## Command Groups
### 1. `mitranet interface`
- `mitranet interface list`: List all configured network interfaces.
- `mitranet interface show <name>`: Show configuration details for a specific interface.

### 2. `mitranet address`
- `mitranet address list`: Display IP addresses assigned across interfaces.
- `mitranet address add <key> <interface> <ip> <prefix>`: Add new IPv4 or IPv6 address.

### 3. `mitranet route`
- `mitranet route list`: Show static and default routing configuration.

### 4. `mitranet config`
- `mitranet config show`: Display active running configuration.
- `mitranet config diff`: Inspect candidate differences.
- `mitranet config validate`: Perform semantic validation.
- `mitranet config apply [--dry-run]`: Persist changes or simulate application.
- `mitranet config rollback`: Rollback configuration to the last known-good state.

### 5. `mitranet system`
- `mitranet system info`: Display official release and platform identity.
