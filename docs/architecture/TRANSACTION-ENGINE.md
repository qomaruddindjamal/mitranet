# MITRANET — TRANSACTION ENGINE SPECIFICATION

**Official Identity:** MitraNet 0.1.0-dev (Code OS: Rinjani)

## 1. Transaction Pipeline
Every administrative operation produces a structured transaction record:
- `transaction_id`: Unique identifier (e.g. `TX-B8F120A4`)
- `timestamp`: Epoch seconds
- `actor`: Requesting user or subsystem context (e.g. `cli`, `tui`, `api`)
- `diff`: Exact line-by-line delta
- `backup_file`: Snapshot of pre-change state in `api/backups/`
- `status`: `COMMITTED` or `ROLLED_BACK`

## 2. Integrity Verification
A transaction is only marked `COMMITTED` after an atomic file write and successful system health probe.
