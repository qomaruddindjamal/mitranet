#!/usr/bin/env python3
"""
ai-asistans.py - MitraNet Internal AI Assistant & Continuous Automation Engine
Version: 1.0.2-rinjani
Licensed under Apache License 2.0.

Provides autonomous indexing, architecture analysis, Linux command planning,
safe testing, crash-recovery, and contextual handover for MitraNet Rinjani.
"""

import os
import sys
import json
import argparse
import datetime
import subprocess
import pathlib
import re

PROJECT_ROOT = pathlib.Path(__file__).resolve().parent.parent

# Add ai directory to sys.path for local module imports
sys.path.insert(0, str(pathlib.Path(__file__).resolve().parent))

try:
    from retriever import ContextualRetriever
    from memory_manager import MemoryManager
    from tools.diagnostics import (
        check_system_services,
        get_booster_telemetry,
        get_wireguard_handshakes,
        get_default_routes,
        analyze_booster_runtime
    )
except ImportError:
    ContextualRetriever = None
    MemoryManager = None
    check_system_services = None
    get_booster_telemetry = None
    get_wireguard_handshakes = None
    get_default_routes = None
    analyze_booster_runtime = None

class MitraNetAssistant:
    def __init__(self, root_dir: pathlib.Path):
        self.root = root_dir
        self.work_state_file = self.root / "MITRANET_WORK_STATE.md"
        self.next_action_file = self.root / "MITRANET_NEXT_ACTION.md"
        self.resume_file = self.root / "MITRANET_RESUME.md"
        self.knowledge_dir = self.root / "ai" / "knowledge"
        self.memory_dir = self.root / "ai" / "memory"
        self.logs_dir = self.root / "ai" / "logs"
        self.logs_dir.mkdir(parents=True, exist_ok=True)

        self.retriever = ContextualRetriever(self.knowledge_dir) if ContextualRetriever else None
        self.memory = MemoryManager(self.memory_dir) if MemoryManager else None

    def log(self, msg: str, level: str = "INFO"):
        ts = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        formatted = f"[{ts}] [{level}] {msg}"
        print(formatted)
        log_file = self.logs_dir / "assistant.log"
        try:
            with open(log_file, "a", encoding="utf-8") as f:
                f.write(formatted + "\n")
        except Exception:
            pass

    # 1. Mode: RECOVER (Read Checkpoints)
    def run_recover(self):
        self.log("Executing RECOVER mode...")
        print("\n=== MITRANET RECOVERY & WORKFLOW STATUS ===")
        for name, p in [
            ("RESUME GUIDE", self.resume_file),
            ("WORK STATE", self.work_state_file),
            ("NEXT ACTION", self.next_action_file)
        ]:
            if p.exists():
                print(f"\n--- {name} ({p.name}) ---")
                lines = p.read_text(encoding="utf-8").splitlines()
                for line in lines[:20]:
                    print("  " + line)
                if len(lines) > 20:
                    print(f"  ... [{len(lines)-20} more lines]")
            else:
                print(f"\n[WARNING] Checkpoint file missing: {p.name}")
        print("\n==========================================")
        return True

    # 2. Mode: ANALYZE (Codebase & Repo Health)
    def run_analyze(self):
        self.log("Executing ANALYZE mode...")
        res = {
            "timestamp": datetime.datetime.now().isoformat(),
            "php_files_count": len(list(self.root.glob("web/**/*.php"))),
            "js_files_count": len(list(self.root.glob("web/**/*.js"))),
            "css_files_count": len(list(self.root.glob("web/**/*.css"))),
            "python_core_files": len(list(self.root.glob("core/**/*.py"))),
            "python_src_files": len(list(self.root.glob("src/**/*.py"))),
        }
        # Check git status
        try:
            git_out = subprocess.run(["git", "status", "--short"], cwd=self.root, capture_output=True, text=True)
            res["git_dirty_files"] = [l for l in git_out.stdout.splitlines() if l.strip()]
        except Exception as e:
            res["git_error"] = str(e)

        print("\n=== MITRANET ARCHITECTURE ANALYSIS ===")
        print(f"Total WebUI PHP Modules: {res['php_files_count']}")
        print(f"Total JavaScript Assets: {res['js_files_count']}")
        print(f"Total Stylesheets (CSS): {res['css_files_count']}")
        print(f"Total Python Core Files: {res['python_core_files']}")
        print(f"Total Python API Files : {res['python_src_files']}")
        print(f"Dirty / Uncommitted Git: {len(res.get('git_dirty_files', []))} files")
        print("=======================================")
        return res

    # 3. Mode: TEST (Local Test Suites)
    def run_test(self):
        self.log("Executing TEST mode (Safe Local Test Suite)...")
        env = os.environ.copy()
        env["PYTHONPATH"] = str(self.root.parent)
        
        tests = [
            "tests/test_core.py",
            "tests/test_webui_api.py",
            "tests/test_booster_regression.py",
            "ai/tests/test_ai_assistant.py"
        ]
        all_passed = True
        for t in tests:
            t_path = self.root / t
            if not t_path.exists():
                self.log(f"Test script not found: {t}", "WARN")
                continue
            self.log(f"Running {t}...")
            r = subprocess.run([sys.executable, str(t_path)], cwd=self.root, env=env, capture_output=True, text=True)
            if r.returncode == 0:
                print(f"  [PASS] {t}")
            else:
                print(f"  [FAIL] {t}\n{r.stderr or r.stdout}")
                all_passed = False
        return all_passed

    # 4. Mode: DIAGNOSE (Live Read-Only System & Booster Telemetry)
    def run_diagnose(self):
        self.log("Executing DIAGNOSE mode (Read-Only Diagnostics)...")
        print("\n=== MITRANET REAL-TIME DIAGNOSTIC REPORT ===")
        
        # 1. Cloud Speed Booster telemetry & Runtime Discrepancy Analysis
        if analyze_booster_runtime:
            analysis = analyze_booster_runtime()
            print("\n[Analisis Status Multi-Stream Booster (Runtime vs Konfigurasi)]:")
            print(f"  Konfigurasi Tersimpan: {analysis.get('configured_state')}")
            print(f"  Kondisi Runtime Real : {analysis.get('runtime_state')}")
            print(f"  Stream Sehat/UP      : {analysis.get('healthy_streams')}")
            print(f"  Stream Gagal/DOWN    : {analysis.get('failed_streams')}")
            print(f"  Stream Counter Nol   : {analysis.get('zero_counter_streams')}")

            if analysis.get("facts"):
                print("  Fakta Terverifikasi:")
                for f in analysis["facts"]:
                    print(f"    - [FAKTA] {f}")
            if analysis.get("warnings"):
                print("  Peringatan Integritas Link:")
                for w in analysis["warnings"]:
                    print(f"    - [PERINGATAN] {w}")
            if analysis.get("missing_info"):
                print("  Informasi Tidak Tersedia:")
                for mi in analysis["missing_info"]:
                    print(f"    - [DATA KOSONG] {mi}")

        elif get_booster_telemetry:
            b_data = get_booster_telemetry()
            print("\n[Cloud Speed Booster Telemetry]:")
            if b_data.get("success"):
                d = b_data.get("data", {})
                active = d.get("active", False)
                print(f"  Status Agregasi : {'AKTIF (MULTI-PATH)' if active else 'NON-AKTIF'}")
                print(f"  Jumlah Streams  : {d.get('stream_count', 0)}")
                print(f"  Algoritma       : {d.get('balancer_mode', 'ecmp').upper()}")
                print(f"  Bypass DSCP/MSS : {d.get('dscp_mode', 'NONE')} / {d.get('clamp_mss', 1360)}")
                print(f"  Total Throughput: RX {d.get('total_rx_formatted', '0 B')} | TX {d.get('total_tx_formatted', '0 B')}")
                for s in d.get("streams", []):
                    print(f"    - Stream #{s.get('id')}: {s.get('interface')} ({s.get('ip')}:{s.get('port')}) | Link: {s.get('status')} | RTT: {s.get('latency')} | RX: {s.get('rx_formatted')} | TX: {s.get('tx_formatted')}")
            else:
                print(f"  API Booster tidak dapat dijangkau: {b_data.get('error')}")

        # 2. WireGuard Handshakes
        if get_wireguard_handshakes:
            wg_data = get_wireguard_handshakes()
            print("\n[WireGuard Live Handshakes]:")
            if wg_data.get("success"):
                hs_list = wg_data.get("handshakes", [])
                if hs_list:
                    for h in hs_list:
                        age_str = f"{h.get('age_seconds')}s lalu" if h.get('age_seconds', -1) >= 0 else "Belum ada"
                        print(f"  Interface: {h.get('interface')} | Status: {h.get('status')} (Age: {age_str}) | Peer: {h.get('peer_pubkey')}")
                else:
                    print("  Tidak ada handshake WireGuard aktif terdeteksi.")
            else:
                print(f"  Status wg: {wg_data.get('error')}")

        # 3. Default Routes
        if get_default_routes:
            r_data = get_default_routes()
            print("\n[Active Default Route]:")
            if r_data.get("success"):
                for r in r_data.get("default_route", []):
                    print(f"  {r}")
            else:
                print(f"  Error: {r_data.get('error')}")

        print("\n============================================")
        return True

    # 5. Mode: HANDOVER (Generate Handover Package)
    def run_handover(self):
        self.log("Executing HANDOVER mode...")
        state_summary = "No state file found."
        next_action_summary = "No next action found."
        if self.work_state_file.exists():
            state_lines = self.work_state_file.read_text(encoding="utf-8").splitlines()
            state_summary = "\n".join(state_lines[:15])
        if self.next_action_file.exists():
            next_action_summary = self.next_action_file.read_text(encoding="utf-8")

        prompt = f"""## RESUME PROMPT (MITRANET CONTINUATION)

1. Proyek: MitraNet Rinjani 1.0.2 (Debian GNU/Linux 13 Appliance)
2. Workspace: {self.root}
3. Status Terkini:
{state_summary}

4. Langkah Pertama Belum Selesai:
{next_action_summary}

Instruksi: Baca MITRANET_RESUME.md, verifikasi kondisi aktual, dan lanjutkan tugas aktif."""
        print("\n=== RESUME PROMPT HANDOVER ===")
        print(prompt)
        print("==============================")
        return prompt

    # 6. Mode: ASK (Contextual Retrieval + Verified Knowledge Q&A)
    def run_ask(self, query: str):
        self.log(f"Executing ASK mode: '{query}'")
        
        # 1. Search knowledge base via contextual retriever
        retrieved_chunks = []
        if self.retriever:
            retrieved_chunks = self.retriever.search(query, top_k=2)

        # 2. Record turn to session memory
        if self.memory:
            self.memory.record_turn("user", query)

        # 3. Synthesize answer with source attribution
        if retrieved_chunks:
            ans_parts = [f"=== JAWABAN BERDASARKAN BASIS PENGETAHUAN MITRANET ===\n"]
            for ch in retrieved_chunks:
                ans_parts.append(f"[{ch['title']} - Sumber: ai/knowledge/{ch['file']} (Relevansi: {ch['score']})]:")
                ans_parts.append(ch['text'])
                ans_parts.append("")
            ans = "\n".join(ans_parts)
        else:
            # Fallback jika query tidak memiliki data cukup di knowledge base
            ans = f"[Informasi Belum Tersedia di Basis Pengetahuan]:\nTidak ditemukan dokumen yang cukup relevan untuk query '{query}'. AI MitraNet menolak mengarang data faktual tanpa sumber terverifikasi."

        if self.memory:
            self.memory.record_turn("assistant", ans)

        print(f"\n{ans}\n")
        return ans


def main():
    parser = argparse.ArgumentParser(description="MitraNet Internal AI Assistant")
    parser.add_argument("--mode", choices=["ASK", "ANALYZE", "PLAN", "TEST", "DIAGNOSE", "REPAIR", "RECOVER", "HANDOVER", "DEPLOY"], default="RECOVER", help="Operational mode")
    parser.add_argument("--query", type=str, default="", help="Query text for ASK mode")

    args = parser.parse_args()
    assistant = MitraNetAssistant(PROJECT_ROOT)

    if args.mode == "RECOVER":
        assistant.run_recover()
    elif args.mode == "ANALYZE":
        assistant.run_analyze()
    elif args.mode == "TEST":
        success = assistant.run_test()
        sys.exit(0 if success else 1)
    elif args.mode == "DIAGNOSE":
        assistant.run_diagnose()
    elif args.mode == "HANDOVER":
        assistant.run_handover()
    elif args.mode == "ASK":
        assistant.run_ask(args.query or "arsitektur")
    else:
        print(f"Mode {args.mode} diaktifkan dalam mode aman.")

if __name__ == "__main__":
    main()
