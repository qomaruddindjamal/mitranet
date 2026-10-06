# MitraNet Hardware, Virtualization, and Bare-Metal Strategy

## 1. Multi-Target Deployment Model

MitraNet is architected to deploy seamlessly across three distinct computing environments without requiring different codebase forks:

```text
                               MITRANET NOS
                                    │
           ┌────────────────────────┼────────────────────────┐
           ▼                        ▼                        ▼
     TARGET 1: VM            TARGET 2: APPLIANCE      TARGET 3: WHITEBOX
   Virtualization / Cloud     Standard Server / PC      OCP Bare-metal Switch
   (Proxmox, KVM, ESXi, AWS)  (Protectli, Intel NICs)   (Edgecore, Celestica)
```

---

## 2. Target 1: Virtualization & Cloud Strategy
- **Hypervisors**: Proxmox VE, KVM / QEMU, VMware ESXi, Microsoft Hyper-V, AWS / GCP cloud instances.
- **Drivers**:
  - `virtio-net`: Paravirtualized network drivers with multiqueue support.
  - `vmxnet3`: VMware paravirtualized network driver.
- **Hardware Acceleration**:
  - PCI Passthrough (SR-IOV / VT-d): Allows MitraNet to directly bypass hypervisor and speak to physical NICs (e.g. Intel X520/X710/E810) for wire-speed routing.
- **Cloud-Init Integration**: Automatic bootstrap provisioning of management IPs, SSH keys, and initial configuration on first boot.

---

## 3. Target 2: Standard Server & Appliance Strategy
- **Form Factors**:
  - Mini-PC / Edge Firewall appliances (e.g., Protectli Vault, Qotom, Yanling, Topton).
  - 1U / 2U Rackmount servers (Dell PowerEdge, HPE ProLiant, Supermicro).
- **Network Interface Support**:
  - Intel Gigabit & 10G/25G/40G/100G (`e1000e`, `igb`, `ixgbe`, `i40e`, `ice`).
  - Realtek Gigabit (`r8169`).
  - Broadcom NetXtreme (`tg3`, `bnx2x`, `bnxt_en`).
- **Storage Subsystems**:
  - Single or Mirrored NVMe / SATA SSDs.
  - Root filesystem on ZFS on Linux (ZoL) or standard ext4 with LVM snapshots.

---

## 4. Target 3: Whitebox Bare-Metal Network Switches
- **Ecosystem**: Open Compute Project (OCP) compliant hardware switches.
- **Boot Protocol**: ONIE (Open Network Install Environment).
- **Physical Layout**: 32x100G QSFP28, 48x25G SFP28 + 6x100G QSFP28, or 48x1G PoE+.
- **Hardware Integration Layer**:
  - ONLP (`libonlp.so`) accessing platform CPLD/I2C for optical SFP DOM telemetry, thermal management, and power supplies.
  - Mellanox / NVIDIA Spectrum switches utilize mainline Linux in-tree `mlxsw` driver (Switchdev native).
  - Marvell Prestera switches utilize mainline Linux in-tree `prestera` driver (Switchdev native).
  - Broadcom switches utilize open SAI microservice interfaces.
