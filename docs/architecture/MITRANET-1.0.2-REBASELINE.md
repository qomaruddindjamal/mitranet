# MitraNet 1.0.2 Architecture & Rebaseline Specification

MitraNet adalah Network Operating System (NOS) enterprise dan whitebox berbasis Debian GNU/Linux.

---

## 1. Identitas & Standar Arsitektur (MitraNet 1.0.2)

- **Official Product**: MitraNet Network Operating System
- **MitraNet Version**: `1.0.2`
- **Native Canonical Configuration Format**: JSON (`/etc/mitranet/config.json`)
- **Native Canonical Schema**: `MitraNet Configuration Schema v1.0.2` (`mitranet-config-v1.0.2.schema.json`)
- **Underlying Kernel / OS**: Debian 13 (Trixie) / Linux Kernel 6.x

---

## 2. Pemisahan Tegas: Native Configuration vs Migration Layer

MitraNet **bukan** porting FreeBSD atau klon pfSense.

```text
                             MITRANET 1.0.2
                                   │
                         Canonical Configuration
                                   │
                              config.json
                                   │
              ┌────────────────────┴────────────────────┐
              │                                         │
       RUNTIME ENGINE                            MIGRATION ENGINE
              │                                         │
        Linux Backend                             pfSense XML
  (iproute2, nftables, FRR)                             │
              │                                  Parser / Normalizer
         Linux Kernel                                   │
              │                                  Semantic Mapper
           Hardware                                     │
   (x86 Server / Whitebox)                       MitraNet Model (1.0.2)
```

### Aturan Isolasi:
1. **pfSense sebagai Reference & Source**:  
   pfSense hanya berfungsi sebagai format import legacy yang diproses melalui:
   - `core/migration/pfsense_xml_parser.py`
   - `core/migration/pfsense_normalizer.py`
   - `core/migration/pfsense_mapper.py`
   - `tests/fixtures/pfsense/`
2. **Native Runtime Cleanliness**:  
   Konfigurasi aktif MitraNet (`running.json`, `candidate.json`, `config.json`) tidak memuat root key `pfsense`, dependensi sintaks BSD, maupun abstraksi pfSense.
3. **Canonical Format**:  
   Format native MitraNet adalah JSON terstruktur yang memetakan seluruh entitas jaringan ke abstraksi Linux Netlink, nftables, dan daemons modern.

---

## 3. Kesiapan Menuju Phase 1 (Linux Networking Core)

Dengan selesainya Phase 0.6, seluruh pipeline konfigurasi, schema, validasi semantik, candidate/running transaction, snapshot sequential, dan test suite telah bersih dan siap untuk menggerakkan kernel Linux pada **Phase 1 — Linux Networking Core**:
- Interface Discovery (`/sys/class/net`, `ip -j link`)
- Netlink Driver Implementation via `pyroute2`
- Linux Bridge, VLAN (802.1Q), Bonding/LACP, VRF
- Routing FIB (Kernel Routing Table, Default Gateway, Static Routes)
- Interface Statistics & Operational Telemetry
