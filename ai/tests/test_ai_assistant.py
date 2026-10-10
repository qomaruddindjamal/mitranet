#!/usr/bin/env python3
"""
test_ai_assistant.py - Test Suite for MitraNet AI Assistant Knowledge & Logic
Licensed under Apache License 2.0.
"""

import unittest
import os
import sys
import pathlib
import tempfile
import shutil

AI_DIR = pathlib.Path(__file__).resolve().parent.parent
sys.path.insert(0, str(AI_DIR))

from retriever import ContextualRetriever
from memory_manager import MemoryManager

class TestAIAssistant(unittest.TestCase):
    def setUp(self):
        self.test_dir = pathlib.Path(tempfile.mkdtemp())
        self.k_dir = self.test_dir / "knowledge"
        self.m_dir = self.test_dir / "memory"
        self.k_dir.mkdir(parents=True)
        self.m_dir.mkdir(parents=True)

        # Seed sample knowledge document
        (self.k_dir / "test_booster.md").write_text(
            "# Cloud Speed Booster\n\n## 1. Multi-Stream Architecture\nBooster menggabungkan 4 tunnel WireGuard.\n\n## 2. DSCP Marking\nNilai AF41 digunakan untuk bypass pembatasan.",
            encoding="utf-8"
        )

    def tearDown(self):
        shutil.rmtree(self.test_dir)

    def test_contextual_retrieval(self):
        retriever = ContextualRetriever(self.k_dir)
        results = retriever.search("WireGuard multi-stream")
        self.assertTrue(len(results) > 0)
        self.assertIn("Multi-Stream Architecture", results[0]["title"])
        self.assertEqual(results[0]["file"], "test_booster.md")

    def test_missing_knowledge_handling(self):
        retriever = ContextualRetriever(self.k_dir)
        # Search for completely unknown topic
        results = retriever.search("resep masakan rendang padang")
        self.assertEqual(len(results), 0)

    def test_memory_session_recording(self):
        mem = MemoryManager(self.m_dir)
        mem.record_turn("user", "Halo assistant")
        mem.record_turn("assistant", "Halo! Ada yang bisa dibantu?")
        
        hist = mem.get_recent_history(limit=2)
        self.assertEqual(len(hist), 2)
        self.assertEqual(hist[0]["role"], "user")
        self.assertEqual(hist[1]["role"], "assistant")

    def test_long_term_verified_facts(self):
        mem = MemoryManager(self.m_dir)
        mem.add_verified_fact("appliance_ip", "10.10.66.228", category="infrastructure")
        facts = mem.get_verified_facts()
        self.assertIn("appliance_ip", facts)
        self.assertEqual(facts["appliance_ip"]["value"], "10.10.66.228")

    def test_no_private_key_leakage(self):
        # Ensure retriever text does not leak private keys
        retriever = ContextualRetriever(AI_DIR / "knowledge")
        for chunk in retriever.chunks:
            self.assertNotIn("PRIVATE KEY", chunk["text"].upper())
            self.assertNotIn("BOOSTERPRIVATEKEY", chunk["text"].upper())

    def test_stale_handshake_and_zero_counter_detection(self):
        from tools.diagnostics import analyze_booster_runtime
        # When no API is running, analyze_booster_runtime must return missing_info rather than crash or hallucinate
        analysis = analyze_booster_runtime()
        self.assertIn("configured_state", analysis)
        self.assertIn("runtime_state", analysis)
        self.assertTrue(len(analysis["missing_info"]) > 0 or analysis["runtime_state"] in ("FULL_AGGREGATION", "DEGRADED", "DOWN"))

    def test_retriever_source_attribution_for_booster(self):
        retriever = ContextualRetriever(AI_DIR / "knowledge")
        results = retriever.search("handshake stale zero-counter")
        self.assertTrue(len(results) > 0)
        self.assertIn("booster_wireguard.md", results[0]["file"])
        self.assertIn("Pola Diagnostik", results[0]["title"])

if __name__ == "__main__":
    unittest.main()
