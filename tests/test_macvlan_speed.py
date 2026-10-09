import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.228', username='root', password='mitranet', timeout=10)

print("=== SPEED TEST COMPARISON ===")

# Test 1: Through Physical IP 10.10.66.228
cmd1 = "curl --interface 10.10.66.228 -o /dev/null -s -w 'PHY (10.10.66.228) Speed: %{speed_download} B/s, Time: %{time_total}s\n' --max-time 30 https://speed.cloudflare.com/__down?bytes=10000000"
stdin, stdout, stderr = ssh.exec_command(cmd1)
print(stdout.read().decode().strip())

# Test 2: Through Virtual Macvlan IP 10.10.66.209
cmd2 = "curl --interface 10.10.66.209 -o /dev/null -s -w 'MACVLAN (10.10.66.209) Speed: %{speed_download} B/s, Time: %{time_total}s\n' --max-time 30 https://speed.cloudflare.com/__down?bytes=10000000"
stdin, stdout, stderr = ssh.exec_command(cmd2)
print(stdout.read().decode().strip())

# Test 3: Concurrent Parallel Download (Both interfaces simultaneously)
print("\n=== TEST PARALLEL CONCURRENT DOWNLOAD (COMBINED BANDWIDTH) ===")
cmd_parallel = """
python3 -c "
import urllib.request, time, threading

results = {}

def dl(iface, name):
    import socket
    # Custom socket binding
    class BoundHTTPHandler(urllib.request.HTTPHandler):
        def http_open(self, req):
            return self.do_open(BoundHTTPConnection, req)
    class BoundHTTPConnection(http.client.HTTPConnection):
        def connect(self):
            self.sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            self.sock.bind((iface, 0))
            self.sock.connect((self.host, self.port))

    import http.client
    opener = urllib.request.build_opener(BoundHTTPHandler)
    t0 = time.time()
    try:
        resp = opener.open('http://speedtest.tele2.net/10MB.zip', timeout=30)
        data = resp.read()
        dur = time.time() - t0
        speed_mbps = (len(data) * 8) / (dur * 1000000)
        results[name] = speed_mbps
    except Exception as e:
        results[name] = str(e)

t1 = threading.Thread(target=dl, args=('10.10.66.228', 'PHY_228'))
t2 = threading.Thread(target=dl, args=('10.10.66.209', 'MACVLAN_209'))

t0_all = time.time()
t1.start(); t2.start()
t1.join(); t2.join()
total_dur = time.time() - t0_all

print('Results per session:', results)
if isinstance(results.get('PHY_228'), (int, float)) and isinstance(results.get('MACVLAN_209'), (int, float)):
    print(f'Combined Parallel Throughput: {results[\"PHY_228\"] + results[\"MACVLAN_209\"]:.2f} Mbps')
"
"""
stdin, stdout, stderr = ssh.exec_command(cmd_parallel)
print(stdout.read().decode().strip())
err = stderr.read().decode().strip()
if err:
    print(f"STDERR: {err}")

ssh.close()
