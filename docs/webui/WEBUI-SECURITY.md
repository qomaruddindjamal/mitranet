# MITRANET WEBUI & REST API SECURITY AUDIT & SPECIFICATION

## 1. Authentication & Cryptography
- **Password Storage:** Credentials stored in `/etc/mitranet/secrets/webui_users.json` using PBKDF2-HMAC-SHA256 with 100,000 iterations and a cryptographically secure 16-byte random salt per user.
- **Session Tokens:** 256-bit entropy generated using Python's `secrets.token_hex(32)`.
- **Session Expiration:** Standard session TTL is 1 hour (3600 seconds) with automatic invalidation upon expiration or manual logout.

## 2. Session Cookies
- **`HttpOnly`:** Enabled to prevent token access via malicious scripts / XSS.
- **`SameSite=Strict`:** Configured to mitigate Cross-Site Request Forgery (CSRF).
- **`Path=/`:** Confined to the appliance root domain.

## 3. CSRF Protection
- **Anti-CSRF Tokens:** Generated cryptographically per session using `secrets.token_hex(24)`.
- **Validation:** Every HTTP POST mutation endpoint strictly validates the `X-CSRF-Token` header using timing-attack resistant `secrets.compare_digest`.
- **Enforcement:** Missing or invalid CSRF tokens return `403 Forbidden`.

## 4. Subprocess & Injection Defense
- **Zero Direct Shell Invocation:** No usage of `shell=True`, `os.system()`, or string-interpolated shell commands.
- **Strict Parameter Validation:** Interface names, VLAN IDs, IP CIDRs, and firewall identifiers undergo strict alphanumeric and schema validations before being forwarded to kernel subsystem services.
- **Path Traversal Prevention:** Log endpoints reject user-supplied file paths and only read from predefined system log facilities.
