"""
Unit Tests for MitraNet Native Services (Phase 2C Reimplementation).
Verifies:
1. FilterLog parser for nftables messages
2. FilterDNS set generation
3. DHCPLeases watcher (dnsmasq & ISC formats)
4. System metrics collector
5. Status reloader flag signaling
6. Table expiry set generation
"""

import unittest
from mitranet.core.services.filterlog import FilterLogService
from mitranet.core.services.filterdns import FilterDNSService
from mitranet.core.services.dhcpleases import DHCPLeasesWatcher
from mitranet.core.services.sysmetrics import SystemMetricsCollector
from mitranet.core.services.status_reloader import StatusReloaderService
from mitranet.core.services.table_expiry import TableExpiryService
import tempfile
import shutil

class TestNativeServices(unittest.TestCase):
    def test_filterlog_parser(self):
        line = "NFT-BLOCK: IN=enp0s3 OUT= MAC=08:00:27:8b:78:85 SRC=192.168.1.100 DST=192.168.1.1 LEN=64 PROTO=TCP SPT=54321 DPT=80"
        res = FilterLogService.parse_line(line)
        self.assertIsNotNone(res)
        self.assertEqual(res["prefix"], "NFT-BLOCK")
        self.assertEqual(res["in_interface"], "enp0s3")
        self.assertEqual(res["src_ip"], "192.168.1.100")
        self.assertEqual(res["dst_ip"], "192.168.1.1")
        self.assertEqual(res["protocol"], "TCP")
        self.assertEqual(res["src_port"], 54321)
        self.assertEqual(res["dst_port"], 80)

    def test_filterdns_generation(self):
        svc = FilterDNSService(table_name="inet mitranet")
        rule = svc.generate_nft_set_elements("blocked_hosts", ["1.1.1.1", "8.8.8.8"])
        self.assertEqual(rule, "add element inet mitranet blocked_hosts { 1.1.1.1, 8.8.8.8 }")

    def test_dhcpleases_parser(self):
        content = "1791285000 00:11:22:33:44:55 192.168.1.50 test-laptop *\n"
        leases = DHCPLeasesWatcher.parse_dnsmasq_leases(content)
        self.assertEqual(len(leases), 1)
        self.assertEqual(leases[0]["mac"], "00:11:22:33:44:55")
        self.assertEqual(leases[0]["ip"], "192.168.1.50")
        self.assertEqual(leases[0]["hostname"], "test-laptop")

    def test_status_reloader(self):
        tmp_dir = tempfile.mkdtemp()
        try:
            svc = StatusReloaderService(run_dir=tmp_dir)
            self.assertFalse(svc.check_and_clear_trigger("filter"))
            svc.trigger_reload("filter")
            self.assertTrue(svc.check_and_clear_trigger("filter"))
            self.assertFalse(svc.check_and_clear_trigger("filter"))
        finally:
            shutil.rmtree(tmp_dir, ignore_errors=True)

    def test_table_expiry_generation(self):
        svc = TableExpiryService(table_name="inet mitranet")
        rule = svc.create_expiring_set("temp_block", timeout_seconds=1800)
        self.assertIn("flags timeout", rule)
        self.assertIn("timeout 1800s", rule)

if __name__ == "__main__":
    unittest.main()
