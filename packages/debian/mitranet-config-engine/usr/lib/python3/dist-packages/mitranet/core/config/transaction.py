"""
MitraNet Candidate/Running Configuration Transaction and Snapshot Engine.
"""

import os
import json
import time
import shutil
from typing import Optional, List, Tuple
from mitranet.core.config.model import MitraNetConfig
from mitranet.core.config.loader import ConfigLoader, ConfigWriter
from mitranet.core.config.validator import ConfigValidator
from mitranet.core.config.exceptions import TransactionError


class ConfigTransactionManager:
    def __init__(self, base_dir: str = "C:/mitranet/tmp/config"):
        self.base_dir = base_dir
        self.running_file = os.path.join(base_dir, "running.json")
        self.candidate_file = os.path.join(base_dir, "candidate.json")
        self.snapshots_dir = os.path.join(base_dir, "snapshots")

        os.makedirs(self.base_dir, exist_ok=True)
        os.makedirs(self.snapshots_dir, exist_ok=True)

        self.running_config: MitraNetConfig = self._init_running()
        self.candidate_config: MitraNetConfig = self._init_candidate()

    def _init_running(self) -> MitraNetConfig:
        if os.path.exists(self.running_file):
            return ConfigLoader.load_from_file(self.running_file)
        default_cfg = MitraNetConfig()
        ConfigWriter.write_to_file(default_cfg, self.running_file)
        return default_cfg

    def _init_candidate(self) -> MitraNetConfig:
        if os.path.exists(self.candidate_file):
            try:
                return ConfigLoader.load_from_file(self.candidate_file)
            except Exception:
                pass
        return self.running_config.model_copy(deep=True)

    def get_running(self) -> MitraNetConfig:
        return self.running_config

    def get_candidate(self) -> MitraNetConfig:
        return self.candidate_config

    def set_candidate(self, cfg: MitraNetConfig) -> List[str]:
        errors = ConfigValidator.validate(cfg)
        if errors:
            return errors
        self.candidate_config = cfg
        ConfigWriter.write_to_file(cfg, self.candidate_file)
        return []

    def commit(self, note: str = "Commit") -> Tuple[bool, str]:
        errors = ConfigValidator.validate(self.candidate_config)
        if errors:
            raise TransactionError(f"Cannot commit invalid candidate configuration: {'; '.join(errors)}")

        # Increment config_version
        self.candidate_config.config_version += 1

        # Create sequential snapshot
        existing_snaps = sorted(
            [f for f in os.listdir(self.snapshots_dir) if f.endswith(".json")]
        )
        next_seq = len(existing_snaps) + 1
        snap_name = f"{next_seq:06d}.json"
        snap_path = os.path.join(self.snapshots_dir, snap_name)

        ConfigWriter.write_to_file(self.candidate_config, snap_path)
        ConfigWriter.write_to_file(self.candidate_config, self.running_file)

        self.running_config = self.candidate_config.model_copy(deep=True)
        return True, f"Committed version {self.running_config.config_version}. Snapshot: {snap_name}"

    def rollback(self, snapshot_id: Optional[str] = None) -> Tuple[bool, str]:
        existing_snaps = sorted(
            [f for f in os.listdir(self.snapshots_dir) if f.endswith(".json")],
            reverse=True
        )
        if not existing_snaps:
            raise TransactionError("No snapshots available for rollback.")

        if snapshot_id:
            target_snap = snapshot_id if snapshot_id.endswith(".json") else f"{int(snapshot_id):06d}.json"
            target_path = os.path.join(self.snapshots_dir, target_snap)
        else:
            # Need previous snapshot (if at least 2 exist, take 2nd, else take 1st)
            target_snap = existing_snaps[1] if len(existing_snaps) > 1 else existing_snaps[0]
            target_path = os.path.join(self.snapshots_dir, target_snap)

        if not os.path.exists(target_path):
            raise TransactionError(f"Snapshot not found: {target_path}")

        restored_cfg = ConfigLoader.load_from_file(target_path)
        self.candidate_config = restored_cfg.model_copy(deep=True)
        return self.commit(f"Rollback to {target_snap}")

    def list_snapshots(self) -> List[str]:
        return sorted([f for f in os.listdir(self.snapshots_dir) if f.endswith(".json")])
