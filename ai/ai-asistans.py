#!/usr/bin/env python3
"""
ai-asistans.py - MitraNet Internal AI Assistant & Continuous Automation Engine
Version: 1.0.2-rinjani
Licensed under Apache License 2.0.

Provides autonomous indexing, architecture analysis, Linux command planning,
safe testing, crash-recovery, and handover packaging for MitraNet Rinjani.
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

class MitraNetAssistant:
    def __init__(self, root_dir: pathlib.Path):
        self.root = root_dir
        self.work_state_file = self.root / "MITRANET_WORK_STATE.md"
        self.next_action_file = self.root / "MITRANET_NEXT_ACTION.md"
        self.resume_file = self.root / "MITRANET_RESUME.md"
        self.knowledge_dir = self.root / "ai" / "knowledge"
        self.memory_dir = self.root / "ai" / "memory"
        self.logs_dir = self.root / "ai" / "logs"

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
        
        tests = ["tests/test_core.py", "tests/test_webui_api.py"]
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

    # 4. Mode: HANDOVER (Generate Handover Package)
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

    # 5. Mode: ASK (Q&A and Architecture Knowledge)
    def run_ask(self, query: str):
        self.log(f"Executing ASK mode: '{query}'")
        query_l = query.lower()
        if "arsitektur" in query_l or "architecture" in query_l:
            ans = """[MitraNet Architecture]:
1. Frontend: PHP 8.4 WebUI (WinBox theme, jQuery, Bootstrap, SweetAlert2) served via PHP built-in server on 127.0.0.1:8000.
2. Management API: Python REST API (src/api/server.py) listening on port 8443, proxying frontend requests and managing core networking.
3. Core Engine: Subsystem networking Debian di core/ (discovery, routing, vlan, bridge, bonding, VRF, and nftables firewall).
4. Appliance Target: Mini PC x86_64 Debian GNU/Linux 13 (Trixie)."""
        elif "deploy" in query_l or "golden rule" in query_l:
            ans = """[MitraNet Golden Rules / Deployment Pipeline]:
Setiap perubahan kode wajib melalui 3 tahapan (deploy_pipeline.py):
1. Sync ke Mini PC (10.10.66.228) -> /usr/share/mitranet/web dan /mitranet/
2. Rebuild ISO -> build/build_iso.py (xorriso)
3. Git commit & push -> https://github.com/qomaruddindjamal/mitranet.git"""
        elif "dns" in query_l:
            ans = """[MitraNet DNS Server]:
Dikelola oleh dnsmasq (port 53) dengan isolasi modul service restart atomic di /etc/dnsmasq.conf."""
        else:
            ans = f"Assistant query: '{query}'. Silakan gunakan parameter --mode ANALYZE, TEST, atau RECOVER untuk aksi kontekstual."
        print(f"\n{ans}\n")
        return ans


def main():
    parser = argparse.ArgumentParser(description="MitraNet Internal AI Assistant")
    parser.add_argument("--mode", choices=["ASK", "ANALYZE", "PLAN", "TEST", "REPAIR", "RECOVER", "HANDOVER", "DEPLOY"], default="RECOVER", help="Operational mode")
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
    elif args.mode == "HANDOVER":
        assistant.run_handover()
    elif args.mode == "ASK":
        assistant.run_ask(args.query or "arsitektur")
    else:
        print(f"Mode {args.mode} diaktifkan dalam mode aman.")

if __name__ == "__main__":
    main()
