# MitraNet Rinjani 1.0.2 — ISO Package Test Matrix

## Phase 2C Verification Matrix

| Test ID | Category | Command / Action | Expected Result | Actual Result | Status |
|---|---|---|---|---|---|
| **P2C-ISO-01** | ISO Build | `xorriso -as mkisofs ...` | Hybrid ISO generated with EFI & isolinux | ISO 998,856,704 bytes generated | **PASS** |
| **P2C-ISO-02** | ISO Structure | `xorriso -find /repository` | `/repository` tree present on ISO | All deb packages & metadata present | **PASS** |
| **P2C-APT-01** | Offline Update | `apt-get update` via `file:/var/local/repository` | InRelease accepted with valid GPG signature | Hit/Get InRelease, package lists read | **PASS** |
| **P2C-DISC-01**| Package Discovery | `apt-cache policy mitranet-* fping` | Packages resolved from offline repo | All 4 MitraNet packages + fping resolved | **PASS** |
| **P2C-INST-01**| Dependency Resolution | `apt-get install -y fping` | Installed without Internet | fping installed, functional (`fping -v`) | **PASS** |
| **P2C-REIN-01**| Reinstall Validation | `apt-get remove` → `apt-get install` | Clean removal and restart of daemon | Unit stopped on remove, resumed on install | **PASS** |
| **P2C-CLI-01** | CLI Execution | `mitranet --version`, `mitranet interface list` | Valid version string and interface discovery | Returned MitraNet 1.0.2, interfaces listed | **PASS** |
| **P2C-SEC-01** | Key Leakage Audit | `grep -rnIE "PRIVATE KEY"` | 0 private keys in repo or ISO | 0 private keys detected | **PASS** |
| **P2C-SEC-02** | Payload Security | Scan debs for FreeBSD ELF / scripts | 0 BSD artifacts found | 0 BSD artifacts found | **PASS** |
| **P2C-REGR-01**| Regression Suite | `python3 -m unittest discover` | 163/163 PASS | 163/163 PASS (0 errors, 0 failures) | **PASS** |
