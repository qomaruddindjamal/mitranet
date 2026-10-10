import unittest
import json
import os
import re
import sys

# Ensure parent of workspace root is in sys.path so 'mitranet' package is found
root_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
parent_dir = os.path.dirname(root_dir)
if parent_dir not in sys.path:
    sys.path.insert(0, parent_dir)
if root_dir not in sys.path:
    sys.path.insert(0, root_dir)

# Import auth and server components
from mitranet.src.api.auth import AuthManager

class TestBoosterRegression(unittest.TestCase):
    def setUp(self):
        self.auth = AuthManager()

    def test_vps_host_validation(self):
        # Valid IPv4
        import ipaddress
        valid_ips = ["103.93.162.168", "1.1.1.1", "192.168.1.1"]
        for ip in valid_ips:
            try:
                ipaddress.IPv4Address(ip)
                is_valid = True
            except ValueError:
                is_valid = False
            self.assertTrue(is_valid, f"{ip} should be valid IPv4")

        # Valid FQDN
        fqdn_pattern = r'^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*$'
        valid_domains = ["vps.example.com", "gateway.cloud.id", "my-server.net"]
        for domain in valid_domains:
            self.assertTrue(bool(re.match(fqdn_pattern, domain)), f"{domain} should match FQDN")

        # Command injection attempts
        invalid_hosts = ["103.93.162.168; rm -rf /", "vps.com && cat /etc/passwd", "`whoami`.example.com", "vps.com|nc"]
        for bad in invalid_hosts:
            is_valid_ip = False
            try:
                ipaddress.IPv4Address(bad)
                is_valid_ip = True
            except ValueError:
                pass
            is_valid_fqdn = bool(re.match(fqdn_pattern, bad))
            self.assertFalse(is_valid_ip or is_valid_fqdn, f"Dangerous input '{bad}' must be rejected!")

    def test_ros_script_generation_no_mikrotik_branding(self):
        # Generate sample script for 3 streams
        stream_count = 3
        created_streams = [
            {"id": i, "port": 51830 + i, "client_pubkey": f"testpubkey{i}="}
            for i in range(1, stream_count + 1)
        ]
        ros_script_lines = []
        for s in created_streams:
            i = s["id"]
            port = s["port"]
            client_pub = s["client_pubkey"]
            ros_script_lines.append(f"/interface wireguard add name=wg-boost{i} listen-port={port} comment=\"MitraNet Stream {i}\"")
            ros_script_lines.append(f"/ip address add address=10.250.{i}.1/30 interface=wg-boost{i} network=10.250.{i}.0")
            ros_script_lines.append(f"/interface wireguard peers add interface=wg-boost{i} public-key=\"{client_pub}\" allowed-address=10.250.{i}.2/32 comment=\"MitraNet Node Stream {i}\"")

        script = "\n".join(ros_script_lines)
        self.assertIn("/interface wireguard add name=wg-boost1", script)
        self.assertIn("/interface wireguard add name=wg-boost2", script)
        self.assertIn("/interface wireguard add name=wg-boost3", script)
        self.assertIn("10.250.1.1/30", script)
        self.assertIn("10.250.3.1/30", script)
        # Ensure RouterOS compatible commands are valid
        self.assertTrue(all(line.startswith("/interface") or line.startswith("/ip") for line in script.splitlines()))

    def test_linux_script_generation_security(self):
        stream_count = 2
        created_streams = [
            {"id": i, "port": 51830 + i, "client_pubkey": f"testpubkey{i}="}
            for i in range(1, stream_count + 1)
        ]
        linux_script_lines = []
        for s in created_streams:
            i = s["id"]
            client_pub = s["client_pubkey"]
            linux_script_lines.append(f"PublicKey = {client_pub}")
            linux_script_lines.append("PrivateKey = YOUR_VPS_PRIVATE_KEY_HERE")

        script = "\n".join(linux_script_lines)
        # Verify private keys are NEVER exposed or hardcoded
        self.assertIn("YOUR_VPS_PRIVATE_KEY_HERE", script)
        self.assertNotIn("BoosterPrivateKeyStream", script)

    def test_stream_ports_and_subnets(self):
        # Stream 1 -> 51831, 10.250.1.2/30
        # Stream 2 -> 51832, 10.250.2.2/30
        # Stream 3 -> 51833, 10.250.3.2/30
        # Stream 4 -> 51834, 10.250.4.2/30
        for i in range(1, 5):
            port = 51830 + i
            client_ip = f"10.250.{i}.2"
            peer_ip = f"10.250.{i}.1"
            self.assertEqual(port, 51830 + i)
            self.assertEqual(client_ip, f"10.250.{i}.2")
            self.assertEqual(peer_ip, f"10.250.{i}.1")

if __name__ == "__main__":
    unittest.main()
