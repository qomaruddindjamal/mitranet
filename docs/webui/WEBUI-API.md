# MITRANET MANAGEMENT REST API v1 SPECIFICATION

## 1. Authentication Endpoints

### `GET /api/v1/ping`
- **Auth:** None
- **Response:** `200 OK`
```json
{
  "status": "ok",
  "time": 1791308559.36
}
```

### `GET /api/v1/auth/status`
- **Auth:** Optional (checks session cookie)
- **Response:** `200 OK`
```json
{
  "authenticated": true,
  "username": "admin"
}
```

### `POST /api/v1/auth/login`
- **Auth:** None
- **Request:**
```json
{
  "username": "admin",
  "password": "mitranet"
}
```
- **Response:** `200 OK` (Sets `mitranet_session` HttpOnly cookie)
```json
{
  "success": true,
  "username": "admin",
  "csrf_token": "e5b8...f9"
}
```

### `POST /api/v1/auth/logout`
- **Auth:** Required
- **Response:** `200 OK` (Clears session cookie)
```json
{
  "success": true,
  "message": "Logged out"
}
```

---

## 2. Resource Query Endpoints (Authenticated)

- `GET /api/v1/system`: System hostname, OS, kernel, CPU, RAM, disk metrics.
- `GET /api/v1/interfaces`: List discovered network interfaces with operational state and traffic counters.
- `GET /api/v1/interfaces/{name}`: Get detailed info and metrics for a specific interface.
- `GET /api/v1/routes`: Discovered kernel IPv4 and IPv6 routes.
- `GET /api/v1/vlans`: Configured 802.1Q VLAN interfaces.
- `GET /api/v1/bridges`: Configured Linux bridge devices.
- `GET /api/v1/bonds`: Configured Linux bond/LACP interfaces.
- `GET /api/v1/vrfs`: Configured VRF routing domains and associated tables.
- `GET /api/v1/firewall`: Current nftables status, running config, and candidate rules.
- `GET /api/v1/gateways`: Real detected default gateways and status.
- `GET /api/v1/config/status`: Candidate and running configuration versions and transaction lock state.
- `GET /api/v1/logs?category={system|firewall|gateway|transaction}`: Real kernel and appliance log streams.

---

## 3. Configuration Mutation Endpoints (Requires Session + `X-CSRF-Token`)

- `POST /api/v1/interfaces/set-state`: Set interface state (`up` or `down`).
- `POST /api/v1/interfaces/set-mtu`: Set interface MTU.
- `POST /api/v1/interfaces/address/add`: Add IPv4 or IPv6 address to interface.
- `POST /api/v1/interfaces/address/remove`: Remove IP address from interface.
- `POST /api/v1/routes/add`: Add route to routing table.
- `POST /api/v1/routes/remove`: Remove route from routing table.
- `POST /api/v1/vlans/create`: Create 802.1Q VLAN sub-interface.
- `POST /api/v1/vlans/delete`: Delete VLAN interface.
- `POST /api/v1/bridges/create`: Create bridge device.
- `POST /api/v1/bridges/delete`: Delete bridge device.
- `POST /api/v1/vrfs/create`: Create VRF instance.
- `POST /api/v1/vrfs/delete`: Delete VRF instance.
- `POST /api/v1/firewall/rule/add`: Add rule to firewall candidate ruleset.
- `POST /api/v1/firewall/rule/delete`: Remove rule from candidate ruleset.
- `POST /api/v1/firewall/apply`: Atomically compile and commit candidate firewall ruleset.
- `POST /api/v1/firewall/reload`: Reload running firewall configuration into nftables.
- `POST /api/v1/config/apply`: Commit candidate configuration changes to running state.
- `POST /api/v1/config/rollback`: Rollback running state to a previous snapshot ID.
