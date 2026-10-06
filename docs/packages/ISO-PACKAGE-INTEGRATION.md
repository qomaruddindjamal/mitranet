# MitraNet Rinjani 1.0.2 — ISO Package Integration Architecture

## 1. Overview

Phase 2C integrates the Phase 2B native Debian package repository into the MitraNet installer and ISO architecture. The integration guarantees full offline operability, ensuring that a system installed from the MitraNet ISO has access to the signed package repository without requiring any Internet connection.

---

## 2. ISO Filesystem Structure

The ISO image (`build/MitraNet-Rinjani-1.0.2-amd64.iso`) embeds the repository directly at the root `/repository` directory alongside standard Debian Netinst installer paths:

```
/
├── .disk/
├── isolinux/
├── install.amd/
├── boot/
├── mitranet/
│   ├── mitranet.tar.gz
│   └── mitranet.asc            <-- MitraNet Release GPG Public Key
├── repository/                 <-- Native MitraNet Offline Repository
│   ├── apt-ftparchive.conf
│   ├── dists/
│   │   └── rinjani/
│   │       ├── InRelease       <-- Signed inline metadata
│   │       ├── Release
│   │       ├── Release.gpg     <-- Detached GPG signature
│   │       └── main/
│   │           ├── binary-all/ (Packages, Packages.gz, Packages.xz)
│   │           └── binary-amd64/ (Packages, Packages.gz, Packages.xz)
│   └── pool/
│       └── main/m/             <-- MitraNet and offline dependency deb packages
└── preseed.cfg
```

---

## 3. Package Pool Inventory

The offline repository pool (`/repository/pool/main/m/`) contains:

1. **Native MitraNet Packages (Debian 13 Trixie Architecture: all):**
   - `mitranet-core_1.0.2-1~deb13u1_all.deb`
   - `mitranet-config-engine_1.0.2-1~deb13u1_all.deb`
   - `mitranet-config-engine_1.0.2-2~deb13u1_all.deb` (Upgrade validation artifact)
   - `mitranet-network-engine_1.0.2-1~deb13u1_all.deb`
   - `mitranet-gateway-monitor_1.0.2-1~deb13u1_all.deb`

2. **Offline Runtime Dependencies:**
   - `fping_5.1-1_amd64.deb`
   - `libyaml-0-2_0.2.5-2_amd64.deb`
   - `python3-pydantic_2.10.6-2_amd64.deb`
   - `python3-pydantic-core_2.27.2-3+b1_amd64.deb`
   - `python3-jsonschema_4.19.2-6_all.deb`
   - `python3-yaml_6.0.2-1+b2_amd64.deb`
   - Complete Python dependency tree for schema and data validation.

---

## 4. Installer Preseed Automation

The preseed configuration (`preseed.cfg`) provisions the system during installation:

```sh
d-i preseed/late_command string \
    in-target mkdir -p /mitranet /etc/mitranet /var/local/repository; \
    mount /dev/sr0 /media || mount /dev/cdrom /media || true; \
    tar -xzf /media/mitranet/mitranet.tar.gz -C /target/mitranet/ || true; \
    cp -r /media/repository/* /target/var/local/repository/ || true; \
    cp /media/mitranet/mitranet.asc /target/etc/apt/trusted.gpg.d/mitranet.asc || true; \
    umount /media || true; \
    echo 'deb [arch=all,amd64 signed-by=/etc/apt/trusted.gpg.d/mitranet.asc] file:/var/local/repository rinjani main' > /target/etc/apt/sources.list.d/mitranet-offline.list; \
    in-target apt-get update -o Dir::Etc::sourcelist="sources.list.d/mitranet-offline.list" -o Dir::Etc::sourceparts="-"; \
    in-target apt-get install -y --no-install-recommends mitranet-config-engine mitranet-network-engine mitranet-core mitranet-gateway-monitor; \
    ...
```

---

## 5. Security & Isolation

- **GPG Key Management:**
  - Public verification key: Installed to `/etc/apt/trusted.gpg.d/mitranet.asc`.
  - Private key: External only. Never packaged or copied into ISO or root filesystem.
- **Repository Trust:**
  - `InRelease` signed with GPG key `9F2BF0892632D17F72741984A04EBAE6AE935D0A` (`support@mitranet.id`).
- **Zero Binary Contamination:**
  - Verified 0 FreeBSD / pfSense binaries or shared libraries.
