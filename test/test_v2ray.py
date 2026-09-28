#!/usr/bin/env python3
"""
MitraNet OS - Automated Test Suite for V2Ray / Xray Integration
Validates VLESS / VMESS configs, link parsers, and CLI modules
"""

import unittest
import os
import json
import sys

# Add programs/xray/cli to path
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "../programs/xray/cli")))
import mitranet_cli

class TestMitraNetXray(unittest.TestCase):
    def setUp(self):
        self.config_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), "../programs/xray/config"))

    def test_json_templates_validity(self):
        """Ensure all Xray JSON config templates are valid and well-formed"""
        config_files = [f for f in os.listdir(self.config_dir) if f.endswith(".json")]
        self.assertGreater(len(config_files), 0, "No JSON config files found in programs/xray/config")

        for f in config_files:
            file_path = os.path.join(self.config_dir, f)
            with open(file_path, "r", encoding="utf-8") as fp:
                try:
                    data = json.load(fp)
                except Exception as e:
                    self.fail(f"File {f} is not valid JSON: {e}")
            
            # Check mandatory keys
            self.assertIn("inbounds", data, f"{f} missing 'inbounds'")
            self.assertIn("outbounds", data, f"{f} missing 'outbounds'")
            self.assertIn("routing", data, f"{f} missing 'routing'")
            print(f"[OK] Configuration template verified: {f}")

    def test_vless_reality_link_parser(self):
        """Test parsing of modern vless:// reality links"""
        sample_link = (
            "vless://12345678-1234-1234-1234-123456789abc@vpn.example.com:443"
            "?security=reality&sni=www.apple.com&fp=chrome&pbk=dGVzdF9wdWJsaWNfa2V5XzEyMzQ1Njc4OQ"
            "&sid=12345678&type=tcp&flow=xtls-rprx-vision#Node-Singapore"
        )
        parsed = mitranet_cli.parse_vless_link(sample_link)
        self.assertEqual(parsed["name"], "Node-Singapore")
        outbound = parsed["outbound"]
        self.assertEqual(outbound["protocol"], "vless")
        self.assertEqual(outbound["streamSettings"]["security"], "reality")
        self.assertEqual(outbound["streamSettings"]["realitySettings"]["serverName"], "www.apple.com")
        self.assertEqual(outbound["settings"]["vnext"][0]["users"][0]["flow"], "xtls-rprx-vision")
        print("[OK] VLESS Reality Link Parser verified successfully.")

    def test_vless_ws_link_parser(self):
        """Test parsing of vless:// over WebSocket and TLS"""
        sample_link = (
            "vless://abcdef12-3456-7890-abcd-ef1234567890@cdn.example.com:443"
            "?security=tls&sni=cdn.example.com&type=ws&path=%2Fws-tunnel#Node-CDN"
        )
        parsed = mitranet_cli.parse_vless_link(sample_link)
        self.assertEqual(parsed["name"], "Node-CDN")
        outbound = parsed["outbound"]
        self.assertEqual(outbound["protocol"], "vless")
        self.assertEqual(outbound["streamSettings"]["network"], "ws")
        self.assertEqual(outbound["streamSettings"]["wsSettings"]["path"], "/ws-tunnel")
        print("[OK] VLESS WebSocket Link Parser verified successfully.")

    def test_vmess_link_parser(self):
        """Test parsing of vmess:// base64 JSON links"""
        vmess_data = {
            "v": "2",
            "ps": "VMess-US-Server",
            "add": "us.proxy.example.com",
            "port": 443,
            "id": "11112222-3333-4444-5555-666677778888",
            "aid": 0,
            "net": "ws",
            "type": "none",
            "host": "us.proxy.example.com",
            "path": "/ray",
            "tls": "tls",
            "sni": "us.proxy.example.com"
        }
        import base64
        b64_link = "vmess://" + base64.b64encode(json.dumps(vmess_data).encode("utf-8")).decode("utf-8")
        parsed = mitranet_cli.parse_vmess_link(b64_link)
        self.assertEqual(parsed["name"], "VMess-US-Server")
        outbound = parsed["outbound"]
        self.assertEqual(outbound["protocol"], "vmess")
        self.assertEqual(outbound["settings"]["vnext"][0]["address"], "us.proxy.example.com")
        print("[OK] VMESS Link Parser verified successfully.")

    def test_full_config_generation(self):
        """Test full Xray configuration generation including routing and inbounds"""
        sample_outbound = {
            "tag": "proxy-test",
            "protocol": "vless",
            "settings": {"vnext": [{"address": "test.node", "port": 443, "users": [{"id": "uuid"}]}]}
        }
        full_cfg = mitranet_cli.build_full_config(sample_outbound)
        self.assertEqual(len(full_cfg["inbounds"]), 3) # SOCKS5, HTTP, and Transparent
        self.assertEqual(full_cfg["outbounds"][0]["tag"], "proxy-test")
        self.assertEqual(full_cfg["outbounds"][1]["tag"], "direct")
        print("[OK] Full Config generation verified successfully.")

    def test_cpu_architecture_detection(self):
        """Test CPU architecture detection function"""
        info = mitranet_cli.detect_cpu_architecture()
        self.assertIn("arch_code", info)
        self.assertIn("category", info)
        self.assertIn("endian", info)
        self.assertIn("is_low_memory", info)
        self.assertIsInstance(info["is_low_memory"], bool)
        print(f"[OK] CPU Architecture detection verified: {info['arch_code']} ({info['category']})")

    def test_multiarch_profiles_validity(self):
        """Validate all hardware and architecture profile JSON files"""
        profiles_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), "../profiles"))
        self.assertTrue(os.path.exists(profiles_dir), "profiles/ directory must exist")
        
        expected_arches = ["x86_64", "arm64", "arm", "mipsbe", "mmips", "smips", "mipsle", "ppc", "sbc_all", "silicon", "vm_all"]
        for arch in expected_arches:
            prof_file = os.path.join(profiles_dir, f"{arch}.json")
            self.assertTrue(os.path.exists(prof_file), f"Profile {arch}.json missing in profiles/")
            with open(prof_file, "r", encoding="utf-8") as fp:
                data = json.load(fp)
            self.assertIn("arch", data)
            self.assertIn("name", data)
            self.assertIn("features", data)
        print(f"[OK] All {len(expected_arches)} Multi-Architecture Profiles verified successfully.")

if __name__ == "__main__":
    unittest.main()
