# MITRANET WEBUI & REST API ARCHITECTURE v1

## 1. Overview
The MitraNet Management WebUI Layer v1 provides a web-based administration interface and an authenticated REST API for managing MitraNet Rinjani 1.0.2 network appliances.

## 2. Architecture Diagram

```text
                    BROWSER (Administrator)
                               │
                               │ HTTP / HTTPS (Port 8443)
                               ▼
               ┌─────────────────────────────────┐
               │         MitraNet WebUI          │
               │   (Vanilla SPA: HTML/CSS/JS)    │
               └───────────────┬─────────────────┘
                               │
                               ▼
               ┌─────────────────────────────────┐
               │    Management REST API Layer    │
               │   (Python http.server runtime)  │
               └───────────────┬─────────────────┘
                               │
        ┌──────────────────────┼───────────────────────┐
        │                      │                       │
        ▼                      ▼                       ▼
  Config Engine          Network Engine         Firewall Engine
(candidate/running)  (iproute2 / links/routes)    (nftables core)
        │                      │                       │
        └──────────────────────┼───────────────────────┘
                               ▼
                      Transaction Engine
                  (Atomic snapshot & rollback)
                               │
                               ▼
                          Linux Kernel
```

## 3. Core Principles
1. **No Direct Shell Commands:** The WebUI and API layer never invoke `shell=True` or arbitrary shell commands. All operations strictly pass through typed MitraNet subsystem models and services.
2. **Zero Runtime Dependencies:** Built strictly on the standard library (`http.server`, `json`, `hashlib`, `secrets`) without requiring external web frameworks or Node.js runtime on Debian 13.
3. **Completely Offline:** No external CDN resources (Google Fonts, Tailwind, CDN JS). All styles, scripts, and assets are hosted locally from `/usr/lib/python3/dist-packages/mitranet/src/api/static/`.
4. **Strong Security Boundary:** Authenticated session tokens stored in HttpOnly cookies with `SameSite=Strict` and mandatory cryptographic CSRF token validation for all state mutations.
