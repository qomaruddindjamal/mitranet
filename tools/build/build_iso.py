import subprocess
import os
import sys

xorriso_exe = r'C:\Windows\system32\xorriso.exe'
output_iso = r'C:\mitranet\iso\MitraNet-Rinjani-1.0.2-amd64.iso'

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
