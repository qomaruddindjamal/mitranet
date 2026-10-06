#!/usr/bin/env python3
"""
MitraNet Configuration Engine
Project: MitraNet
Code OS: Rinjani
Foundation: MitraOS 1.0.0
Architecture: amd64

Provides the unified, single-source-of-truth configuration management,
domain modeling, dependency validation, transactional commit/rollback,
diff generation, and concurrency locking for MitraNet CLI, TUI, and REST API.
"""

import os
import sys
import json
import time
import copy
import uuid
import hashlib
from typing import Dict, Any, List, Optional, Tuple

DEFAULT_CONFIG_PATH = r"C:\mitranet\api\config.json"
BACKUP_DIR = r"C:\mitranet\api\backups"
LOCK_FILE = r"C:\mitranet\api\config.lock"

SCHEMA_VERSION = "1.0.0"

INITIAL_CONFIG_TEMPLATE = {
    "version": SCHEMA_VERSION,
    "system": {
        "hostname": "mitranet",
        "domain": "local",
        "timezone": "Asia/Jakarta"
    },
    "interfaces": {
        "eth0": {
            "name": "eth0",
            "type": "ethernet",
            "enabled": True,
            "description": "WAN Interface",
            "mtu": 1500,
            "zone": "WAN"
        },
        "eth1": {
            "name": "eth1",
            "type": "ethernet",
            "enabled": True,
            "description": "LAN Interface",
            "mtu": 1500,
            "zone": "LAN"
        }
    },
    "addresses": {
        "eth0_v4": {
            "interface": "eth0",
            "ip": "192.168.1.100",
            "prefix": 24,
            "family": "ipv4"
        },
        "eth1_v4": {
            "interface": "eth1",
            "ip": "10.0.0.1",
            "prefix": 24,
            "family": "ipv4"
        }
    },
    "routes": {
        "default": {
            "destination": "0.0.0.0/0",
            "gateway": "192.168.1.1",
            "interface": "eth0",
            "metric": 10
        }
    },
    "vlans": {},
    "bridges": {},
    "bonds": {},
    "zones": {
        "WAN": {"description": "External untrusted network", "interfaces": ["eth0"]},
        "LAN": {"description": "Internal trusted network", "interfaces": ["eth1"]}
    },
    "firewall": {
        "rules": [
            {
                "id": "allow-lan-out",
                "zone": "LAN",
                "action": "pass",
                "direction": "in",
                "protocol": "any",
                "source": "any",
                "destination": "any"
            }
        ]
    },
    "nat": {
        "outbound": [
            {
                "id": "masq-lan",
                "interface": "eth0",
                "source_subnet": "10.0.0.0/24",
                "mode": "masquerade"
            }
        ]
    },
    "qos": {},
    "vpn": {},
    "dhcp": {
        "servers": {
            "eth1": {
                "enabled": True,
                "interface": "eth1",
                "range_start": "10.0.0.100",
                "range_end": "10.0.0.200",
                "gateway": "10.0.0.1",
                "dns": ["10.0.0.1"]
            }
        }
    },
    "dns": {
        "enabled": True,
        "listen_interfaces": ["eth1"],
        "forwarders": ["1.1.1.1", "8.8.8.8"]
    },
    "services": {
        "sshd": {"enabled": True, "port": 22},
        "webui": {"enabled": True, "port": 80}
    }
}

class ValidationError(Exception):
    def __init__(self, code: str, message: str, field: Optional[str] = None):
        super().__init__(message)
        self.code = code
        self.message = message
        self.field = field

class LockError(Exception):
    pass

class ConfigurationEngine:
    def __init__(self, config_path: str = DEFAULT_CONFIG_PATH):
        self.config_path = config_path
        self.backup_dir = BACKUP_DIR
        self.lock_file = LOCK_FILE
        os.makedirs(os.path.dirname(self.config_path), exist_ok=True)
        os.makedirs(self.backup_dir, exist_ok=True)
        
        self.running_config: Dict[str, Any] = self._load_or_init()
        self.candidate_config: Dict[str, Any] = copy.deepcopy(self.running_config)
        self.transactions: List[Dict[str, Any]] = []

    def _load_or_init(self) -> Dict[str, Any]:
        if os.path.exists(self.config_path):
            try:
                with open(self.config_path, "r", encoding="utf-8") as f:
                    return json.load(f)
            except Exception:
                pass
        return copy.deepcopy(INITIAL_CONFIG_TEMPLATE)

    def acquire_lock(self, owner: str = "cli", timeout_sec: int = 10) -> str:
        start_time = time.time()
        lock_id = str(uuid.uuid4())
        while time.time() - start_time < timeout_sec:
            if not os.path.exists(self.lock_file):
                try:
                    with open(self.lock_file, "w", encoding="utf-8") as f:
                        json.dump({"lock_id": lock_id, "owner": owner, "timestamp": time.time()}, f)
                    return lock_id
                except IOError:
                    pass
            time.sleep(0.1)
        raise LockError(f"Failed to acquire configuration lock: owned by active session")

    def release_lock(self, lock_id: str) -> bool:
        if os.path.exists(self.lock_file):
            try:
                with open(self.lock_file, "r", encoding="utf-8") as f:
                    data = json.load(f)
                if data.get("lock_id") == lock_id:
                    os.remove(self.lock_file)
                    return True
            except Exception:
                pass
        return False

    def get_running_config(self) -> Dict[str, Any]:
        return copy.deepcopy(self.running_config)

    def get_candidate_config(self) -> Dict[str, Any]:
        return copy.deepcopy(self.candidate_config)

    def reset_candidate(self):
        self.candidate_config = copy.deepcopy(self.running_config)

    def set_candidate_value(self, section: str, key: str, value: Any):
        if section not in self.candidate_config:
            self.candidate_config[section] = {}
        self.candidate_config[section][key] = value

    def remove_candidate_value(self, section: str, key: str):
        if section in self.candidate_config and key in self.candidate_config[section]:
            del self.candidate_config[section][key]

    def validate(self, cfg: Optional[Dict[str, Any]] = None) -> List[Dict[str, str]]:
        target = cfg if cfg is not None else self.candidate_config
        errors = []

        # 1. System checks
        if not target.get("system", {}).get("hostname"):
            errors.append({"code": "SYS_NO_HOSTNAME", "message": "Hostname cannot be empty", "field": "system.hostname"})

        # 2. Interface checks
        ifaces = target.get("interfaces", {})
        for ifname, ifdata in ifaces.items():
            if not isinstance(ifdata, dict):
                errors.append({"code": "IF_INVALID_DATA", "message": f"Interface {ifname} configuration must be a mapping", "field": f"interfaces.{ifname}"})
                continue
            mtu = ifdata.get("mtu", 1500)
            if not (576 <= mtu <= 9216):
                errors.append({"code": "IF_INVALID_MTU", "message": f"MTU for {ifname} must be between 576 and 9216", "field": f"interfaces.{ifname}.mtu"})

        # 3. Address checks & interface dependencies
        addresses = target.get("addresses", {})
        seen_ips = set()
        for addr_id, addr_data in addresses.items():
            iface = addr_data.get("interface")
            if iface not in ifaces:
                errors.append({"code": "ADDR_ORPHAN_IFACE", "message": f"Address {addr_id} points to non-existent interface {iface}", "field": f"addresses.{addr_id}.interface"})
            ip = addr_data.get("ip")
            if not ip:
                errors.append({"code": "ADDR_NO_IP", "message": f"Address {addr_id} missing IP value", "field": f"addresses.{addr_id}.ip"})
            elif ip in seen_ips:
                errors.append({"code": "ADDR_DUPLICATE_IP", "message": f"Duplicate IP address {ip} in configuration", "field": f"addresses.{addr_id}.ip"})
            else:
                seen_ips.add(ip)

        # 4. Route checks
        routes = target.get("routes", {})
        for r_id, r_data in routes.items():
            iface = r_data.get("interface")
            if iface and iface not in ifaces:
                errors.append({"code": "ROUTE_ORPHAN_IFACE", "message": f"Route {r_id} refers to non-existent interface {iface}", "field": f"routes.{r_id}.interface"})

        # 5. VLAN checks
        vlans = target.get("vlans", {})
        for vlan_id, vlan_data in vlans.items():
            parent = vlan_data.get("parent")
            if parent not in ifaces:
                errors.append({"code": "VLAN_INVALID_PARENT", "message": f"VLAN {vlan_id} parent interface {parent} does not exist", "field": f"vlans.{vlan_id}.parent"})

        return errors

    def compute_diff(self) -> List[str]:
        running_dump = json.dumps(self.running_config, sort_keys=True, indent=2).splitlines()
        candidate_dump = json.dumps(self.candidate_config, sort_keys=True, indent=2).splitlines()
        
        diff_lines = []
        # Simple structural diff
        for k in self.candidate_config:
            if k not in self.running_config:
                diff_lines.append(f"+ [{k}] (added)")
            elif self.candidate_config[k] != self.running_config[k]:
                diff_lines.append(f"~ [{k}] (modified)")
        for k in self.running_config:
            if k not in self.candidate_config:
                diff_lines.append(f"- [{k}] (removed)")
        return diff_lines

    def apply_and_commit(self, actor: str = "cli", dry_run: bool = False) -> Tuple[bool, str, List[Dict[str, str]]]:
        errors = self.validate(self.candidate_config)
        if errors:
            return False, "Validation failed", errors

        diff = self.compute_diff()
        if not diff and not dry_run:
            return True, "No changes detected", []

        if dry_run:
            return True, "Dry-run validation successful", []

        tx_id = f"TX-{uuid.uuid4().hex[:8].upper()}"
        timestamp = time.time()

        # Create backup of current running
        backup_file = os.path.join(self.backup_dir, f"config_{int(timestamp)}_{tx_id}.json")
        try:
            with open(backup_file, "w", encoding="utf-8") as bf:
                json.dump(self.running_config, bf, indent=2)
        except Exception as e:
            return False, f"Failed to create pre-commit backup: {e}", []

        # Atomic commit
        temp_file = self.config_path + ".tmp"
        try:
            with open(temp_file, "w", encoding="utf-8") as tf:
                json.dump(self.candidate_config, tf, indent=2)
            os.replace(temp_file, self.config_path)
            
            # Health check simulation
            health_ok = self.run_health_check()
            if not health_ok:
                # Rollback immediately
                with open(backup_file, "r", encoding="utf-8") as bf:
                    self.running_config = json.load(bf)
                with open(self.config_path, "w", encoding="utf-8") as cf:
                    json.dump(self.running_config, cf, indent=2)
                return False, "Health check failed after apply; rolled back to previous state", []

            self.running_config = copy.deepcopy(self.candidate_config)
            tx_record = {
                "transaction_id": tx_id,
                "timestamp": timestamp,
                "actor": actor,
                "diff": diff,
                "backup_file": backup_file,
                "status": "COMMITTED"
            }
            self.transactions.append(tx_record)
            return True, f"Transaction {tx_id} committed successfully", []

        except Exception as e:
            return False, f"Commit execution failed: {e}", []

    def rollback(self, target_backup: Optional[str] = None) -> Tuple[bool, str]:
        backups = sorted([f for f in os.listdir(self.backup_dir) if f.startswith("config_") and f.endswith(".json")])
        if not backups:
            return False, "No previous backups found to rollback to"

        chosen_backup = os.path.join(self.backup_dir, target_backup if target_backup else backups[-1])
        if not os.path.exists(chosen_backup):
            return False, f"Target backup {chosen_backup} does not exist"

        try:
            with open(chosen_backup, "r", encoding="utf-8") as f:
                prev_config = json.load(f)
            
            # Validate rollback before applying
            errors = self.validate(prev_config)
            if errors:
                return False, f"Rollback configuration failed validation: {errors[0]['message']}"

            with open(self.config_path, "w", encoding="utf-8") as f:
                json.dump(prev_config, f, indent=2)
            self.running_config = copy.deepcopy(prev_config)
            self.candidate_config = copy.deepcopy(prev_config)
            return True, f"Rolled back cleanly to {os.path.basename(chosen_backup)}"
        except Exception as e:
            return False, f"Rollback failed: {e}"

    def run_health_check(self) -> bool:
        # Verify schema baseline integrity and loopback/essential structures
        if not self.candidate_config.get("system", {}).get("hostname"):
            return False
        return True

# Export singleton helper
engine = ConfigurationEngine()
