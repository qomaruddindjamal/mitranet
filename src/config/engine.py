"""
MitraNet Configuration Engine.
Manages candidate configuration, schema validation, atomic commits, and snapshot rollbacks.
"""

import json
import os
import shutil
import time
from typing import Tuple, List, Optional
from mitranet.src.config.models import MitraNetMasterConfig


class ConfigEngine:
    def __init__(self, config_dir: str = "C:/mitranet/tmp/config"):
        self.config_dir = config_dir
        self.config_file = os.path.join(config_dir, "mitranet.json")
        self.backup_dir = os.path.join(config_dir, "backups")
        os.makedirs(self.config_dir, exist_ok=True)
        os.makedirs(self.backup_dir, exist_ok=True)
        self.running_config: MitraNetMasterConfig = self._load_or_initialize()
        self.candidate_config: MitraNetMasterConfig = self.running_config.model_copy(deep=True)

    def _load_or_initialize(self) -> MitraNetMasterConfig:
        if os.path.exists(self.config_file):
            try:
                with open(self.config_file, "r", encoding="utf-8") as f:
                    data = json.load(f)
                    return MitraNetMasterConfig.model_validate(data)
            except Exception:
                pass
        # Default fallback initialization
        default_cfg = MitraNetMasterConfig()
        return default_cfg

    def get_running_config(self) -> MitraNetMasterConfig:
        return self.running_config

    def get_candidate_config(self) -> MitraNetMasterConfig:
        return self.candidate_config

    def update_candidate(self, new_config: MitraNetMasterConfig) -> Tuple[bool, List[str]]:
        """Validate candidate configuration and set it if valid."""
        errors = self.validate_config(new_config)
        if errors:
            return False, errors
        self.candidate_config = new_config
        return True, []

    def validate_config(self, cfg: MitraNetMasterConfig) -> List[str]:
        """Perform semantic and cross-reference integrity checks."""
        errors = []
        interface_names = {iface.name for iface in cfg.interfaces}

        # Check VLAN parents
        for iface in cfg.interfaces:
            if iface.type == "vlan":
                if not iface.parent:
                    errors.append(f"VLAN {iface.name} is missing a parent interface.")
                elif iface.parent not in interface_names:
                    errors.append(f"VLAN {iface.name} parent '{iface.parent}' does not exist.")

        # Check Bridge members
        for iface in cfg.interfaces:
            if iface.type == "bridge" and iface.bridge_members:
                for member in iface.bridge_members:
                    if member not in interface_names:
                        errors.append(f"Bridge {iface.name} references non-existent member '{member}'.")

        # Check Firewall rules interface bindings
        for rule in cfg.firewall_rules:
            if rule.interface != "any" and rule.interface not in interface_names:
                errors.append(f"Firewall rule {rule.id} references undefined interface '{rule.interface}'.")

        # Check Port Forwards
        for pf in cfg.nat_port_forwards:
            if pf.interface not in interface_names:
                errors.append(f"Port forward {pf.id} references undefined interface '{pf.interface}'.")

        # Check DHCP servers
        for dhcp in cfg.dhcp_servers:
            if dhcp.interface not in interface_names:
                errors.append(f"DHCP server bound to undefined interface '{dhcp.interface}'.")

        return errors

    def commit(self, description: str = "Commit via MitraNet Engine") -> Tuple[bool, str]:
        """Atomically commit candidate configuration to running configuration."""
        errors = self.validate_config(self.candidate_config)
        if errors:
            return False, f"Validation failed: {'; '.join(errors)}"

        # Create backup snapshot
        timestamp = int(time.time())
        backup_file = os.path.join(self.backup_dir, f"config_{timestamp}.json")
        try:
            with open(backup_file, "w", encoding="utf-8") as f:
                f.write(self.running_config.model_dump_json(indent=2))
        except Exception as e:
            return False, f"Failed to create backup snapshot: {e}"

        # Write new active configuration
        temp_file = f"{self.config_file}.tmp"
        try:
            with open(temp_file, "w", encoding="utf-8") as f:
                f.write(self.candidate_config.model_dump_json(indent=2))
            shutil.move(temp_file, self.config_file)
            self.running_config = self.candidate_config.model_copy(deep=True)
            return True, f"Configuration successfully committed. Snapshot: {os.path.basename(backup_file)}"
        except Exception as e:
            if os.path.exists(temp_file):
                os.remove(temp_file)
            return False, f"Atomic write failed: {e}"

    def rollback(self, snapshot_filename: Optional[str] = None) -> Tuple[bool, str]:
        """Roll back to the previous snapshot or specified backup file."""
        if snapshot_filename:
            target_path = os.path.join(self.backup_dir, snapshot_filename)
        else:
            backups = sorted(os.listdir(self.backup_dir), reverse=True)
            if not backups:
                return False, "No backup snapshots available to rollback to."
            target_path = os.path.join(self.backup_dir, backups[0])

        try:
            with open(target_path, "r", encoding="utf-8") as f:
                data = json.load(f)
                loaded_cfg = MitraNetMasterConfig.model_validate(data)
                self.candidate_config = loaded_cfg
                return self.commit(f"Rollback to {os.path.basename(target_path)}")
        except Exception as e:
            return False, f"Rollback failed: {e}"
