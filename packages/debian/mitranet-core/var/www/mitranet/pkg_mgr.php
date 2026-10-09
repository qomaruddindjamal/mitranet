<?php
/*
 * pkg_mgr.php - MitraNet Package Manager: Available Packages & Synchronization
 * Full Inter-Package Relations, Dependencies, Conflicts & Debian Mapping Matrix
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("System", "Package Manager", "Available Packages");
$pglinks = array("", "/pkg_mgr_installed.php", "@self");
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$tab_array = array(
    array("Installed Packages", false, "/pkg_mgr_installed.php"),
    array("Available Packages &amp; Relations", true, "/pkg_mgr.php")
);
display_top_tabs($tab_array, false, 'pills');

// Complete Package Relations Matrix from pfSense 2.9 to Debian 13 (Trixie)
$packages_relations = array(
    array(
        'name' => 'acme',
        'version' => '1.3.2',
        'category' => 'Utilities & Tools',
        'desc' => 'Automated Certificate Management Environment, for automated use of LetsEncrypt certificates.',
        'deps' => array('php85-8.5.7', 'php85-ftp-8.5.7', 'pecl-ssh2-1.3.1', 'socat-1.8.1.1'),
        'deb' => 'certbot, acme-tiny, python3-acme',
        'service' => 'cron/certbot.timer',
        'conflicts' => array(),
        'menu' => '/services_acme.php (Services -> ACME Certificates)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'ANDwatch',
        'version' => '2.1_3',
        'category' => 'Monitoring & Analytics',
        'desc' => 'ANDwatch is a daemon for monitoring ARP and Neighbor Discovery activity, keeping track of IP to Ethernet address mappings, and providing notifications when the mappings change.',
        'deps' => array('andwatch-2.4.0'),
        'deb' => 'arpwatch, ndpmon',
        'service' => 'arpwatch.service',
        'conflicts' => array(),
        'menu' => '/services_andwatch.php (Services -> ANDwatch)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'apcupsd',
        'version' => '0.3.92_12',
        'category' => 'Monitoring & Analytics',
        'desc' => '"apcupsd" can be used for controlling all APC UPS models It can monitor and log the current power and battery status, perform automatic shutdown, and can run in network mode in order to power down other hosts on a LAN',
        'deps' => array('apcupsd-3.14.14_6'),
        'deb' => 'apcupsd',
        'service' => 'apcupsd.service',
        'conflicts' => array(),
        'menu' => '/status_apcupsd.php (Status / Services -> APCUPSD)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'arping',
        'version' => '1.2.2_7',
        'category' => 'Utilities & Tools',
        'desc' => 'Broadcasts a who-has ARP packet on the network and prints answers.',
        'deps' => array('arping-2.25'),
        'deb' => 'iputils-arping, arping',
        'service' => 'cli',
        'conflicts' => array(),
        'menu' => '/services_arping.php (Services -> arping)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'arpwatch',
        'version' => '0.2.5',
        'category' => 'Monitoring & Analytics',
        'desc' => 'This package contains tools that monitors ethernet activity and maintains a database of ethernet/ip address pairings. It also reports certain changes via email.',
        'deps' => array('arpwatch-3.9'),
        'deb' => 'arpwatch',
        'service' => 'arpwatch.service',
        'conflicts' => array(),
        'menu' => '/services_arpwatch.php (Services -> ARPwatch)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'Avahi',
        'version' => '2.2_10',
        'category' => 'Core Network Services',
        'desc' => 'Avahi is a system which facilitates host and service discovery in local networks via mDNS (Multicast DNS) and DNS-SD (DNS Service Discovery). This package allows mDNS/DNS-SD protocols to work across multiple LAN segments. mDNS/DNS-SD is known in Apple circles as "Bonjour" and is part of the Zeroconf suite of protocols.',
        'deps' => array('avahi-app-0.8_6', 'nss_mdns-0.14.1.20200624_1'),
        'deb' => 'avahi-daemon',
        'service' => 'avahi-daemon.service',
        'conflicts' => array(),
        'menu' => '/services_avahi.php (Services -> Avahi)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'Backup',
        'version' => '0.7',
        'category' => 'System & Hardware Management',
        'desc' => 'Tool to Backup and Restore files and directories.',
        'deps' => array(),
        'deb' => 'mitranet-backup, tar, rsync',
        'service' => 'systemd',
        'conflicts' => array(),
        'menu' => '/services_backup.php (Services -> Backup)',
        'status' => 'Builtin MitraNet'
    ),
    array(
        'name' => 'bandwidthd',
        'version' => '0.7.11',
        'category' => 'VPN & Tunnels',
        'desc' => 'BandwidthD tracks usage of TCP/IP network subnets and builds html files with graphs to display utilization. Charts are built by individual IPs, and by default display utilization over 2 day, 8 day, 40 day, and 400 day periods. Furthermore, each IP address\'s utilization can be logged out in CDF format, or to a backend database server. HTTP, TCP, UDP, ICMP, VPN, and P2P traffic are color coded.',
        'deps' => array('bandwidthd-2.0.1_12'),
        'deb' => 'bandwidthd',
        'service' => 'bandwidthd.service',
        'conflicts' => array(),
        'menu' => '/services_bandwidthd.php (Services -> bandwidthd)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'bind',
        'version' => '9.20_8',
        'category' => 'Core Network Services',
        'desc' => 'pfSense GUI for BIND DNS server',
        'deps' => array('bind920-9.20.23'),
        'deb' => 'bind9, bind9-utils',
        'service' => 'named.service',
        'conflicts' => array(),
        'menu' => '/services_bind.php (Services -> BIND DNS)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'cellular',
        'version' => '1.2.8',
        'category' => 'Utilities & Tools',
        'desc' => 'pfSense GUI for Cellular Cards Currently it supports certain Huawei models, Simcom and Quectel EC25.',
        'deps' => array('py-pyserial-3.5_4', 'python311-3.11.15_3'),
        'deb' => 'modemmanager, libqmi-utils, libmbim-utils',
        'service' => 'ModemManager.service',
        'conflicts' => array(),
        'menu' => '/services_cellular.php (Services -> cellular)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'collectd',
        'version' => '1.1',
        'category' => 'Monitoring & Analytics',
        'desc' => 'collectd is a small daemon written in C for performance. It reads various system & network statistics and can forward them to another collectd server for storage.',
        'deps' => array('collectd5-5.12.0_24'),
        'deb' => 'collectd, collectd-core',
        'service' => 'collectd.service',
        'conflicts' => array(),
        'menu' => '/services_collectd.php (Services -> collectd)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'Cron',
        'version' => '0.3.8_8',
        'category' => 'System & Hardware Management',
        'desc' => 'The cron utility is used to manage commands on a schedule.',
        'deps' => array(),
        'deb' => 'cron',
        'service' => 'cron.service',
        'conflicts' => array(),
        'menu' => '/services_cron.php (Services -> Cron)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'darkstat',
        'version' => '3.1.6',
        'category' => 'Monitoring & Analytics',
        'desc' => 'darkstat is a network statistics gatherer. It\'s a packet sniffer that runs as a background process on a cable/DSL router, gathers all sorts of statistics about network usage, and serves them over HTTP.',
        'deps' => array('darkstat-3.0.721_1'),
        'deb' => 'darkstat',
        'service' => 'darkstat.service',
        'conflicts' => array(),
        'menu' => '/services_darkstat.php (Status / Services -> Darkstat)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'Filer',
        'version' => '0.62.2',
        'category' => 'Utilities & Tools',
        'desc' => 'Allows you to create and overwrite files from the GUI.',
        'deps' => array(),
        'deb' => 'mitranet-core (REST file editor)',
        'service' => 'webui',
        'conflicts' => array(),
        'menu' => '/diag_filer.php (Diagnostics -> Filer)',
        'status' => 'Builtin MitraNet'
    ),
    array(
        'name' => 'freeradius3',
        'version' => '0.16.4_1',
        'category' => 'Core Network Services',
        'desc' => 'A free implementation of the RADIUS protocol. Supports MySQL, PostgreSQL, LDAP, Kerberos.',
        'deps' => array('bash-5.3.15', 'freeradius3-3.2.10', 'python311-3.11.15_3'),
        'deb' => 'freeradius, freeradius-utils',
        'service' => 'freeradius.service',
        'conflicts' => array(),
        'menu' => '/services_freeradius.php (Services -> FreeRADIUS)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'frr',
        'version' => '2.1.3_3',
        'category' => 'Routing & Multicast',
        'desc' => 'FRR routing daemon for BGP, OSPF, and OSPF6 Conflicts with Quagga OSPF and OpenBGPD. These packages cannot be installed at the same time.',
        'deps' => array('frr10-10.7.1', 'frr10-pythontools-10.7.1'),
        'deb' => 'frr, frr-pythontools',
        'service' => 'frr.service',
        'conflicts' => array(),
        'menu' => '/services_frr.php (Services -> FRR BGP/OSPF)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'FTP_Client_Proxy',
        'version' => '0.3_12',
        'category' => 'Core Network Services',
        'desc' => 'Basic FTP Client Proxy using ftp-proxy from FreeBSD.',
        'deps' => array(),
        'deb' => 'frox, ftp-proxy',
        'service' => 'systemd',
        'conflicts' => array(),
        'menu' => '/services_ftp_client_proxy.php (Services -> FTP_Client_Proxy)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'haproxy',
        'version' => '0.65.7',
        'category' => 'Proxy & Load Balancing',
        'desc' => 'The Reliable, High Performance TCP/HTTP(S) Load Balancer. This package implements the TCP, HTTP and HTTPS balancing features from haproxy. Supports ACLs for smart backend switching.',
        'deps' => array('haproxy-3.2.19'),
        'deb' => 'haproxy',
        'service' => 'haproxy.service',
        'conflicts' => array('haproxy-devel'),
        'menu' => '/services_haproxy.php (Services -> HAProxy)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'haproxy-devel',
        'version' => '0.66.7',
        'category' => 'Proxy & Load Balancing',
        'desc' => 'The Reliable, High Performance TCP/HTTP(S) Load Balancer. This package implements the TCP, HTTP and HTTPS balancing features from haproxy. Supports ACLs for smart backend switching.',
        'deps' => array('haproxy-devel-3.3.d10'),
        'deb' => 'haproxy',
        'service' => 'haproxy.service',
        'conflicts' => array('haproxy'),
        'menu' => '/services_haproxy.php (Services -> HAProxy Devel)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'iperf',
        'version' => '3.0.6',
        'category' => 'Utilities & Tools',
        'desc' => 'Iperf is a tool for testing network throughput, loss, and jitter.',
        'deps' => array('iperf3-3.21'),
        'deb' => 'iperf, iperf3',
        'service' => 'iperf3.service',
        'conflicts' => array(),
        'menu' => '/diag_iperf.php (Diagnostics -> iPerf)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'LADVD',
        'version' => '1.2.2_7',
        'category' => 'Utilities & Tools',
        'desc' => 'Send and decode link layer advertisements. Support for LLDP (Link Layer Discovery Protocol), CDP (Cisco Discovery Protocol), EDP (Extreme Discovery Protocol) and NDP (Nortel Discovery Protocol).',
        'deps' => array('ladvd-1.1.4'),
        'deb' => 'ladvd, lldpd',
        'service' => 'ladvd.service',
        'conflicts' => array(),
        'menu' => '/services_ladvd.php (Services -> LADVD)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'LCDproc',
        'version' => '0.12.4',
        'category' => 'System & Hardware Management',
        'desc' => 'LCD display driver.',
        'deps' => array('lcdproc-0.5.9_1'),
        'deb' => 'lcdproc',
        'service' => 'LCDd.service',
        'conflicts' => array(),
        'menu' => '/services_lcdproc.php (Services -> LCDproc)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'Lightsquid',
        'version' => '3.0.11',
        'category' => 'Proxy & Load Balancing',
        'desc' => 'LightSquid is a high performance web proxy reporting tool. Includes proxy realtime statistics (SQStat). Requires Squid package.',
        'deps' => array('lightsquid-1.8_5', 'lighttpd-1.4.83'),
        'deb' => 'lightsquid',
        'service' => 'apache2/php',
        'conflicts' => array(),
        'menu' => '/services_lightsquid.php (Services -> Lightsquid)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'lldpd',
        'version' => '0.9.13',
        'category' => 'Utilities & Tools',
        'desc' => 'lldpd provies support for the 802.1ab Link Layer Discovery Protocol (LLDP), as well as support for several proprietary discovery protocols including Cisco Discovery Protocol (CDP), Extreme Discovery Protocol (EDP), Foundry Discovery Protocol (FDP), and Nortel Discovery Protocol (NDP / SONMP).',
        'deps' => array('lldpd-1.0.21'),
        'deb' => 'lldpd',
        'service' => 'lldpd.service',
        'conflicts' => array(),
        'menu' => '/services_lldpd.php (Services -> lldpd)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'mailreport',
        'version' => '3.6.4_7',
        'category' => 'Utilities & Tools',
        'desc' => 'Allows you to setup periodic e-mail reports containing command output, and log file contents',
        'deps' => array(),
        'deb' => 'bsd-mailx, msmtp, postfix',
        'service' => 'cron',
        'conflicts' => array(),
        'menu' => '/services_mailreport.php (Services -> mailreport)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'mcast-bridge',
        'version' => '1.4',
        'category' => 'Routing & Multicast',
        'desc' => 'Multicast Bridge is a daemon for forwarding UDP multicast data between network interfaces, supporting both IPv4 and IPv6. It supports statically configured forwarding, and dynamic interest-based forwarding using IGMP and MLD.',
        'deps' => array('mcast-bridge-1.7.0'),
        'deb' => 'smcroute, pimd',
        'service' => 'smcroute.service',
        'conflicts' => array(),
        'menu' => '/services_mcast-bridge.php (Services -> mcast-bridge)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'mDNS-Bridge',
        'version' => '3.0',
        'category' => 'Core Network Services',
        'desc' => 'mDNS Bridge is a daemon for sharing Multicast DNS (mDNS) information across multiple network interfaces. This allows local DNS Service Discovery (DNS-SD) across multiple network segments, which is commonly used for sharing network print services, audio/video streaming, home automation, etc.',
        'deps' => array('mdns-bridge-3.0.0'),
        'deb' => 'avahi-daemon (reflector)',
        'service' => 'avahi-daemon.service',
        'conflicts' => array(),
        'menu' => '/services_mdns-bridge.php (Services -> mDNS-Bridge)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'mtr-nox11',
        'version' => '0.85.6_6',
        'category' => 'Utilities & Tools',
        'desc' => 'Enhanced traceroute replacement. mtr combines the functionality of the traceroute and ping programs in a single network diagnostic tool.',
        'deps' => array('mtr-0.96'),
        'deb' => 'mtr-tiny',
        'service' => 'cli',
        'conflicts' => array(),
        'menu' => '/services_mtr-nox11.php (Services -> mtr-nox11)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'net-snmp',
        'version' => '0.1.6',
        'category' => 'Monitoring & Analytics',
        'desc' => 'A GUI for the NET-SNMP Daemon.',
        'deps' => array('net-snmp-5.9.5.2,1'),
        'deb' => 'snmpd, snmp',
        'service' => 'snmpd.service',
        'conflicts' => array(),
        'menu' => '/services_net-snmp.php (Services -> net-snmp)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'Netgate_Firmware_Upgrade',
        'version' => '0.47.3',
        'category' => 'Utilities & Tools',
        'desc' => 'Provide a mechanism to update firmware of Netgate hardware',
        'deps' => array('flashrom-1.6.0_1', 'libuuid-2.42.1'),
        'deb' => 'mitranet-update, apt',
        'service' => 'systemd',
        'conflicts' => array(),
        'menu' => '/services_netgate_firmware_upgrade.php (Services -> Netgate_Firmware_Upgrade)',
        'status' => 'Builtin MitraNet'
    ),
    array(
        'name' => 'nmap',
        'version' => '1.4.4_12',
        'category' => 'Utilities & Tools',
        'desc' => 'Nmap is a utility for network exploration or security auditing. It supports ping scanning (determine which hosts are up), many port scanning techniques (determine what services the hosts are offering), version detection (determine what application/service is running on a port), and TCP/IP fingerprinting (remote host OS or device identification). It also offers flexible target and port specification, decoy/stealth scanning, SunRPC scanning, and more.',
        'deps' => array('nmap-7.99_1'),
        'deb' => 'nmap',
        'service' => 'cli',
        'conflicts' => array(),
        'menu' => '/services_nmap.php (Services -> nmap)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'node_exporter',
        'version' => '0.18.1_8',
        'category' => 'Monitoring & Analytics',
        'desc' => 'Prometheus exporter for machine metrics',
        'deps' => array('node_exporter-1.11.0_3', 'python311-3.11.15_3'),
        'deb' => 'prometheus-node-exporter',
        'service' => 'prometheus-node-exporter.service',
        'conflicts' => array(),
        'menu' => '/services_node_exporter.php (Services -> node_exporter)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'Notes',
        'version' => '0.2.9_6',
        'category' => 'Utilities & Tools',
        'desc' => 'Track things you want to note for this system.',
        'deps' => array(),
        'deb' => 'mitranet-core',
        'service' => 'webui',
        'conflicts' => array(),
        'menu' => '/services_notes.php (Services -> Notes)',
        'status' => 'Builtin MitraNet'
    ),
    array(
        'name' => 'nrpe',
        'version' => '4.3.1_2',
        'category' => 'Utilities & Tools',
        'desc' => 'pfSense software package GUI for Nagios NRPE nrpe is used to execute Nagios plugins on remote hosts and report the results to the main Nagios server. From the Nagios homepage: Allows you to execute "local" plugins (like check_disk, check_procs, etc.) on remote hosts. The check_nrpe plugin is called from Nagios and actually makes the plugin requests to the remote host. Requires that nrpe be running on the remote host (either as a standalone daemon or as a service under inetd).',
        'deps' => array('nrpe-4.1.3'),
        'deb' => 'nagios-nrpe-server',
        'service' => 'nagios-nrpe-server.service',
        'conflicts' => array(),
        'menu' => '/services_nrpe.php (Services -> nrpe)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'ntopng',
        'version' => '6.2.0_7',
        'category' => 'Monitoring & Analytics',
        'desc' => 'ntopng (replaces ntop) is a network probe that shows network usage in a way similar to what top does for processes. In interactive mode, it displays the network status on the user\'s terminal. In Web mode it acts as a Web server, creating an HTML dump of the network status. It sports a NetFlow/sFlow emitter/collector, an HTTP-based client interface for creating ntop-centric monitoring applications, and RRD for persistently storing traffic statistics.',
        'deps' => array('gdbm-1.26', 'graphviz-15.0.0_1', 'libmaxminddb-1.13.3', 'ntopng-6.6.d20260604,1', 'redis-8.8.0', 'webfonts-0.30_14'),
        'deb' => 'ntopng, ntopng-data',
        'service' => 'ntopng.service',
        'conflicts' => array(),
        'menu' => '/services_ntopng.php (Services -> ntopng)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'nut',
        'version' => '2.8.2_9',
        'category' => 'Monitoring & Analytics',
        'desc' => 'Network UPS Tools provides support for monitoring of Uninterruptible Power Supplies. It supports UPS units attached locally via USB or serial, and remote units via the SNMP protocol, the APCUPSD protocol or the NUT protocol.',
        'deps' => array('nut-2.8.5_1'),
        'deb' => 'nut, nut-server, nut-client',
        'service' => 'nut-server.service',
        'conflicts' => array(),
        'menu' => '/services_nut.php (Services -> NUT UPS)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'Open-VM-Tools',
        'version' => '10.1.0_8,1',
        'category' => 'Utilities & Tools',
        'desc' => 'VMware Tools is a suite of utilities that enhances the performance of the virtual machine\'s guest operating system and improves management of the virtual machine.',
        'deps' => array('open-vm-tools-13.1.0,2'),
        'deb' => 'open-vm-tools',
        'service' => 'open-vm-tools.service',
        'conflicts' => array(),
        'menu' => '/services_open-vm-tools.php (Services -> Open-VM-Tools)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'openvpn-client-export',
        'version' => '1.9.13',
        'category' => 'VPN & Tunnels',
        'desc' => 'Exports pre-configured OpenVPN Client configurations directly from pfSense software.',
        'deps' => array('7-zip-26.01', 'openvpn-2.7.5', 'openvpn-client-export-2.7.4', 'zip-3.0_5'),
        'deb' => 'openvpn, easy-rsa',
        'service' => 'webui',
        'conflicts' => array(),
        'menu' => '/vpn_openvpn_export.php (VPN -> OpenVPN Export)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'pfBlockerNG',
        'version' => '3.2.17_1',
        'category' => 'Security & IDS/IPS',
        'desc' => 'Manage IPv4/v6 List Sources into \'Deny, Permit or Match\' formats. GeoIP database by MaxMind Inc. (GeoLite2 Free version). De-Duplication, Suppression, and Reputation enhancements. Provision to download from diverse List formats. Advanced Integration for Proofpoint ET IQRisk IP Reputation Threat Sources. Domain Name (DNSBL) blocking via Unbound DNS Resolver.',
        'deps' => array('gnugrep-3.12', 'grepcidr-2.0_1', 'iprange-2.0.0_1', 'jq-1.8.1', 'libmaxminddb-1.13.3', 'lighttpd-1.4.83', 'php85-8.5.7', 'php85-intl-8.5.7', 'py-maxminddb-2.8.2', 'py-sqlite3-3.11.15_10', 'python311-3.11.15_3', 'rsync-3.4.4'),
        'deb' => 'dnsmasq/unbound blocklists, nftables sets, ipset',
        'service' => 'mitranet-firewall',
        'conflicts' => array('pfBlockerNG-devel'),
        'menu' => '/services_pfblockerng.php (Firewall -> pfBlockerNG)',
        'status' => 'Builtin MitraNet'
    ),
    array(
        'name' => 'pfBlockerNG-devel',
        'version' => '3.2.17_1',
        'category' => 'Security & IDS/IPS',
        'desc' => 'pfBlockerNG-devel is the Next Generation of pfBlockerNG. Manage IPv4/v6 List Sources into \'Deny, Permit or Match\' formats. GeoIP database by MaxMind Inc. (GeoLite2 Free version). De-Duplication, Suppression, and Reputation enhancements. Provision to download from diverse List formats. Advanced Integration for Proofpoint ET IQRisk IP Reputation Threat Sources. Domain Name (DNSBL) blocking via Unbound DNS Resolver.',
        'deps' => array('gnugrep-3.12', 'grepcidr-2.0_1', 'iprange-2.0.0_1', 'jq-1.8.1', 'libmaxminddb-1.13.3', 'lighttpd-1.4.83', 'php85-8.5.7', 'php85-intl-8.5.7', 'py-maxminddb-2.8.2', 'py-sqlite3-3.11.15_10', 'python311-3.11.15_3', 'rsync-3.4.4'),
        'deb' => 'dnsmasq/unbound blocklists, nftables sets, ipset',
        'service' => 'mitranet-firewall',
        'conflicts' => array('pfBlockerNG'),
        'menu' => '/services_pfblockerng.php (Firewall -> pfBlockerNG)',
        'status' => 'Builtin MitraNet'
    ),
    array(
        'name' => 'pimd',
        'version' => '0.0.3_9',
        'category' => 'Routing & Multicast',
        'desc' => 'PIMD Multicast Routing. Lightweight, stand-alone implementation of Protocol Independent Multicast-Sparse Mode. Conflicts with Quagga OSPF. These packages cannot be installed at the same time.',
        'deps' => array('pimd-2.3.2b_1'),
        'deb' => 'pimd',
        'service' => 'pimd.service',
        'conflicts' => array(),
        'menu' => '/services_pimd.php (Services -> pimd)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'RRD_Summary',
        'version' => '2.2.2',
        'category' => 'Utilities & Tools',
        'desc' => 'RRD Summary Page, which will give estimated month-over-month traffic passed In/Out during the specified period.',
        'deps' => array(),
        'deb' => 'rrdtool, librrds-perl',
        'service' => 'webui',
        'conflicts' => array(),
        'menu' => '/services_rrd_summary.php (Services -> RRD_Summary)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'Service_Watchdog',
        'version' => '1.8.7_7',
        'category' => 'Monitoring & Analytics',
        'desc' => 'Monitors for stopped services and restarts them.',
        'deps' => array(),
        'deb' => 'monit, systemd watchdog',
        'service' => 'systemd',
        'conflicts' => array(),
        'menu' => '/services_service_watchdog.php (Services -> Service_Watchdog)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'Shellcmd',
        'version' => '1.0.7',
        'category' => 'System & Hardware Management',
        'desc' => 'The shellcmd utility is used to manage commands on system startup.',
        'deps' => array(),
        'deb' => 'systemd, /etc/rc.local, cron @reboot',
        'service' => 'systemd',
        'conflicts' => array(),
        'menu' => '/services_shellcmd.php (Services -> Shellcmd)',
        'status' => 'Builtin MitraNet'
    ),
    array(
        'name' => 'siproxd',
        'version' => '1.1.8_1',
        'category' => 'Proxy & Load Balancing',
        'desc' => 'Proxy for handling NAT of multiple SIP devices to a single public IP.',
        'deps' => array('siproxd-0.8.4'),
        'deb' => 'siproxd',
        'service' => 'siproxd.service',
        'conflicts' => array(),
        'menu' => '/services_siproxd.php (Services -> siproxd)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'snmptt',
        'version' => '1.0.3',
        'category' => 'Monitoring & Analytics',
        'desc' => 'SNMPTT (SNMP Trap Translator) is an SNMP trap handler written in Perl for use with the Net-SNMP. Easy to setup and use.',
        'deps' => array('snmptt-1.5_1'),
        'deb' => 'snmptt',
        'service' => 'snmptt.service',
        'conflicts' => array(),
        'menu' => '/services_snmptt.php (Services -> snmptt)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'snort',
        'version' => '4.1.10',
        'category' => 'Security & IDS/IPS',
        'desc' => 'Snort is an open source network intrusion prevention and detection system (IDS/IPS). Combining the benefits of signature, protocol, and anomaly-based inspection.',
        'deps' => array('snort-2.9.20_9'),
        'deb' => 'snort, snort-rules-default',
        'service' => 'snort.service',
        'conflicts' => array('suricata'),
        'menu' => '/services_snort.php (Services -> Snort IDS)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'softflowd',
        'version' => '1.2.6_4',
        'category' => 'System & Hardware Management',
        'desc' => 'Softflowd is flow-based network traffic analyser capable of Cisco NetFlow data export. Softflowd semi-statefully tracks traffic flows recorded by listening on a network interface or by reading a packet capture file. These flows may be reported via NetFlow to a collecting host or summarised within softflowd itself. Softflowd supports Netflow versions 1, 5, 9 and 10 (IPFIX) and is fully IPv6-capable - it can track IPv6 flows and send export datagrams via IPv6. It also supports export to multicast groups, allowing for redundant flow collectors.',
        'deps' => array('softflowd-1.0.0_1'),
        'deb' => 'softflowd',
        'service' => 'softflowd.service',
        'conflicts' => array(),
        'menu' => '/services_softflowd.php (Services -> softflowd)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'squid',
        'version' => '0.5.11',
        'category' => 'Security & IDS/IPS',
        'desc' => 'High performance web proxy cache (3.5 branch). It combines Squid as a proxy server with its capabilities of acting as a HTTP / HTTPS reverse proxy. It includes an Exchange-Web-Access (OWA) Assistant, SSL filtering and antivirus integration via C-ICAP.',
        'deps' => array('c-icap-modules-0.5.7_1', 'squid-7.4', 'squid_radius_auth-1.10', 'squidclamav-7.3_2'),
        'deb' => 'squid',
        'service' => 'squid.service',
        'conflicts' => array(),
        'menu' => '/services_squid.php (Services -> Squid Proxy)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'squidGuard',
        'version' => '1.16.30',
        'category' => 'Security & IDS/IPS',
        'desc' => 'High performance web proxy URL filter.',
        'deps' => array('pfSense-pkg-squid-0.5.11', 'squidguard-1.4_15'),
        'deb' => 'squidguard',
        'service' => 'squid.service',
        'conflicts' => array(),
        'menu' => '/services_squidguard.php (Services -> SquidGuard)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'Status_Traffic_Totals',
        'version' => '2.3.5_3',
        'category' => 'Utilities & Tools',
        'desc' => 'Traffic Totals page under the Status menu, which will give a total amount of traffic passed In/Out over the period of hours, days, and months. Uses vnStat for data collection.',
        'deps' => array('vnstat-2.13'),
        'deb' => 'vnstat, vnstati',
        'service' => 'vnstat.service',
        'conflicts' => array(),
        'menu' => '/services_status_traffic_totals.php (Services -> Status_Traffic_Totals)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'stunnel',
        'version' => '5.52.2_1',
        'category' => 'Proxy & Load Balancing',
        'desc' => 'SSL encryption wrapper between remote client and local or remote servers.',
        'deps' => array('stunnel-5.78,1'),
        'deb' => 'stunnel4',
        'service' => 'stunnel4.service',
        'conflicts' => array(),
        'menu' => '/services_stunnel.php (Services -> stunnel)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'sudo',
        'version' => '0.3.4',
        'category' => 'Utilities & Tools',
        'desc' => 'sudo allows delegation of privileges to users in the shell so commands can be run as other users, such as root.',
        'deps' => array('sudo-1.9.17p2_2'),
        'deb' => 'sudo',
        'service' => 'cli',
        'conflicts' => array(),
        'menu' => '/services_sudo.php (Services -> sudo)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'suricata',
        'version' => '7.0.9_1',
        'category' => 'Security & IDS/IPS',
        'desc' => 'High Performance Network IDS, IPS and Security Monitoring engine by OISF.',
        'deps' => array('suricata-8.0.5_1'),
        'deb' => 'suricata',
        'service' => 'suricata.service',
        'conflicts' => array('snort'),
        'menu' => '/services_suricata.php (Services -> Suricata IPS)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'syslog-ng',
        'version' => '1.16.5',
        'category' => 'Utilities & Tools',
        'desc' => 'Syslog-ng syslog server. This service is not intended to replace the default pfSense syslog server but rather acts as an independent syslog server.',
        'deps' => array('logrotate-3.22.0', 'syslog-ng-4.11.0_2'),
        'deb' => 'syslog-ng, syslog-ng-core',
        'service' => 'syslog-ng.service',
        'conflicts' => array(),
        'menu' => '/services_syslog_ng.php (Services -> Syslog-ng)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'System_Patches',
        'version' => '2.3.5_1',
        'category' => 'System & Hardware Management',
        'desc' => 'A package to apply and maintain custom and recommended system patches.',
        'deps' => array(),
        'deb' => 'patch, dpkg, git',
        'service' => 'cli',
        'conflicts' => array(),
        'menu' => '/services_system_patches.php (Services -> System_Patches)',
        'status' => 'Builtin MitraNet'
    ),
    array(
        'name' => 'Tailscale',
        'version' => '0.1.10',
        'category' => 'VPN & Tunnels',
        'desc' => 'Tailscale is a mesh VPN alternative, based on WireGuard, that connects your computers, databases, and services together securely without any proxies.',
        'deps' => array('tailscale-1.98.5_1'),
        'deb' => 'tailscale',
        'service' => 'tailscaled.service',
        'conflicts' => array(),
        'menu' => '/services_tailscale.php (VPN -> Tailscale)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'Telegraf',
        'version' => '0.9.3',
        'category' => 'Monitoring & Analytics',
        'desc' => 'Telegraf is an agent written in Go for collecting, processing, aggregating, and writing metrics.',
        'deps' => array('telegraf-1.39.0'),
        'deb' => 'telegraf',
        'service' => 'telegraf.service',
        'conflicts' => array(),
        'menu' => '/services_telegraf.php (Services -> Telegraf)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'tftpd',
        'version' => '0.2',
        'category' => 'Core Network Services',
        'desc' => 'tftpd installs and runs a TFTP server. We use the versatile tftp-hpa server.',
        'deps' => array('tftp-hpa-5.2_3'),
        'deb' => 'tftpd-hpa',
        'service' => 'tftpd-hpa.service',
        'conflicts' => array(),
        'menu' => '/services_tftpd.php (Services -> tftpd)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'tinc',
        'version' => '1.0.39',
        'category' => 'VPN & Tunnels',
        'desc' => 'tinc is a Virtual Private Network (VPN) daemon that uses tunnelling and encryption to create a secure private network between hosts on the Internet. Because the tunnel appears to the IP level network code as a normal network device, there is no need to adapt any existing software. This tunnelling allows VPN sites to share information with each other over the Internet without exposing any information to others. A single tinc daemon can accept more than one connection at a time, thus making it possible to create larger virtual networks, because some limitations are circumvented. Instead of most other VPN implementations, tinc encapsulates each network packet in its own UDP packet, instead of encapsulating all into one TCP or even PPP over TCP stream. This results in lower latencies, less overhead, and in general better responsiveness and throughput. LICENSE: GPL3 or later with execption to link with OpenSSL',
        'deps' => array('tinc-1.0.37'),
        'deb' => 'tinc',
        'service' => 'tinc.service',
        'conflicts' => array(),
        'menu' => '/services_tinc.php (Services -> tinc)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'udpbroadcastrelay',
        'version' => '1.2.8',
        'category' => 'Routing & Multicast',
        'desc' => 'A GUI for UDP Broadcast Relay. This program listens for UDP broadcast packets and retransmits on additional interfaces.',
        'deps' => array('udpbroadcastrelay-1.1'),
        'deb' => 'udp-broadcast-relay-linux, bcrelay',
        'service' => 'systemd',
        'conflicts' => array(),
        'menu' => '/services_udpbroadcastrelay.php (Services -> udpbroadcastrelay)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'WireGuard',
        'version' => '0.2.13_4',
        'category' => 'VPN & Tunnels',
        'desc' => 'WireGuard(R) is an extremely simple yet fast and modern VPN that utilizes state-of-the-art cryptography. It aims to be faster, simpler, leaner, and more useful than IPSec, while avoiding the massive headache. It intends to be considerably more performant than OpenVPN. WireGuard is designed as a general purpose VPN for running on embedded interfaces and super computers alike, fit for many different circumstances. Initially released for the Linux kernel, it is now cross-platform and widely deployable. It is currently under heavy development, but already it might be regarded as the most secure, easiest to use, and simplest VPN solution in the industry.',
        'deps' => array('wireguard-kmod (FreeBSD)', 'wireguard-tools', 'libuv'),
        'deb' => 'wireguard, wireguard-tools, linux-headers-amd64 (in-tree kernel wireguard.ko)',
        'service' => 'wg-quick@wg0.service',
        'conflicts' => array(),
        'menu' => '/wg/vpn_wg_tunnels.php (VPN / Sidebar -> WireGuard)',
        'status' => 'ACTIVE & RUNNING (.deb)'
    ),
    array(
        'name' => 'zabbix-agent6',
        'version' => '1.1_1',
        'category' => 'Monitoring & Analytics',
        'desc' => 'Zabbix agent is deployed on a monitoring target to actively monitor local resources and applications (hard drives, memory, processor statistics etc). The agent gathers operational information locally and reports data to Zabbix server for further processing. In case of failures (such as a hard disk running full or a crashed service process), Zabbix server can actively alert the administrators of the particular machine that reported the failure. Zabbix is an enterprise-class open source distributed monitoring solution.',
        'deps' => array('zabbix6-agent-6.0.46'),
        'deb' => 'zabbix-agent, zabbix-agent2',
        'service' => 'zabbix-agent.service',
        'conflicts' => array('zabbix-agent7', 'zabbix-agent74'),
        'menu' => '/services_zabbixagent.php (Services -> Zabbix Agent)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'zabbix-agent7',
        'version' => '1.1_1',
        'category' => 'Monitoring & Analytics',
        'desc' => 'Zabbix agent is deployed on a monitoring target to actively monitor local resources and applications (hard drives, memory, processor statistics etc). The agent gathers operational information locally and reports data to Zabbix server for further processing. In case of failures (such as a hard disk running full or a crashed service process), Zabbix server can actively alert the administrators of the particular machine that reported the failure. Zabbix is an enterprise-class open source distributed monitoring solution.',
        'deps' => array('zabbix7-agent-7.0.27'),
        'deb' => 'zabbix-agent, zabbix-agent2',
        'service' => 'zabbix-agent.service',
        'conflicts' => array('zabbix-agent6', 'zabbix-agent74'),
        'menu' => '/services_zabbixagent.php (Services -> Zabbix Agent 7)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'zabbix-agent74',
        'version' => '1.1_1',
        'category' => 'Monitoring & Analytics',
        'desc' => 'Zabbix agent is deployed on a monitoring target to actively monitor local resources and applications (hard drives, memory, processor statistics etc). The agent gathers operational information locally and reports data to Zabbix server for further processing. In case of failures (such as a hard disk running full or a crashed service process), Zabbix server can actively alert the administrators of the particular machine that reported the failure. Zabbix is an enterprise-class open source distributed monitoring solution.',
        'deps' => array('zabbix74-agent-7.4.11'),
        'deb' => 'zabbix-agent, zabbix-agent2',
        'service' => 'zabbix-agent.service',
        'conflicts' => array('zabbix-agent6', 'zabbix-agent7'),
        'menu' => '/services_zabbixagent.php (Services -> Zabbix Agent 7.4)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'zabbix-proxy6',
        'version' => '1.1_1',
        'category' => 'Monitoring & Analytics',
        'desc' => 'A Zabbix proxy can collect performance and availability data on behalf of the Zabbix server. This way, a proxy can take on itself some of the load of collecting data and offload the Zabbix server. Also, using a proxy is the easiest way of implementing centralized and distributed monitoring, when all agents and proxies report to one Zabbix server and all data is collected centrally. Zabbix is an enterprise-class open source distributed monitoring solution.',
        'deps' => array('zabbix6-proxy-6.0.46'),
        'deb' => 'zabbix-proxy-sqlite3',
        'service' => 'zabbix-proxy.service',
        'conflicts' => array('zabbix-proxy7', 'zabbix-proxy74'),
        'menu' => '/services_zabbix-proxy6.php (Services -> zabbix-proxy6)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'zabbix-proxy7',
        'version' => '1.1_1',
        'category' => 'Monitoring & Analytics',
        'desc' => 'A Zabbix proxy can collect performance and availability data on behalf of the Zabbix server. This way, a proxy can take on itself some of the load of collecting data and offload the Zabbix server. Also, using a proxy is the easiest way of implementing centralized and distributed monitoring, when all agents and proxies report to one Zabbix server and all data is collected centrally. Zabbix is an enterprise-class open source distributed monitoring solution.',
        'deps' => array('zabbix7-proxy-7.0.27'),
        'deb' => 'zabbix-proxy-sqlite3',
        'service' => 'zabbix-proxy.service',
        'conflicts' => array('zabbix-proxy6', 'zabbix-proxy74'),
        'menu' => '/services_zabbix-proxy7.php (Services -> zabbix-proxy7)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'zabbix-proxy74',
        'version' => '1.1_1',
        'category' => 'Monitoring & Analytics',
        'desc' => 'A Zabbix proxy can collect performance and availability data on behalf of the Zabbix server. This way, a proxy can take on itself some of the load of collecting data and offload the Zabbix server. Also, using a proxy is the easiest way of implementing centralized and distributed monitoring, when all agents and proxies report to one Zabbix server and all data is collected centrally. Zabbix is an enterprise-class open source distributed monitoring solution.',
        'deps' => array('zabbix74-proxy-7.4.11'),
        'deb' => 'zabbix-proxy-sqlite3',
        'service' => 'zabbix-proxy.service',
        'conflicts' => array('zabbix-proxy6', 'zabbix-proxy7'),
        'menu' => '/services_zabbix-proxy74.php (Services -> zabbix-proxy74)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'zeek',
        'version' => '3.0.7',
        'category' => 'Utilities & Tools',
        'desc' => 'Zeek (formerly Bro) is a passive, open-source network traffic analyzer. It detects specific attacks, including those defined by signatures or events, as well as unusual activity.',
        'deps' => array('zeek-8.0.8'),
        'deb' => 'zeek, zeek-core',
        'service' => 'zeek.service',
        'conflicts' => array(),
        'menu' => '/services_zeek.php (Services -> zeek)',
        'status' => 'Available in Debian'
    ),
    array(
        'name' => 'xray-core',
        'version' => '1.8.24',
        'category' => 'VPN & Tunnels',
        'desc' => 'Next-generation multi-protocol proxy engine supporting VLESS Reality, VMess WS, Trojan, Shadowsocks, and TUN routing.',
        'deps' => array('xray-bin', 'geoip-database', 'geosite-database'),
        'deb' => 'xray-core (Linux amd64), ca-certificates',
        'service' => 'xray.service (Port 10443)',
        'conflicts' => array('v2ray-core'),
        'menu' => '/vpn_xray.php (VPN / Sidebar -> Xray-core)',
        'status' => 'ACTIVE & RUNNING (Linux amd64)'
    )
);

// Grouping by Category for Summary Metrics
$categories = array();
foreach ($packages_relations as $pr) {
    $cat = $pr['category'];
    if (!isset($categories[$cat])) {
        $categories[$cat] = 0;
    }
    $categories[$cat]++;
}
?>

<div class="panel panel-default">
	<div class="panel-heading">
		<h2 class="panel-title"><i class="fa-solid fa-diagram-project"></i> Package Architecture &amp; Inter-Package Relations</h2>
	</div>
	<div class="panel-body">
		<div class="alert alert-info">
			<i class="fa-solid fa-circle-nodes"></i> <strong>Relasi Dependensi &amp; Ekosistem Paket:</strong> 
			Menampilkan relasi lengkap antar paket (<strong>Dependencies</strong>, <strong>Debian .deb Mapping</strong>, <strong>Systemd Daemons</strong>, <strong>Conflicts / Mutual Exclusion</strong>, dan <strong>WebUI Menu Hook</strong>) untuk seluruh <strong><?=count($packages_relations)?></strong> paket.
		</div>

		<!-- Category Metrics Badges -->
		<div class="mb-20">
			<strong>Kategori Subsystem:</strong><br/>
			<?php foreach ($categories as $c_name => $c_count): ?>
				<span class="label label-default pkg-tag">
					<?=htmlspecialchars($c_name)?>: <strong><?=$c_count?></strong>
				</span>
			<?php endforeach; ?>
		</div>

		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed table-bordered table-vmid">
				<thead>
					<tr class="active">
						<th class="col-minw-140">Paket Asal (pfSense)</th>
						<th class="col-minw-130">Kategori</th>
						<th class="col-minw-220">Relasi Dependensi (.pkg &rarr; .deb)</th>
						<th class="col-minw-180">Daemon / Runtime Service</th>
						<th class="col-minw-180">WebUI Menu Hook</th>
						<th class="col-minw-140">Status Sinkronisasi</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($packages_relations as $p): ?>
					<tr>
						<td>
							<strong class="fs-13"><?=htmlspecialchars($p['name'])?></strong><br/>
							<span class="text-muted"><small>v<?=htmlspecialchars($p['version'])?></small></span>
							<p class="mt-5 fs-11 text-muted"><?=htmlspecialchars($p['desc'])?></p>
						</td>
						<td>
							<span class="label label-primary"><?=htmlspecialchars($p['category'])?></span>
						</td>
						<td>
							<strong>Debian Package:</strong><br/>
							<code><?=htmlspecialchars($p['deb'])?></code><br/>
							<?php if (!empty($p['deps'])): ?>
								<div class="mt-5">
									<small class="text-muted"><i class="fa-solid fa-link"></i> Upstream Dependencies:</small><br/>
									<?php foreach ($p['deps'] as $dep): ?>
										<span class="label label-info pkg-dep-tag"><i class="fa-solid fa-paperclip"></i> <?=htmlspecialchars($dep)?></span>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
							<?php if (!empty($p['conflicts'])): ?>
								<div class="mt-5">
									<small class="text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Konflik / Mutual Exclusion:</small><br/>
									<?php foreach ($p['conflicts'] as $conf): ?>
										<span class="label label-danger"><?=htmlspecialchars($conf)?></span>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</td>
						<td>
							<code><?=htmlspecialchars($p['service'])?></code>
						</td>
						<td>
							<i class="fa-solid fa-arrow-up-right-from-square"></i> <small><?=htmlspecialchars($p['menu'])?></small>
						</td>
						<td>
							<?php if (strpos($p['status'], 'ACTIVE') !== false): ?>
								<span class="label label-success fs-11"><i class="fa-solid fa-bolt"></i> <?=htmlspecialchars($p['status'])?></span>
							<?php else: ?>
								<span class="label label-info fs-11"><i class="fa-solid fa-check"></i> <?=htmlspecialchars($p['status'])?></span>
							<?php endif; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<?php include(__DIR__ . '/includes/foot.inc'); ?>
