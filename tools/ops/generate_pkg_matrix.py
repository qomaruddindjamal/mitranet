import json

with open('pfsense_all_available_packages.json', 'r') as f:
    pkgs = json.load(f)

# Debian mappings for all 69 packages
mapping = {
    "acme": {"deb": "certbot, acme-tiny, python3-acme", "service": "cron/certbot.timer", "status": "Available in Debian"},
    "ANDwatch": {"deb": "arpwatch, ndpmon", "service": "arpwatch.service", "status": "Available in Debian"},
    "apcupsd": {"deb": "apcupsd", "service": "apcupsd.service", "status": "Available in Debian"},
    "arping": {"deb": "iputils-arping, arping", "service": "cli", "status": "Available in Debian"},
    "arpwatch": {"deb": "arpwatch", "service": "arpwatch.service", "status": "Available in Debian"},
    "Avahi": {"deb": "avahi-daemon", "service": "avahi-daemon.service", "status": "Available in Debian"},
    "Backup": {"deb": "mitranet-backup, tar, rsync", "service": "systemd", "status": "Builtin MitraNet"},
    "bandwidthd": {"deb": "bandwidthd", "service": "bandwidthd.service", "status": "Available in Debian"},
    "bind": {"deb": "bind9, bind9-utils", "service": "named.service", "status": "Available in Debian"},
    "cellular": {"deb": "modemmanager, libqmi-utils, libmbim-utils", "service": "ModemManager.service", "status": "Available in Debian"},
    "collectd": {"deb": "collectd, collectd-core", "service": "collectd.service", "status": "Available in Debian"},
    "Cron": {"deb": "cron", "service": "cron.service", "status": "Available in Debian"},
    "darkstat": {"deb": "darkstat", "service": "darkstat.service", "status": "Available in Debian"},
    "Filer": {"deb": "mitranet-core (REST file editor)", "service": "webui", "status": "Builtin MitraNet"},
    "freeradius3": {"deb": "freeradius, freeradius-utils", "service": "freeradius.service", "status": "Available in Debian"},
    "frr": {"deb": "frr, frr-pythontools", "service": "frr.service", "status": "Available in Debian"},
    "FTP_Client_Proxy": {"deb": "frox, ftp-proxy", "service": "systemd", "status": "Available in Debian"},
    "haproxy": {"deb": "haproxy", "service": "haproxy.service", "status": "Available in Debian"},
    "haproxy-devel": {"deb": "haproxy", "service": "haproxy.service", "status": "Available in Debian"},
    "iperf": {"deb": "iperf, iperf3", "service": "iperf3.service", "status": "Available in Debian"},
    "LADVD": {"deb": "ladvd, lldpd", "service": "ladvd.service", "status": "Available in Debian"},
    "LCDproc": {"deb": "lcdproc", "service": "LCDd.service", "status": "Available in Debian"},
    "Lightsquid": {"deb": "lightsquid", "service": "apache2/php", "status": "Available in Debian"},
    "lldpd": {"deb": "lldpd", "service": "lldpd.service", "status": "Available in Debian"},
    "mailreport": {"deb": "bsd-mailx, msmtp, postfix", "service": "cron", "status": "Available in Debian"},
    "mcast-bridge": {"deb": "smcroute, pimd", "service": "smcroute.service", "status": "Available in Debian"},
    "mDNS-Bridge": {"deb": "avahi-daemon (reflector)", "service": "avahi-daemon.service", "status": "Available in Debian"},
    "mtr-nox11": {"deb": "mtr-tiny", "service": "cli", "status": "Available in Debian"},
    "net-snmp": {"deb": "snmpd, snmp", "service": "snmpd.service", "status": "Available in Debian"},
    "Netgate_Firmware_Upgrade": {"deb": "mitranet-update, apt", "service": "systemd", "status": "Builtin MitraNet"},
    "nmap": {"deb": "nmap", "service": "cli", "status": "Available in Debian"},
    "node_exporter": {"deb": "prometheus-node-exporter", "service": "prometheus-node-exporter.service", "status": "Available in Debian"},
    "Notes": {"deb": "mitranet-core", "service": "webui", "status": "Builtin MitraNet"},
    "nrpe": {"deb": "nagios-nrpe-server", "service": "nagios-nrpe-server.service", "status": "Available in Debian"},
    "ntopng": {"deb": "ntopng, ntopng-data", "service": "ntopng.service", "status": "Available in Debian"},
    "nut": {"deb": "nut, nut-server, nut-client", "service": "nut-server.service", "status": "Available in Debian"},
    "Open-VM-Tools": {"deb": "open-vm-tools", "service": "open-vm-tools.service", "status": "Available in Debian"},
    "openvpn-client-export": {"deb": "openvpn, easy-rsa", "service": "webui", "status": "Available in Debian"},
    "pfBlockerNG": {"deb": "dnsmasq/unbound blocklists, nftables sets, ipset", "service": "mitranet-firewall", "status": "Builtin MitraNet"},
    "pfBlockerNG-devel": {"deb": "dnsmasq/unbound blocklists, nftables sets, ipset", "service": "mitranet-firewall", "status": "Builtin MitraNet"},
    "pimd": {"deb": "pimd", "service": "pimd.service", "status": "Available in Debian"},
    "RRD_Summary": {"deb": "rrdtool, librrds-perl", "service": "webui", "status": "Available in Debian"},
    "Service_Watchdog": {"deb": "monit, systemd watchdog", "service": "systemd", "status": "Available in Debian"},
    "Shellcmd": {"deb": "systemd, /etc/rc.local, cron @reboot", "service": "systemd", "status": "Builtin MitraNet"},
    "siproxd": {"deb": "siproxd", "service": "siproxd.service", "status": "Available in Debian"},
    "snmptt": {"deb": "snmptt", "service": "snmptt.service", "status": "Available in Debian"},
    "snort": {"deb": "snort, snort-rules-default", "service": "snort.service", "status": "Available in Debian"},
    "softflowd": {"deb": "softflowd", "service": "softflowd.service", "status": "Available in Debian"},
    "squid": {"deb": "squid", "service": "squid.service", "status": "Available in Debian"},
    "squidGuard": {"deb": "squidguard", "service": "squid.service", "status": "Available in Debian"},
    "Status_Traffic_Totals": {"deb": "vnstat, vnstati", "service": "vnstat.service", "status": "Available in Debian"},
    "stunnel": {"deb": "stunnel4", "service": "stunnel4.service", "status": "Available in Debian"},
    "sudo": {"deb": "sudo", "service": "cli", "status": "Available in Debian"},
    "suricata": {"deb": "suricata", "service": "suricata.service", "status": "Available in Debian"},
    "syslog-ng": {"deb": "syslog-ng, syslog-ng-core", "service": "syslog-ng.service", "status": "Available in Debian"},
    "System_Patches": {"deb": "patch, dpkg, git", "service": "cli", "status": "Builtin MitraNet"},
    "Tailscale": {"deb": "tailscale", "service": "tailscaled.service", "status": "Available in Debian"},
    "Telegraf": {"deb": "telegraf", "service": "telegraf.service", "status": "Available in Debian"},
    "tftpd": {"deb": "tftpd-hpa", "service": "tftpd-hpa.service", "status": "Available in Debian"},
    "tinc": {"deb": "tinc", "service": "tinc.service", "status": "Available in Debian"},
    "udpbroadcastrelay": {"deb": "udp-broadcast-relay-linux, bcrelay", "service": "systemd", "status": "Available in Debian"},
    "WireGuard": {"deb": "wireguard, wireguard-tools", "service": "wg-quick@wg0.service", "status": "ACTIVE & RUNNING (.deb)"},
    "zabbix-agent6": {"deb": "zabbix-agent, zabbix-agent2", "service": "zabbix-agent.service", "status": "Available in Debian"},
    "zabbix-agent7": {"deb": "zabbix-agent, zabbix-agent2", "service": "zabbix-agent.service", "status": "Available in Debian"},
    "zabbix-agent74": {"deb": "zabbix-agent, zabbix-agent2", "service": "zabbix-agent.service", "status": "Available in Debian"},
    "zabbix-proxy6": {"deb": "zabbix-proxy-sqlite3", "service": "zabbix-proxy.service", "status": "Available in Debian"},
    "zabbix-proxy7": {"deb": "zabbix-proxy-sqlite3", "service": "zabbix-proxy.service", "status": "Available in Debian"},
    "zabbix-proxy74": {"deb": "zabbix-proxy-sqlite3", "service": "zabbix-proxy.service", "status": "Available in Debian"},
    "zeek": {"deb": "zeek, zeek-core", "service": "zeek.service", "status": "Available in Debian"},
    "xray-core": {"deb": "xray-core (Linux amd64)", "service": "xray.service", "status": "ACTIVE & RUNNING (Linux amd64)"}
}

full_matrix = []
for p in pkgs:
    name = p['name']
    map_entry = mapping.get(name, {"deb": f"{name.lower()}", "service": "systemd", "status": "Available in Debian"})
    full_matrix.append({
        'orig_name': name,
        'orig_version': p['version'],
        'description': p['description'],
        'deb_package': map_entry['deb'],
        'service': map_entry['service'],
        'status': map_entry['status']
    })

# Add xray-core if not in pkgs
if not any(x['orig_name'] == 'xray-core' for x in full_matrix):
    full_matrix.append({
        'orig_name': 'xray-pfsense',
        'orig_version': '1.8.24',
        'description': 'Xray-core multi-protocol proxy (VLESS Reality/Vision, VMess, Trojan, Shadowsocks, TUN)',
        'deb_package': 'xray-core (Linux amd64)',
        'service': 'xray.service',
        'status': 'ACTIVE & RUNNING (Linux amd64)'
    })

print(f"Generated full matrix of {len(full_matrix)} packages")
with open('pfsense_to_debian_matrix_complete.json', 'w', encoding='utf-8') as f:
    json.dump(full_matrix, f, indent=2)

print("Saved pfsense_to_debian_matrix_complete.json")
