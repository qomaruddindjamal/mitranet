import paramiko

runner = """#!/bin/bash
VM_NAME="$1"
CONFIG="/etc/mitranet/vms/${VM_NAME}.env"

if [ -f "$CONFIG" ]; then
    source "$CONFIG"
fi

exec /usr/bin/qemu-system-x86_64 \\
    -name "${VM_NAME}" \\
    -m "${RAM_MB:-512}" \\
    -smp "${VCPU:-1}" \\
    -nographic \\
    -drive file=/var/lib/mitranet/vms/${VM_NAME}/disk.qcow2,if=virtio,format=qcow2 \\
    -netdev user,id=net0,hostfwd=tcp::${PORT_FWD:-8888}-:8888 \\
    -device virtio-net-pci,netdev=net0
"""

service = """[Unit]
Description=MitraNet Virtual Machine (%i)
After=network.target

[Service]
Type=simple
ExecStart=/usr/local/bin/mitranet-vm-runner %i
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
"""

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('192.168.56.101', username='root', password='mitranet', timeout=5)
sftp = ssh.open_sftp()

with sftp.file('/usr/local/bin/mitranet-vm-runner', 'w') as f:
    f.write(runner)

with sftp.file('/etc/systemd/system/mitranet-vm@.service', 'w') as f:
    f.write(service)

sftp.close()

script = """
chmod +x /usr/local/bin/mitranet-vm-runner
systemctl daemon-reload
systemctl start mitranet-vm@aapanel
sleep 2
systemctl is-active mitranet-vm@aapanel
ps aux | grep qemu-system-x86_64 | grep -v grep
"""
stdin, stdout, stderr = ssh.exec_command(script)
print("OUT:", stdout.read().decode('utf-8', errors='ignore'))
print("ERR:", stderr.read().decode('utf-8', errors='ignore'))
ssh.close()
