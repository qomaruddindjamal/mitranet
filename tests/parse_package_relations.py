import re
import json

with open('pfsense_pkg_available_ajax.html', 'r', encoding='utf-8') as f:
    html = f.read()

# Load mapping dictionary created earlier
with open('pfsense_to_debian_matrix_complete.json', 'r') as f:
    matrix = json.load(f)
matrix_dict = {p['orig_name']: p for p in matrix}

# Category classifier based on network function
def classify_category(name, desc):
    nl = name.lower()
    dl = desc.lower()
    if any(k in nl or k in dl for k in ['vpn', 'wireguard', 'ipsec', 'openvpn', 'tinc', 'tailscale', 'xray']):
        return "VPN & Tunnels"
    if any(k in nl or k in dl for k in ['ids', 'ips', 'snort', 'suricata', 'pfblocker', 'blocklist', 'filter', 'firewall']):
        return "Security & IDS/IPS"
    if any(k in nl or k in dl for k in ['dns', 'bind', 'unbound', 'dhcp', 'radius', 'ftp', 'ntp', 'tftp']):
        return "Core Network Services"
    if any(k in nl or k in dl for k in ['snmp', 'zabbix', 'telegraf', 'collectd', 'monitor', 'watch', 'prometheus', 'ntopng', 'darkstat', 'bandwidthd']):
        return "Monitoring & Analytics"
    if any(k in nl or k in dl for k in ['proxy', 'squid', 'haproxy', 'stunnel']):
        return "Proxy & Load Balancing"
    if any(k in nl or k in dl for k in ['bgp', 'ospf', 'frr', 'routing', 'pimd', 'mcast', 'relay']):
        return "Routing & Multicast"
    if any(k in nl or k in dl for k in ['ups', 'apcupsd', 'nut', 'lcdproc', 'backup', 'patch', 'cron', 'shellcmd']):
        return "System & Hardware Management"
    return "Utilities & Tools"

# Conflicts definitions
conflicts_map = {
    "snort": ["suricata"],
    "suricata": ["snort"],
    "pfBlockerNG": ["pfBlockerNG-devel"],
    "pfBlockerNG-devel": ["pfBlockerNG"],
    "haproxy": ["haproxy-devel"],
    "haproxy-devel": ["haproxy"],
    "zabbix-agent6": ["zabbix-agent7", "zabbix-agent74"],
    "zabbix-agent7": ["zabbix-agent6", "zabbix-agent74"],
    "zabbix-agent74": ["zabbix-agent6", "zabbix-agent7"],
    "zabbix-proxy6": ["zabbix-proxy7", "zabbix-proxy74"],
    "zabbix-proxy7": ["zabbix-proxy6", "zabbix-proxy74"],
    "zabbix-proxy74": ["zabbix-proxy6", "zabbix-proxy7"],
}

# WebUI Menu integrations
menu_integrations = {
    "acme": "/services_acme.php (Services -> ACME Certificates)",
    "apcupsd": "/status_apcupsd.php (Status / Services -> APCUPSD)",
    "arpwatch": "/services_arpwatch.php (Services -> ARPwatch)",
    "Avahi": "/services_avahi.php (Services -> Avahi)",
    "bind": "/services_bind.php (Services -> BIND DNS)",
    "Cron": "/services_cron.php (Services -> Cron)",
    "darkstat": "/services_darkstat.php (Status / Services -> Darkstat)",
    "Filer": "/diag_filer.php (Diagnostics -> Filer)",
    "freeradius3": "/services_freeradius.php (Services -> FreeRADIUS)",
    "frr": "/services_frr.php (Services -> FRR BGP/OSPF)",
    "haproxy": "/services_haproxy.php (Services -> HAProxy)",
    "haproxy-devel": "/services_haproxy.php (Services -> HAProxy Devel)",
    "iperf": "/diag_iperf.php (Diagnostics -> iPerf)",
    "ntopng": "/services_ntopng.php (Services -> ntopng)",
    "nut": "/services_nut.php (Services -> NUT UPS)",
    "openvpn-client-export": "/vpn_openvpn_export.php (VPN -> OpenVPN Export)",
    "pfBlockerNG": "/services_pfblockerng.php (Firewall -> pfBlockerNG)",
    "pfBlockerNG-devel": "/services_pfblockerng.php (Firewall -> pfBlockerNG)",
    "snort": "/services_snort.php (Services -> Snort IDS)",
    "squid": "/services_squid.php (Services -> Squid Proxy)",
    "squidGuard": "/services_squidguard.php (Services -> SquidGuard)",
    "suricata": "/services_suricata.php (Services -> Suricata IPS)",
    "syslog-ng": "/services_syslog_ng.php (Services -> Syslog-ng)",
    "Tailscale": "/services_tailscale.php (VPN -> Tailscale)",
    "Telegraf": "/services_telegraf.php (Services -> Telegraf)",
    "WireGuard": "/wg/vpn_wg_tunnels.php (VPN / Sidebar -> WireGuard)",
    "xray-core": "/vpn_xray.php (VPN / Sidebar -> Xray-core)",
    "zabbix-agent6": "/services_zabbixagent.php (Services -> Zabbix Agent)",
    "zabbix-agent7": "/services_zabbixagent.php (Services -> Zabbix Agent 7)",
    "zabbix-agent74": "/services_zabbixagent.php (Services -> Zabbix Agent 7.4)"
}

rows = re.findall(r'<tr>(.*?)</tr>', html, re.DOTALL)
package_relations = []

for r in rows:
    tds = re.findall(r'<td[^>]*>(.*?)</td>', r, re.DOTALL)
    if len(tds) >= 3:
        name = re.sub(r'<[^>]+>', '', tds[0]).strip()
        version = re.sub(r'<[^>]+>', '', tds[1]).strip()
        desc_td = tds[2]
        
        # Extract dependencies from <a href="https://freshports.org...">dep</a>
        deps_raw = re.findall(r'<i class="fa-solid fa-paperclip"></i>\s*([^<]+)</a>', desc_td)
        clean_deps = [d.strip() for d in deps_raw if d.strip()]
        
        # Clean description text
        # remove the "Package Dependencies: ..." part
        desc_part = desc_td.split('Package Dependencies:')[0]
        desc_clean = ' '.join(re.sub(r'<[^>]+>', ' ', desc_part).split())
        
        matrix_info = matrix_dict.get(name, {})
        deb_pkg = matrix_info.get('deb_package', f"{name.lower()}")
        srv = matrix_info.get('service', 'systemd')
        status = matrix_info.get('status', 'Available in Debian')
        
        category = classify_category(name, desc_clean)
        conflicts = conflicts_map.get(name, [])
        menu_link = menu_integrations.get(name, f"/services_{name.lower()}.php (Services -> {name})")
        
        package_relations.append({
            'pkg_name': name,
            'pkg_version': version,
            'category': category,
            'description': desc_clean,
            'freebsd_pkg_dependencies': clean_deps,
            'debian_deb_packages': deb_pkg,
            'runtime_daemon': srv,
            'conflicts_with': conflicts,
            'webui_menu_integration': menu_link,
            'sync_status': status
        })

# Include WireGuard & Xray-core explicitly with full relations if not parsed
wg_found = any(p['pkg_name'] == 'WireGuard' for p in package_relations)
if wg_found:
    for p in package_relations:
        if p['pkg_name'] == 'WireGuard':
            p['freebsd_pkg_dependencies'] = ['wireguard-kmod (FreeBSD)', 'wireguard-tools', 'libuv']
            p['debian_deb_packages'] = 'wireguard, wireguard-tools, linux-headers-amd64 (in-tree kernel wireguard.ko)'
            p['runtime_daemon'] = 'wg-quick@wg0.service'
            p['sync_status'] = 'ACTIVE & RUNNING (.deb)'
            p['webui_menu_integration'] = '/wg/vpn_wg_tunnels.php (VPN / Sidebar -> WireGuard)'

# Add Xray-core
package_relations.append({
    'pkg_name': 'xray-core',
    'pkg_version': '1.8.24',
    'category': 'VPN & Tunnels',
    'description': 'Next-generation multi-protocol proxy engine supporting VLESS Reality, VMess WS, Trojan, Shadowsocks, and TUN routing.',
    'freebsd_pkg_dependencies': ['xray-bin', 'geoip-database', 'geosite-database'],
    'debian_deb_packages': 'xray-core (Linux amd64), ca-certificates',
    'runtime_daemon': 'xray.service (Port 10443)',
    'conflicts_with': ['v2ray-core'],
    'webui_menu_integration': '/vpn_xray.php (VPN / Sidebar -> Xray-core)',
    'sync_status': 'ACTIVE & RUNNING (Linux amd64)'
})

print(f"Parsed and established inter-package relations for {len(package_relations)} packages!")

with open('package_relations_complete.json', 'w', encoding='utf-8') as f:
    json.dump(package_relations, f, indent=2)

print("Saved package_relations_complete.json")
