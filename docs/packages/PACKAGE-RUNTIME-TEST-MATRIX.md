# MitraNet Rinjani 1.0.2 — Package Runtime Test Matrix

## Phase 2C Runtime Test Matrix

| Test Suite | Components Tested | Execution Target | Test Count | Result |
|---|---|---|---|---|
| `test_native_baseline.py` | Config schema, isolation, zero pfSense keys | Host / VM | 3 tests | **PASS** |
| `test_config_model.py` | MitraNet configuration Pydantic models | Host / VM | 4 tests | **PASS** |
| `test_candidate_running.py` | Candidate configuration & persistence | Host / VM | 5 tests | **PASS** |
| `test_network_discovery.py` | Linux interface discovery | VM Kernel | 6 tests | **PASS** |
| `test_interface_config.py` | MTU, MAC, IP address assignment | VM Kernel | 12 tests | **PASS** |
| `test_routing_core.py` | Linux kernel routing operations | VM Kernel | 10 tests | **PASS** |
| `test_vlan.py` | 802.1Q VLAN interface creation & deletion | VM Kernel | 8 tests | **PASS** |
| `test_bridge.py` | Linux bridge creation & member attachment | VM Kernel | 8 tests | **PASS** |
| `test_bonding.py` | Linux bonding / LACP aggregation | VM Kernel | 9 tests | **PASS** |
| `test_vrf.py` | Linux VRF table creation & attachment | VM Kernel | 10 tests | **PASS** |
| `test_transaction.py` | Phase 1F Transaction Engine rollback | VM Kernel | 15 tests | **PASS** |
| `test_native_services.py` | FilterLog, FilterDNS, DHCP leases, metrics, reloader | Host / VM | 5 tests | **PASS** |
| **Complete Full Suite** | **All Phase 0.5 – Phase 2C tests combined** | **Live Debian VM** | **168 tests** | **168 / 168 PASS** |
