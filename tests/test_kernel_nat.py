import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('192.168.56.101', username='root', password='mitranet', timeout=5)

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd)
    return (stdout.read() + stderr.read()).decode('utf-8', errors='replace')

print('Current nft tables:')
print(run('nft list tables'))

sftp = ssh.open_sftp()
nft_script = """table inet test_mitranet_nat {
    chain prerouting {
        type nat hook prerouting priority dstnat; policy accept;
        iifname "enp0s8" tcp dport 8080 counter dnat ip to 192.168.56.101:8443
    }
    chain postrouting {
        type nat hook postrouting priority srcnat; policy accept;
        oifname "enp0s3" counter masquerade
    }
}
"""
with sftp.file('/tmp/test_nat.nft', 'w') as f:
    f.write(nft_script)
sftp.close()

print('Checking test_nat.nft syntax with nft -c -f /tmp/test_nat.nft:')
print(run('nft -c -f /tmp/test_nat.nft'))

print('Applying test_nat.nft to verify kernel support:')
print(run('nft -f /tmp/test_nat.nft'))

print('Listing table test_mitranet_nat:')
print(run('nft list table inet test_mitranet_nat'))

print('Deleting test table:')
print(run('nft delete table inet test_mitranet_nat'))

ssh.close()
