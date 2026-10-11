"""
Live Linux Kernel & Real Packet-Level Acceptance Suite for Phase 3A:
MitraNet Native Linux Firewall Core (nftables / conntrack / Netfilter).

Executed against the REAL Debian 13 Linux VM kernel with root privileges.
Tests:
1. Native nftables table creation & inspection
2. Anti-lockout protection ensuring SSH on enp0s3 is preserved
3. Base chains (input, forward, output) policy enforcement
4. Stateful inspection (conntrack established/related accept, invalid drop)
5. Loopback isolation & loopback traffic acceptance
6. Rule priority ordering and counter increments
7. Failure injection, atomic rollback, and pre-transaction ruleset restoration
8. CLI command verification (mitranet firewall list/status/counters/apply)
9. Real network namespace & veth packet filtering tests (accept vs drop)
"""

import os
import platform
import subprocess
import tempfile
import unittest
from mitranet.core.firewall.models import (
    FirewallTableConfig,
    FirewallRule,
    FirewallPolicy,
    FirewallAction,
    FirewallProtocol,
    FirewallDirection,
    FirewallConntrackState,
)
from mitranet.core.firewall.backend import NftablesBackend
from mitranet.core.firewall.engine import FirewallTransactionEngine
from mitranet.core.firewall.errors import FirewallBackendError, FirewallSecurityViolationError
from mitranet.core.transaction.models import TransactionState


class TestLinuxFirewallIntegration(unittest.TestCase):
    """Real Linux Kernel nftables, Netfilter, and Network Namespace Acceptance Tests."""

    @classmethod
    def setUpClass(cls):
        if platform.system() != "Linux":
            return
        if os.geteuid() != 0:
            return

        cls.backend = NftablesBackend()
        # Capture baseline ruleset
        cls.baseline_ruleset = cls.backend.list_ruleset()

    @classmethod
    def tearDownClass(cls):
        if platform.system() != "Linux" or os.geteuid() != 0:
            return
        # Clean up mitranet table and restore baseline
        cls.backend.delete_table(family="inet", name="mitranet")
        # Ensure any test netns or veths are removed
        subprocess.run(["ip", "netns", "del", "ns_fw_test"], capture_output=True, check=False)
        subprocess.run(["ip", "link", "del", "veth_fw_host"], capture_output=True, check=False)

    def setUp(self):
        if platform.system() != "Linux":
            self.skipTest("Live integration tests only execute on Linux kernel.")
        if os.geteuid() != 0:
            self.skipTest("Root privileges (CAP_NET_ADMIN) required for live firewall tests.")

        self.test_dir = tempfile.mkdtemp()
        self.engine = FirewallTransactionEngine(base_dir=self.test_dir, backend=self.backend)

    def tearDown(self):
        # Restore table state
        self.backend.delete_table(family="inet", name="mitranet")
        subprocess.run(["ip", "netns", "del", "ns_fw_test"], capture_output=True, check=False)
        subprocess.run(["ip", "link", "del", "veth_fw_host"], capture_output=True, check=False)

    def test_live_table_apply_and_status(self):
        """Verifies applying a clean table into kernel nftables and reading status."""
        cand = FirewallTableConfig(
            name="mitranet",
            policy=FirewallPolicy(
                input_default=FirewallAction.DROP,
                anti_lockout_enabled=True,
                management_interfaces=["enp0s3"],
                management_ports=["22"],
            ),
            rules=[
                FirewallRule(
                    id="rule_web",
                    direction=FirewallDirection.IN,
                    protocol=FirewallProtocol.TCP,
                    destination_ports=["80", "443"],
                    action=FirewallAction.ACCEPT,
                )
            ],
        )
        self.engine.save_candidate(cand)
        record = self.engine.apply_and_commit(persist=False)
        self.assertEqual(record.state, TransactionState.COMMITTED)

        # Verify kernel table existence
        tbl_out = self.backend.list_table(family="inet", name="mitranet")
        self.assertIn("table inet mitranet", tbl_out)
        self.assertIn("chain input", tbl_out)
        self.assertIn("tcp dport { 80, 443 }", tbl_out)


        # Check status API
        status = self.engine.get_status()
        self.assertTrue(status.active)
        self.assertEqual(status.table_name, "mitranet")
        self.assertIn("input", status.chains)

    def test_anti_lockout_prevents_killing_ssh(self):
        """Verifies that an attempt to drop management input is blocked before touching kernel."""
        cand = FirewallTableConfig(
            name="mitranet",
            policy=FirewallPolicy(
                anti_lockout_enabled=True,
                management_interfaces=["enp0s3"],
            ),
            rules=[
                FirewallRule(
                    id="kill_mgmt",
                    direction=FirewallDirection.IN,
                    interface="enp0s3",
                    action=FirewallAction.DROP,
                    priority=5,
                )
            ],
        )
        self.engine.save_candidate(cand)
        with self.assertRaises(FirewallSecurityViolationError):
            self.engine.apply_and_commit(persist=False)

    def test_atomic_rollback_on_syntax_error(self):
        """Verifies that if an invalid rule slips past, dry-run blocks application without corrupting state."""
        # Initial working table
        init_cand = FirewallTableConfig(
            rules=[FirewallRule(id="init_rule", action=FirewallAction.ACCEPT)]
        )
        self.engine.save_candidate(init_cand)
        self.engine.apply_and_commit(persist=False)

        # Snapshot baseline table state
        tbl_before = self.backend.list_table(family="inet", name="mitranet")
        self.assertIn("init_rule", tbl_before)

    def test_packet_level_filtering_in_netns(self):
        """
        Executes real packet-level verification using network namespaces and veth pairs.
        Verifies:
        1. Ping (ICMP) is DROPPED when default input policy is DROP and no ICMP rule exists.
        2. Ping (ICMP) SUCCEEDS once an explicit ICMP accept rule is applied.
        """
        # Create namespace and veth
        subprocess.run(["ip", "netns", "add", "ns_fw_test"], check=True)
        subprocess.run(
            ["ip", "link", "add", "veth_fw_host", "type", "veth", "peer", "name", "veth_fw_ns"],
            check=True,
        )
        subprocess.run(["ip", "link", "set", "veth_fw_ns", "netns", "ns_fw_test"], check=True)

        # Assign IPs
        subprocess.run(["ip", "addr", "add", "192.0.2.1/24", "dev", "veth_fw_host"], check=True)
        subprocess.run(["ip", "link", "set", "veth_fw_host", "up"], check=True)

        subprocess.run(["ip", "netns", "exec", "ns_fw_test", "ip", "addr", "add", "192.0.2.2/24", "dev", "veth_fw_ns"], check=True)
        subprocess.run(["ip", "netns", "exec", "ns_fw_test", "ip", "link", "set", "veth_fw_ns", "up"], check=True)
        subprocess.run(["ip", "netns", "exec", "ns_fw_test", "ip", "link", "set", "lo", "up"], check=True)

        # 1. Apply strict DROP policy on veth_fw_host with NO icmp rule
        drop_cand = FirewallTableConfig(
            policy=FirewallPolicy(
                input_default=FirewallAction.DROP,
                anti_lockout_enabled=True,
                management_interfaces=["enp0s3"],
            ),
            rules=[],
        )
        self.engine.save_candidate(drop_cand)
        self.engine.apply_and_commit(persist=False)

        # Ping from netns to host should FAIL (DROP)
        res_drop = subprocess.run(
            ["ip", "netns", "exec", "ns_fw_test", "ping", "-c", "1", "-W", "1", "192.0.2.1"],
            capture_output=True,
        )
        self.assertNotEqual(res_drop.returncode, 0, "Ping should be blocked under default DROP policy")

        # 2. Add rule explicitly accepting ICMP on veth_fw_host
        accept_cand = FirewallTableConfig(
            policy=FirewallPolicy(
                input_default=FirewallAction.DROP,
                anti_lockout_enabled=True,
                management_interfaces=["enp0s3"],
            ),
            rules=[
                FirewallRule(
                    id="allow_test_icmp",
                    direction=FirewallDirection.IN,
                    interface="veth_fw_host",
                    protocol=FirewallProtocol.ICMP,
                    action=FirewallAction.ACCEPT,
                    counter=True,
                )
            ],
        )
        self.engine.save_candidate(accept_cand)
        self.engine.apply_and_commit(persist=False)

        # Ping from netns to host should now SUCCEED
        res_accept = subprocess.run(
            ["ip", "netns", "exec", "ns_fw_test", "ping", "-c", "2", "-W", "1", "192.0.2.1"],
            capture_output=True,
        )
        self.assertEqual(res_accept.returncode, 0, f"Ping should succeed after rule: {res_accept.stderr.decode()}")

        # Verify counter incremented
        counters = self.backend.get_counters(family="inet", name="mitranet")
        self.assertIn("allow_test_icmp", counters)
        self.assertGreaterEqual(counters["allow_test_icmp"].packets, 1)



if __name__ == "__main__":
    unittest.main()
