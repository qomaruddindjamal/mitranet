"""
MitraNet Wireless / WiFi Engine and Subsystem Helper.
Provides interface querying, RF scanning, mode switching (AP, Station, Repeater),
hostapd & wpa_supplicant orchestration, access-lists, security profiles, and client dump.
Zero external runtime dependencies.
"""

import os
import re
import json
import time
import subprocess
import logging
from typing import Dict, Any, List, Optional

logger = logging.getLogger("mitranet.wifi")

WIFI_CONF_DIR = "/etc/mitranet/wifi"


def ensure_wifi_dirs() -> None:
    """Ensure directory structure for wireless profiles exists."""
    os.makedirs(WIFI_CONF_DIR, exist_ok=True)


def get_wireless_interfaces() -> List[Dict[str, Any]]:
    """Discover real physical and virtual wireless interfaces via nl80211 / iw and sysfs."""
    devices: Dict[str, Dict[str, Any]] = {}

    # 1. Query nl80211 wireless interfaces via iw dev
    try:
        proc = subprocess.run(["iw", "dev"], capture_output=True, text=True, timeout=5)
        current_phy = None
        for line in proc.stdout.splitlines():
            line = line.strip()
            if line.startswith("phy#"):
                current_phy = line
            elif line.startswith("Interface "):
                parts = line.split()
                if len(parts) >= 2:
                    ifname = parts[1]
                    devices[ifname] = {
                        "name": ifname,
                        "phy": current_phy
                    }
    except Exception as e:
        logger.warning("Error running iw dev: %s", e)

    # 2. Discover from sysfs net devices
    net_path = "/sys/class/net"
    if os.path.isdir(net_path):
        for ifname in os.listdir(net_path):
            p = os.path.join(net_path, ifname)
            if os.path.isdir(os.path.join(p, "wireless")) or os.path.isdir(os.path.join(p, "phy80211")):
                if ifname not in devices:
                    devices[ifname] = {"name": ifname, "phy": None}

    results: List[Dict[str, Any]] = []

    # 3. Enrich each wireless interface
    for ifname, d in devices.items():
        altname = ifname
        alt_path = f"/sys/class/net/{ifname}/altnames"
        if os.path.isfile(alt_path):
            try:
                with open(alt_path, "r") as af:
                    first = af.readline().strip()
                    if first:
                        altname = first
            except Exception:
                pass
        d["altname"] = altname
        d["type"] = "wlan"
        d["mode"] = "managed"
        d["channel"] = "auto"
        d["frequency"] = "auto"
        d["channel_width"] = "20 MHz"
        d["txpower"] = "auto"
        d["mac_address"] = "00:00:00:00:00:00"

        # iw dev <if> info
        try:
            info_proc = subprocess.run(["iw", "dev", ifname, "info"], capture_output=True, text=True, timeout=5)
            for l in info_proc.stdout.splitlines():
                l = l.strip()
                if l.startswith("type "):
                    d["mode"] = l.split(None, 1)[1].strip()
                elif l.startswith("addr "):
                    d["mac_address"] = l.split(None, 1)[1].strip()
                elif "channel" in l and "MHz" in l:
                    ch_m = re.search(r"channel\s+(\d+)\s*\(([\d.]+)\s*MHz\)(?:,\s*width:\s*([\d\s\w]+))?", l, re.IGNORECASE)
                    if ch_m:
                        d["channel"] = ch_m.group(1)
                        d["frequency"] = f"{ch_m.group(2)} MHz"
                        if ch_m.group(3):
                            d["channel_width"] = ch_m.group(3).strip()
                elif l.startswith("txpower "):
                    d["txpower"] = l.split(None, 1)[1].strip()
                elif l.startswith("wiphy "):
                    w_num = l.split(None, 1)[1].strip()
                    if not d.get("phy"):
                        d["phy"] = f"phy#{w_num}"
        except Exception:
            pass

        # MAC address fallback
        mac_path = f"/sys/class/net/{ifname}/address"
        if d["mac_address"] == "00:00:00:00:00:00" and os.path.isfile(mac_path):
            try:
                with open(mac_path, "r") as mf:
                    d["mac_address"] = mf.read().strip()
            except Exception:
                pass

        # MTU & Flags
        mtu = 1500
        mtu_path = f"/sys/class/net/{ifname}/mtu"
        if os.path.isfile(mtu_path):
            try:
                with open(mtu_path, "r") as mtf:
                    mtu = int(mtf.read().strip())
            except Exception:
                pass
        d["mtu"] = mtu
        d["l2mtu"] = 1500

        flags = 0
        flags_path = f"/sys/class/net/{ifname}/flags"
        if os.path.isfile(flags_path):
            try:
                with open(flags_path, "r") as ff:
                    flags = int(ff.read().strip(), 16)
            except Exception:
                pass
        d["is_up"] = bool(flags & 1)

        oper_state = "DOWN"
        oper_path = f"/sys/class/net/{ifname}/operstate"
        if os.path.isfile(oper_path):
            try:
                with open(oper_path, "r") as opf:
                    oper_state = opf.read().strip().upper()
            except Exception:
                pass
        d["oper_state"] = oper_state

        # Bands supported
        phy_idx = re.sub(r"[^0-9]", "", d.get("phy") or "0")
        bands = []
        try:
            phy_proc = subprocess.run(["iw", "phy", f"phy{phy_idx}", "info"], capture_output=True, text=True, timeout=5)
            if "Band 1:" in phy_proc.stdout:
                bands.append("2.4GHz")
            if "Band 2:" in phy_proc.stdout:
                bands.append("5GHz")
        except Exception:
            pass
        d["band"] = " / ".join(bands) if bands else "2.4GHz"

        # Link status
        d["link_connected"] = False
        d["ssid"] = "None (Not Connected)"
        d["bssid"] = "-"
        d["signal"] = "-"
        try:
            link_proc = subprocess.run(["iw", "dev", ifname, "link"], capture_output=True, text=True, timeout=5)
            if link_proc.stdout and "Not connected" not in link_proc.stdout:
                d["link_connected"] = True
                sm = re.search(r"SSID:\s*(.+)", link_proc.stdout)
                if sm:
                    d["ssid"] = sm.group(1).strip()
                bm = re.search(r"Connected to\s+([0-9a-f:]{17})", link_proc.stdout, re.IGNORECASE)
                if bm:
                    d["bssid"] = bm.group(1).lower()
                fm = re.search(r"freq:\s*([\d.]+)", link_proc.stdout)
                if fm:
                    d["frequency"] = f"{fm.group(1)} MHz"
                sigm = re.search(r"signal:\s*([-\d.]+\s*dBm)", link_proc.stdout)
                if sigm:
                    d["signal"] = sigm.group(1)
        except Exception:
            pass

        # Load persisted profile if any
        profile_path = f"{WIFI_CONF_DIR}/profile_{ifname}.json"
        if os.path.isfile(profile_path):
            try:
                with open(profile_path, "r") as pf:
                    pdata = json.load(pf)
                d["mode"] = pdata.get("mode", d["mode"])
                d["band"] = pdata.get("band", d["band"])
                d["channel"] = pdata.get("channel", d["channel"])
                d["channel_width"] = pdata.get("channel_width", d["channel_width"])
                d["txpower"] = pdata.get("txpower", d["txpower"])
                d["security"] = pdata.get("security", "wpa2")
                d["passphrase"] = pdata.get("passphrase", "")
                d["country_code"] = pdata.get("country_code", "ID")
                if d["mode"] == "ap" and pdata.get("ssid"):
                    d["ssid"] = pdata.get("ssid")
                    d["bssid"] = d["mac_address"]
                elif d["mode"] == "managed" and pdata.get("ssid") and not d["link_connected"]:
                    d["ssid"] = f"{pdata.get('ssid')} (Configured)"
            except Exception:
                pass
        else:
            d["security"] = "wpa2"
            d["passphrase"] = ""
            d["country_code"] = "ID"

        # Traffic statistics
        rx_b = tx_b = rx_p = tx_p = 0
        try:
            rx_b_path = f"/sys/class/net/{ifname}/statistics/rx_bytes"
            tx_b_path = f"/sys/class/net/{ifname}/statistics/tx_bytes"
            rx_p_path = f"/sys/class/net/{ifname}/statistics/rx_packets"
            tx_p_path = f"/sys/class/net/{ifname}/statistics/tx_packets"
            if os.path.isfile(rx_b_path):
                with open(rx_b_path, "r") as rf: rx_b = int(rf.read().strip())
                with open(tx_b_path, "r") as tf: tx_b = int(tf.read().strip())
                with open(rx_p_path, "r") as rpf: rx_p = int(rpf.read().strip())
                with open(tx_p_path, "r") as tpf: tx_p = int(tpf.read().strip())
        except Exception:
            pass
        d["traffic"] = {
            "rx_bytes": rx_b,
            "tx_bytes": tx_b,
            "rx_packets": rx_p,
            "tx_packets": tx_p
        }

        results.append(d)

    return results


def scan_wireless_networks(ifname: str = "wlan0") -> List[Dict[str, Any]]:
    """Perform real active/passive wireless RF survey via iw dev scan."""
    # Ensure interface is up
    try:
        subprocess.run(["ip", "link", "set", ifname, "up"], stderr=subprocess.DEVNULL, timeout=5)
        time.sleep(0.3)
    except Exception:
        pass

    networks: List[Dict[str, Any]] = []
    try:
        proc = subprocess.run(["iw", "dev", ifname, "scan"], capture_output=True, text=True, timeout=15)
        out = proc.stdout
        if not out:
            return networks

        cur_bssid = None
        curr: Dict[str, Any] = {}
        for line in out.splitlines():
            line = line.strip()
            if line.startswith("BSS "):
                if cur_bssid and curr.get("ssid"):
                    networks.append(curr)
                bm = re.search(r"BSS\s+([0-9a-f:]{17})", line, re.IGNORECASE)
                cur_bssid = bm.group(1).lower() if bm else None
                curr = {
                    "bssid": cur_bssid,
                    "ssid": "",
                    "signal": "",
                    "freq": "",
                    "channel": "",
                    "security": "Open"
                }
            elif cur_bssid:
                if line.startswith("SSID:"):
                    raw = line[5:].strip()
                    # Decode hex escape \x20 etc
                    clean = re.sub_callback(r"\\x([0-9a-fA-F]{2})", lambda m: chr(int(m.group(1), 16)), raw) if hasattr(re, 'sub_callback') else raw
                    curr["ssid"] = clean
                elif line.startswith("signal:"):
                    sm = re.search(r"signal:\s*([-\d.]+\s*dBm)", line)
                    if sm: curr["signal"] = sm.group(1)
                elif line.startswith("freq:"):
                    fm = re.search(r"freq:\s*([\d.]+)", line)
                    if fm: curr["freq"] = f"{fm.group(1)} MHz"
                elif "DS Parameter set: channel" in line:
                    cm = re.search(r"channel\s+(\d+)", line, re.IGNORECASE)
                    if cm: curr["channel"] = cm.group(1)
                elif "primary channel:" in line:
                    pcm = re.search(r"primary channel:\s*(\d+)", line, re.IGNORECASE)
                    if pcm and not curr.get("channel"): curr["channel"] = pcm.group(1)
                elif "RSN:" in line or "WPA:" in line:
                    curr["security"] = "WPA2/WPA3"
                elif "capability: ESS Privacy" in line and curr.get("security") == "Open":
                    curr["security"] = "Protected (WEP)"

        if cur_bssid and curr.get("ssid"):
            networks.append(curr)
    except Exception as e:
        logger.warning("Scan error on %s: %s", ifname, e)

    return networks


def get_connected_clients(ifname: str = "wlan0") -> List[Dict[str, Any]]:
    """Retrieve connected client stations via iw dev <if> station dump."""
    clients: List[Dict[str, Any]] = []
    try:
        proc = subprocess.run(["iw", "dev", ifname, "station", "dump"], capture_output=True, text=True, timeout=5)
        out = proc.stdout
        if not out:
            return clients

        cur_station = None
        curr: Dict[str, Any] = {}
        for line in out.splitlines():
            line = line.strip()
            if line.startswith("Station "):
                if cur_station and curr.get("mac"):
                    clients.append(curr)
                sm = re.search(r"Station\s+([0-9a-f:]{17})", line, re.IGNORECASE)
                cur_station = sm.group(1).lower() if sm else None
                curr = {
                    "mac": cur_station,
                    "interface": ifname,
                    "signal": "-",
                    "rx_bytes": 0,
                    "tx_bytes": 0,
                    "rx_packets": 0,
                    "tx_packets": 0,
                    "tx_bitrate": "-",
                    "rx_bitrate": "-",
                    "connected_time": "-",
                    "inactive_time": "-"
                }
            elif cur_station:
                if line.startswith("signal:"):
                    sigm = re.search(r"signal:\s*([-\d.]+\s*dBm)", line)
                    if sigm: curr["signal"] = sigm.group(1)
                elif line.startswith("rx bytes:"):
                    rxb = re.search(r"rx bytes:\s*(\d+)", line)
                    if rxb: curr["rx_bytes"] = int(rxb.group(1))
                elif line.startswith("tx bytes:"):
                    txb = re.search(r"tx bytes:\s*(\d+)", line)
                    if txb: curr["tx_bytes"] = int(txb.group(1))
                elif line.startswith("rx packets:"):
                    rxp = re.search(r"rx packets:\s*(\d+)", line)
                    if rxp: curr["rx_packets"] = int(rxp.group(1))
                elif line.startswith("tx packets:"):
                    txp = re.search(r"tx packets:\s*(\d+)", line)
                    if txp: curr["tx_packets"] = int(txp.group(1))
                elif line.startswith("tx bitrate:"):
                    curr["tx_bitrate"] = line.split(None, 2)[-1].strip()
                elif line.startswith("rx bitrate:"):
                    curr["rx_bitrate"] = line.split(None, 2)[-1].strip()
                elif "connected time:" in line:
                    ctm = re.search(r"connected time:\s*(\d+)\s*seconds", line, re.IGNORECASE)
                    if ctm: curr["connected_time"] = f"{ctm.group(1)}s"
                elif "inactive time:" in line:
                    itm = re.search(r"inactive time:\s*(\d+)\s*ms", line, re.IGNORECASE)
                    if itm: curr["inactive_time"] = f"{itm.group(1)} ms"

        if cur_station and curr.get("mac"):
            clients.append(curr)
    except Exception as e:
        logger.warning("Station dump error on %s: %s", ifname, e)

    return clients


def apply_wireless_configuration(ifname: str, cfg: Dict[str, Any]) -> Tuple[bool, str]:
    """
    Apply wireless configuration to interface:
    - Sets mode: 'ap', 'managed' (station/client), or 'repeater'
    - Configures hostapd or wpa_supplicant
    - Manages DHCP server and NAT masquerade for AP mode
    """
    ensure_wifi_dirs()
    profile_path = f"{WIFI_CONF_DIR}/profile_{ifname}.json"
    with open(profile_path, "w") as pf:
        json.dump(cfg, pf, indent=2)

    mode = cfg.get("mode", "managed").lower()
    is_enabled = bool(cfg.get("enable", True))
    mtu = int(cfg.get("mtu", 1500))

    # Stop running hostapd for this interface
    subprocess.run(f"pkill -9 -f 'hostapd.*{ifname}'", shell=True, stderr=subprocess.DEVNULL)

    if not is_enabled:
        subprocess.run(["ip", "link", "set", ifname, "down"], stderr=subprocess.DEVNULL)
        return True, f"Interface {ifname} disabled."

    # Bring up & set MTU
    subprocess.run(["ip", "link", "set", ifname, "up"], stderr=subprocess.DEVNULL)
    if 576 <= mtu <= 1500:
        subprocess.run(["ip", "link", "set", ifname, "mtu", str(mtu)], stderr=subprocess.DEVNULL)

    if mode == "ap":
        # Kill wpa_supplicant on this interface so hostapd gets exclusive control
        subprocess.run(f"pkill -9 -f 'wpa_supplicant.*{ifname}'", shell=True, stderr=subprocess.DEVNULL)

        ssid = cfg.get("ssid", "").strip() or "MitraNet-AP"
        channel = int(cfg.get("channel", 36) or 36)
        if channel <= 0:
            channel = 36
        hw_mode = "a" if channel > 14 else "g"
        country = cfg.get("country_code", "ID").strip().upper() or "ID"
        passphrase = cfg.get("passphrase", "").strip()
        security = cfg.get("security", "wpa2").lower()

        conf_lines = [
            f"interface={ifname}",
            "driver=nl80211",
            f"ssid={ssid}",
            f"hw_mode={hw_mode}",
            f"channel={channel}",
            f"country_code={country}",
            "ieee80211n=1"
        ]
        if hw_mode == "a":
            conf_lines.append("ieee80211ac=1")
        conf_lines.append("wmm_enabled=1")

        if security == "open" or not passphrase:
            pass
        elif security == "wpa3":
            conf_lines.extend([
                "wpa=2",
                "wpa_key_mgmt=SAE",
                f"sae_password={passphrase}",
                "rsn_pairwise=CCMP"
            ])
        else:
            conf_lines.extend([
                "wpa=2",
                "wpa_key_mgmt=WPA-PSK",
                "rsn_pairwise=CCMP",
                f"wpa_passphrase={passphrase}"
            ])

        conf_path = f"{WIFI_CONF_DIR}/hostapd_{ifname}.conf"
        with open(conf_path, "w") as cf:
            cf.write("\n".join(conf_lines) + "\n")

        # Assign Gateway IP to AP interface if not assigned
        ap_ip = cfg.get("gateway_ip", "10.10.1.254/24")
        subprocess.run(f"ip addr add {ap_ip} dev {ifname} 2>/dev/null || true", shell=True)

        # Launch hostapd
        subprocess.run(f"hostapd -B {conf_path}", shell=True, stderr=subprocess.DEVNULL)

        # Setup dynamic DHCP configuration for AP clients
        dhcp_conf_path = f"/etc/dnsmasq.d/dhcp_{ifname}.conf"
        dhcp_content = (
            f"# status=enabled\n"
            f"interface={ifname}\n"
            f"bind-dynamic\n"
            f"dhcp-range={ifname},10.10.1.10,10.10.1.240,255.255.255.0,12h\n"
            f"dhcp-option={ifname},option:router,10.10.1.254\n"
            f"dhcp-option={ifname},option:dns-server,8.8.8.8,1.1.1.1\n"
        )
        try:
            with open(dhcp_conf_path, "w") as df:
                df.write(dhcp_content)
            subprocess.run(["systemctl", "restart", "dnsmasq"], stderr=subprocess.DEVNULL)
        except Exception as de:
            logger.warning("Could not write DHCP config for %s: %s", ifname, de)

        # Ensure NAT Masquerade
        try:
            subprocess.run(f"iptables -t nat -C POSTROUTING -s 10.10.1.0/24 ! -o {ifname} -j MASQUERADE 2>/dev/null || iptables -t nat -A POSTROUTING -s 10.10.1.0/24 ! -o {ifname} -j MASQUERADE", shell=True)
        except Exception:
            pass

        return True, f"Access Point on {ifname} (SSID: {ssid}, CH: {channel}) active."

    else:
        # Mode Managed / Station
        ssid = cfg.get("ssid", "").strip()
        passphrase = cfg.get("passphrase", "").strip()
        country = cfg.get("country_code", "ID").strip().upper() or "ID"

        if ssid:
            wpa_conf = (
                f"ctrl_interface=/run/wpa_supplicant\n"
                f"update_config=1\n"
                f"country={country}\n\n"
                f"network={{\n"
                f'\tssid="{ssid}"\n'
            )
            if passphrase:
                wpa_conf += f'\tpsk="{passphrase}"\n'
            else:
                wpa_conf += f"\tkey_mgmt=NONE\n"
            wpa_conf += "}\n"

            conf_path = f"{WIFI_CONF_DIR}/wpa_{ifname}.conf"
            with open(conf_path, "w") as wf:
                wf.write(wpa_conf)

            subprocess.run(f"pkill -9 -f 'wpa_supplicant.*{ifname}'", shell=True, stderr=subprocess.DEVNULL)
            subprocess.run(f"wpa_supplicant -B -i {ifname} -c {conf_path}", shell=True, stderr=subprocess.DEVNULL)
            subprocess.run(f"dhcpcd -b {ifname}", shell=True, stderr=subprocess.DEVNULL)

            return True, f"Station mode active on {ifname}, connecting to {ssid}."

        return True, f"Interface {ifname} set to managed mode."
