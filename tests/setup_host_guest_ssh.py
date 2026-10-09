import paramiko

def check():
    c = paramiko.SSHClient()
    c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    c.connect('192.168.56.101', username='root', password='mitranet')
    
    # Check ssh keys, install sshpass or set up ssh key on guest
    cmd = """
which sshpass || apt-get install -y sshpass
"""
    stdin, stdout, stderr = c.exec_command(cmd)
    print("INSTALL SSHPASS:\n" + stdout.read().decode())
    
    # Also check if host has an ssh key
    stdin, stdout, stderr = c.exec_command("[ -f /root/.ssh/id_rsa.pub ] || ssh-keygen -t rsa -N '' -f /root/.ssh/id_rsa")
    print("KEYGEN:\n" + stdout.read().decode())

    # Copy key to guest
    stdin, stdout, stderr = c.exec_command("sshpass -p 'K0323205' ssh-copy-id -o StrictHostKeyChecking=no root@192.168.101.118")
    print("SSH COPY ID:\n" + stdout.read().decode() + "\n" + stderr.read().decode())

    # Now test direct ssh
    stdin, stdout, stderr = c.exec_command("ssh -o StrictHostKeyChecking=no root@192.168.101.118 'cat /etc/init.d/bt default'")
    print("BT DEFAULT OUTPUT:\n" + stdout.read().decode())

    c.close()

if __name__ == '__main__':
    check()
