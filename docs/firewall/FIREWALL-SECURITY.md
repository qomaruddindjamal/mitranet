# MitraNet Rinjani 1.0.2 — Firewall Security & Anti-Lockout Specification

## 1. Anti-Lockout Protection Engine

Management access must be strictly preserved to prevent operator lockout:
1. **Protected Interfaces:** Defined by `policy.management_interfaces` (default: `enp0s3`).
2. **Protected Ports:** Defined by `policy.management_ports` (default: `22` for SSH).
3. **In-Kernel Invariant:** The compiler unconditionally injects `iifname "<mgmt_iface>" tcp dport <mgmt_ports> counter accept` before any user input rules.
4. **Validation Barrier:** `FirewallValidator.validate_anti_lockout` scans candidate input rules in priority order. If any rule performs a blanket drop or reject matching the management interface without an explicit preceding accept rule, the transaction is rejected immediately with a `FirewallSecurityViolationError`.

---

## 2. Command & Metacharacter Injection Immunity

All user inputs (Rule IDs, interface names, IP addresses, CIDR ranges, port strings, log prefixes) are strictly validated:
- Metacharacters rejected: `;`, `&`, `|`, `$`, `` ` ``, `\n`, `\r`, `(`, `)`, `"`, `'`.
- Execution uses `subprocess.run(shell=False)` with argument arrays.
- Temporary files are written in secure directories and removed immediately upon operation completion.
