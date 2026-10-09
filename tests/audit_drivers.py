import paramiko
import sys

def audit_drivers():
    c = paramiko.SSHClient()
    c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    c.connect('192.168.56.101', username='root', password='mitranet')

    commands = [
        ('Audio / Sound Drivers Loaded', 'lsmod | grep -E "snd|sound|audio"'),
        ('GPU / DRM Graphic Desktop Drivers Loaded', 'lsmod | grep -E "drm|nouveau|radeon|amdgpu|i915"'),
        ('Bluetooth Modules Loaded', 'lsmod | grep -E "bluetooth|btusb|bnep|rfcomm"'),
        ('Media / TV / Webcam / Radio Modules', 'lsmod | grep -E "videodev|uvcvideo|media|rc_core"'),
        ('Total Loaded Kernel Modules Count', 'lsmod | wc -l'),
        ('Desktop / GUI / Audio Packages', 'dpkg -l | grep -E "alsa|pulseaudio|pipewire|x11|xorg|cups|bluez"'),
        ('Modprobe Blacklist Current', 'ls -la /etc/modprobe.d/')
    ]

    for title, cmd in commands:
        stdin, stdout, stderr = c.exec_command(cmd)
        raw = stdout.read().decode('utf-8', errors='replace')
        sys.stdout.buffer.write(f"\n=== {title} ===\n{raw}\n".encode('utf-8'))

    c.close()

if __name__ == '__main__':
    audit_drivers()
