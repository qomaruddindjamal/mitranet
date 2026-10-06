"""
Security & Command Injection Tests for MitraNet Firewall.
Phase 3A: Tests adversarial inputs, shell characters, dangerous prefixes,
and subprocess execution boundaries (shell=False invariance).
"""

import unittest
from mitranet.core.firewall.models import (
    FirewallTableConfig,
    FirewallRule,
    FirewallZone,
    FirewallAction,
)
from mitranet.core.firewall.validator import FirewallValidator
from mitranet.core.firewall.errors import FirewallValidationError
from pydantic import ValidationError


class TestFirewallSecurity(unittest.TestCase):
    """Test suite ensuring strict immunity against command and syntax injection."""

    def test_injection_in_rule_id(self):
        injections = [
            "rule; cat /etc/passwd",
            "rule && reboot",
            "rule | id",
            "rule$(whoami)",
            "rule`id`",
            "rule\nflush ruleset",
        ]
        for inj in injections:
            with self.subTest(inj=inj):
                with self.assertRaises((ValidationError, FirewallValidationError)):
                    r = FirewallRule(id=inj)
                    FirewallValidator.validate_rule(r)

    def test_injection_in_zone_name(self):
        injections = ["WAN;rm -rf /", "LAN && echo pwned", "DMZ|nc -l 4444"]
        for inj in injections:
            with self.subTest(inj=inj):
                with self.assertRaises((ValidationError, FirewallValidationError)):
                    z = FirewallZone(name=inj)
                    FirewallValidator.validate_injection_safety(z.name, "zone")

    def test_injection_in_interface(self):
        injections = [
            "eth0; rm -rf /",
            "enp0s3 && touch /tmp/pwn",
            "lo | ls",
            "enp0s3$(id)",
        ]
        for inj in injections:
            with self.subTest(inj=inj):
                with self.assertRaises(FirewallValidationError):
                    FirewallValidator.validate_interface(inj)

    def test_injection_in_ip_address(self):
        injections = [
            "10.0.0.1; reboot",
            "192.168.1.1 && date",
            "10.0.0.1/24 | ls",
            "10.0.0.1$(id)",
        ]
        for inj in injections:
            with self.subTest(inj=inj):
                with self.assertRaises(FirewallValidationError):
                    FirewallValidator.validate_ip_or_cidr(inj, family="inet")

    def test_injection_in_log_prefix(self):
        injections = [
            'DROP"; shutdown -h now; "',
            "LOG$(whoami)",
            "FW: `id`",
        ]
        for inj in injections:
            with self.subTest(inj=inj):
                with self.assertRaises((ValidationError, FirewallValidationError)):
                    r = FirewallRule(id="safe_id", log=True, log_prefix=inj)
                    FirewallValidator.validate_rule(r)


if __name__ == "__main__":
    unittest.main()
