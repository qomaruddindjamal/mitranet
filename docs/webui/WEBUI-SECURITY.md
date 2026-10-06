# MITRANET — WEB UI SECURITY & PRIVILEGE BOUNDARY

**Official Identity:** MitraNet 0.1.0-dev (Code OS: Rinjani)

## 1. Authentication & Session Validation
- Web sessions are verified via `public_html/includes/auth.php`.
- Session tokens and credentials are validated before forwarding requests to the REST API.
- All 401 Unauthorized responses trigger session alerts and prompt re-authentication.

## 2. Direct Execution Audit
- Zero raw `shell_exec`, `passthru`, `system`, or `proc_open` calls exist in Web UI presentation pages.
- Direct execution is completely forbidden from Web UI PHP pages.
- Terminal access `/api/terminal/exec` is strictly guarded by role authorization (`role == 'administrator'`).

## 3. XSS & Injection Prevention
- All dynamic inputs displayed on screen pass through `escapeHtml()` in `public_html/includes/common.js`.
- No raw server strings are injected into DOM nodes without context sanitization.
