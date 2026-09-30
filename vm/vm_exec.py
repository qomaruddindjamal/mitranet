import sys
import paramiko

def run_ssh(cmd, ip="172.21.162.163", user="root", pwd="pfsense"):
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        client.connect(ip, port=22, username=user, password=pwd, timeout=5)
        stdin, stdout, stderr = client.exec_command(cmd)
        out = stdout.read().decode('utf-8', errors='replace')
        err = stderr.read().decode('utf-8', errors='replace')
        print(out)
        if err:
            print(err, file=sys.stderr)
    except Exception as e:
        print(f"SSH Error: {e}", file=sys.stderr)
        sys.exit(1)
    finally:
        client.close()

if __name__ == "__main__":
    if len(sys.argv) > 1:
        run_ssh(" ".join(sys.argv[1:]))
    else:
        print("Usage: vm_exec.py <command>")
