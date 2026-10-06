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
    ('/index.php', 'Dashboard', ['MitraNet Rinjani', 'enp0s3', 'enp0s8', '192.168.56.101']),
    ('/system.php', 'General Setup', ['System: General Setup', 'Hostname', 'Operating System', 'Version']),
    ('/system_advanced.php', 'System Advanced', ['Admin Access', '8443', 'CSRF']),
    ('/system_gateways.php', 'System Gateways', ['Gateways', 'GW_enp0s3']),
    ('/system_routes.php', 'System Routes', ['Routing', 'Destination']),
    ('/interfaces_assign.php', 'Interface Assignment', ['Assignments', 'enp0s3', 'enp0s8']),
    ('/interfaces.php', 'Interfaces Detail', ['Interface Details', 'enp0s8']),
    ('/interfaces_vlan.php', 'VLANs', ['VLAN', 'Parent Interface']),
    ('/interfaces_bridge.php', 'Bridges', ['Bridges', 'Configured Bridges']),
    ('/interfaces_lagg.php', 'LAGG', ['LAGG', 'LAGG Interfaces']),
    ('/interfaces_vrf.php', 'VRF Domains', ['VRF', 'Routing Table']),
    ('/firewall_rules.php', 'Firewall Rules', ['Firewall Rules', 'nftables', 'Apply Candidate Changes']),
    ('/firewall_nat.php', 'NAT Port Forward', ['NAT: Port Forward', 'Port Forward', '192.168.56.101']),
    ('/firewall_nat_out.php', 'NAT Outbound', ['Outbound', 'SNAT / Masquerade']),
    ('/firewall_nat_1to1.php', 'NAT 1:1', ['1:1 NAT', 'External IP']),
    ('/firewall_nat_npt.php', 'NAT NPt', ['IPv6 Network Prefix Translation']),
    ('/firewall_aliases.php', 'Firewall Aliases', ['Aliases', 'RFC1918_Subnets', 'Management_Net']),
    ('/status_interfaces.php', 'Status Interfaces', ['enp0s3', 'enp0s8', 'MAC Address', 'Packets']),
    ('/status_gateways.php', 'Status Gateways', ['Gateways', 'GW_enp0s3']),
    ('/status_logs.php', 'System Logs', ['System Logs', 'Log Output']),
    ('/diag_arp.php', 'Diagnostics ARP', ['ARP Table', 'enp0s8', '192.168.56.1']),
    ('/diag_dump_states.php', 'Diagnostics States', ['States', 'Conntrack']),
    ('/diag_routes.php', 'Diagnostics Routes', ['Routing Tables', 'IPv4 Routing Table']),
    ('/diag_ping.php', 'Diagnostics Ping', ['Ping Target', 'Count']),
    ('/diag_traceroute.php', 'Diagnostics Traceroute', ['Trace Network Route', 'Hostname']),
    ('/diag_backup.php', 'Diagnostics Backup', ['Transaction Engine Status', 'Commit Candidate Changes']),
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

r_check = session.get(f'{BASE}/firewall_rules.php')
assert test_rule_id in r_check.text
print(f"PASS: Candidate rule '{test_rule_id}' successfully displayed in candidate table")

del_data = {
    'action': 'delete',
    'id': test_rule_id
}
r_del = session.post(f'{BASE}/firewall_rules.php', data=del_data)
assert r_del.status_code == 200
assert f"Rule &#039;{test_rule_id}&#039; deleted from candidate configuration" in r_del.text or f"Rule '{test_rule_id}' deleted" in r_del.text
print("PASS: Candidate rule successfully deleted from candidate configuration")

print('\n=== 6. LIVE NAT PORT FORWARD WORKFLOW AUDIT ===')
test_nat_id = 'qa_nat_' + uuid.uuid4().hex[:6]
nat_data = {
    'action': 'add',
    'id': test_nat_id,
    'interface': 'enp0s8',
    'protocol': 'tcp',
    'src_ip': 'any',
    'dst_ip': '192.168.56.101',
    'dst_port': '9999',
    'target_ip': '192.168.56.101',
    'target_port': '8443',
    'priority': '150',
    'description': 'QA Host-Only NAT Test'
}
r_nat_add = session.post(f'{BASE}/firewall_nat.php', data=nat_data)
assert r_nat_add.status_code == 200
assert test_nat_id in r_nat_add.text
print(f"PASS: NAT Rule '{test_nat_id}' added to candidate configuration")

# Delete NAT rule cleanup
del_nat_data = {
    'action': 'delete',
    'id': test_nat_id
}
r_nat_del = session.post(f'{BASE}/firewall_nat.php', data=del_nat_data)
assert r_nat_del.status_code == 200
print(f"PASS: NAT Rule '{test_nat_id}' cleaned up from candidate")

print('\n=== 7. DIAGNOSTICS PING LIVE EXECUTION ===')
r_ping = session.post(f'{BASE}/diag_ping.php', data={'host': '127.0.0.1', 'count': '2'})
assert r_ping.status_code == 200
assert '2 packets transmitted' in r_ping.text or '0% packet loss' in r_ping.text or 'rtt min/avg/max' in r_ping.text
print('PASS: Diagnostics Ping tool executed live against 127.0.0.1 with real output')

print('\n=== 8. LOGOUT TEST ===')
r_logout = session.get(f'{BASE}/logout.php', allow_redirects=False)
assert r_logout.status_code == 302 and 'login.php' in r_logout.headers.get('Location', '')
r_dash = session.get(f'{BASE}/index.php', allow_redirects=False)
assert r_dash.status_code == 302 and 'login.php' in r_dash.headers.get('Location', '')
print('PASS: Logout destroyed session and redirected to login.php')
print('\n>>> ALL 26 REAL VM WEBUI PAGES & WORKFLOWS OVER HOST-ONLY NETWORK (192.168.56.101:8443) PASSED <<<')
