import os
import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect("172.21.162.163", port=22, username="root", password="pfsense")
sftp = client.open_sftp()

remote_dir = "/mnt_orig/var/cache/pkg"
local_dir = r"c:\MitraNet\sources\netgate\packages\All"
os.makedirs(local_dir, exist_ok=True)

files = sftp.listdir(remote_dir)
print("Files in cache:", files)

for f in files:
    if f.endswith(".pkg") and not "~" in f:
        rpath = f"{remote_dir}/{f}"
        lpath = os.path.join(local_dir, f)
        print(f"Downloading {f}...")
        sftp.get(rpath, lpath)
        print(f"Downloaded {f}: {os.path.getsize(lpath)} bytes")

sftp.close()
client.close()
print("Done!")
