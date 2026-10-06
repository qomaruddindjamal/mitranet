"""
MitraNet Network Transaction Snapshot Capture and Restoration Engine.
Phase 1F: Captures full link, address, route, VLAN, bridge, bond, and VRF states with cryptographic hash integrity.
"""

import os
import json
import hashlib
from datetime import datetime, timezone
from typing import Dict, Any, List, Optional
from mitranet.core.network.backend import NetworkBackend, LinuxNetworkBackend
from mitranet.core.network.discovery import InterfaceDiscoveryService
from mitranet.core.network.routing_discovery import RouteDiscoveryService
from mitranet.core.network.vlan import VlanService
from mitranet.core.network.bridge import BridgeService
from mitranet.core.network.bonding import BondService
from mitranet.core.network.vrf import VRFService
from mitranet.core.transaction.models import NetworkStateSnapshot
from mitranet.core.transaction.exceptions import SnapshotIntegrityError


class NetworkSnapshotManager:
    """Manages taking network snapshots, verifying their integrity, and persisting them."""

    def __init__(
        self,
        snapshot_dir: str = "/var/lib/mitranet/snapshots",
        backend: Optional[NetworkBackend] = None,
    ):
        self.snapshot_dir = snapshot_dir
        self.backend = backend or LinuxNetworkBackend()
        self.discovery = InterfaceDiscoveryService(backend=self.backend)
        self.route_discovery = RouteDiscoveryService(backend=self.backend)
        self.vlan_service = VlanService(backend=self.backend)
        self.bridge_service = BridgeService(backend=self.backend)
        self.bond_service = BondService(backend=self.backend)
        self.vrf_service = VRFService(backend=self.backend)
        os.makedirs(self.snapshot_dir, exist_ok=True)

    @staticmethod
    def compute_hash(data: Dict[str, Any]) -> str:
        """Computes deterministic SHA256 hash of structured data."""
        serialized = json.dumps(data, sort_keys=True)
        return hashlib.sha256(serialized.encode("utf-8")).hexdigest()

    def capture_snapshot(
        self,
        transaction_id: str,
        candidate_config: Optional[Dict[str, Any]] = None,
        running_config: Optional[Dict[str, Any]] = None,
    ) -> NetworkStateSnapshot:
        """Captures complete current network state across all subsystems."""
        timestamp = datetime.now(timezone.utc).isoformat()
        snapshot_id = f"snap_{datetime.now(timezone.utc).strftime('%Y%m%d_%H%M%S')}_{transaction_id[:8]}"

        # 1. Links & Slaves
        detailed_links = self.backend.get_detailed_links()
        slaves_map: Dict[str, List[str]] = {}
        for link in detailed_links:
            master = link.get("master")
            ifname = link.get("ifname")
            if master and ifname:
                slaves_map.setdefault(master, []).append(ifname)

        # 2. Addresses
        addr_info = self.backend.get_addr_info()
        addrs_map: Dict[str, List[str]] = {}
        for ai in addr_info:
            dev = ai.get("dev") or ai.get("ifname")
            local = ai.get("local")
            prefixlen = ai.get("prefixlen")
            if dev and local:
                addrs_map.setdefault(dev, []).append(f"{local}/{prefixlen}")

        # 3. Routes
        routes_v4 = self.backend.get_routes(family="inet", table=254)
        routes_v6 = self.backend.get_routes(family="inet6", table=254)

        # 4. Virtual Devices
        vlans = [v.model_dump() for v in self.vlan_service.discover_vlans()]
        bridges = [b.model_dump() for b in self.bridge_service.discover_bridges()]
        bonds = [b.model_dump() for b in self.bond_service.discover_bonds()]
        vrfs = [v.model_dump() for v in self.vrf_service.discover_vrfs()]

        payload_for_hash = {
            "links": detailed_links,
            "addrs": addrs_map,
            "routes_v4": routes_v4,
            "routes_v6": routes_v6,
            "vlans": vlans,
            "bridges": bridges,
            "bonds": bonds,
            "vrfs": vrfs,
            "candidate": candidate_config,
            "running": running_config,
        }
        state_hash = self.compute_hash(payload_for_hash)

        snap = NetworkStateSnapshot(
            snapshot_id=snapshot_id,
            timestamp=timestamp,
            transaction_id=transaction_id,
            state_hash=state_hash,
            interfaces=detailed_links,
            slaves=slaves_map,
            addresses=addrs_map,
            routes_v4=routes_v4,
            routes_v6=routes_v6,
            vlans=vlans,
            bridges=bridges,
            bonds=bonds,
            vrfs=vrfs,
            candidate_config=candidate_config,
            running_config=running_config,
        )

        # Persist snapshot
        snap_file = os.path.join(self.snapshot_dir, f"{snapshot_id}.json")
        with open(snap_file, "w", encoding="utf-8") as f:
            f.write(snap.model_dump_json(indent=2))

        return snap

    def load_snapshot(self, snapshot_id: str) -> NetworkStateSnapshot:
        """Loads and verifies cryptographic integrity of snapshot file."""
        if not snapshot_id.endswith(".json"):
            filename = f"{snapshot_id}.json"
        else:
            filename = snapshot_id
        snap_file = os.path.join(self.snapshot_dir, filename)
        if not os.path.exists(snap_file):
            raise SnapshotIntegrityError(f"Snapshot file not found: {snap_file}")

        try:
            with open(snap_file, "r", encoding="utf-8") as f:
                data = json.load(f)
            snap = NetworkStateSnapshot.model_validate(data)
        except Exception as e:
            raise SnapshotIntegrityError(f"Corrupt snapshot JSON: {e}")

        payload_for_hash = {
            "links": snap.interfaces,
            "addrs": snap.addresses,
            "routes_v4": snap.routes_v4,
            "routes_v6": snap.routes_v6,
            "vlans": snap.vlans,
            "bridges": snap.bridges,
            "bonds": snap.bonds,
            "vrfs": snap.vrfs,
            "candidate": snap.candidate_config,
            "running": snap.running_config,
        }
        recomputed = self.compute_hash(payload_for_hash)
        if recomputed != snap.state_hash:
            raise SnapshotIntegrityError(
                f"Snapshot integrity mismatch! Expected {snap.state_hash}, got {recomputed}."
            )
        return snap
