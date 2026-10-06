"""
MitraNet Routing Discovery Service (Phase 1C).
Queries kernel routing tables via backend, parses raw iproute2 JSON records,
and produces strongly-typed RouteState models.
"""

import logging
from typing import List, Optional, Dict, Any
from mitranet.core.network.models import RouteState
from mitranet.core.network.backend import NetworkBackend, LinuxNetworkBackend
from mitranet.core.network.validator import RouteValidator

logger = logging.getLogger("mitranet.network.routing_discovery")


class RouteDiscoveryService:
    """Discovers, filters, and standardizes kernel routing table entries."""

    def __init__(self, backend: Optional[NetworkBackend] = None):
        self.backend = backend or LinuxNetworkBackend()

    def _normalize_destination(self, dst_raw: Optional[str], family: str) -> str:
        """Normalizes 'default' or bare IP addresses into canonical CIDR notation."""
        if not dst_raw or dst_raw.lower() == "default":
            return "::/0" if family == "inet6" else "0.0.0.0/0"
        if "/" not in dst_raw:
            return f"{dst_raw}/128" if family == "inet6" else f"{dst_raw}/32"
        return dst_raw

    def _parse_route_item(self, item: Dict[str, Any], family: str, table: int) -> RouteState:
        """Constructs RouteState from iproute2 json object."""
        dst_raw = item.get("dst")
        canonical_dst = self._normalize_destination(dst_raw, family)

        # Gateway / Next-hop
        gateway = item.get("gateway")
        # Egress interface
        interface = item.get("dev")
        # Priority / Metric
        metric = item.get("metric")
        if metric is not None:
            try:
                metric = int(metric)
            except (ValueError, TypeError):
                metric = None

        # Table from item or parameter
        item_table = item.get("table", table)
        if isinstance(item_table, str):
            try:
                item_table = int(item_table)
            except ValueError:
                item_table = 254
        elif item_table is None:
            item_table = table

        protocol = item.get("protocol")
        scope = item.get("scope")
        route_type = item.get("type", "unicast")
        prefsrc = item.get("prefsrc")
        flags = item.get("flags", [])
        if not isinstance(flags, list):
            flags = [str(flags)]

        return RouteState(
            family="inet6" if family == "inet6" else "inet",
            destination=canonical_dst,
            gateway=gateway,
            interface=interface,
            table=item_table,
            metric=metric,
            protocol=protocol,
            scope=scope,
            type=route_type,
            prefsrc=prefsrc,
            flags=flags,
        )

    def get_routes(self, family: Optional[str] = None, table: Optional[int] = 254) -> List[RouteState]:
        """
        Discovers routes from kernel.
        family: 'inet', 'inet6', or None for both.
        table: integer routing table ID (default 254=main). None for all tables if supported.
        """
        routes: List[RouteState] = []

        if family in (None, "inet", "ipv4"):
            raw_v4 = self.backend.get_routes(family="inet", table=table)
            for item in raw_v4:
                try:
                    routes.append(self._parse_route_item(item, family="inet", table=table or 254))
                except Exception as e:
                    logger.warning("Error parsing IPv4 route entry %s: %s", item, e)

        if family in (None, "inet6", "ipv6"):
            raw_v6 = self.backend.get_routes(family="inet6", table=table)
            for item in raw_v6:
                try:
                    routes.append(self._parse_route_item(item, family="inet6", table=table or 254))
                except Exception as e:
                    logger.warning("Error parsing IPv6 route entry %s: %s", item, e)

        return routes

    def get_ipv4_routes(self, table: Optional[int] = 254) -> List[RouteState]:
        """Discovers IPv4 routing table entries."""
        return self.get_routes(family="inet", table=table)

    def get_ipv6_routes(self, table: Optional[int] = 254) -> List[RouteState]:
        """Discovers IPv6 routing table entries."""
        return self.get_routes(family="inet6", table=table)

    def get_default_routes(self, family: Optional[str] = None, table: Optional[int] = 254) -> List[RouteState]:
        """Returns only default routes (0.0.0.0/0 or ::/0)."""
        all_routes = self.get_routes(family=family, table=table)
        return [r for r in all_routes if r.is_default]
