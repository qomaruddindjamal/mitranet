# MitraNet Testing & Verification Architecture

## 1. Testing Framework Objectives
The MitraNet verification system ensures zero regressions, strict schema validation, configuration immutability guarantees, and functional equivalence across all network modules.

## 2. Test Tiers

```text
  ┌────────────────────────────────────────────────────────┐
  │                 Tier 4: Hardware & Lab Tests           │
  │     (Physical NICs, SFP transceivers, OCP Whitebox)    │
  └───────────────────────────▲────────────────────────────┘
                              │
  ┌───────────────────────────┴────────────────────────────┐
  │         Tier 3: Network Namespace & Simulation         │
  │      (Linux netns veth pairs, routing, nftables)       │
  └───────────────────────────▲────────────────────────────┘
                              │
  ┌───────────────────────────┴────────────────────────────┐
  │          Tier 2: Migration & Ingestion Tests           │
  │   (pfSense config.xml parsing, RouterOS script parser) │
  └───────────────────────────▲────────────────────────────┘
                              │
  ┌───────────────────────────┴────────────────────────────┐
  │            Tier 1: Unit & Schema Validation            │
  │     (Pydantic model integrity, atomic transactions)    │
  └────────────────────────────────────────────────────────┘
```

### 2.1 Tier 1: Unit & Schema Testing
- Validates that configuration inputs strictly follow Pydantic data models.
- Tests IP address format validations, subnet boundary checks, port ranges, and conflict detection (e.g. duplicate IPs across interfaces).

### 2.2 Tier 2: Migration Ingestion Testing
- Tests parsing of real pfSense `config.xml` files (including the default config extracted from the pfSense ISO).
- Verifies that interface mappings, firewall rules, NAT directives, and DHCP reservations map cleanly without data corruption or loss.

### 2.3 Tier 3: Linux Network Namespace Integration Testing
- Uses isolated Linux network namespaces (`ip netns`) to spin up virtual routers connected via `veth` pairs.
- Validates that compiled `nftables` rulesets successfully load into the kernel, drop blocked traffic, forward permitted traffic, and execute NAT masquerade without syntax errors.

### 2.4 Tier 4: Hardware & Performance Testing
- Automated iperf3 / TRex throughput benchmarks.
- Verification of transceiver DOM optical readings and fan PWM controls.
