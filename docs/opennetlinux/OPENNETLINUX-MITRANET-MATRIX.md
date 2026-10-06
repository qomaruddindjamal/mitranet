# OpenNetLinux to MitraNet Integration Matrix

## 1. Integration Strategy
MitraNet incorporates OpenNetLinux's hardware ecosystem into a modular abstraction service (`mitranet-platformd`). Standard x86 router appliances will run without ONL overhead, while deployment on whitebox bare-metal switches activates the hardware peripheral and switchdev layers.

| Subsystem | ONL Implementation | Debian Linux Stack | MitraNet Module | Strategy | Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Boot & Install** | ONIE loader + ONL installer script | GRUB2 / EFI + Debian Live Installer + ONIE bin wrapper | `mitranet.installer.onie` | ADAPT | DESIGN ONLY |
| **Transceiver Telemetry (SFP/QSFP)** | ONLP `onlp_sfp` API reading I2C A0/A2 EEPROM | Linux `ethtool -m <iface>` + `libonlp` wrapper | `mitranet.hardware.sfp` | ADAPT | PROTOTYPE |
| **Thermal Monitoring** | ONLP `onlp_thermal` | Linux `hwmon` sysfs (`/sys/class/hwmon/`) | `mitranet.hardware.thermal` | DIRECT USE | PROTOTYPE |
| **Fan Control & Speed**| ONLP `onlp_fan` | Linux `hwmon` pwm controls + ONLP C binding | `mitranet.hardware.fans` | ADAPT | PROTOTYPE |
| **Chassis Power Supplies (PSU)** | ONLP `onlp_psu` | PMBus kernel drivers / CPLD sysfs / ONLP | `mitranet.hardware.psu` | ADAPT | DESIGN ONLY |
| **Front Panel LEDs** | ONLP `onlp_led` | Linux `/sys/class/leds/` subsystem | `mitranet.hardware.leds` | DIRECT USE | PROTOTYPE |
| **L2 Switching Fabric** | OpenNSL / Broadcom SDK / SAI | Linux `switchdev` driver + `bridge` | `mitranet.network.switching` | REPLACE / SWITCHDEV | DESIGN ONLY |
| **Port Speed & FEC** | ONL platform port config | `ethtool` link mode and forward error correction | `mitranet.network.interfaces` | DIRECT USE | PROTOTYPE |
| **Chassis Information** | ONLP EEPROM / ONIE TLV EEPROM | Decode ONIE TLV via Python `struct` / `hexdump` | `mitranet.hardware.chassis` | DIRECT USE | PROTOTYPE |

---

## 2. Platform Driver Architecture Diagram

```text
  ┌──────────────────────────────────────────────────────────────┐
  │                 MitraNet Management Daemon                   │
  │                   (WebUI, CLI, SNMP, REST)                   │
  └──────────────────────────────┬───────────────────────────────┘
                                 │
                                 ▼
  ┌──────────────────────────────────────────────────────────────┐
  │                 MitraNet Hardware Abstraction                │
  │                     (mitranet-platformd)                     │
  └──────────────┬───────────────────────────────┬───────────────┘
                 │                               │
        (Standard Server/PC)             (Whitebox Switch)
                 │                               │
                 ▼                               ▼
  ┌──────────────────────────────┐┌──────────────────────────────┐
  │   Linux Native Subsystems    ││      ONLP Driver Wrapper     │
  │  - /sys/class/hwmon/         ││  - libonlp.so                │
  │  - /sys/class/leds/          ││  - SFP I2C EEPROM            │
  │  - ethtool netlink           ││  - CPLD / FPGA sysfs         │
  └──────────────────────────────┘└──────────────────────────────┘
```
