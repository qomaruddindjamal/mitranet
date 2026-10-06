# MITRANET — SECURITY & PRIVILEGE BOUNDARY MODEL

**Official Identity:** MitraNet 0.1.0-dev (Code OS: Rinjani)

## 1. Principle of Least Privilege

- **Web UI (`public_html/`)**: Runs unprivileged under web server user. Strictly prohibited from calling shell subshells directly.
- **REST API (`api/REST/server.py`)**: Authenticated endpoint layer with RBAC authorization and token validation.
- **Backend Managers**: Execute controlled commands using bounded argument lists (`subprocess.run(list, shell=False)`).
- **Kernel Capabilities**: Restricted to `CAP_NET_ADMIN`, `CAP_NET_RAW`, and `CAP_NET_BIND_SERVICE`.

## 2. Vulnerability Prevention Audit

- **Shell Injections**: Mitigated by strict list-based execution.
- **API Privilege Escalation**: Mitigated by RBAC and session token validation.
- **Secrets & Tokens**: Persisted exclusively in secure, restricted local storage.
