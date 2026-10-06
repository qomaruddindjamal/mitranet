"""
MitraNet Network Dependency Planner.
Phase 1F: Orders network configuration operations in topological dependency order.

Strict Ordering:
1. Physical Lower-Level Netdevs (UP, MTU, MAC)
2. VLAN Interfaces
3. Bridges & Member Attachments
4. Bonds & Slaves (LACP / active-backup)
5. VRF Instances & Enslaved Interfaces
6. IP Address Assignments
7. Routing Table Rules & Static Routes
"""

from typing import List, Dict, Any, Optional
from mitranet.core.config.model import MitraNetConfig
from mitranet.core.transaction.models import NetworkOperation


class DependencyPlanner:
    """Builds a dependency-ordered execution plan from desired MitraNetConfig diff against running."""

    @classmethod
    def generate_plan(
        cls,
        candidate: MitraNetConfig,
        running: Optional[MitraNetConfig] = None,
    ) -> List[NetworkOperation]:
        """
        Calculates diff and produces an ordered sequence of NetworkOperations.
        Inverse operations are recorded with each forward step for exact rollback.
        """
        operations: List[NetworkOperation] = []
        op_idx = 1

        running_interfaces = running.interfaces if running else {}
        running_vlans = running.vlans if running else {}
        running_bridges = running.bridges if running else {}
        running_bonds = running.bonds if running else {}
        running_vrfs = running.vrfs if running else {}
        running_routes = {
            (r.destination, r.gateway, r.interface): r for r in (running.routing.static_routes if running else [])
        }

        # 1. Base Interfaces (physical / loopback / etc)
        for iface_key, iface in candidate.interfaces.items():
            run_iface = running_interfaces.get(iface_key)
            dev = iface.device

            # Admin state change
            if run_iface is None or run_iface.enabled != iface.enabled:
                if iface.enabled:
                    operations.append(
                        NetworkOperation(
                            op_id=f"op_{op_idx:04d}",
                            op_type="set_link_up",
                            target=dev,
                            params={"interface": dev},
                            inverse_op="set_link_down" if (run_iface and not run_iface.enabled) else "set_link_down",
                            inverse_params={"interface": dev},
                        )
                    )
                    op_idx += 1
                else:
                    operations.append(
                        NetworkOperation(
                            op_id=f"op_{op_idx:04d}",
                            op_type="set_link_down",
                            target=dev,
                            params={"interface": dev},
                            inverse_op="set_link_up",
                            inverse_params={"interface": dev},
                        )
                    )
                    op_idx += 1

            # MTU change
            if run_iface is None or run_iface.mtu != iface.mtu:
                if iface.mtu and iface.mtu != 1500:
                    old_mtu = run_iface.mtu if run_iface else 1500
                    operations.append(
                        NetworkOperation(
                            op_id=f"op_{op_idx:04d}",
                            op_type="set_mtu",
                            target=dev,
                            params={"interface": dev, "mtu": iface.mtu},
                            inverse_op="set_mtu",
                            inverse_params={"interface": dev, "mtu": old_mtu},
                        )
                    )
                    op_idx += 1

        # 2. 802.1Q VLANs
        for vkey, vlan in candidate.vlans.items():
            if vkey not in running_vlans or running_vlans[vkey] != vlan:
                parent_dev = vlan.parent
                if parent_dev in candidate.interfaces:
                    parent_dev = candidate.interfaces[parent_dev].device
                vlan_dev = f"{parent_dev}.{vlan.id}"
                operations.append(
                    NetworkOperation(
                        op_id=f"op_{op_idx:04d}",
                        op_type="create_vlan",
                        target=vlan_dev,
                        params={"name": vlan_dev, "parent": parent_dev, "vlan_id": vlan.id},
                        inverse_op="delete_vlan",
                        inverse_params={"name": vlan_dev},
                    )
                )
                op_idx += 1

        # 3. Linux Bridges
        for bkey, bridge in candidate.bridges.items():
            if bkey not in running_bridges:
                br_name = bkey
                operations.append(
                    NetworkOperation(
                        op_id=f"op_{op_idx:04d}",
                        op_type="create_bridge",
                        target=br_name,
                        params={"name": br_name, "stp": bridge.stp},
                        inverse_op="delete_bridge",
                        inverse_params={"name": br_name},
                    )
                )
                op_idx += 1
                for member in bridge.members:
                    mdev = candidate.interfaces[member].device if member in candidate.interfaces else member
                    operations.append(
                        NetworkOperation(
                            op_id=f"op_{op_idx:04d}",
                            op_type="add_bridge_port",
                            target=mdev,
                            params={"bridge": br_name, "port": mdev},
                            inverse_op="remove_bridge_port",
                            inverse_params={"bridge": br_name, "port": mdev},
                        )
                    )
                    op_idx += 1

        # 4. Bonding
        for bond_key, bond in candidate.bonds.items():
            if bond_key not in running_bonds:
                bname = bond_key
                operations.append(
                    NetworkOperation(
                        op_id=f"op_{op_idx:04d}",
                        op_type="create_bond",
                        target=bname,
                        params={"name": bname, "mode": bond.mode},
                        inverse_op="delete_bond",
                        inverse_params={"name": bname},
                    )
                )
                op_idx += 1
                for slave in bond.slaves:
                    sdev = candidate.interfaces[slave].device if slave in candidate.interfaces else slave
                    operations.append(
                        NetworkOperation(
                            op_id=f"op_{op_idx:04d}",
                            op_type="add_bond_slave",
                            target=sdev,
                            params={"bond": bname, "slave": sdev},
                            inverse_op="remove_bond_slave",
                            inverse_params={"bond": bname, "slave": sdev},
                        )
                    )
                    op_idx += 1

        # 5. VRFs
        for vrf_name, vrf in candidate.vrfs.items():
            if vrf_name not in running_vrfs or running_vrfs[vrf_name] != vrf:
                operations.append(
                    NetworkOperation(
                        op_id=f"op_{op_idx:04d}",
                        op_type="create_vrf",
                        target=vrf_name,
                        params={"name": vrf_name, "table": vrf.table_id},
                        inverse_op="delete_vrf",
                        inverse_params={"name": vrf_name},
                    )
                )
                op_idx += 1
                for iface_ref in vrf.interfaces:
                    idevice = candidate.interfaces[iface_ref].device if iface_ref in candidate.interfaces else iface_ref
                    operations.append(
                        NetworkOperation(
                            op_id=f"op_{op_idx:04d}",
                            op_type="add_vrf_interface",
                            target=idevice,
                            params={"vrf": vrf_name, "interface": idevice},
                            inverse_op="remove_vrf_interface",
                            inverse_params={"vrf": vrf_name, "interface": idevice},
                        )
                    )
                    op_idx += 1

        # 6. IP Addresses
        for iface_key, iface in candidate.interfaces.items():
            run_iface = running_interfaces.get(iface_key)
            dev = iface.device
            cand_ipv4 = iface.ipv4
            run_ipv4 = run_iface.ipv4 if run_iface else None

            if cand_ipv4 and cand_ipv4.mode == "static" and cand_ipv4.address and cand_ipv4.prefix is not None:
                cand_cidr = f"{cand_ipv4.address}/{cand_ipv4.prefix}"
                run_cidr = f"{run_ipv4.address}/{run_ipv4.prefix}" if (run_ipv4 and run_ipv4.mode == "static" and run_ipv4.address) else None
                if cand_cidr != run_cidr:
                    operations.append(
                        NetworkOperation(
                            op_id=f"op_{op_idx:04d}",
                            op_type="add_address",
                            target=dev,
                            params={"interface": dev, "cidr": cand_cidr},
                            inverse_op="remove_address",
                            inverse_params={"interface": dev, "cidr": cand_cidr},
                        )
                    )
                    op_idx += 1

        # 7. Static Routes
        for route in candidate.routing.static_routes:
            key = (route.destination, route.gateway, route.interface)
            if key not in running_routes:
                operations.append(
                    NetworkOperation(
                        op_id=f"op_{op_idx:04d}",
                        op_type="add_route",
                        target=route.destination,
                        params={
                            "destination": route.destination,
                            "gateway": route.gateway,
                            "interface": route.interface,
                            "metric": route.metric,
                        },
                        inverse_op="remove_route",
                        inverse_params={
                            "destination": route.destination,
                            "gateway": route.gateway,
                            "interface": route.interface,
                        },
                    )
                )
                op_idx += 1

        return operations
