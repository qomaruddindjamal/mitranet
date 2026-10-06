# MitraNet Rinjani 1.0.2 — Phase 1B Linux Interface Configuration

MitraNet Rinjani 1.0.2 mengimplementasikan mutasi dan konfigurasi network interface aktual pada kernel Linux melalui backend runtime dengan arsitektur terisolasi, verifikasi pasca-konfigurasi (post-write verification), dan mekanisme pengamanan ketat (security validation & loopback safety).

---

## 1. Identitas Sistem Operasi

- **OS Name**: MitraNet
- **OS Code Name**: Rinjani
- **Release Version**: 1.0.2
- **Pretty Name**: MitraNet Rinjani 1.0.2
- **Previous Release**: Rinjani 1.0.1

---

## 2. Arsitektur Komponen

```text
  CLI Layer:
    mitranet interface up <interface>
    mitranet interface down <interface>
    mitranet interface set <interface> mtu <value>
    mitranet interface set <interface> mac <mac_address>
    mitranet interface address add <interface> <CIDR>
    mitranet interface address remove <interface> <CIDR>
             │
             ▼
  InterfaceConfigurationService (`core/network/config_service.py`)
    ├── InterfaceConfigValidator (`core/network/validator.py`)
    └── Loopback & Management Safety Guards
             │
             ▼
  LinuxNetworkBackend (`core/network/backend/linux.py`)
    (Subprocess safe execution: shell=False, timeout, returncode check)
             │
             ▼
     iproute2 (`ip link`, `ip address`)
             │
             ▼
      Linux Kernel (6.x)
             │
             ▼
  Post-Write Verification via InterfaceDiscoveryService (`core/network/discovery.py`)
             │
             ▼
  Result Confirmation / Verification Failure Alert
```

---

## 3. Siklus Eksekusi: Validate → Capture → Apply → Discover → Verify

Setiap operasi mutasi pada interface mengikuti alur deterministik:
1. **Validate**:
   - Validasi nama interface terhadap regex `^[a-zA-Z0-9_.-]{1,15}$`.
   - Pencegahan injeksi shell (karakter `;`, `&`, `|`, `$`, `` ` ``, `>`, `<`, spasi, newline langsung ditolak dengan `NetworkSecurityError`).
   - Validasi MTU dalam batas range aman (68 s/d 9216).
   - Validasi format MAC address IEEE 802 (`XX:XX:XX:XX:XX:XX`).
   - Validasi IPv4/IPv6 prefix CIDR menggunakan modul Python `ipaddress`.
2. **Safety Check**:
   - Loopback safety protection: `lo` dilarang dinonaktifkan (`down`), MAC `lo` tidak boleh diubah, dan `127.0.0.1/8` serta `::1/128` dilarang dihapus.
   - Pengecekan duplikasi alamat IP sebelum apply.
   - Pengecekan keberadaan alamat IP sebelum remove.
3. **Capture State**:
   - Status interface sebelum perubahan dicatat melalui `InterfaceDiscoveryService`.
4. **Apply**:
   - Eksekusi perintah `iproute2` langsung via `LinuxNetworkBackend` tanpa shell (`shell=False`).
5. **Discover & Verify**:
   - Membaca ulang state aktual dari kernel melalui `InterfaceDiscoveryService`.
   - Jika state aktual tidak sesuai target yang diinginkan, operasi memicu `VerificationFailureError`.

---

## 4. Perintah CLI

### 4.1 Interface Up & Down
```bash
# Menyalakan interface
mitranet interface up eth2

# Mematikan interface
mitranet interface down eth2
```

### 4.2 Mengubah MTU
```bash
# Mengatur MTU menjadi 9000 (Jumbo frame)
mitranet interface set eth2 mtu 9000
```

### 4.3 Mengubah MAC Address
```bash
# Mengatur MAC address hardware
mitranet interface set eth2 mac 52:54:00:12:34:99
```

### 4.4 Menambah & Menghapus Alamat IP
```bash
# Menambahkan IPv4
mitranet interface address add eth2 192.0.2.10/24

# Menambahkan IPv6
mitranet interface address add eth2 2001:db8:100::10/64

# Menghapus IPv4
mitranet interface address remove eth2 192.0.2.10/24

# Menghapus IPv6
mitranet interface address remove eth2 2001:db8:100::10/64
```

---

## 5. Keamanan & Integritas Kernel

1. **Anti Command Injection**: Seluruh perintah dijalankan sebagai list argumen pada `subprocess.run(shell=False)` dengan timeout 10 detik. Upaya seperti `eth0;whoami` atau `$(whoami)` dicegah pada tahap validasi nama.
2. **Loopback Protection**: Sistem menjamin `lo` tetap beroperasi dan alamat loopback fundamental tidak terhapus.
3. **Pemisahan Model**:
   - `config.json` = Desired Configuration State
   - Linux Kernel = Actual Runtime State
