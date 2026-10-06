# OpenNetLinux License Audit & IP Compliance

## 1. Compliance Principles
MitraNet adopts a strict **Functional Reimplementation** model. No proprietary or restrictive source code is imported into MitraNet repository.

## 2. License Analysis by Upstream ONL Subsystem

| Subsystem | Primary License | Permissibility for MitraNet | Compliance Action |
| :--- | :--- | :--- | :--- |
| **ONIE Bootloader** | GPL-2.0-or-later | Permissible (Independent Bootloader) | Shipped as independent installer image; no static linking with MitraNet daemons. |
| **ONLP (Core Library)** | Eclipse Public License 1.0 (EPL-1.0) / Apache-2.0 | Permissible | Kept as dynamically loaded C library (`libonlp.so`); API invoked through clean C FFI boundaries. |
| **Kernel Platform Drivers** | GPL-2.0-only (Linux Kernel Module) | Permissible | Distributed as separate DKMS modules or standard out-of-tree GPL modules matching kernel license. |
| **Broadcom OpenNSL** | Proprietary Broadcom Click-through EULA | **RESTRICTED** | **Excluded from MitraNet Core**. Replaced with Linux mainline `switchdev` drivers (e.g., Mellanox mlxsw) or standard open SAI layers. |
| **SONiC / SAI Headers** | Apache-2.0 | Permissible | Used as behavioral and structural specification. |

## 3. Safe Cleanroom Architecture
- MitraNet Python and Rust services communicate with hardware peripherals via standard Linux `/sys` and `/dev` kernel interfaces.
- Proprietary vendor SDKs are never packaged into the base ISO.
- In-tree Linux kernel drivers (`mlxsw`, `prestera`, `dsa`) are prioritized over vendor out-of-tree SDKs.
