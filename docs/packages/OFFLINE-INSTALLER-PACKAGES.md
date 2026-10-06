# MitraNet Rinjani 1.0.2 — Offline Installer Packages Specification

## 1. Specification

MitraNet Rinjani 1.0.2 is designed to run in air-gapped / offline enterprise environments. The installer media supplies all required packages and dependencies locally.

---

## 2. Package Manifest

| Package Name | Architecture | Role | Source |
|---|---|---|---|
| `mitranet-core` | `all` | CLI entrypoints, migration utilities, OS metadata | MitraNet Native |
| `mitranet-config-engine` | `all` | Config loader, schemas, models, Phase 1F Transaction Engine | MitraNet Native |
| `mitranet-network-engine` | `all` | VLAN, Linux Bridge, LACP, Routing, VRF backends | MitraNet Native |
| `mitranet-gateway-monitor` | `all` | Python gateway ping daemon, systemd unit | MitraNet Native |
| `fping` | `amd64` | ICMP probing utility for gateway monitor daemon | Debian 13 Base |
| `python3-pydantic` | `amd64` | Data model validation library | Debian 13 Base |
| `python3-pydantic-core` | `amd64` | Rust core engine for Pydantic | Debian 13 Base |
| `python3-jsonschema` | `all` | Schema validator for configuration | Debian 13 Base |
| `python3-yaml` | `amd64` | YAML parsing & export | Debian 13 Base |
| `libyaml-0-2` | `amd64` | C YAML parser library | Debian 13 Base |

---

## 3. Offline APT Source Configuration

The installed system contains `/etc/apt/sources.list.d/mitranet-offline.list`:
```
deb [arch=all,amd64 signed-by=/etc/apt/trusted.gpg.d/mitranet.asc] file:/var/local/repository rinjani main
```

Running `apt-get update` reads directly from `/var/local/repository/dists/rinjani/InRelease`, validating GPG signatures offline and allowing seamless package discovery and updates.
