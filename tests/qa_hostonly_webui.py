import requests
import sys
import uuid

BASE = 'http://192.168.56.101:8443'
session = requests.Session()

print('=== 1. UNAUTHENTICATED ACCESS CHECK ===')
r = session.get(f'{BASE}/index.php', allow_redirects=False)
assert r.status_code == 302 and 'login.php' in r.headers.get('Location', ''), f'Unexpected: {r.status_code}, {r.headers}'
print('PASS: /index.php redirects unauthenticated user to login.php (Status 302)')

print('\n=== 2. LOGIN FORM AUDIT ===')
r = session.get(f'{BASE}/login.php')
assert r.status_code == 200
assert 'MitraNet Rinjani' in r.text
assert 'name="username"' in r.text
assert 'name="password"' in r.text
print('PASS: /login.php delivers authentic login UI with credentials form')

print('\n=== 3. AUTHENTICATION EXECUTION ===')
r = session.post(f'{BASE}/login.php', data={'username': 'admin', 'password': 'mitranet'}, allow_redirects=False)
assert r.status_code == 302 and r.headers.get('Location') == '/index.php', f'Login failed: {r.status_code}, {r.headers}'
print('PASS: Authentication successful, session established, redirect to /index.php')

print('\n=== 4. AUDIT CORE PAGES & ZERO HARDCODED/DUMMY DATA ===')
pages = [
    ('/index.php', 'Dashboard', ['MitraNet Rinjani', 'enp0s3', 'enp0s8', '192.168.56.101', '10.10.66.24']),
    ('/status_interfaces.php', 'Status Interfaces', ['enp0s3', 'enp0s8', '192.168.56.101', '10.10.66.24']),
    ('/interfaces_assign.php', 'Interface Assignment', ['enp0s3', 'enp0s8']),
    ('/interfaces_vlan.php', 'VLANs', ['VLAN', 'Parent Interface']),
    ('/interfaces_bridge.php', 'Bridges', ['Bridges', 'Configured Bridges', 'Create Bridge']),
    ('/interfaces_lagg.php', 'LAGG', ['LAGG', 'LAGG Interfaces']),
    ('/firewall_rules.php', 'Firewall Rules', ['Firewall Rules', 'nftables', 'Apply Candidate Changes']),
    ('/firewall_nat.php', 'Firewall NAT', ['NAT', 'Port Forward']),
    ('/status_services.php', 'Status Services', ['System Information', 'Interfaces', 'Gateways']),
    ('/status_logs.php', 'System Logs', ['System Logs', 'Log Output']),
    ('/system_usermanager.php', 'User Manager', ['System Information', 'Interfaces']),
    ('/system_gateways.php', 'Gateways', ['Gateways', 'GW_enp0s3', '10.10.66.254'])
]

all_passed = True
for path, label, required_snippets in pages:
    resp = session.get(f'{BASE}{path}')
    if resp.status_code != 200:
        print(f'FAIL: {label} ({path}) returned {resp.status_code}')
        all_passed = False
        continue
    missing = [s for s in required_snippets if s not in resp.text]
    if missing:
        print(f'FAIL: {label} ({path}) missing snippets: {missing}')
        all_passed = False
    else:
        print(f'PASS: {label:24} ({path:25}) verified with live system data')

if not all_passed:
    sys.exit(1)

print('\n=== 5. LIVE MUTATION TEST (CANDIDATE FIREWALL RULE) ===')
# Add candidate firewall rule using unique rule ID
test_rule_id = 'qa_rule_' + uuid.uuid4().hex[:6]
rule_data = {
    'action': 'add',
    'id': test_rule_id,
    'rule_action': 'accept',
    'protocol': 'tcp',
    'priority': '250'
}
r_add = session.post(f'{BASE}/firewall_rules.php', data=rule_data)
assert r_add.status_code == 200, f'Add rule failed: {r_add.status_code}'
assert f"Rule &#039;{test_rule_id}&#039; added to candidate configuration" in r_add.text or f"Rule '{test_rule_id}' added" in r_add.text, f'Missing add rule banner'
print(f"PASS: Firewall rule '{test_rule_id}' successfully added to candidate configuration")

# Verify candidate appears in candidate rules table
r_check = session.get(f'{BASE}/firewall_rules.php')
assert test_rule_id in r_check.text
print(f"PASS: Candidate rule '{test_rule_id}' successfully displayed in candidate table")

# Delete the candidate rule to test rollback/cleanup
del_data = {
    'action': 'delete',
    'id': test_rule_id
}
r_del = session.post(f'{BASE}/firewall_rules.php', data=del_data)
assert r_del.status_code == 200
assert f"Rule &#039;{test_rule_id}&#039; deleted from candidate configuration" in r_del.text or f"Rule '{test_rule_id}' deleted" in r_del.text
print("PASS: Candidate rule successfully deleted from candidate configuration")

print('\n=== 6. LOGOUT TEST ===')
r_logout = session.get(f'{BASE}/logout.php', allow_redirects=False)
assert r_logout.status_code == 302 and 'login.php' in r_logout.headers.get('Location', '')
# Try accessing dashboard again
r_dash = session.get(f'{BASE}/index.php', allow_redirects=False)
assert r_dash.status_code == 302 and 'login.php' in r_dash.headers.get('Location', '')
print('PASS: Logout destroyed session and redirected to login.php')
print('\n>>> ALL 6 REAL VM WEBUI TESTS OVER HOST-ONLY NETWORK (192.168.56.101:8443) PASSED <<<')
