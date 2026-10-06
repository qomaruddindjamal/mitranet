# MitraNet Native Package Test Matrix & Validation Results

## 1. Test Matrix Overview

Every MitraNet native package is subjected to rigorous lifecycle, installation, dependency, upgrade, removal, and runtime validation on a real Debian GNU/Linux 13 (Trixie) virtual machine.

---

## 2. Validation Execution & Results

| Test Category | Target Component | Test Command / Procedure | Expected Result | Actual Result | Status |
|---|---|---|---|---|---|
| **dpkg-deb Metadata Validation** | All 4 packages | `dpkg-deb --info <pkg>` | Valid control metadata, correct dependencies, valid fields | All control fields, architectures, descriptions valid | **PASS** |
| **Archive Content Validation** | All 4 packages | `dpkg-deb --contents <pkg>` | Correct directory hierarchy, correct permissions, no collisions | 0 collisions, correct `/usr`, `/etc`, `/lib` layouts | **PASS** |
| **Missing Dependency Failure** | `mitranet-core` | `dpkg -i mitranet-core.deb` alone | Blocked by dpkg due to missing `mitranet-config-engine` | Correctly halted with missing dependency error | **PASS** |
| **Simultaneous Installation** | All 4 packages | `dpkg -i *.deb` | All packages installed, dependencies satisfied, postinst executed | Status `ii` on all 4 packages | **PASS** |
| **Conffile Preservation on Upgrade** | `mitranet-config-engine` | User edits `/etc/mitranet/config.json`, install revision `1.0.2-2` | Custom user configuration preserved, no overwrite | `CONFFILE_PRESERVED_PASS` verified | **PASS** |
| **Package Removal** | `mitranet-gateway-monitor` | `dpkg -r mitranet-gateway-monitor` | Binary removed, systemd service stopped and unregistered | Service stopped, inactive, clean removal | **PASS** |
| **Package Reinstallation** | `mitranet-gateway-monitor` | `dpkg -i mitranet-gateway-monitor.deb` | Package reinstalled, service restarted | `SERVICE_ACTIVE_AFTER_REINSTALL_PASS` | **PASS** |
| **Runtime CLI Validation** | `mitranet-core` | `/usr/bin/mitranet --version` | Outputs standard OS version | `MitraNet Rinjani 1.0.2` verified | **PASS** |
| **Runtime Gateway Monitor** | `mitranet-gateway-monitor` | `/usr/bin/mitranet-gateway-monitor --once` | Reports JSON status (`UP`, loss `0.0%`) | Valid JSON status returned | **PASS** |
| **Systemd Daemon State** | `mitranet-gateway-monitor` | `systemctl is-active mitranet-gateway-monitor` | Reports `active` | Active and healthy | **PASS** |
| **APT Repository Generation** | Repository | `apt-ftparchive packages` & `release` | Generates `Packages`, `Release`, GPG signatures | InRelease & Release.gpg created | **PASS** |
| **APT Update Validation** | APT Client | `apt-get update` against local file repo | Repository indexed without warnings or errors | `Hit: file:/var/www/html/mitranet-repo` verified | **PASS** |
| **Full System Regression** | System suite | Python unittest runner (`163` tests) | 100% test pass on live Debian kernel | `163/163 PASS`, 0 failures, 0 errors | **PASS** |
