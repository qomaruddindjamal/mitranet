"""
MitraNet Firewall Transaction Engine.
Phase 3A: Orchestrates the atomic lifecycle of firewall changes using the
Phase 1F transaction state machine principles:
VALIDATE -> SNAPSHOT -> PLAN -> COMPILE -> TEST/CHECK -> ATOMIC APPLY -> DISCOVER -> VERIFY -> COMMIT (or ROLLBACK).
"""

import hashlib
import json
import logging
import os
from typing import Dict, List, Optional, Set
from datetime import datetime, timezone

from mitranet.core.firewall.models import (
    FirewallTableConfig,
    FirewallRule,
    FirewallZone,
    FirewallPolicy,
    FirewallState,
    FirewallRuleCounter,
)
from mitranet.core.firewall.errors import (
    FirewallError,
    FirewallValidationError,
    FirewallSecurityViolationError,
    FirewallCompilationError,
    FirewallBackendError,
    FirewallRollbackError,
)
from mitranet.core.firewall.validator import FirewallValidator
from mitranet.core.firewall.compiler import NftablesCompiler
from mitranet.core.firewall.planner import FirewallPlanner, FirewallOperation
from mitranet.core.firewall.backend import NftablesBackend
from mitranet.core.firewall.snapshot import FirewallSnapshotManager, FirewallSnapshot
from mitranet.core.transaction.models import TransactionState, TransactionRecord
from mitranet.core.transaction.lock import TransactionLock

logger = logging.getLogger("mitranet.firewall.engine")


class FirewallTransactionEngine:
    """Production-grade Atomic Transaction Engine for MitraNet Firewall."""

    def __init__(
        self,
        base_dir: str = "/var/lib/mitranet",
        backend: Optional[NftablesBackend] = None,
        lock_timeout: float = 10.0,
    ):
        self.base_dir = base_dir
        self.fw_dir = os.path.join(base_dir, "firewall")
        self.candidate_file = os.path.join(self.fw_dir, "candidate.json")
        self.running_file = os.path.join(self.fw_dir, "running.json")
        self.state_file = os.path.join(self.fw_dir, "firewall_state.json")
        self.snapshot_dir = os.path.join(base_dir, "snapshots", "firewall")
        self.lock_file = os.path.join(self.fw_dir, "firewall.lock")

        os.makedirs(self.fw_dir, exist_ok=True)
        os.makedirs(self.snapshot_dir, exist_ok=True)

        self.backend = backend or NftablesBackend()
        self.snapshot_mgr = FirewallSnapshotManager(snapshot_dir=self.snapshot_dir, backend=self.backend)
        self.lock = TransactionLock(lock_file=self.lock_file, timeout_seconds=int(lock_timeout))

        self.running_config: FirewallTableConfig = self._init_running()
        self.candidate_config: FirewallTableConfig = self._init_candidate()

    def _init_running(self) -> FirewallTableConfig:
        if os.path.exists(self.running_file):
            try:
                with open(self.running_file, "r", encoding="utf-8") as f:
                    data = json.load(f)
                return FirewallTableConfig(**data)
            except Exception as e:
                logger.error("Failed to load running firewall config: %s", e)
        return FirewallTableConfig()

    def _init_candidate(self) -> FirewallTableConfig:
        if os.path.exists(self.candidate_file):
            try:
                with open(self.candidate_file, "r", encoding="utf-8") as f:
                    data = json.load(f)
                return FirewallTableConfig(**data)
            except Exception as e:
                logger.error("Failed to load candidate firewall config: %s", e)
        # Default candidate mirrors running initially
        return self.running_config.model_copy(deep=True)

    def save_candidate(self, config: FirewallTableConfig) -> None:
        """Saves new candidate configuration to disk without mutating runtime state."""
        self.candidate_config = config
        with open(self.candidate_file, "w", encoding="utf-8") as f:
            f.write(config.model_dump_json(indent=2))

    def save_running(self, config: FirewallTableConfig) -> None:
        """Saves active running configuration to disk after successful commit."""
        self.running_config = config
        with open(self.running_file, "w", encoding="utf-8") as f:
            f.write(config.model_dump_json(indent=2))

    def plan(self) -> List[FirewallOperation]:
        """Calculates differential operations from running to candidate."""
        return FirewallPlanner.generate_plan(
            candidate=self.candidate_config,
            running=self.running_config,
        )

    def validate(self, known_interfaces: Optional[Set[str]] = None) -> List[str]:
        """Validates candidate firewall configuration."""
        return FirewallValidator.validate_table_config(
            config=self.candidate_config,
            known_interfaces=known_interfaces,
        )

    def apply_and_commit(
        self,
        tx_id: Optional[str] = None,
        known_interfaces: Optional[Set[str]] = None,
        persist: bool = True,
    ) -> TransactionRecord:
        """
        Executes atomic firewall configuration transaction:
        1. Acquire lock
        2. VALIDATE candidate & anti-lockout rules
        3. SNAPSHOT running ruleset
        4. COMPILE candidate to nftables script
        5. CHECK syntax dry-run (nft -c)
        6. APPLY atomically (nft -f)
        7. VERIFY table and chains in kernel
        8. COMMIT (update running.json)
        If any step fails, automatic ROLLBACK is performed.
        """
        if not tx_id:
            tx_id = f"tx_fw_{int(datetime.now(timezone.utc).timestamp())}_{os.getpid()}"

        record = TransactionRecord(
            transaction_id=tx_id,
            state=TransactionState.PREPARING,
            started_at=datetime.now(timezone.utc).isoformat(),
        )

        self.lock.acquire(tx_id)
        try:
            # 1. VALIDATING
            record.state = TransactionState.VALIDATING
            try:
                self.validate(known_interfaces=known_interfaces)
            except Exception as e:
                record.state = TransactionState.FAILED
                record.error = f"Validation failed: {e}"
                raise

            # 2. SNAPSHOTTING
            record.state = TransactionState.SNAPSHOTTING
            snap_id = f"snap_{tx_id}"
            try:
                snap = self.snapshot_mgr.capture(
                    snapshot_id=snap_id,
                    config_dump=self.running_config.model_dump(),
                )
                record.snapshot_id = snap_id
            except Exception as e:
                record.state = TransactionState.FAILED
                record.error = f"Snapshot capture failed: {e}"
                raise

            # 3. COMPILE
            try:
                nft_script = NftablesCompiler.compile_table(self.candidate_config)
            except Exception as e:
                record.state = TransactionState.FAILED
                record.error = f"Compilation failed: {e}"
                raise

            # 4. TEST / CHECK (Dry-Run)
            try:
                self.backend.check_syntax(nft_script)
            except Exception as e:
                record.state = TransactionState.FAILED
                record.error = f"Syntax pre-check failed: {e}"
                raise

            # 5. ATOMIC APPLY
            record.state = TransactionState.APPLYING
            try:
                self.backend.apply_ruleset(nft_script)
            except Exception as apply_err:
                record.state = TransactionState.ROLLING_BACK
                logger.error("Apply failed; initiating atomic rollback: %s", apply_err)
                try:
                    self.snapshot_mgr.rollback(snap_id)
                    record.state = TransactionState.ROLLED_BACK
                    record.error = f"Apply error: {apply_err}. Rollback successfully restored baseline."
                except Exception as rb_err:
                    record.state = TransactionState.RECOVERY_REQUIRED
                    record.recovery_required = True
                    record.error = f"Apply error: {apply_err}. CRITICAL: Rollback also failed: {rb_err}"
                    raise FirewallRollbackError(record.error)
                raise FirewallBackendError(record.error)

            # 6. VERIFY
            record.state = TransactionState.VERIFYING
            try:
                table_out = self.backend.list_table(
                    family=self.candidate_config.family.value,
                    name=self.candidate_config.name,
                )
                if not table_out or "chain input" not in table_out:
                    raise FirewallBackendError("Verification failed: Table or input chain missing in kernel")
            except Exception as verify_err:
                record.state = TransactionState.ROLLING_BACK
                logger.error("Verification failed; rolling back: %s", verify_err)
                try:
                    self.snapshot_mgr.rollback(snap_id)
                    record.state = TransactionState.ROLLED_BACK
                    record.error = f"Verification error: {verify_err}. Rollback restored baseline."
                except Exception as rb_err:
                    record.state = TransactionState.RECOVERY_REQUIRED
                    record.recovery_required = True
                    record.error = f"Verification error: {verify_err}. CRITICAL: Rollback failed: {rb_err}"
                    raise FirewallRollbackError(record.error)
                raise FirewallBackendError(record.error)

            # 7. COMMIT
            record.state = TransactionState.COMMITTING
            self.save_running(self.candidate_config.model_copy(deep=True))
            if persist:
                self.persist_ruleset()
            record.state = TransactionState.COMMITTED
            record.completed_at = datetime.now(timezone.utc).isoformat()
        finally:
            self.lock.release(tx_id)

        return record

    def rollback_to_snapshot(self, snapshot_id: str) -> None:
        """Explicit rollback to a specific prior snapshot."""
        tx_id = f"tx_rb_{int(datetime.now(timezone.utc).timestamp())}_{os.getpid()}"
        self.lock.acquire(tx_id)
        try:
            snap = self.snapshot_mgr.load(snapshot_id)
            self.snapshot_mgr.rollback(snapshot_id)
            if snap.config_dump:
                cfg = FirewallTableConfig(**snap.config_dump)
                self.save_running(cfg)
                self.save_candidate(cfg)
        finally:
            self.lock.release(tx_id)


    def persist_ruleset(self) -> str:
        """
        Saves current committed ruleset to /etc/nftables.conf or /etc/mitranet/firewall.nft
        for persistence across reboots.
        """
        persist_dir = "/etc/mitranet"
        os.makedirs(persist_dir, exist_ok=True)
        persist_file = os.path.join(persist_dir, "firewall.nft")

        nft_script = NftablesCompiler.compile_table(self.running_config)
        with open(persist_file, "w", encoding="utf-8") as f:
            f.write(nft_script)

        # Also write to standard /etc/nftables.conf if on Linux Debian
        if os.path.exists("/etc/nftables.conf") or os.path.exists("/etc/nftables"):
            try:
                with open("/etc/nftables.conf", "w", encoding="utf-8") as f:
                    f.write("#!/usr/sbin/nft -f\nflush ruleset\n\n" + nft_script)
            except Exception as e:
                logger.warning("Could not write /etc/nftables.conf: %s", e)

        return persist_file

    def get_status(self) -> FirewallState:
        """Retrieves runtime status and counters of the firewall engine."""
        table_fam = self.running_config.family.value
        table_name = self.running_config.name

        table_str = self.backend.list_table(family=table_fam, name=table_name)
        active = bool(table_str.strip())
        
        sha = hashlib.sha256()
        sha.update(table_str.encode("utf-8"))
        ruleset_hash = sha.hexdigest() if active else None

        counters = self.backend.get_counters(family=table_fam, name=table_name) if active else {}

        chains = []
        if active:
            for line in table_str.splitlines():
                line_s = line.strip()
                if line_s.startswith("chain "):
                    parts = line_s.split()
                    if len(parts) >= 2:
                        chains.append(parts[1])

        return FirewallState(
            active=active,
            table_name=table_name,
            table_family=table_fam,
            rule_count=len(self.running_config.rules),
            chains=chains,
            ruleset_hash=ruleset_hash,
            last_applied=datetime.now(timezone.utc).isoformat() if active else None,
            counters=counters,
        )
