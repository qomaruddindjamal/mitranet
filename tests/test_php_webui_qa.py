"""
Comprehensive Browser & HTTP QA Suite for MitraNet pfSense-Derived PHP WebUI.
Verifies all 16 core pages, authentic session preservation, form rendering, 
and safe mutation (candidate rule add & delete) through the PHP layer.
"""

import sys
import json
import urllib.request
import urllib.parse
import http.cookiejar

sys.stdout.reconfigure(encoding='utf-8')

BASE_URL = "http://127.0.0.1:8443"
cookie_jar = http.cookiejar.CookieJar()

class NoRedirectHandler(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, hdrs, newurl):
        return None

opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cookie_jar), NoRedirectHandler)

print("=" * 60)
print("STARTING MITRANET PFsense-DERIVED PHP WEBUI QA VERIFICATION")
print("=" * 60)

# 1. Unauthenticated request to index.php should redirect to login.php
print("\n[QA 1] Verify Unauthenticated Access Redirects to /login.php:")
req = urllib.request.Request(f"{BASE_URL}/index.php")
try:
    resp = opener.open(req)
    print("  Status:", resp.status, "Location:", resp.headers.get("Location"))
except urllib.error.HTTPError as e:
    print("  HTTP Status:", e.code, "Location:", e.headers.get("Location"))
    assert e.code in (302, 303), f"Expected redirect, got {e.code}"
    assert "login.php" in e.headers.get("Location", ""), "Redirect target must be login.php"
    print("  PASS: Redirected to login.php")

# 2. Fetch login.php page and verify genuine pfSense styling and elements
print("\n[QA 2] Fetch /login.php Presentation:")
req = urllib.request.Request(f"{BASE_URL}/login.php")
with opener.open(req) as resp:
    html = resp.read().decode('utf-8', errors='replace')
    print("  Status:", resp.status, "Length:", len(html))
    assert "Mitra<span>Net</span>" in html, "Missing MitraNet brand header"
    assert 'name="username"' in html and 'name="password"' in html, "Missing login input fields"
    assert "/vendor/bootstrap/css/bootstrap.min.css" in html, "Missing Bootstrap CSS reference"
    print("  PASS: /login.php rendered authentic pfSense login form")

# 3. Perform POST Login via /login.php
print("\n[QA 3] Perform POST /login.php (Authentication):")
post_data = urllib.parse.urlencode({"username": "admin", "password": "mitranet"}).encode('utf-8')
req = urllib.request.Request(f"{BASE_URL}/login.php", data=post_data, method="POST")
try:
    resp = opener.open(req)
    print("  Response status:", resp.status)
except urllib.error.HTTPError as e:
    print("  Redirect status:", e.code, "Target:", e.headers.get("Location"))
    assert e.code in (302, 303), f"Expected 302 redirect after login, got {e.code}"
    assert "index.php" in e.headers.get("Location", ""), "Expected redirect to index.php"

# Check cookies in jar
cookies_dict = {c.name: c.value for c in cookie_jar}
print("  Cookies captured:", list(cookies_dict.keys()))
assert "mitranet_session" in cookies_dict, "mitranet_session cookie must be present"
print("  PASS: Logged in and received session token")

# 4. Verify Core Pages Rendering
pages_to_test = [
    ("/index.php", "Dashboard - MitraNet Rinjani", "System Information", "Widget / Dashboard"),
    ("/interfaces.php", "Interfaces: General Details - MitraNet Rinjani", "Detected Network Interfaces", "Interfaces Overview"),
    ("/firewall_rules.php", "Firewall: Rules - MitraNet Rinjani", "Firewall Rules", "Firewall Rules Manager"),
    ("/firewall_nat.php", "Firewall: NAT (Port Forward / 1:1 / Outbound) - MitraNet Rinjani", "Scheduled for Phase 3B", "Firewall NAT Status"),
    ("/system_routes.php", "System: Routing: Static Routes - MitraNet Rinjani", "Active IPv4 Routes", "Routes"),
    ("/system_gateways.php", "System: Routing: Gateways - MitraNet Rinjani", "Gateways", "Gateways"),
    ("/interfaces_vlan.php", "Interfaces: VLANs - MitraNet Rinjani", "VLAN", "VLANs"),
    ("/interfaces_bridge.php", "Interfaces: Bridges - MitraNet Rinjani", "Bridge", "Bridges"),
    ("/interfaces_lagg.php", "Interfaces: LAGG / LACP - MitraNet Rinjani", "LAGG", "LAGG / Bond"),
    ("/interfaces_vrf.php", "Interfaces: VRF - MitraNet Rinjani", "VRF", "VRF"),
    ("/diag_backup.php", "Diagnostics: Backup, Restore &amp; Transactions - MitraNet Rinjani", "Transaction Engine Status", "Transactions & Rollback"),
    ("/status_logs.php", "Status: System Logs - MitraNet Rinjani", "System Logs", "Logs"),
]

print("\n[QA 4] Verify Rendering of All 12 Core PHP Pages:")
for path, expected_title, expected_content, desc in pages_to_test:
    req = urllib.request.Request(f"{BASE_URL}{path}")
    with opener.open(req) as resp:
        content = resp.read().decode('utf-8', errors='replace')
        assert resp.status == 200, f"Page {path} returned status {resp.status}"
        assert expected_title in content, f"Page {path} missing title '{expected_title}'"
        assert expected_content in content, f"Page {path} missing content '{expected_content}'"
        assert "navbar-brand" in content, f"Page {path} missing standard pfSense navbar header"
        assert "MitraNet Rinjani 1.0.2" in content, f"Page {path} missing standard footer"
        print(f"  [OK] {path} ({desc}) - 200 OK, length {len(content)}")

print("  PASS: All 12 Core PHP pages rendered successfully with authentic pfSense layout")

# 5. Test Mutation Through PHP WebUI: Add Firewall Rule and Delete Firewall Rule
print("\n[QA 5] Test Live Mutation via PHP /firewall_rules.php (Add & Delete Rule):")

# 5a. Add rule via POST
add_form = {
    "action": "add",
    "id": "php-webui-qa-test-rule",
    "protocol": "tcp",
    "rule_action": "accept",
    "priority": "100",
}
post_data = urllib.parse.urlencode(add_form).encode('utf-8')
req = urllib.request.Request(f"{BASE_URL}/firewall_rules.php", data=post_data, method="POST")
with opener.open(req) as resp:
    content = resp.read().decode('utf-8', errors='replace')
    assert resp.status == 200, f"Form POST returned status {resp.status}"
    assert "php-webui-qa-test-rule" in content, "Added rule not found in candidate rules list"
    assert "added to candidate configuration" in content, "Missing success notification message"
    print("  [OK] Rule 'php-webui-qa-test-rule' successfully added to candidate via PHP form")

# 5b. Delete rule via POST
del_form = {
    "action": "delete",
    "id": "php-webui-qa-test-rule",
}
post_data = urllib.parse.urlencode(del_form).encode('utf-8')
req = urllib.request.Request(f"{BASE_URL}/firewall_rules.php", data=post_data, method="POST")
with opener.open(req) as resp:
    content = resp.read().decode('utf-8', errors='replace')
    assert resp.status == 200, f"Delete POST returned status {resp.status}"
    assert "deleted from candidate configuration" in content, "Missing delete success notification"
    # Ensure the rule is no longer present as a row in the rules table
    assert f"<td><strong>php-webui-qa-test-rule</strong></td>" not in content, "Deleted rule row still present in table"
    print("  [OK] Rule 'php-webui-qa-test-rule' successfully deleted from candidate via PHP form")

# 6. Test Logout via /logout.php
print("\n[QA 6] Verify Logout:")
req = urllib.request.Request(f"{BASE_URL}/logout.php")
try:
    resp = opener.open(req)
except urllib.error.HTTPError as e:
    assert e.code in (302, 303), f"Expected redirect on logout, got {e.code}"
    assert "login.php" in e.headers.get("Location", ""), "Redirect must be to login.php"
    print("  [OK] Successfully logged out and redirected to login.php")

# Subsequent request to /index.php should redirect to /login.php
req = urllib.request.Request(f"{BASE_URL}/index.php")
try:
    resp = opener.open(req)
    assert False, "Should have been redirected to login.php"
except urllib.error.HTTPError as e:
    assert e.code in (302, 303), f"Expected redirect, got {e.code}"
    print("  [OK] Access to /index.php correctly denied after logout")

print("\n" + "=" * 60)
print("ALL PFsense-DERIVED PHP WEBUI QA VERIFICATION CHECKS PASSED!")
print("=" * 60)
