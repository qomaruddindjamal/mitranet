# MitraNet Rinjani — Phase 1F Architecture Specification
# Native Transaction & Recovery Engine

## 1. Overview & Objective

MitraNet Rinjani 1.0.2 provides a production-grade, state-machine-driven Transaction and Recovery Engine (`mitranet.core.transaction`). The engine operates natively against the Linux network stack (`netlink`/`iproute2`), enforcing:
- Candidate vs Running configuration separation.
- Pre-apply schema, semantic, and dependency validation.
- Cryptographically authenticated network state snapshots prior to mutations.
- Strict topological dependency-ordered execution plan generation (links → VLANs → bridges → bonds → VRFs → IP addresses → routes).
- Full forward apply with runtime state verification.
- Automatic rollback of partial mutations upon failure injection or errors.
- Atomic file-based concurrency locking with PID liveness detection to eliminate stale locks.
- Crash detection and startup recovery workflows (`RECOVERY_REQUIRED`).
- Protection for active management interface (`enp0s3`) and loopback (`lo`).

---

## 2. Transaction State Machine

The transaction state machine is defined in `mitranet.core.transaction.models.TransactionState` and enforced by `NetworkTransactionEngine.transition_state`:

```text
IDLE
  ↓
PREPARING (Calculates diff plan against running)
  ↓
VALIDATING (Config schema, interface refs, security checks)
  ↓
SNAPSHOTTING (Cryptographic snapshot with SHA-256 state hash)
  ↓
APPLYING (Executes operations in dependency order)
  ↓
VERIFYING (Checks actual Linux kernel netlink objects)
  ↓
COMMITTING (Atomically writes candidate to running.json)
  ↓
COMMITTED
```

### Failure & Rollback Transitions

```text
APPLYING / VERIFYING
  ↓ (on failure)
ROLLING_BACK (Executes inverse operations in reverse order)
  ↓
ROLLED_BACK (Post-rollback verification confirms baseline restored)
```

If rollback itself encounters unexpected obstacles:
```text
ROLLING_BACK → RECOVERY_REQUIRED
```

---

## 3. Dependency-Ordered Execution Plan

`DependencyPlanner.generate_plan` computes differential operations against `running.json` following the topological order:
1. **Physical & Base Netdevs**: Administrative state (`UP`/`DOWN`), MTU adjustments.
2. **802.1Q VLANs**: Child VLAN creation on parent interfaces.
3. **Linux Bridges & Port Attachments**: Bridge devices, STP configuration, and bridge member enslaved links.
4. **Linux Bonding**: Bond masters (`802.3ad`, `active-backup`, etc.) and enslaved physical devices.
5. **VRF Routing Domains**: VRF creation, FIB routing table assignment, and member interface enslavement.
6. **IP Address Assignments**: IPv4/IPv6 CIDR assignment with conflict checking.
7. **Static Routing**: Next-hop and interface routes across routing tables.

Every forward operation generates a paired **inverse operation** (e.g. `create_vrf` ↔ `delete_vrf`, `add_address` ↔ `remove_address`) to ensure deterministic rollback.

---

## 4. Snapshot Integrity & Concurrency Control

- **Cryptographic State Hash**: The snapshot manager (`NetworkSnapshotManager`) captures all detailed link attributes, IP addresses, kernel routes, VLANs, bridges, bonds, and VRFs, computing a deterministic SHA-256 hash over the canonical JSON representation. Any tampering or disk corruption triggers `SnapshotIntegrityError`.
- **Single-Writer Lock**: Concurrency is managed via `TransactionLock` storing `{lock_id, transaction_id, pid, acquired_at, hostname}`. If a process terminates abnormally, PID liveness is validated via POSIX `os.kill(pid, 0)` checks, permitting recovery from stale locks without deadlock.

---

## 5. Startup Recovery & Crash Resilience

At MitraNet startup (or via `mitranet config status`):
- Checks for incomplete transactions recorded in `/var/lib/mitranet/transaction_state.json`.
- If a previous process crashed in `APPLYING`, `VERIFYING`, or `COMMITTING`, the system transitions to `RECOVERY_REQUIRED`.
- Prevents silent or unverified commits after process interruption.

---

## 6. Management Safety

- Dynamically detects the active management interface (carrying the default route, e.g. `enp0s3`).
- Forbids administrative shutdown (`DOWN`) or IP address removal from the management interface.
- Loopback `lo` is permanently protected against deletion or modification.
