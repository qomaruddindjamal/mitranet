import os
import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect("172.21.162.163", port=22, username="root", password="pfsense")
sftp = client.open_sftp()

remote_dir = "/packages/All"
local_dir = r"c:\MitraNet\sources\netgate\packages\All"
os.makedirs(local_dir, exist_ok=True)

files = sftp.listdir(remote_dir)
print(f"Total files in /packages/All: {len(files)}")

downloaded = 0
for f in files:
    if f.endswith(".pkg") and not "~" in f:
        rpath = f"{remote_dir}/{f}"
        lpath = os.path.join(local_dir, f)
        if not os.path.exists(lpath) or os.path.getsize(lpath) == 0:
            sftp.get(rpath, lpath)
            downloaded += 1

print(f"Downloaded {downloaded} new packages. Total local: {len(os.listdir(local_dir))}")

# Fetch repo catalogs
for f in ['meta.conf', 'data.pkg', 'packagesite.pkg']:
    rpath = f"/packages/{f}"
    lpath = f"c:/MitraNet/sources/netgate/packages/{f}"
    sftp.get(rpath, lpath)
    print(f"Fetched repo catalog {f}")

sftp.close()
client.close()
print("All packages synchronized successfully!")
