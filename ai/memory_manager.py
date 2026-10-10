#!/usr/bin/env python3
"""
memory_manager.py - Session & Long-Term Memory Manager for MitraNet AI Assistant
Maintains verified project decisions, conversation history, and feedback.
"""

import os
import json
import pathlib
import datetime
from typing import Dict, Any, List, Optional

class MemoryManager:
    """Manages short-term session memory and persistent long-term knowledge."""

    def __init__(self, memory_dir: pathlib.Path):
        self.memory_dir = memory_dir
        self.memory_dir.mkdir(parents=True, exist_ok=True)
        self.session_file = self.memory_dir / "session_memory.json"
        self.long_term_file = self.memory_dir / "long_term_memory.json"
        self.feedback_file = self.memory_dir / "feedback_log.json"

    def _read_json(self, file_path: pathlib.Path, default_val: Any) -> Any:
        if not file_path.exists():
            return default_val
        try:
            return json.loads(file_path.read_text(encoding="utf-8"))
        except Exception:
            return default_val

    def _write_json(self, file_path: pathlib.Path, data: Any):
        temp_file = file_path.with_suffix(".tmp")
        temp_file.write_text(json.dumps(data, indent=2, ensure_ascii=False), encoding="utf-8")
        temp_file.replace(file_path)

    # 1. Session Memory
    def record_turn(self, role: str, message: str, meta: Optional[Dict[str, Any]] = None):
        """Append user/assistant interaction turn to session memory."""
        session = self._read_json(self.session_file, {"session_id": "default", "history": []})
        entry = {
            "timestamp": datetime.datetime.now().isoformat(),
            "role": role,
            "message": message,
            "meta": meta or {}
        }
        session["history"].append(entry)
        # Cap session history to 50 turns to prevent unbounded growth
        if len(session["history"]) > 50:
            session["history"] = session["history"][-50:]
        self._write_json(self.session_file, session)

    def get_recent_history(self, limit: int = 5) -> List[Dict[str, Any]]:
        session = self._read_json(self.session_file, {"history": []})
        return session.get("history", [])[-limit:]

    def clear_session(self):
        self._write_json(self.session_file, {"session_id": "default", "history": []})

    # 2. Long-Term Verified Memory
    def add_verified_fact(self, key: str, value: Any, category: str = "decision"):
        """Store permanent verified architectural facts and user preferences."""
        lt = self._read_json(self.long_term_file, {"facts": {}})
        lt["facts"][key] = {
            "updated_at": datetime.datetime.now().isoformat(),
            "category": category,
            "value": value
        }
        self._write_json(self.long_term_file, lt)

    def get_verified_facts(self) -> Dict[str, Any]:
        lt = self._read_json(self.long_term_file, {"facts": {}})
        return lt.get("facts", {})

    # 3. Feedback Log
    def log_feedback(self, query: str, answer: str, rating: str, comment: str = ""):
        feedback = self._read_json(self.feedback_file, {"ratings": []})
        feedback["ratings"].append({
            "timestamp": datetime.datetime.now().isoformat(),
            "query": query,
            "answer": answer,
            "rating": rating,
            "comment": comment
        })
        self._write_json(self.feedback_file, feedback)
