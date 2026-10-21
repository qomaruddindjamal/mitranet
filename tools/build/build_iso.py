import subprocess
import os
import sys
import tarfile

project_root = r'C:\mitranet'
xorriso_exe = r'C:\Windows\system32\xorriso.exe'
output_iso = r'C:\mitranet\iso\MitraNet-Rinjani-1.0.2-amd64.iso'
iso_work_dir = r'C:\mitranet\build\iso_work'
mitranet_tar = os.path.join(iso_work_dir, 'mitranet', 'mitranet.tar.gz')

print("[ISO BUILD] Packaging canonical source into mitranet.tar.gz...")
os.makedirs(os.path.dirname(mitranet_tar), exist_ok=True)
with tarfile.open(mitranet_tar, "w:gz") as tar:
    for folder in ['web', 'core', 'src', 'tests']:
        folder_path = os.path.join(project_root, folder)
        if os.path.exists(folder_path):
            tar.add(folder_path, arcname=folder)
print(f"[ISO BUILD] Packed latest source into {mitranet_tar} (size: {os.path.getsize(mitranet_tar)} bytes)")

cmd = [
    xorriso_exe,
    '-as', 'mkisofs',
    '-r', '-V', 'MitraNet_1.0.2_amd64',
    '-J', '-joliet-long',
    '-b', 'isolinux/isolinux.bin',
    '-c', 'isolinux/boot.cat',
    '-no-emul-boot',
    '-boot-load-size', '4',
    '-boot-info-table',
    '-eltorito-alt-boot',
    '-e', 'boot/grub/efi.img',
    '-no-emul-boot',
    '-isohybrid-gpt-basdat',
    '-o', output_iso,
    'iso_work'
]

print("Building ISO with command:")
print(" ".join(cmd))
res = subprocess.run(cmd, cwd=r'C:\mitranet\build', capture_output=True, text=True)
print("STDOUT:\n", res.stdout)
print("STDERR:\n", res.stderr)
full_iso_path = os.path.join(r'C:\mitranet\build', output_iso)
if res.returncode == 0:
    size = os.path.getsize(full_iso_path)
    print(f"SUCCESS: ISO created at {full_iso_path}, size: {size} bytes")
else:
    print(f"FAILED with return code: {res.returncode}")
