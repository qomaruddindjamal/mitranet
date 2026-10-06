"""
MitraNet WebUI & Management Authentication and Session Manager.
PBKDF2-HMAC-SHA256 password hashing, tokenized session cookies, CSRF protection.
Zero plaintext passwords, zero external runtime dependencies.
"""

import os
import json
import time
import secrets
import hashlib
import binascii
from typing import Optional, Dict, Any

AUTH_FILE = "/etc/mitranet/secrets/webui_users.json"
DEFAULT_USER = "admin"
DEFAULT_PASS = "mitranet"


class AuthManager:
    """Manages administrator credentials, password hashing, and active sessions."""

    def __init__(self, auth_file: str = AUTH_FILE):
        self.auth_file = auth_file
        self.sessions: Dict[str, Dict[str, Any]] = {}  # token -> session data
        self.csrf_tokens: Dict[str, float] = {}       # csrf_token -> expiry timestamp
        self._ensure_auth_store()

    def _ensure_auth_store(self) -> None:
        """Ensures the auth database file exists with initial admin credentials."""
        os.makedirs(os.path.dirname(self.auth_file), exist_ok=True)
        if not os.path.exists(self.auth_file):
            self.create_user(DEFAULT_USER, DEFAULT_PASS)

    @staticmethod
    def hash_password(password: str, iterations: int = 100000) -> str:
        """Hashes password with random salt using PBKDF2-HMAC-SHA256."""
        salt = os.urandom(16)
        dk = hashlib.pbkdf2_hmac("sha256", password.encode("utf-8"), salt, iterations)
        salt_hex = binascii.hexlify(salt).decode("ascii")
        hash_hex = binascii.hexlify(dk).decode("ascii")
        return f"pbkdf2_sha256:{iterations}:{salt_hex}:{hash_hex}"

    @staticmethod
    def verify_password(password: str, stored_hash: str) -> bool:
        """Verifies candidate password against stored PBKDF2 hash safely."""
        try:
            parts = stored_hash.split(":")
            if len(parts) != 4 or parts[0] != "pbkdf2_sha256":
                return False
            iterations = int(parts[1])
            salt = binascii.unhexlify(parts[2])
            expected_dk = parts[3]
            dk = hashlib.pbkdf2_hmac("sha256", password.encode("utf-8"), salt, iterations)
            computed_hex = binascii.hexlify(dk).decode("ascii")
            return secrets.compare_digest(computed_hex, expected_dk)
        except Exception:
            return False

    def create_user(self, username: str, password: str) -> None:
        """Stores a new user with hashed password."""
        users = {}
        if os.path.exists(self.auth_file):
            try:
                with open(self.auth_file, "r", encoding="utf-8") as f:
                    users = json.load(f)
            except Exception:
                users = {}
        users[username] = {
            "password_hash": self.hash_password(password),
            "created_at": time.time(),
        }
        with open(self.auth_file, "w", encoding="utf-8") as f:
            json.dump(users, f, indent=2)

    def authenticate(self, username: str, password: str) -> bool:
        """Validates credentials against auth store."""
        if not os.path.exists(self.auth_file):
            self._ensure_auth_store()
        try:
            with open(self.auth_file, "r", encoding="utf-8") as f:
                users = json.load(f)
            user_data = users.get(username)
            if not user_data:
                return False
            return self.verify_password(password, user_data.get("password_hash", ""))
        except Exception:
            return False

    def create_session(self, username: str, ttl_seconds: int = 3600) -> str:
        """Generates a secure session token and associated CSRF token."""
        token = secrets.token_hex(32)
        csrf = secrets.token_hex(24)
        now = time.time()
        self.sessions[token] = {
            "username": username,
            "created_at": now,
            "expires_at": now + ttl_seconds,
            "csrf_token": csrf,
        }
        self.csrf_tokens[csrf] = now + ttl_seconds
        return token

    def validate_session(self, token: Optional[str]) -> Optional[Dict[str, Any]]:
        """Validates session token and handles expiration."""
        if not token or token not in self.sessions:
            return None
        session = self.sessions[token]
        if time.time() > session["expires_at"]:
            del self.sessions[token]
            return None
        return session

    def validate_csrf(self, session: Dict[str, Any], csrf_header: Optional[str]) -> bool:
        """Checks CSRF token matching the authenticated session."""
        if not csrf_header or not session:
            return False
        expected = session.get("csrf_token")
        if not expected:
            return False
        return secrets.compare_digest(expected, csrf_header)

    def destroy_session(self, token: Optional[str]) -> None:
        """Invalidates active session upon logout."""
        if token and token in self.sessions:
            sess = self.sessions.pop(token)
            csrf = sess.get("csrf_token")
            if csrf in self.csrf_tokens:
                self.csrf_tokens.pop(csrf, None)
