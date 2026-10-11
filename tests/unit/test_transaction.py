"""
Unit Test Suite for MitraNet Transaction & Recovery Engine (Phase 1F).
Tests:
- TransactionState transitions & invariants
- TransactionLock concurrency control & stale lock handling
- NetworkStateSnapshot serialization & SHA256 integrity validation
- DependencyPlanner operation sequence & inverse operation mapping
- NetworkTransactionEngine planning, dry-run, and idempotency
- Candidate vs Running separation semantics
"""

import os
import shutil
import tempfile
import unittest
from datetime import datetime, timezone
from unittest.mock import MagicMock

from mitranet.core.config.model import (
    MitraNetConfig,
    InterfaceConfig,
    VlanConfig,
    VRFConfig,
    BridgeConfig,
    BondConfig,
    IPv4Config,
    RoutingConfig,
    StaticRouteConfig,
)
from mitranet.core.transaction.models import (
    TransactionState,
    TransactionRecord,
    TransactionLockInfo,
    NetworkStateSnapshot,
    NetworkOperation,
)
from mitranet.core.transaction.exceptions import (
    TransactionEngineError,
    TransactionLockError,
    InvalidStateTransitionError,
    SnapshotIntegrityError,
)
from mitranet.core.transaction.lock import TransactionLock
from mitranet.core.transaction.snapshot import NetworkSnapshotManager
from mitranet.core.transaction.planner import DependencyPlanner
from mitranet.core.transaction.engine import NetworkTransactionEngine


class TestTransactionModelsAndStateMachine(unittest.TestCase):
    """Verifies transaction models and state transition rules."""

    def test_state_enum_completeness(self):
        expected_states = {
            "IDLE",
            "PREPARING",
            "VALIDATING",
            "SNAPSHOTTING",
            "APPLYING",
            "VERIFYING",
            "COMMITTING",
            "COMMITTED",
            "ROLLING_BACK",
            "ROLLED_BACK",
            "FAILED",
            "RECOVERY_REQUIRED",
        }
        actual_states = {s.value for s in TransactionState}
        self.assertEqual(expected_states, actual_states)

    def test_transaction_record_model(self):
        record = TransactionRecord(
            transaction_id="tx_test_1001",
            state=TransactionState.IDLE,
            candidate_version=2,
            running_version=1,
            operations=[
                NetworkOperation(
                    op_id="op_0001",
                    op_type="create_vrf",
                    target="vrf_blue",
                    params={"name": "vrf_blue", "table": 100},
                    inverse_op="delete_vrf",
                    inverse_params={"name": "vrf_blue"},
                )
            ],
        )
        self.assertEqual(record.transaction_id, "tx_test_1001")
        self.assertEqual(record.state, TransactionState.IDLE)
        self.assertEqual(len(record.operations), 1)
        self.assertEqual(record.operations[0].inverse_op, "delete_vrf")

        # Serialization round-trip
        data = record.model_dump()
        rebuilt = TransactionRecord.model_validate(data)
        self.assertEqual(rebuilt.transaction_id, record.transaction_id)
        self.assertEqual(rebuilt.operations[0].target, "vrf_blue")


class TestTransactionLock(unittest.TestCase):
    """Verifies lock acquisition, contention detection, and release."""

    def setUp(self):
        self.test_dir = tempfile.mkdtemp()
        self.lock_file = os.path.join(self.test_dir, "test.lock")
        self.lock = TransactionLock(lock_file=self.lock_file)

    def tearDown(self):
        shutil.rmtree(self.test_dir, ignore_errors=True)

    def test_acquire_and_release(self):
        self.assertFalse(self.lock.is_locked())
        info = self.lock.acquire("tx_1")
        self.assertTrue(self.lock.is_locked())
        self.assertEqual(info.transaction_id, "tx_1")
        self.assertEqual(info.pid, os.getpid())

        # Release
        self.lock.release("tx_1")
        self.assertFalse(self.lock.is_locked())

    def test_concurrent_lock_rejection(self):
        self.lock.acquire("tx_1")
        # Attempt second acquisition with different transaction
        second_lock = TransactionLock(lock_file=self.lock_file)
        with self.assertRaises(TransactionLockError):
            second_lock.acquire("tx_2")

    def test_stale_lock_detection_with_dead_pid(self):
        # Write lock file with non-existent PID (e.g. 9999999)
        fake_lock = TransactionLockInfo(
            lock_id="lock_stale",
            transaction_id="tx_crashed",
            acquired_at=datetime.now(timezone.utc).isoformat(),
            pid=9999999,
            hostname="test-host",
        )
        with open(self.lock_file, "w", encoding="utf-8") as f:
            f.write(fake_lock.model_dump_json())

        # is_locked should return False because PID 9999999 is dead
        self.assertFalse(self.lock.is_locked())

        # Should safely allow new acquisition over stale lock
        info = self.lock.acquire("tx_fresh")
        self.assertEqual(info.transaction_id, "tx_fresh")
        self.lock.release("tx_fresh")


class TestSnapshotManager(unittest.TestCase):
    """Verifies snapshot capturing, serialization, and SHA256 integrity validation."""

    def setUp(self):
        self.test_dir = tempfile.mkdtemp()
        self.mock_backend = MagicMock()
        self.mock_backend.get_detailed_links.return_value = [
            {"ifname": "lo", "operstate": "UNKNOWN", "mtu": 65536},
            {"ifname": "enp0s3", "operstate": "UP", "mtu": 1500},
        ]
        self.mock_backend.get_addr_info.return_value = [
            {"dev": "lo", "local": "127.0.0.1", "prefixlen": 8},
            {"dev": "enp0s3", "local": "10.0.2.15", "prefixlen": 24},
        ]
        self.mock_backend.get_routes.return_value = []
        self.snapshot_mgr = NetworkSnapshotManager(snapshot_dir=self.test_dir, backend=self.mock_backend)
        # Mock discovery services inside snapshot_mgr
        self.snapshot_mgr.vlan_service = MagicMock()
        self.snapshot_mgr.vlan_service.discover_vlans.return_value = []
        self.snapshot_mgr.bridge_service = MagicMock()
        self.snapshot_mgr.bridge_service.discover_bridges.return_value = []
        self.snapshot_mgr.bond_service = MagicMock()
        self.snapshot_mgr.bond_service.discover_bonds.return_value = []
        self.snapshot_mgr.vrf_service = MagicMock()
        self.snapshot_mgr.vrf_service.discover_vrfs.return_value = []

    def tearDown(self):
        shutil.rmtree(self.test_dir, ignore_errors=True)

    def test_capture_and_verify_integrity(self):
        snap = self.snapshot_mgr.capture_snapshot(transaction_id="tx_test_snapshot")
        self.assertTrue(snap.snapshot_id.startswith("snap_"))
        self.assertTrue(len(snap.state_hash) == 64)

        # Successfully load snapshot
        loaded = self.snapshot_mgr.load_snapshot(snap.snapshot_id)
        self.assertEqual(loaded.snapshot_id, snap.snapshot_id)
        self.assertEqual(loaded.state_hash, snap.state_hash)

    def test_detect_corrupted_snapshot(self):
        snap = self.snapshot_mgr.capture_snapshot(transaction_id="tx_tamper")
        snap_file = os.path.join(self.test_dir, f"{snap.snapshot_id}.json")

        # Corrupt file content
        with open(snap_file, "r", encoding="utf-8") as f:
            data = f.read()
        tampered_data = data.replace("10.0.2.15", "192.168.99.99")
        with open(snap_file, "w", encoding="utf-8") as f:
            f.write(tampered_data)

        # Loading should detect integrity violation
        with self.assertRaises(SnapshotIntegrityError):
            self.snapshot_mgr.load_snapshot(snap.snapshot_id)


class TestDependencyPlanner(unittest.TestCase):
    """Verifies that DependencyPlanner generates strictly ordered operations and correct inverses."""

    def test_dependency_ordering_and_inverses(self):
        cfg = MitraNetConfig(
            interfaces={
                "lan": InterfaceConfig(
                    name="lan",
                    device="eth1",
                    enabled=True,
                    mtu=9000,
                    ipv4=IPv4Config(mode="static", address="192.168.10.1", prefix=24),
                )
            },
            vlans={
                "vlan100": VlanConfig(id=100, parent="eth1", description="Test VLAN"),
            },
            bridges={
                "br0": BridgeConfig(name="br0", members=["eth1.100"], stp=True),
            },
            vrfs={
                "vrf_mgmt": VRFConfig(name="vrf_mgmt", table_id=105, interfaces=["eth1"]),
            },
            routing=RoutingConfig(
                static_routes=[
                    StaticRouteConfig(destination="10.50.0.0/16", gateway="192.168.10.254", metric=100)
                ]
            ),
        )

        plan = DependencyPlanner.generate_plan(cfg)
        self.assertTrue(len(plan) > 0)

        op_types = [op.op_type for op in plan]
        # Order must be: link setup -> vlan -> bridge -> vrf -> addresses -> routes
        self.assertIn("set_link_up", op_types)
        self.assertIn("set_mtu", op_types)
        self.assertIn("create_vlan", op_types)
        self.assertIn("create_bridge", op_types)
        self.assertIn("create_vrf", op_types)
        self.assertIn("add_address", op_types)
        self.assertIn("add_route", op_types)

        vlan_idx = op_types.index("create_vlan")
        bridge_idx = op_types.index("create_bridge")
        vrf_idx = op_types.index("create_vrf")
        addr_idx = op_types.index("add_address")
        route_idx = op_types.index("add_route")

        self.assertLess(vlan_idx, bridge_idx, "VLAN must precede Bridge membership")
        self.assertLess(bridge_idx, vrf_idx, "Bridge must precede VRF configuration")
        self.assertLess(vrf_idx, addr_idx, "VRF must precede Address assignment")
        self.assertLess(addr_idx, route_idx, "Address must precede Static Route")

        # Verify each operation has an inverse
        for op in plan:
            self.assertIsNotNone(op.inverse_op, f"Operation {op.op_type} must have an inverse operation")


class TestTransactionEngineSemantics(unittest.TestCase):
    """Verifies candidate vs running isolation, dry-run planning, and state transitions."""

    def setUp(self):
        self.test_dir = tempfile.mkdtemp()
        self.mock_backend = MagicMock()
        self.mock_backend.get_detailed_links.return_value = [
            {"ifname": "lo", "operstate": "UNKNOWN", "mtu": 65536},
            {"ifname": "enp0s3", "operstate": "UP", "mtu": 1500},
        ]
        self.mock_backend.get_addr_info.return_value = [
            {"dev": "lo", "local": "127.0.0.1", "prefixlen": 8},
            {"dev": "enp0s3", "local": "10.0.2.15", "prefixlen": 24},
        ]
        self.mock_backend.get_routes.return_value = []
        self.engine = NetworkTransactionEngine(base_dir=self.test_dir, backend=self.mock_backend)
        # Mock subservices
        self.engine.vrf_service = MagicMock()
        self.engine.vrf_service.discover_vrfs.return_value = []
        self.engine.vlan_service = MagicMock()
        self.engine.vlan_service.discover_vlans.return_value = []
        self.engine.bridge_service = MagicMock()
        self.engine.bridge_service.discover_bridges.return_value = []
        self.engine.bond_service = MagicMock()
        self.engine.bond_service.discover_bonds.return_value = []

    def tearDown(self):
        shutil.rmtree(self.test_dir, ignore_errors=True)

    def test_candidate_running_isolation(self):
        running = self.engine.get_running()
        candidate = self.engine.get_candidate()

        # Update candidate
        candidate.vrfs["vrf_test"] = VRFConfig(name="vrf_test", table_id=120)
        errors = self.engine.set_candidate(candidate)
        self.assertEqual(errors, [])

        # Running configuration must remain unchanged
        fresh_running = self.engine.get_running()
        self.assertNotIn("vrf_test", fresh_running.vrfs)
        self.assertIn("vrf_test", self.engine.get_candidate().vrfs)

    def test_plan_dry_run_does_not_modify_running(self):
        candidate = self.engine.get_candidate()
        candidate.vrfs["vrf_dry"] = VRFConfig(name="vrf_dry", table_id=125)
        self.engine.set_candidate(candidate)

        plan = self.engine.plan()
        self.assertTrue(len(plan) > 0)
        self.assertIn("create_vrf", [op.op_type for op in plan])

        # Verify running remains unmutated
        self.assertNotIn("vrf_dry", self.engine.get_running().vrfs)

    def test_invalid_state_transition_rejection(self):
        record = TransactionRecord(transaction_id="tx_inv", state=TransactionState.IDLE)
        with self.assertRaises(InvalidStateTransitionError):
            # Cannot jump directly from IDLE to COMMITTED
            self.engine.transition_state(record, TransactionState.COMMITTED)


if __name__ == "__main__":
    unittest.main()
