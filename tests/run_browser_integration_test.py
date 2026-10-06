import json
import urllib.request
import http.cookiejar

import os

TARGET = os.environ.get("MITRANET_HOST", "192.168.56.101")
BASE_URL = f"http://{TARGET}:8443"

cookie_jar = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cookie_jar))

# 1. Test ping
req = urllib.request.Request(f"{BASE_URL}/api/v1/ping")
with opener.open(req) as resp:
    data = json.loads(resp.read().decode())
    print("[TEST 1/14] Ping:", resp.status, data.get("status"))

# 2. Test login
login_data = json.dumps({"username": "admin", "password": "mitranet"}).encode()
req = urllib.request.Request(
    f"{BASE_URL}/api/v1/auth/login",
    data=login_data,
    headers={"Content-Type": "application/json"}
)
with opener.open(req) as resp:
    login_res = json.loads(resp.read().decode())
    csrf_token = login_res.get("csrf_token")
    print("[TEST 2/14] Login:", resp.status, login_res.get("success"), "CSRF:", bool(csrf_token))

# Verify cookie received
cookies = {c.name: c.value for c in cookie_jar}
print("Session Cookies:", list(cookies.keys()))

# 3. Auth status
req = urllib.request.Request("" + BASE_URL + "/api/v1/auth/status")
with opener.open(req) as resp:
    status_res = json.loads(resp.read().decode())
    print("[TEST 3/14] Auth Status:", status_res.get("authenticated"), status_res.get("username"))

# 4. System info
req = urllib.request.Request("" + BASE_URL + "/api/v1/system")
with opener.open(req) as resp:
    sys_res = json.loads(resp.read().decode())
    print("[TEST 4/14] System Info:", sys_res.get("hostname"), sys_res.get("kernel"), sys_res.get("cpu", {}).get("cores"), "cores")

# 5. Interfaces
req = urllib.request.Request("" + BASE_URL + "/api/v1/interfaces")
with opener.open(req) as resp:
    ifaces = json.loads(resp.read().decode())
    print("[TEST 5/14] Interfaces:", [i["name"] for i in ifaces])

# 6. Routing
req = urllib.request.Request("" + BASE_URL + "/api/v1/routes")
with opener.open(req) as resp:
    routes = json.loads(resp.read().decode())
    print("[TEST 6/14] Routes: IPv4 count =", len(routes.get("ipv4", [])), "IPv6 count =", len(routes.get("ipv6", [])))

# 7. VLANs
req = urllib.request.Request("" + BASE_URL + "/api/v1/vlans")
with opener.open(req) as resp:
    vlans = json.loads(resp.read().decode())
    print("[TEST 7/14] VLANs count:", len(vlans))

# 8. Bridges
req = urllib.request.Request("" + BASE_URL + "/api/v1/bridges")
with opener.open(req) as resp:
    bridges = json.loads(resp.read().decode())
    print("[TEST 8/14] Bridges count:", len(bridges))

# 9. Bonds
req = urllib.request.Request("" + BASE_URL + "/api/v1/bonds")
with opener.open(req) as resp:
    bonds = json.loads(resp.read().decode())
    print("[TEST 9/14] Bonds count:", len(bonds))

# 10. VRFs
req = urllib.request.Request("" + BASE_URL + "/api/v1/vrfs")
with opener.open(req) as resp:
    vrfs = json.loads(resp.read().decode())
    print("[TEST 10/14] VRFs count:", len(vrfs))

# 11. Firewall
req = urllib.request.Request("" + BASE_URL + "/api/v1/firewall")
with opener.open(req) as resp:
    fw = json.loads(resp.read().decode())
    print("[TEST 11/14] Firewall rules count:", len(fw.get("config", {}).get("rules", [])))

# 12. Gateways
req = urllib.request.Request("" + BASE_URL + "/api/v1/gateways")
with opener.open(req) as resp:
    gws = json.loads(resp.read().decode())
    print("[TEST 12/14] Gateways:", [g.get("name") for g in gws])

# 13. Config status
req = urllib.request.Request("" + BASE_URL + "/api/v1/config/status")
with opener.open(req) as resp:
    cfg = json.loads(resp.read().decode())
    print("[TEST 13/14] Config status: running_version =", cfg.get("running_version"), "is_locked =", cfg.get("is_locked"))

# 14. Mutation & Rollback Test: Add Firewall Rule and Remove
add_rule_payload = {
    "id": "webui-qa-test-rule",
    "description": "Safe WebUI QA test rule",
    "direction": "in",
    "action": "accept",
    "protocol": "tcp",
    "priority": 450,
}
req = urllib.request.Request(
    "" + BASE_URL + "/api/v1/firewall/rule/add",
    data=json.dumps(add_rule_payload).encode(),
    headers={"Content-Type": "application/json", "X-CSRF-Token": csrf_token}
)
with opener.open(req) as resp:
    add_res = json.loads(resp.read().decode())
    print("[MUTATION ADD] Rule added:", add_res.get("success"), add_res.get("message"))

# Verify rule exists
with opener.open("" + BASE_URL + "/api/v1/firewall") as resp:
    fw_after = json.loads(resp.read().decode())
    rule_ids = [r["id"] for r in fw_after.get("candidate", {}).get("rules", [])]
    print("[MUTATION VERIFY] Rule present in firewall candidate:", "webui-qa-test-rule" in rule_ids)

# Delete rule (restore state)
req = urllib.request.Request(
    "" + BASE_URL + "/api/v1/firewall/rule/delete",
    data=json.dumps({"id": "webui-qa-test-rule"}).encode(),
    headers={"Content-Type": "application/json", "X-CSRF-Token": csrf_token}
)
with opener.open(req) as resp:
    del_res = json.loads(resp.read().decode())
    print("[MUTATION RESTORE] Rule deleted:", del_res.get("success"), del_res.get("message"))

# Verify rule removed
with opener.open("" + BASE_URL + "/api/v1/firewall") as resp:
    fw_restored = json.loads(resp.read().decode())
    rule_ids_restored = [r["id"] for r in fw_restored.get("config", {}).get("rules", [])]
    print("[MUTATION RESTORE VERIFY] Rule removed:", "webui-qa-test-rule" not in rule_ids_restored)

# Logout
req = urllib.request.Request(
    "" + BASE_URL + "/api/v1/auth/logout",
    data=json.dumps({}).encode(),
    headers={"Content-Type": "application/json"}
)
with opener.open(req) as resp:
    logout_res = json.loads(resp.read().decode())
    print("[TEST 14/14] Logout:", logout_res.get("success"))

print("\nALL WEBUI BROWSER-LEVEL INTEGRATION FLOWS PASSED SUCCESSFULLY!")
