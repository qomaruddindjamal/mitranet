import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('192.168.56.101', port=22, username='root', password='mitranet')

cmd = """
curl -s -c /tmp/c.txt -X POST http://127.0.0.1:8443/api/v1/auth/login -H 'Content-Type: application/json' -d '{"username":"admin","password":"mitranet"}' >/dev/null
echo "=== BEFORE RESIZE ==="
qemu-img info -U /var/lib/mitranet/vms/aapanel/disk.qcow2 | grep 'virtual size'

echo "=== UPDATE DISK TO 15 GB ==="
curl -s -b /tmp/c.txt -X POST http://127.0.0.1:8443/api/v1/services/kvm/update -H 'Content-Type: application/json' -d '{"id":"aapanel","disk_gb":15}'
echo ""

echo "=== AFTER RESIZE ==="
qemu-img info -U /var/lib/mitranet/vms/aapanel/disk.qcow2 | grep 'virtual size'
"""

stdin, stdout, stderr = ssh.exec_command(cmd)
print(stdout.read().decode('utf-8'))
ssh.close()
