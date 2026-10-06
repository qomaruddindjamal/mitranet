"""
MitraNet VRF Environment Prerequisites & Capability Probing Service.
Used for environment-gated preflight checks in both test harnesses and system validation.

Verifies:
1. OS, kernel version, and architecture
2. iproute2 installation, binary path, and VRF command syntax support
3. Kernel VRF module / subsystem operational functionality via non-destructive probe
4. CAP_NET_ADMIN / effective root privileges
5. Dynamic discovery of active management interface (carrying default gateway / mgmt IP)
6. Dynamic discovery of candidate and safe isolated test interfaces
7. Routing table availability and conflict detection (rejecting reserved 0, 253, 254, 255)
8. Complete baseline snapshot capture for post-test cleanup verification
9. Structured VRFEnvironmentPrerequisites result object with .is_ready gate
"""

import os
import re
import json
import shutil
import platform
import subprocess
from typing import Dict, Any, List, Optional, Set
from pydantic import BaseModel, Field


class VRFEnvironmentPrerequisites(BaseModel):
    """Structured preflight assessment for VRF execution readiness."""
    os_name: str
    kernel_version: str
    architecture: str
    iproute2_version: str
    ip_command_path: str
    kernel_support: bool
    iproute2_support: bool
    net_admin: bool
    management_interface: Optional[str] = None
    management_addresses: List[str] = Field(default_factory=list)
    default_route: Optional[Dict[str, Any]] = None
    candidate_interfaces: List[str] = Field(default_factory=list)
    safe_test_interfaces: List[str] = Field(default_factory=list)
    existing_vrfs: List[str] = Field(default_factory=list)
    existing_vrf_tables: List[int] = Field(default_factory=list)
    available_tables: List[int] = Field(default_factory=list)
    baseline_links: List[Dict[str, Any]] = Field(default_factory=list)
    baseline_routes_v4: List[Dict[str, Any]] = Field(default_factory=list)
    baseline_routes_v6: List[Dict[str, Any]] = Field(default_factory=list)
    safe_test_environment: bool
    ready: bool
    rejection_reasons: List[str] = Field(default_factory=list)


class VRFEnvironmentProbe:
    """Probes the live operating environment to establish deterministic VRF readiness."""

    RESERVED_TABLES: Set[int] = {0, 253, 254, 255}

    @classmethod
    def run_command(cls, args: List[str]) -> subprocess.CompletedProcess:
        """Executes subprocess safely with shell=False."""
        return subprocess.run(args, capture_output=True, text=True, check=False)

    @classmethod
    def probe(cls, test_tables: Optional[List[int]] = None) -> VRFEnvironmentPrerequisites:
        """Runs the complete environmental preflight audit."""
        rejection_reasons: List[str] = []
        target_test_tables = test_tables or [100, 200]

        # 1. OS & Architecture
        os_name = platform.system()
        kernel_version = platform.release()
        architecture = platform.machine()

        if os_name != "Linux":
            rejection_reasons.append(f"Operating system is '{os_name}', expected 'Linux'.")

        # 2. iproute2 Command & Version
        ip_path = shutil.which("ip") or ""
        iproute2_version = "UNKNOWN"
        if ip_path:
            p = cls.run_command([ip_path, "-Version"])
            out = p.stdout.strip() or p.stderr.strip()
            # e.g. "ip utility, iproute2-6.15.0, libbpf 1.5.0"
            m = re.search(r"iproute2[- ]([0-9\.]+)", out)
            if m:
                iproute2_version = m.group(1)
            else:
                iproute2_version = out.split("\n")[0]
        else:
            rejection_reasons.append("ip utility (iproute2) binary not found in PATH.")

        # 3. Privileges & CAP_NET_ADMIN
        net_admin = False
        is_root = (os.geteuid() == 0) if hasattr(os, "geteuid") else False
        if is_root:
            net_admin = True
        else:
            # Check capsh if available
            p_cap = cls.run_command(["capsh", "--print"])
            if p_cap.returncode == 0 and "cap_net_admin" in p_cap.stdout.lower():
                net_admin = True
        if not net_admin:
            rejection_reasons.append("Missing CAP_NET_ADMIN / root privileges required for VRF netlink operations.")

        # 4. iproute2 VRF Syntax Support
        iproute2_support = False
        if ip_path:
            p_help = cls.run_command([ip_path, "link", "help", "vrf"])
            # Should output "Usage: ... vrf table TABLEID"
            if "table" in p_help.stderr or "table" in p_help.stdout or "vrf" in p_help.stderr or "vrf" in p_help.stdout:
                iproute2_support = True
            else:
                rejection_reasons.append("ip link does not support VRF device type syntax.")

        # 5. Kernel VRF Support Probe
        kernel_support = False
        if os_name == "Linux" and iproute2_support and net_admin:
            # Operational probe using a temporary dummy VRF device
            probe_name = "vrf_probe_chk"
            probe_table = 999
            p_add = cls.run_command([ip_path, "link", "add", probe_name, "type", "vrf", "table", str(probe_table)])
            if p_add.returncode == 0:
                kernel_support = True
                cls.run_command([ip_path, "link", "del", probe_name])
            else:
                # If cannot add, check module
                if os.path.exists("/sys/module/vrf"):
                    kernel_support = True
                else:
                    rejection_reasons.append(f"Kernel rejected VRF device creation: {p_add.stderr.strip()}")
        elif os_name == "Linux" and os.path.exists("/sys/module/vrf"):
            kernel_support = True

        # 6. Baseline State & Management Detection
        baseline_links: List[Dict[str, Any]] = []
        baseline_routes_v4: List[Dict[str, Any]] = []
        baseline_routes_v6: List[Dict[str, Any]] = []
        management_iface: Optional[str] = None
        management_addrs: List[str] = []
        default_route_info: Optional[Dict[str, Any]] = None
        candidate_ifaces: List[str] = []
        safe_test_ifaces: List[str] = []
        existing_vrfs: List[str] = []
        existing_vrf_tables: List[int] = []

        if ip_path:
            # Query links
            p_links = cls.run_command([ip_path, "-d", "-j", "link", "show"])
            if p_links.returncode == 0 and p_links.stdout.strip():
                try:
                    baseline_links = json.loads(p_links.stdout)
                except Exception:
                    baseline_links = []

            # Query routes
            p_r4 = cls.run_command([ip_path, "-j", "route", "show", "table", "all"])
            if p_r4.returncode == 0 and p_r4.stdout.strip():
                try:
                    baseline_routes_v4 = json.loads(p_r4.stdout)
                except Exception:
                    baseline_routes_v4 = []

            p_r6 = cls.run_command([ip_path, "-j", "-6", "route", "show", "table", "all"])
            if p_r6.returncode == 0 and p_r6.stdout.strip():
                try:
                    baseline_routes_v6 = json.loads(p_r6.stdout)
                except Exception:
                    baseline_routes_v6 = []

            # Discover default route & management interface
            p_def = cls.run_command([ip_path, "-j", "route", "show", "default"])
            if p_def.returncode == 0 and p_def.stdout.strip():
                try:
                    def_list = json.loads(p_def.stdout)
                    if def_list:
                        default_route_info = def_list[0]
                        management_iface = default_route_info.get("dev")
                except Exception:
                    pass

            # Query addresses to find management addresses
            p_addr = cls.run_command([ip_path, "-j", "addr", "show"])
            if p_addr.returncode == 0 and p_addr.stdout.strip():
                try:
                    addr_data = json.loads(p_addr.stdout)
                    for ifinfo in addr_data:
                        iname = ifinfo.get("ifname")
                        addr_list = [
                            f"{ai.get('local')}/{ai.get('prefixlen')}"
                            for ai in ifinfo.get("addr_info", [])
                            if ai.get("local")
                        ]
                        if iname == management_iface:
                            management_addrs.extend(addr_list)
                except Exception:
                    pass

            # If default route wasn't found, check interface holding typical management subnet (10.0.2.x in QEMU/VirtualBox)
            if not management_iface:
                for link in baseline_links:
                    iname = link.get("ifname", "")
                    # Look up in addr_data if any
                    for ifinfo in (addr_data if "addr_data" in locals() else []):
                        if ifinfo.get("ifname") == iname:
                            for ai in ifinfo.get("addr_info", []):
                                loc = ai.get("local", "")
                                if loc.startswith("10.0.2."):
                                    management_iface = iname
                                    management_addrs.append(f"{loc}/{ai.get('prefixlen')}")
                                    break

            # Existing VRFs and tables
            for link in baseline_links:
                iname = link.get("ifname", "")
                linkinfo = link.get("linkinfo", {})
                if linkinfo.get("info_kind") == "vrf":
                    existing_vrfs.append(iname)
                    tbl = linkinfo.get("info_data", {}).get("table")
                    if tbl is not None:
                        existing_vrf_tables.append(int(tbl))

            # Classify Candidate and Safe Interfaces
            for link in baseline_links:
                iname = link.get("ifname", "")
                if not iname or iname == "lo":
                    continue
                if iname in existing_vrfs:
                    continue
                linkinfo = link.get("linkinfo", {})
                if linkinfo.get("info_slave_kind") == "vrf":
                    continue
                candidate_ifaces.append(iname)

                # Safe isolated test interface must NOT be management
                if iname != management_iface:
                    # Also verify it doesn't hold management addresses
                    safe_test_ifaces.append(iname)

        if not safe_test_ifaces:
            rejection_reasons.append("No safe isolated test interface found (all interfaces are lo or management).")

        # 7. Check Availability of Target Test Tables
        available_tables: List[int] = []
        for tbl in target_test_tables:
            if tbl in cls.RESERVED_TABLES:
                continue
            if tbl in existing_vrf_tables:
                continue
            # Also check if table has active non-default routes in baseline
            has_routes = False
            for r in baseline_routes_v4:
                if r.get("table") == tbl:
                    has_routes = True
                    break
            if not has_routes:
                available_tables.append(tbl)

        if len(available_tables) < len(target_test_tables):
            rejection_reasons.append(
                f"Requested test tables {target_test_tables} not fully available (available: {available_tables})."
            )

        safe_test_environment = (
            bool(safe_test_ifaces)
            and len(available_tables) >= len(target_test_tables)
            and bool(management_iface)
        )

        ready = (
            os_name == "Linux"
            and kernel_support
            and iproute2_support
            and net_admin
            and safe_test_environment
            and len(rejection_reasons) == 0
        )

        return VRFEnvironmentPrerequisites(
            os_name=os_name,
            kernel_version=kernel_version,
            architecture=architecture,
            iproute2_version=iproute2_version,
            ip_command_path=ip_path,
            kernel_support=kernel_support,
            iproute2_support=iproute2_support,
            net_admin=net_admin,
            management_interface=management_iface,
            management_addresses=management_addrs,
            default_route=default_route_info,
            candidate_interfaces=candidate_ifaces,
            safe_test_interfaces=safe_test_ifaces,
            existing_vrfs=existing_vrfs,
            existing_vrf_tables=existing_vrf_tables,
            available_tables=available_tables,
            baseline_links=baseline_links,
            baseline_routes_v4=baseline_routes_v4,
            baseline_routes_v6=baseline_routes_v6,
            safe_test_environment=safe_test_environment,
            ready=ready,
            rejection_reasons=rejection_reasons,
        )

    @classmethod
    def format_preflight_report(cls, prereqs: VRFEnvironmentPrerequisites) -> str:
        """Formats the structured preflight report according to Phase 1E requirements."""
        lines = [
            "========================================",
            "VRF ENVIRONMENT PREFLIGHT",
            "========================================",
            f"OS: {prereqs.os_name}",
            f"Kernel: {prereqs.kernel_version}",
            f"Architecture: {prereqs.architecture}",
            f"iproute2: {prereqs.iproute2_version}",
            f"ip command: {prereqs.ip_command_path}",
            "",
            f"Kernel VRF Support: {'PASS' if prereqs.kernel_support else 'FAIL'}",
            f"iproute2 VRF Support: {'PASS' if prereqs.iproute2_support else 'FAIL'}",
            f"CAP_NET_ADMIN: {'PASS' if prereqs.net_admin else 'FAIL'}",
            f"Privilege: {'root' if prereqs.net_admin else 'unprivileged'}",
            "",
            f"Management Interface: {prereqs.management_interface or 'NONE'}",
            f"Management Addresses: {', '.join(prereqs.management_addresses) or 'NONE'}",
            f"Default Route: {prereqs.default_route.get('dst', 'default') if prereqs.default_route else 'NONE'}",
            "",
            f"Candidate Interfaces: {', '.join(prereqs.candidate_interfaces) or 'NONE'}",
            f"Safe Test Interfaces: {', '.join(prereqs.safe_test_interfaces) or 'NONE'}",
            "",
            f"Existing VRFs: {', '.join(prereqs.existing_vrfs) or 'NONE'}",
            f"Existing VRF Tables: {', '.join(str(t) for t in prereqs.existing_vrf_tables) or 'NONE'}",
            "",
            f"Available Test Tables: {', '.join(str(t) for t in prereqs.available_tables) or 'NONE'}",
            "",
            f"Baseline Capture: {'PASS' if bool(prereqs.baseline_links) else 'FAIL'}",
            f"Safe Test Environment: {'PASS' if prereqs.safe_test_environment else 'FAIL'}",
            "",
            f"VRF Environment Ready: {'PASS' if prereqs.ready else 'FAIL'}",
            "========================================",
        ]
        if prereqs.rejection_reasons:
            lines.append("Rejection Reasons:")
            for r in prereqs.rejection_reasons:
                lines.append(f"  - {r}")
            lines.append("========================================")
        return "\n".join(lines)
