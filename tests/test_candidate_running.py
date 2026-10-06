"""
Test Suite: Candidate vs Running Configuration, Commit and Rollback.
"""

import os
import shutil
import unittest
from mitranet.core.config.model import MitraNetConfig, InterfaceConfig, InterfaceIPv4
from mitranet.core.config.transaction import ConfigTransactionManager


class TestCandidateRunningRollback(unittest.TestCase):
    def setUp(self):
        self.test_dir = "C:/mitranet/tmp/test_transactions"
        if os.path.exists(self.test_dir):
            shutil.rmtree(self.test_dir)
        self.mgr = ConfigTransactionManager(base_dir=self.test_dir)

    def tearDown(self):
        if os.path.exists(self.test_dir):
            shutil.rmtree(self.test_dir)

    def test_candidate_edit_and_commit(self):
        candidate = self.mgr.get_candidate()
        candidate.system.hostname = "edge-router-01"
        candidate.interfaces["lan"] = InterfaceConfig(
            device="eth0",
            role="lan",
            ipv4=InterfaceIPv4(mode="static", address="10.0.0.1", prefix=24)
        )

        errors = self.mgr.set_candidate(candidate)
        self.assertEqual(len(errors), 0)

        success, msg = self.mgr.commit("Added LAN interface")
        self.assertTrue(success)
        self.assertEqual(self.mgr.get_running().system.hostname, "edge-router-01")
        self.assertEqual(self.mgr.get_running().config_version, 2)

    def test_rollback(self):
        # 1. First commit
        c1 = self.mgr.get_candidate()
        c1.system.hostname = "state-1"
        self.mgr.set_candidate(c1)
        self.mgr.commit("State 1")

        # 2. Second commit
        c2 = self.mgr.get_candidate()
        c2.system.hostname = "state-2"
        self.mgr.set_candidate(c2)
        self.mgr.commit("State 2")
        self.assertEqual(self.mgr.get_running().system.hostname, "state-2")

        # 3. Rollback
        success, msg = self.mgr.rollback()
        self.assertTrue(success)
        self.assertEqual(self.mgr.get_running().system.hostname, "state-1")


if __name__ == "__main__":
    unittest.main()
