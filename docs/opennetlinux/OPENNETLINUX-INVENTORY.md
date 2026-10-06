# OpenNetLinux (ONL) Architecture & Binary Inventory

*Source: OpenNetLinux binary archives (`https://opennetlinux.org/binaries/`), Open Compute Project (OCP) specifications, and ONLP specifications.*

## 1. Executive Summary & Purpose
OpenNetLinux (ONL) is an open-source Linux distribution for bare-metal switch hardware (whitebox switches) founded by Big Switch Networks under the Open Compute Project (OCP).

In MitraNet, ONL serves as **Priority 3 (Hardware / Whitebox / Switching Foundation)**, enabling MitraNet to run not only on x86/amd64 standard server/router hardware but also on OCP bare-metal network switches (Accton/Edgecore, Celestica, Delta, Quanta).

---

## 2. Core Architecture Subsystems

### 2.1 ONIE (Open Network Install Environment)
- **Role**: Bootloader and network install environment for bare metal switches.
- **Workflow**: BIOS/UEFI/U-Boot initializes $\rightarrow$ executes ONIE kernel $\rightarrow$ locates installer image via DHCP/TFTP/HTTP $\rightarrow$ installs MitraNet NOS directly to switch storage (eMMC, SSD).
- **MitraNet Strategy**: Build an ONIE-compliant installer package (`mitranet-onie-installer.bin`) alongside standard Debian ISO.

### 2.2 ONLP (Open Network Linux Platform Library)
- **Role**: Hardware abstraction layer for switch chassis peripherals.
- **APIs**:
  - `onlp_sfp`: SFP/SFP+/QSFP/QSFP28 transceiver presence, EEPROM DOM telemetry, optical power levels, tx_fault, rx_loss.
  - `onlp_fan`: Chassis fan speed control (PWM), RPM monitoring, failure detection.
  - `onlp_psu`: Power supply units (AC/DC), status, voltage, current, input power.
  - `onlp_thermal`: Temperature sensors placed across switch ASIC, CPU board, airflow path.
  - `onlp_led`: Chassis front-panel LEDs, port activity/link state LEDs.
- **Kernel & Bus Interfacing**: Accesses I2C/SMBus, CPLD, GPIO, and FPGA registers via Linux `sysfs` (`/sys/bus/i2c/devices/`).

### 2.3 Switch Silicon Abstraction & Drivers
- **Switchdev**: In-tree Linux kernel driver model representing switch ASIC physical ports as standard Linux netdevices (`swp1`, `swp2`, etc.), allowing standard Linux tools (`ip`, `bridge`, `tc`) to configure hardware ASIC forwarding tables.
- **OpenSwitch / SAI (Switch Abstraction Interface)**: C-level standardized API across silicon vendors (Broadcom StrataXGS/Tomahawk/Trident, Mellanox Spectrum, Marvell Prestera, Barefoot Tofino).

---

## 3. Package Inventory & Debian Compatibility Audit

| Component / Package | Upstream Role | Debian 13 (Trixie) Compatibility | MitraNet Adoption Strategy |
| :--- | :--- | :--- | :--- |
| **`onlp` / `libonlp`** | Hardware peripheral abstraction (SFP, Fan, PSU, Temp, LED) | Compatible (C library + Python/Rust bindings) | **REBUILD & ADAPT**: Compile modern `libonlp` against Debian 13 libc6. Integrate with MitraNet telemetry daemon. |
| **`onl-platform-*`** | Platform specific kernel modules & sysfs platform drivers | Kernel-specific (requires DKMS or in-tree build) | **ADAPT / PORT**: Port platform drivers to Debian 6.x Linux kernel via DKMS. |
| **`onie-installer`** | Installer payload for ONIE | Compatible (Self-extracting shell/initramfs) | **DIRECT USE**: Package MitraNet rootfs into standard ONIE installer format. |
| **`opennsl` / `bcm-sdk`** | Proprietary Broadcom switch silicon drivers | Kernel ABI sensitive; licensing restrictions | **REPLACE / ISOLATE**: Rely on kernel `switchdev` drivers or containerized SAI microservice. |
| **`i2c-tools`** | I2C bus debugging and telemetry (`i2cdetect`, `i2cget`) | Available in Debian main | **DIRECT USE**: Standard Debian dependency. |
| **`ethtool`** | Link speed, autoneg, PHY diagnostics, FEC controls | Available in Debian main | **DIRECT USE**: Core network driver interface. |

---

## 4. Hardware Analysis: Whitebox Switch Platform Coverage

| Vendor | Models | Target Switch Silicon | MitraNet Capability Target |
| :--- | :--- | :--- | :--- |
| **Edgecore / Accton** | AS4610, AS5712, AS5812, AS7712 | Broadcom Helix4, Trident II, Trident II+, Tomahawk | 1G/10G/25G/40G/100G L2/L3 Hardware Switching |
| **Celestica** | DX010, D2060 | Broadcom Tomahawk, Trident III | Top-of-Rack (ToR) 100G Data Center Switching |
| **Mellanox / NVIDIA** | SN2010, SN2100, SN2700 | Mellanox Spectrum-1/2/3 | Native Switchdev (In-tree Linux Kernel Driver) |
