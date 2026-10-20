"""Central credentials for MitraNet lab/ops scripts.

Values are read from .env at the repository root (gitignored).
Copy .env.example -> .env and fill real values. NEVER commit .env.
"""
import os
from pathlib import Path

_REPO_ROOT = Path(__file__).resolve().parent
_ENV_FILE = _REPO_ROOT / ".env"

def _load_env_file() -> None:
    """Load KEY=VALUE pairs from .env without overriding existing env."""
    if not _ENV_FILE.exists():
        return
    for raw in _ENV_FILE.read_text(encoding="utf-8").splitlines():
        line = raw.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, _, value = line.partition("=")
        key = key.strip()
        value = value.strip().strip('"').strip("'")
        if key and key not in os.environ:
            os.environ[key] = value

_load_env_file()

def _require(env_key: str) -> str:
    value = os.environ.get(env_key, "")
    if not value:
        raise RuntimeError(
            f"Missing credential '{env_key}': create .env from .env.example "
            f"at {_REPO_ROOT} and fill in the value."
        )
    return value

_ENV_KEYS = {
    "MINIPC_HOST":     "MITRANET_MINIPC_HOST",
    "MINIPC_USER":     "MITRANET_MINIPC_USER",
    "MINIPC_PASS":     "MITRANET_MINIPC_PASS",
    "LAB_HOST":        "MITRANET_LAB_HOST",
    "LAB_USER":        "MITRANET_LAB_USER",
    "LAB_PASS":        "MITRANET_LAB_PASS",
    "VM_HOST":         "MITRANET_VM_HOST",
    "VM_USER":         "MITRANET_VM_USER",
    "VM_PASS":         "MITRANET_VM_PASS",
    "VM_NAT_HOST":     "MITRANET_VM_NAT_HOST",
    "VM_NAT_PORT":     "MITRANET_VM_NAT_PORT",
    "VPS_HOST":        "MITRANET_VPS_HOST",
    "VPS_USER":        "MITRANET_VPS_USER",
    "VPS_PASS":        "MITRANET_VPS_PASS",
    "VPS_SSH_KEY":     "MITRANET_VPS_SSH_KEY",
    "L2TP_PSK":        "MITRANET_L2TP_PSK",
    "WEBUI_USER":      "MITRANET_WEBUI_USER",
    "WEBUI_PASS":      "MITRANET_WEBUI_PASS",
    "API_SECRET":      "MITRANET_API_SECRET",
}

def __getattr__(name: str) -> str:
    if name in _ENV_KEYS:
        return _require(_ENV_KEYS[name])
    raise AttributeError(f"module 'mitranet_secrets' has no attribute '{name}'")

def available() -> list:
    """Logical names that currently have a value (names only, no values)."""
    return [n for n, k in _ENV_KEYS.items() if os.environ.get(k)]
