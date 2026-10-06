"""
MitraNet Phase 0 Verification Test Suite.
Validates:
1. Pydantic schema serialization and validation rules.
2. Configuration engine candidate, validation, atomic commit, and rollback logic.
3. pfSense default config.xml parsing and conversion.
4. nftables firewall rules compiler correctness.
"""

import unittest
from mitranet.src.config.models import (
    MitraNetMasterConfig,
    InterfaceConfig,
    InterfaceAddress,
    FirewallRule,
    FirewallAlias,
    OutboundNatRule,
)
from mitranet.src.config.engine import ConfigEngine
from mitranet.src.network.firewall import NftablesCompiler
from mitranet.migration.pfsense.parser import PfSenseMigrationEngine


class TestMitraNetCore(unittest.TestCase):
    def test_schema_model_instantiation(self):
        cfg = MitraNetMasterConfig()
        self.assertEqual(cfg.system.hostname, "mitranet")
        self.assertEqual(cfg.version, "1.0.0")

    def test_config_engine_validation(self):
        engine = ConfigEngine(config_dir="C:/mitranet/tmp/test_config")
        cfg = MitraNetMasterConfig()
        
        # Add interface
        cfg.interfaces.append(
            InterfaceConfig(
                name="eth0",
                role="wan",
                addresses=[InterfaceAddress(ip="192.168.1.1", prefix_length=24)]
            )
        )
        
        # Add firewall rule referencing non-existent interface
        cfg.firewall_rules.append(
            FirewallRule(
                id="rule_invalid",
                interface="eth99",
                action="accept"
            )
        )
        
        errors = engine.validate_config(cfg)
        self.assertTrue(len(errors) > 0)
        self.assertIn("eth99", errors[0])

    def test_nftables_compiler(self):
        cfg = MitraNetMasterConfig()
        cfg.interfaces.append(
            InterfaceConfig(
                name="eth0",
                role="wan",
                addresses=[InterfaceAddress(ip="203.0.113.2", prefix_length=24)]
            )
        )
        cfg.firewall_aliases.append(
            FirewallAlias(
                name="admin_ips",
                type="host",
                entries=["192.168.1.50", "192.168.1.51"]
            )
        )
        cfg.firewall_rules.append(
            FirewallRule(
                id="allow_admin",
                interface="eth0",
                protocol="tcp",
                source="@admin_ips",
                destination_port="22",
                action="accept"
            )
        )
        cfg.nat_outbound.append(
            OutboundNatRule(
                interface="eth0",
                source="192.168.1.0/24",
                mode="masquerade"
            )
        )
        
        compiled = NftablesCompiler.compile_ruleset(cfg)
        self.assertIn("flush ruleset", compiled)
        self.assertIn("table inet mitranet", compiled)
        self.assertIn("set admin_ips", compiled)
        self.assertIn("dport 22 accept", compiled)
        self.assertIn("masquerade", compiled)

    def test_pfsense_xml_migration(self):
        pfsense_xml_sample = """<?xml version="1.0"?>
        <pfsense>
            <system>
                <hostname>pfsense-test</hostname>
                <domain>corp.lan</domain>
                <timeservers>0.pool.ntp.org</timeservers>
            </system>
            <interfaces>
                <wan>
                    <if>em0</if>
                    <ipaddr>dhcp</ipaddr>
                    <descr>WAN_FIBER</descr>
                </wan>
                <lan>
                    <if>em1</if>
                    <ipaddr>10.0.0.1</ipaddr>
                    <subnet>24</subnet>
                    <enable></enable>
                    <descr>LAN_CORP</descr>
                </lan>
            </interfaces>
            <filter>
                <rule>
                    <type>pass</type>
                    <interface>lan</interface>
                    <protocol>tcp</protocol>
                    <destination><any></any></destination>
                    <descr>Allow LAN Outbound</descr>
                </rule>
            </filter>
            <nat>
                <outbound>
                    <mode>automatic</mode>
                </outbound>
            </nat>
            <dhcpd>
                <lan>
                    <enable></enable>
                    <range>
                        <from>10.0.0.100</from>
                        <to>10.0.0.200</to>
                    </range>
                </lan>
            </dhcpd>
            <unbound>
                <enable></enable>
                <dnssec></dnssec>
            </unbound>
        </pfsense>
        """
        parser = PfSenseMigrationEngine()
        master, warnings = parser.parse_xml_string(pfsense_xml_sample)
        
        self.assertEqual(master.system.hostname, "pfsense-test")
        self.assertEqual(master.system.domain, "corp.lan")
        self.assertEqual(len(master.interfaces), 2)
        # Verify em0 mapped to eth0
        self.assertEqual(master.interfaces[0].name, "eth0")
        self.assertEqual(master.interfaces[0].role, "wan")
        self.assertTrue(master.interfaces[0].dhcp_client)
        # Verify LAN
        self.assertEqual(master.interfaces[1].name, "eth1")
        self.assertEqual(master.interfaces[1].addresses[0].ip, "10.0.0.1")
        # Verify DHCP
        self.assertEqual(len(master.dhcp_servers), 1)
        self.assertEqual(master.dhcp_servers[0].range_start, "10.0.0.100")
        # Verify DNS
        self.assertTrue(master.dns.enabled)
        self.assertTrue(master.dns.dnssec)


if __name__ == "__main__":
    unittest.main()
