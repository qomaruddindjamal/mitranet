import paramiko

vps = paramiko.SSHClient()
vps.set_missing_host_key_policy(paramiko.AutoAddPolicy())
vps.connect('103.93.162.168', port=22, username='citramedia', password='K0323205', look_for_keys=False, allow_agent=False, timeout=8)

clean_cmds = [
    '/interface gre remove [find name=gre-mitranet]',
    '/interface ipip remove [find name=ipip-mitranet]',
    '/interface eoip remove [find name=eoip-mitranet]',
    '/interface vxlan vteps remove [find interface=vxlan-mitranet]',
    '/interface vxlan remove [find name=vxlan-mitranet]',
    '/ip address remove [find interface=gre-mitranet]',
    '/ip address remove [find interface=ipip-mitranet]',
    '/ip address remove [find interface=eoip-mitranet]',
    '/ip address remove [find interface=vxlan-mitranet]',
    '/ip address remove [find address="10.254.1.1/30"]',
    '/ip address remove [find address="10.253.1.1/30"]',
    '/ip address remove [find address="10.252.1.1/30"]',
    '/ip address remove [find address="10.251.1.1/30"]',
]

for cmd in clean_cmds:
    _, stdout, _ = vps.exec_command(cmd)
    stdout.read()

print("VPS Test Cleanup Complete!")
vps.close()
