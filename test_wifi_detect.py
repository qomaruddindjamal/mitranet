import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.228', port=22, username='root', password='mitranet', timeout=5)

remote_script = """
import subprocess, re, os, glob, json

def get_wifi_devices():
    devices = []
    # 1. iw dev
    try:
        out = subprocess.check_output(['iw', 'dev'], stderr=subprocess.DEVNULL, text=True)
        current_phy = None
        for line in out.splitlines():
            line = line.strip()
            if line.startswith('phy#'):
                current_phy = line
            elif line.startswith('Interface '):
                ifname = line.split()[1]
                devices.append({'name': ifname, 'phy': current_phy})
    except Exception:
        pass
    
    # 2. Check /sys/class/net/*
    for p in glob.glob('/sys/class/net/*'):
        name = os.path.basename(p)
        if os.path.exists(f'{p}/wireless') or os.path.exists(f'{p}/phy80211'):
            if not any(d['name'] == name for d in devices):
                devices.append({'name': name, 'phy': None})

    for d in devices:
        name = d['name']
        try:
            info = subprocess.check_output(['iw', 'dev', name, 'info'], stderr=subprocess.DEVNULL, text=True)
            for l in info.splitlines():
                l = l.strip()
                if l.startswith('type '):
                    d['mode'] = l.split(' ', 1)[1]
                elif l.startswith('addr '):
                    d['mac_address'] = l.split(' ', 1)[1]
                elif l.startswith('channel '):
                    d['channel'] = l.split(' ', 1)[1]
                elif l.startswith('txpower '):
                    d['txpower'] = l.split(' ', 1)[1]
        except Exception:
            pass

        # Bands supported from phy info
        phy = d.get('phy')
        phy_name = phy.replace('#', '') if phy else 'phy0'
        bands = []
        try:
            phy_info = subprocess.check_output(['iw', 'phy', phy_name, 'info'], stderr=subprocess.DEVNULL, text=True)
            if 'Band 1:' in phy_info:
                bands.append('2.4GHz')
            if 'Band 2:' in phy_info:
                bands.append('5GHz')
        except Exception:
            bands = ['2.4GHz / 5GHz']
        d['supported_bands'] = ' / '.join(bands) if bands else '2.4GHz'

        # MAC from sysfs if not found
        if 'mac_address' not in d:
            try:
                d['mac_address'] = open(f'/sys/class/net/{name}/address').read().strip()
            except Exception:
                d['mac_address'] = '00:00:00:00:00:00'

        # MTU
        try:
            d['mtu'] = int(open(f'/sys/class/net/{name}/mtu').read().strip())
        except Exception:
            d['mtu'] = 1500

        # Operstate & is_up
        try:
            d['oper_state'] = open(f'/sys/class/net/{name}/operstate').read().strip().upper()
        except Exception:
            d['oper_state'] = 'DOWN'

        try:
            flags = int(open(f'/sys/class/net/{name}/flags').read().strip(), 16)
            d['is_up'] = bool(flags & 1)
        except Exception:
            d['is_up'] = False

        # Link status
        try:
            link = subprocess.check_output(['iw', 'dev', name, 'link'], stderr=subprocess.DEVNULL, text=True)
            if 'Not connected.' in link:
                d['ssid'] = 'None'
                d['connected'] = False
                d['bssid'] = None
            else:
                m = re.search(r'SSID:\s*(.+)', link)
                d['ssid'] = m.group(1).strip() if m else 'Connected'
                d['connected'] = True
                m_bssid = re.search(r'Connected to\s+([0-9a-f:]{17})', link, re.I)
                d['bssid'] = m_bssid.group(1) if m_bssid else None
                m_freq = re.search(r'freq:\s*(\d+)', link)
                d['freq'] = m_freq.group(1) + ' MHz' if m_freq else None
                m_sig = re.search(r'signal:\s*([-\d]+ dBm)', link)
                d['signal'] = m_sig.group(1) if m_sig else None
        except Exception:
            d['ssid'] = 'None'
            d['connected'] = False

    return devices

print(json.dumps(get_wifi_devices(), indent=2))
"""

stdin, stdout, stderr = ssh.exec_command(f"python3 -c '{remote_script}'")
print(stdout.read().decode('utf-8', errors='ignore'))
ssh.close()
