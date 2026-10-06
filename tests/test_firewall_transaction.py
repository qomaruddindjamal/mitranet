"""
Transaction & Atomic Rollback Tests for MitraNet Firewall.
Phase 3A: Tests dry-run syntax checking, snapshot capture, atomic application,
failure injection, and rollback restoration.
"""

import os
import shutil
import tempfile
import unittest
from unittest.mock import MagicMock
from mitranet.core.firewall.models import (
    FirewallTableConfig,
    FirewallRule,
    FirewallPolicy,
    FirewallAction,
)
from mitranet.core.firewall.backend import NftablesBackend
from mitranet.core.firewall.engine import FirewallTransactionEngine
from mitranet.core.firewall.errors import FirewallBackendError
from mitranet.core.transaction.models import TransactionState


class TestFirewallTransaction(unittest.TestCase):
    """Test suite verifying transactional state machine and atomic rollback."""

    def setUp(self):
        self.test_dir = tempfile.mkdtemp()
        self.mock_backend = MagicMock(spec=NftablesBackend)
        self.mock_backend.list_table.return_value = "table inet mitranet { chain input { policy drop; } }"
        self.mock_backend.list_ruleset.return_value = "table inet mitranet { chain input { policy drop; } }"
        self.mock_backend.get_counters.return_value = {}

        self.engine = FirewallTransactionEngine(
            base_dir=self.test_dir,
            backend=self.mock_backend,
        )

    def tearDown(self):
        shutil.rmtree(self.test_dir, ignore_errors=True)

    def test_successful_transaction(self):
        cand = FirewallTableConfig(
            rules=[FirewallRule(id="rule1", action=FirewallAction.ACCEPT)]
        )
        self.engine.save_candidate(cand)

        record = self.engine.apply_and_commit(persist=False)
        self.assertEqual(record.state, TransactionState.COMMITTED)
        self.mock_backend.check_syntax.assert_called_once()
        self.mock_backend.apply_ruleset.assert_called_once()
        self.assertEqual(len(self.engine.running_config.rules), 1)

    def test_failed_apply_triggers_rollback(self):
        cand = FirewallTableConfig(
            rules=[FirewallRule(id="rule_fail", action=FirewallAction.ACCEPT)]
        )
        self.engine.save_candidate(cand)

        # Inject failure on first apply, but succeed on rollback apply
        self.mock_backend.apply_ruleset.side_effect = [
            FirewallBackendError("Kernel rejected rule"),
            None,
        ]

        with self.assertRaises(FirewallBackendError):
            self.engine.apply_and_commit(persist=False)

        # Running config must remain unchanged
        self.assertEqual(len(self.engine.running_config.rules), 0)



if __name__ == "__main__":
    unittest.main()
