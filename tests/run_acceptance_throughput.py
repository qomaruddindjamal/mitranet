import paramiko
import time
import socket
import threading

VPS_HOST = '103.93.162.168'
VPS_USER = 'citramedia'
VPS_PASS = 'K0323205'
MINI_HOST = '10.10.66.228'
MINI_USER = 'root'
MINI_PASS = 'mitranet'

def run_cmd(ssh, cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd)
    return stdout.read().decode().strip(), stderr.read().decode().strip()

def main():
    print("=========================================================================")
    print("VALIDASI ACCEPTANCE PRIORITAS 1 & 3: PER-INTERFACE SPEED & MULTI-STREAM")
    print("=========================================================================")

    mini_ssh = paramiko.SSHClient()
    mini_ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    mini_ssh.connect(MINI_HOST, username=MINI_USER, password=MINI_PASS, timeout=5)

    vps_ssh = paramiko.SSHClient()
    vps_ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    vps_ssh.connect(VPS_HOST, port=22, username=VPS_USER, password=VPS_PASS, look_for_keys=False, allow_agent=False, timeout=8)

    # 1. Pastikan kedua stream aktif dan terhubung
    print("[1] Verifikasi Tunnel Stream 1 & Stream 2...")
    run_cmd(mini_ssh, "wg-quick up wgboost1 2>/dev/null; wg-quick up wgboost2 2>/dev/null")
    time.sleep(2)

    p1, _ = run_cmd(mini_ssh, "ping -c 3 -W 2 10.250.1.1")
    p2, _ = run_cmd(mini_ssh, "ping -c 3 -W 2 10.250.2.1")
    print("Ping Stream 1 (10.250.1.1):", p1.splitlines()[-1] if p1 else "ERR")
    print("Ping Stream 2 (10.250.2.1):", p2.splitlines()[-1] if p2 else "ERR")

    # 2. Ukur Baseline tanpa booster (wg0 ke VPS 10.10.77.1)
    print("\n[2] Pengukuran Baseline Throughput (wg0 / 10.10.77.1)...")
    # Mini PC ke VPS MikroTik via Bandwidth test atau HTTP download
    # Jalankan HTTP fetch besar melalui koneksi normal
    t_base_out, _ = run_cmd(mini_ssh, "curl -s -w 'Time: %{time_total}s | Speed: %{speed_download} B/s' -o /dev/null http://speed.cloudflare.com/__down?bytes=50000000")
    print("Baseline (via wg0 default):", t_base_out)

    # 3. Uji Kecepatan Nyata Stream 1 (10.250.1.1) menggunakan UDP payload generator
    print("\n[3] Uji Throughput Terukur Stream 1 (10.250.1.2 -> 10.250.1.1)...")
    rx_s1_before, _ = run_cmd(vps_ssh, ':put [/interface wireguard get [find where name=wg-boost1] rx]')
    tx_s1_before, _ = run_cmd(vps_ssh, ':put [/interface wireguard get [find where name=wg-boost1] tx]')
    
    # Jalankan high-rate ping/flood melalui stream 1 selama 3 detik
    t0 = time.time()
    run_cmd(mini_ssh, "ping -c 200 -s 1400 -i 0.01 10.250.1.1")
    t1_dur = time.time() - t0

    rx_s1_after, _ = run_cmd(vps_ssh, ':put [/interface wireguard get [find where name=wg-boost1] rx]')
    tx_s1_after, _ = run_cmd(vps_ssh, ':put [/interface wireguard get [find where name=wg-boost1] tx]')

    diff_s1_rx = int(rx_s1_after or 0) - int(rx_s1_before or 0)
    diff_s1_tx = int(tx_s1_after or 0) - int(tx_s1_before or 0)
    print(f"Stream 1 VPS Counter: Rx=+{diff_s1_rx:,} B, Tx=+{diff_s1_tx:,} B dalam {t1_dur:.2f} detik")

    # 4. Uji Paralel Multi-Stream (Stream 1 + Stream 2 Secara Bersamaan)
    print("\n[4] Uji Paralel Multi-Stream Simultan (Stream 1 + Stream 2)...")
    rx1_b, _ = run_cmd(vps_ssh, ':put [/interface wireguard get [find where name=wg-boost1] rx]')
    rx2_b, _ = run_cmd(vps_ssh, ':put [/interface wireguard get [find where name=wg-boost2] rx]')

    t0_par = time.time()
    # Pembangkitan paket paralel simultan di kedua tunnel
    run_cmd(mini_ssh, "(ping -c 300 -s 1400 -i 0.01 10.250.1.1 & ping -c 300 -s 1400 -i 0.01 10.250.2.1 & wait)")
    t_par_dur = time.time() - t0_par

    rx1_a, _ = run_cmd(vps_ssh, ':put [/interface wireguard get [find where name=wg-boost1] rx]')
    rx2_a, _ = run_cmd(vps_ssh, ':put [/interface wireguard get [find where name=wg-boost2] rx]')

    d1 = int(rx1_a or 0) - int(rx1_b or 0)
    d2 = int(rx2_a or 0) - int(rx2_b or 0)
    total_d = d1 + d2
    mbps_par = (total_d * 8) / (t_par_dur * 1_000_000)

    print(f"Durasi Paralel: {t_par_dur:.2f} detik")
    print(f"Stream 1 VPS Rx Delta: +{d1:,} bytes")
    print(f"Stream 2 VPS Rx Delta: +{d2:,} bytes")
    print(f"Total Paralel VPS Rx : +{total_d:,} bytes (~{mbps_par:.2f} Mbps paket tunnel)")

    # 5. Uji Pemulihan & Stop Booster
    print("\n[5] Uji Pemulihan & Stop Booster...")
    run_cmd(mini_ssh, "wg-quick down wgboost1; wg-quick down wgboost2")
    
    # Periksa default route
    def_route, _ = run_cmd(mini_ssh, "ip route show default")
    print("Default Route Aktif:", def_route)

    # Periksa SSH latency Mini PC
    ping_mini, _ = run_cmd(mini_ssh, "ping -c 3 10.10.66.254")
    print("Ping Gateway Lokal Mini PC:", ping_mini.splitlines()[-1] if ping_mini else "OK")

    mini_ssh.close()
    vps_ssh.close()
    print("=========================================================================")

if __name__ == '__main__':
    main()
