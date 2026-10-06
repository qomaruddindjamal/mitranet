# MITRANET REPRODUCIBLE RELEASE ARTIFACTS SPECIFICATION

## 1. OFFICIAL IDENTITY & CANONICAL ARTIFACT
- **Project**: MitraNet
- **Version**: 0.1.0-dev (Target release: `1.0.0`)
- **Foundation**: MitraOS 1.0.0
- **Code OS**: Rinjani
- **Architecture**: amd64
- **Official GitHub**: [https://github.com/qomaruddindjamal/mitranet.git](https://github.com/qomaruddindjamal/mitranet.git)

---

## 2. CANONICAL RELEASE ARTIFACT DEFINITION
The future official release artifact MUST adhere strictly to the exact naming convention:
```text
mitranet-rinjani-installer.1.0.0.iso
```

### Prohibited Alternate Names:
- `MitraNet.iso` (FORBIDDEN)
- `MitraNet-1.0.iso` (FORBIDDEN)
- `MitraNet-Rinjani.iso` (FORBIDDEN)
- `MitraOS-Rinjani.iso` (FORBIDDEN)
- `mitranet-amd64.iso` (FORBIDDEN)

---

## 3. RELEASE BUNDLE STRUCTURE
The complete future public release package comprises:
1. **Release ISO**: `mitranet-rinjani-installer.1.0.0.iso` (Distributed outside source Git repository via GitHub Release Assets / CDN)
2. **Checksum Verification**: `SHA256SUMS` (Signed sha256 checksums file)
3. **Release Manifest**: `RELEASE-MANIFEST.json`
4. **Release Notes**: `RELEASE-NOTES-1.0.0.md`
5. **Installation Guide**: `INSTALLATION-GUIDE.md`

---

## 4. RELEASE MANIFEST SCHEMA
```json
{
  "project": "MitraNet",
  "version": "1.0.0",
  "code_os": "Rinjani",
  "foundation": "MitraOS 1.0.0",
  "architecture": "amd64",
  "iso_filename": "mitranet-rinjani-installer.1.0.0.iso",
  "iso_size_bytes": 99774464,
  "iso_sha256": "8e414785059f42b5f1cf7872cf926213c2b037e6291e9ba0d194bb3c9dbe1392",
  "build_date": "2026-10-06T00:00:00Z",
  "build_method": "xorriso-reproducible-hybrid",
  "package_count": 204,
  "package_manifest_version": "1.0.0",
  "installer_version": "1.0.0",
  "kernel_version": "6.12.111+deb13-amd64",
  "bootloader_version": "GRUB 2.12-9+deb13u2",
  "components": {
    "control_plane": "REST API v1.0.0",
    "configuration_engine": "ConfigEngine 1.0.0",
    "web_ui": "MitraNet Web UI 1.0.0",
    "cli": "mitranet CLI v1.0.0",
    "tui": "mitranet TUI v1.0.0"
  }
}
```

---

## 5. REPOSITORY AND ARTIFACT BOUNDARIES
- **Inside Git Repository**: Source code, modular PHP/JS Web UI, Python API/engine, packaging scripts, test suites, architecture documentation, 204 package archives in `packages/`.
- **Outside Git Repository**: `*.iso` binary images, temporary chroot files, cache files, local configuration files (`api/config.json`, `api/backups/`).
