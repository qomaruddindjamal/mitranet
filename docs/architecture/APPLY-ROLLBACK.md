# MITRANET — APPLY & ROLLBACK ARCHITECTURE

**Official Identity:** MitraNet 0.1.0-dev (Code OS: Rinjani)

## 1. Apply Workflow
1. **Validate Candidate**: Full schema, semantic, and dependency check.
2. **Compute Diff**: Highlight all structural additions, modifications, and deletions.
3. **Pre-Apply Snapshot**: Store serialized running state in `api/backups/config_<timestamp>_<tx_id>.json`.
4. **Atomic Write**: Write to temporary file and atomically swap into `api/config.json`.
5. **Run Health Checks**: Probe basic connectivity and schema stability.
6. **Finalize or Rollback**: If probe fails, automatically restore pre-apply snapshot.

## 2. Manual Rollback
Administrators can restore previous known-good snapshots at any time via CLI `mitranet config rollback` or TUI Option 6.
