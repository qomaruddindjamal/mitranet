# MitraNet 1.0.2 — Phase 1A Linux Network Interface Discovery & State

MitraNet membaca kondisi network interface aktual dari kernel Linux melalui backend runtime, terisolasi dari model konfigurasi `config.json`.

---

## 1. Arsitektur Komponen

```text
  CLI Layer:
    mitranet interface list [--json]
    mitranet interface show <name> [--json]
             │
             ▼
  InterfaceDiscoveryService (`core/network/discovery.py`)
             │
             ▼
  LinuxNetworkBackend (`core/network/backend/linux.py`)
             │
             ├───────────────────────┬────────────────────────┐
             ▼                       ▼                        ▼
       rtnetlink / iproute2     /sys/class/net/          ethtool (optional)
        (`ip -j link/addr`)        `carrier`, `stats`
             │                       │                        │
             └───────────────────────┼────────────────────────┘
                                     │
                                     ▼
                            Linux Kernel (6.x)
                                     │
                             Physical / Virtual NICs
```

---

## 2. Model Data Runtime (`NetworkInterfaceState`)

Diimplementasikan menggunakan Pydantic v2 di `core/network/models.py`:
- `name`: Nama device netlink (e.g., `eth0`, `ens18`, `lo`)
- `index`: Kernel `ifindex`
- `type`: Tipe link (`ether`, `loopback`, `vlan`, `bridge`, `bond`, `wireguard`)
- `mac_address`: Alamat hardware MAC (format `52:54:00:12:34:56`)
- `mtu`: Ukuran MTU
- `admin_state`: State administratif dari flag kernel (`UP` / `DOWN`)
- `oper_state`: State operasional kernel (`UP`, `DOWN`, `UNKNOWN`, `DORMANT`, `LOWERLAYERDOWN`)
- `carrier`: Kehadiran sinyal carrier fisik dari `/sys/class/net/<iface>/carrier` (`True`/`False`/`None`)
- `flags`: Flags kernel lengkap (`["UP", "BROADCAST", "MULTICAST", "LOWER_UP"]`)
- `ipv4_addresses`: Daftar alamat IPv4 beserta prefix CIDR
- `ipv6_addresses`: Daftar alamat IPv6 beserta prefix CIDR
- `statistics`: Counter bytes, packets, errors, dan dropped untuk RX dan TX.

---

## 3. Isolasi & Keamanan

1. **Non-Root Execution**: Discovery bersifat read-only dan dapat dieksekusi tanpa hak istimewa `sudo` atau root.
2. **Tidak Bergantung pada Nama Statis**: Discovery bekerja secara dinamis pada berbagai skema penamaan interface Linux (`ethX`, `enpXsY`, `ensX`, `enoX`, `lo`, `brX`, dll.).
3. **Pemisahan Desired vs Actual State**:
   - `config.json` = Desired Configuration State
   - `core/network/discovery.py` = Actual Runtime Kernel State
