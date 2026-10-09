import os
import sys
import subprocess
import paramiko

MINIPC_IP = "10.10.66.228"
MINIPC_USER = "root"
MINIPC_PASS = "mitranet"
PROJECT_ROOT = r"C:\mitranet"

def log(msg):
    print(f"[DEPLOY PIPELINE] {msg}")

def sync_to_minipc():
    log(f"1/4 Connecting to Mini PC ({MINIPC_IP})...")
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        ssh.connect(MINIPC_IP, username=MINIPC_USER, password=MINIPC_PASS, timeout=12)
    except Exception as e:
        log(f"ERROR: Cannot connect to Mini PC: {e}")
        return False

    sftp = ssh.open_sftp()
    
    # Folders to sync: web, core, src
    def sftp_sync_dir(local_d, remote_d):
        try:
            sftp.mkdir(remote_d)
        except Exception:
            pass
        for item in os.listdir(local_d):
            if item.startswith('.') or item == '__pycache__':
                continue
            lp = os.path.join(local_d, item)
            rp = remote_d + "/" + item
            if os.path.isdir(lp):
                sftp_sync_dir(lp, rp)
            else:
                try:
                    sftp.put(lp, rp)
                except Exception as e:
                    print(f"  Fail to put {rp}: {e}")

    log("2/4 Syncing web/, core/, src/ to Mini PC...")
    sftp_sync_dir(os.path.join(PROJECT_ROOT, "web"), "/mitranet/web")
    sftp_sync_dir(os.path.join(PROJECT_ROOT, "web"), "/usr/share/mitranet/web")
    sftp_sync_dir(os.path.join(PROJECT_ROOT, "core"), "/mitranet/core")
    sftp_sync_dir(os.path.join(PROJECT_ROOT, "src"), "/mitranet/src")
    
    # Restart API server if running
    log("2.1/4 Restarting MitraNet API on Mini PC...")
    ssh.exec_command("pkill -f 'src/api/server.py' 2>/dev/null; pkill -f 'python3 -m mitranet.api' 2>/dev/null")
    ssh.exec_command("nohup python3 /mitranet/src/api/server.py > /tmp/api.log 2>&1 &")
    
    sftp.close()
    ssh.close()
    log("Sync to Mini PC complete!")
    return True

def rebuild_deb_and_iso():
    log("3/4 Rebuilding ISO image via build/build_iso.py...")
    cmd = [sys.executable, os.path.join(PROJECT_ROOT, "build", "build_iso.py")]
    res = subprocess.run(cmd, cwd=PROJECT_ROOT, capture_output=True, text=True)
    if res.returncode == 0:
        log("ISO Rebuild SUCCESS!")
    else:
        log(f"WARNING: ISO Rebuild returned code {res.returncode}:\n{res.stderr}")
        return False
    return True

def push_to_github(commit_msg="chore: auto-sync update to repo, minipc and iso"):
    log("4/4 Checking Git status and pushing to GitHub...")
    subprocess.run(["git", "add", "."], cwd=PROJECT_ROOT, capture_output=True)
    
    status_res = subprocess.run(["git", "status", "--porcelain"], cwd=PROJECT_ROOT, capture_output=True, text=True)
    if status_res.stdout.strip():
        subprocess.run(["git", "commit", "-m", commit_msg], cwd=PROJECT_ROOT, capture_output=True)
        push_res = subprocess.run(["git", "push", "origin", "main"], cwd=PROJECT_ROOT, capture_output=True, text=True)
        if push_res.returncode == 0:
            log("Git push to GitHub SUCCESS!")
        else:
            log(f"Git push error: {push_res.stderr}")
            return False
    else:
        log("Working tree already clean, nothing to commit.")
    return True

def run_pipeline(commit_msg=None):
    print("=" * 60)
    print("  MitraNet Automated Deployment Pipeline")
    print("=" * 60)
    sync_to_minipc()
    rebuild_deb_and_iso()
    push_to_github(commit_msg or "chore: automatic code update and pipeline sync")
    print("=" * 60)
    print("  Pipeline execution complete!")
    print("=" * 60)

if __name__ == "__main__":
    msg = sys.argv[1] if len(sys.argv) > 1 else None
    run_pipeline(msg)
