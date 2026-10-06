"""
MitraNet Linux nftables Backend.
Phase 3A: Executes validated nftables commands via subprocess.run(shell=False).
Supports:
- Check syntax dry-run: nft -c -f <file>
- Atomic apply: nft -f <file>
- Ruleset listing: nft -j list ruleset, nft list table <family> <name>
- Flush/Delete table: nft delete table <family> <name>
- Counter extraction: nft -j list table <family> <name>
"""

import json
import logging
import os
import subprocess
import tempfile
from typing import Dict, List, Optional, Tuple, Any
from mitranet.core.firewall.errors import FirewallBackendError
from mitranet.core.firewall.models import FirewallRuleCounter

logger = logging.getLogger("mitranet.firewall.backend")


class NftablesBackend:
    """Linux kernel nftables executor."""

    def __init__(self, nft_binary: str = "/usr/sbin/nft"):
        self.nft_binary = nft_binary if os.path.exists(nft_binary) else "nft"

    def _execute(self, args: List[str], input_data: Optional[str] = None, timeout: int = 15) -> Tuple[int, str, str]:
        """
        Executes an nft command with shell=False, capturing stdout and stderr.
        """
        cmd = [self.nft_binary] + args
        try:
            res = subprocess.run(
                cmd,
                input=input_data,
                text=True,
                capture_output=True,
                timeout=timeout,
                check=False,
            )
            return res.returncode, res.stdout, res.stderr
        except FileNotFoundError:
            raise FirewallBackendError(f"nftables binary not found: '{self.nft_binary}'")
        except subprocess.TimeoutExpired:
            raise FirewallBackendError(f"nftables command timed out after {timeout} seconds: {' '.join(cmd)}")
        except Exception as e:
            raise FirewallBackendError(f"Failed to execute nft command: {e}")

    def check_syntax(self, ruleset_str: str) -> None:
        """
        Validates the ruleset using nft -c (check only, zero mutation).
        Raises FirewallBackendError if invalid.
        """
        with tempfile.NamedTemporaryFile("w", suffix=".nft", delete=False) as f:
            f.write(ruleset_str)
            temp_path = f.name

        try:
            rc, out, err = self._execute(["-c", "-f", temp_path])
            if rc != 0:
                raise FirewallBackendError(f"nftables syntax check failed (rc={rc}): {err.strip()}")
        finally:
            if os.path.exists(temp_path):
                os.unlink(temp_path)

    def apply_ruleset(self, ruleset_str: str) -> None:
        """
        Applies ruleset atomically via `nft -f <file>`.
        """
        # Pre-validate with -c first
        self.check_syntax(ruleset_str)

        with tempfile.NamedTemporaryFile("w", suffix=".nft", delete=False) as f:
            f.write(ruleset_str)
            temp_path = f.name

        try:
            rc, out, err = self._execute(["-f", temp_path])
            if rc != 0:
                raise FirewallBackendError(f"Failed to apply nftables ruleset (rc={rc}): {err.strip()}")
        finally:
            if os.path.exists(temp_path):
                os.unlink(temp_path)

    def list_table(self, family: str = "inet", name: str = "mitranet") -> str:
        """Returns the raw text of the specified table if it exists."""
        rc, out, err = self._execute(["list", "table", family, name])
        if rc != 0:
            if "No such file or directory" in err or "does not exist" in err:
                return ""
            raise FirewallBackendError(f"Failed to list table {family} {name}: {err.strip()}")
        return out

    def list_ruleset(self) -> str:
        """Returns the complete raw text of the nftables ruleset."""
        rc, out, err = self._execute(["list", "ruleset"])
        if rc != 0:
            raise FirewallBackendError(f"Failed to list nftables ruleset: {err.strip()}")
        return out

    def delete_table(self, family: str = "inet", name: str = "mitranet") -> bool:
        """Deletes the specified table if it exists. Returns True if deleted, False if did not exist."""
        rc, out, err = self._execute(["delete", "table", family, name])
        if rc != 0:
            if "No such file or directory" in err or "does not exist" in err:
                return False
            raise FirewallBackendError(f"Failed to delete table {family} {name}: {err.strip()}")
        return True

    def flush_table(self, family: str = "inet", name: str = "mitranet") -> None:
        """Flushes all chains and rules inside the specified table."""
        rc, out, err = self._execute(["flush", "table", family, name])
        if rc != 0:
            if "No such file or directory" in err or "does not exist" in err:
                return
            raise FirewallBackendError(f"Failed to flush table {family} {name}: {err.strip()}")

    def get_counters(self, family: str = "inet", name: str = "mitranet") -> Dict[str, FirewallRuleCounter]:
        """
        Extracts rule counters by parsing `nft -j list table <family> <name>`.
        Maps comment `mitranet:rule:<rule_id>` to packets and bytes.
        """
        rc, out, err = self._execute(["-j", "list", "table", family, name])
        if rc != 0:
            return {}

        counters: Dict[str, FirewallRuleCounter] = {}
        try:
            data = json.loads(out)
            nftables_objs = data.get("nftables", [])
            for item in nftables_objs:
                if "rule" in item:
                    rule_info = item["rule"]
                    comment = rule_info.get("comment", "")
                    if comment.startswith("mitranet:rule:"):
                        rule_id = comment.split("mitranet:rule:", 1)[1]
                        # Find counter expr
                        exprs = rule_info.get("expr", [])
                        for expr in exprs:
                            if "counter" in expr:
                                c_info = expr["counter"]
                                counters[rule_id] = FirewallRuleCounter(
                                    rule_id=rule_id,
                                    packets=c_info.get("packets", 0),
                                    bytes=c_info.get("bytes", 0),
                                )
        except Exception as e:
            logger.warning("Failed to parse JSON counters from nftables: %s", e)

        return counters
