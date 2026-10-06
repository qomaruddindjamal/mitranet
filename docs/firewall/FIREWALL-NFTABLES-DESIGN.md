# MitraNet Rinjani 1.0.2 — nftables Backend Design

## 1. Table & Chain Structure

MitraNet manages its own isolated namespace inside `table inet mitranet`:

```text
table inet mitranet {
    chain input {
        type filter hook input priority filter; policy drop;
        ct state { established, related } counter accept comment "mitranet:policy:conntrack_established_related"
        ct state invalid counter drop comment "mitranet:policy:conntrack_invalid"
        iifname "lo" counter accept comment "mitranet:policy:loopback_accept"
        iifname "enp0s3" tcp dport 22 counter accept comment "mitranet:anti_lockout:mgmt_ssh"
        <user input rules>
    }

    chain forward {
        type filter hook forward priority filter; policy drop;
        ct state { established, related } counter accept comment "mitranet:policy:conntrack_established_related"
        ct state invalid counter drop comment "mitranet:policy:conntrack_invalid"
        <user forward rules>
    }

    chain output {
        type filter hook output priority filter; policy accept;
        ct state { established, related } counter accept comment "mitranet:policy:conntrack_established_related"
        <user output rules>
    }
}
```

---

## 2. Invariance & Non-Destructive Operation

- MitraNet only creates, inspects, and modifies `table inet mitranet`.
- Third-party rulesets (e.g. Docker, Libvirt, wireguard, tailscale) residing in separate tables are never flushed or disrupted.
- Syntax dry-run (`nft -c -f`) ensures malformed inputs never corrupt active kernel state.
- All operations execute via `subprocess.run(shell=False)` with timeout and error-code enforcement.
