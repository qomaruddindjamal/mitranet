# MitraNet Rinjani 1.0.2 — Firewall Architecture (Phase 3A)

## 1. Overview & Core Philosophy

MitraNet Rinjani 1.0.2 implements a 100% Linux-native, carrier-grade data-plane firewall engine built upon:
- **Linux Netfilter & nftables (`inet` table)**
- **Connection Tracking (`conntrack`)**
- **Kernel Routing & Namespace Filtering**

MitraNet strictly deprecates and rejects legacy FreeBSD/pfSense firewall subsystems (`pf`, `ipfw`, `pfctl`, BSD kernel modules). All rules compile down directly into deterministic `nftables` syntax and execute atomically via kernel netlink transactions.

---

## 2. Architecture & Pipeline

```text
MitraNet Configuration (Candidate)
              ↓
  Semantic & Security Validation (FirewallValidator)
  - IP/CIDR family matching
  - Port range bounds (1-65535)
  - Character & shell injection immunity (DANGEROUS_CHARS)
  - Management Anti-Lockout Verification (enp0s3:22 preservation)
              ↓
  Differential Planning (FirewallPlanner)
  - Identifies ADD_RULE, DELETE_RULE, UPDATE_RULE, SET_ZONE, SET_POLICY
  - Inverse operations prepared for every differential step
              ↓
  Compilation (NftablesCompiler)
  - Base chains (input, forward, output)
  - Stateful conntrack hooks (established/related accept, invalid drop)
  - Loopback (lo) isolation
  - Rule-ID comments (`mitranet:rule:<id>`)
  - Packet and byte counters
              ↓
  Transaction Engine (FirewallTransactionEngine)
  - Lock acquisition via TransactionLock
  - Snapshot capture & SHA-256 hash
  - Syntax dry-run test (`nft -c -f`)
  - Atomic kernel apply (`nft -f`)
  - Runtime verification
  - Commit to running.json & persistence (`/etc/mitranet/firewall.nft`)
```

---

## 3. Subsystem Components

1. **`core/firewall/models.py`**:
   - Pydantic v2 schemas: `FirewallRule`, `FirewallPolicy`, `FirewallZone`, `FirewallTableConfig`, `FirewallState`, `FirewallRuleCounter`.
   - Direction: `in`, `out`, `forward`.
   - Protocol: `any`, `tcp`, `udp`, `tcp_udp`, `icmp`, `icmpv6`, `esp`, `ah`, `gre`.
   - Action: `accept`, `drop`, `reject`.
2. **`core/firewall/validator.py`**:
   - Injection prevention against shell metacharacters (`;`, `&`, `|`, `$`, `` ` ``, `\n`).
   - Anti-lockout invariant enforcement: Prevents blanket input drops on management interfaces without an explicit preceding accept rule.
3. **`core/firewall/compiler.py`**:
   - Deterministic translation into nftables script.
4. **`core/firewall/planner.py`**:
   - Calculates exact delta between running and candidate rulesets.
5. **`core/firewall/backend.py`**:
   - `subprocess.run(shell=False)` execution wrapper for `nft`.
   - Atomic apply, dry-run check, JSON counter extraction.
6. **`core/firewall/snapshot.py`**:
   - Captures running ruleset, table definition, and SHA-256 hash. Enables full rollback.
7. **`core/firewall/engine.py`**:
   - Orchestrates the atomic transaction lifecycle with single-writer lock control.
