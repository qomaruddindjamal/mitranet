"""
MitraNet Firewall Snapshot & Recovery Manager.
Phase 3A: Captures pre-transaction nftables ruleset text and SHA-256 hash.
Enables reliable verification and exact atomic rollback.
"""

import hashlib
import json
import logging
import os
from datetime import datetime, timezone
from typing import Optional
from pydantic import BaseModel, Field
from mitranet.core.firewall.backend import NftablesBackend
from mitranet.core.firewall.errors import FirewallSnapshotError

logger = logging.getLogger("mitranet.firewall.snapshot")


class FirewallSnapshot(BaseModel):
    """Immutable snapshot record of the firewall state before an atomic operation."""
    snapshot_id: str
    timestamp: str = Field(default_factory=lambda: datetime.now(timezone.utc).isoformat())
    ruleset_hash: str
    ruleset_content: str
    table_content: str
    config_dump: Optional[dict] = None


class FirewallSnapshotManager:
    """Manages creation, verification, and persistence of firewall ruleset snapshots."""

    def __init__(self, snapshot_dir: str = "/var/lib/mitranet/snapshots/firewall", backend: Optional[NftablesBackend] = None):
        self.snapshot_dir = snapshot_dir
        self.backend = backend or NftablesBackend()
        os.makedirs(self.snapshot_dir, exist_ok=True)

    def capture(self, snapshot_id: str, config_dump: Optional[dict] = None) -> FirewallSnapshot:
        """
        Captures the current running nftables ruleset and table state into a verifiable snapshot.
        """
        try:
            full_ruleset = self.backend.list_ruleset()
            table_str = self.backend.list_table(family="inet", name="mitranet")
            
            sha = hashlib.sha256()
            sha.update(full_ruleset.encode("utf-8"))
            sha.update(table_str.encode("utf-8"))
            ruleset_hash = sha.hexdigest()

            snap = FirewallSnapshot(
                snapshot_id=snapshot_id,
                ruleset_hash=ruleset_hash,
                ruleset_content=full_ruleset,
                table_content=table_str,
                config_dump=config_dump,
            )

            filepath = os.path.join(self.snapshot_dir, f"{snapshot_id}.json")
            with open(filepath, "w", encoding="utf-8") as f:
                f.write(snap.model_dump_json(indent=2))

            return snap
        except Exception as e:
            raise FirewallSnapshotError(f"Failed to capture firewall snapshot '{snapshot_id}': {e}")

    def load(self, snapshot_id: str) -> FirewallSnapshot:
        """Loads a stored snapshot from disk and verifies integrity."""
        filepath = os.path.join(self.snapshot_dir, f"{snapshot_id}.json")
        if not os.path.exists(filepath):
            raise FirewallSnapshotError(f"Snapshot '{snapshot_id}' not found at {filepath}")

        try:
            with open(filepath, "r", encoding="utf-8") as f:
                data = json.load(f)
            snap = FirewallSnapshot(**data)

            # Integrity check
            sha = hashlib.sha256()
            sha.update(snap.ruleset_content.encode("utf-8"))
            sha.update(snap.table_content.encode("utf-8"))
            if sha.hexdigest() != snap.ruleset_hash:
                raise FirewallSnapshotError(f"Snapshot '{snapshot_id}' checksum mismatch (corrupted snapshot)")

            return snap
        except Exception as e:
            raise FirewallSnapshotError(f"Failed to load or verify snapshot '{snapshot_id}': {e}")

    def rollback(self, snapshot_id: str) -> None:
        """
        Restores the firewall ruleset to the state captured in the snapshot.
        If table_content was empty, deletes the table to restore pre-state.
        """
        snap = self.load(snapshot_id)
        if snap.table_content.strip():
            # Reapply snapshot table
            self.backend.apply_ruleset(snap.table_content)
        else:
            # Table did not exist originally, remove it
            self.backend.delete_table(family="inet", name="mitranet")
