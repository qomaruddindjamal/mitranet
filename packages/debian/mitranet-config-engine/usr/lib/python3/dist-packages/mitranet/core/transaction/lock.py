"""
MitraNet Transaction Lock & Concurrency Control.
Phase 1F: Ensures single-writer atomicity and detects stale locks safely.
"""

import os
import json
import time
import socket
from datetime import datetime, timezone
from typing import Optional
from mitranet.core.transaction.models import TransactionLockInfo
from mitranet.core.transaction.exceptions import TransactionLockError


class TransactionLock:
    """Acquires and releases atomic file-based transaction lock."""

    def __init__(self, lock_file: str = "/var/lock/mitranet_transaction.lock", timeout_seconds: int = 15):
        self.lock_file = lock_file
        self.timeout_seconds = timeout_seconds
        self.current_lock: Optional[TransactionLockInfo] = None
        os.makedirs(os.path.dirname(self.lock_file), exist_ok=True)

    def is_locked(self) -> bool:
        if not os.path.exists(self.lock_file):
            return False
        # Check stale lock
        try:
            with open(self.lock_file, "r", encoding="utf-8") as f:
                data = json.load(f)
            info = TransactionLockInfo.model_validate(data)
            # Check if PID is still alive
            if self._pid_exists(info.pid):
                return True
            else:
                # Stale lock: PID no longer exists
                return False
        except Exception:
            return False

    def acquire(self, transaction_id: str) -> TransactionLockInfo:
        """Acquires lock or raises TransactionLockError if held."""
        if self.is_locked():
            # Read who holds it
            try:
                with open(self.lock_file, "r", encoding="utf-8") as f:
                    data = json.load(f)
                holder = data.get("transaction_id", "unknown")
                pid = data.get("pid", 0)
                raise TransactionLockError(
                    f"Transaction lock already held by transaction '{holder}' (PID: {pid})."
                )
            except TransactionLockError:
                raise
            except Exception:
                raise TransactionLockError("Transaction lock is currently active.")

        lock_info = TransactionLockInfo(
            lock_id=f"lock_{int(time.time())}",
            transaction_id=transaction_id,
            acquired_at=datetime.now(timezone.utc).isoformat(),
            pid=os.getpid(),
            hostname=socket.gethostname(),
        )

        try:
            with open(self.lock_file, "w", encoding="utf-8") as f:
                f.write(lock_info.model_dump_json(indent=2))
            self.current_lock = lock_info
            return lock_info
        except Exception as e:
            raise TransactionLockError(f"Failed to write lock file: {e}")

    def release(self, transaction_id: Optional[str] = None):
        """Releases lock safely."""
        if os.path.exists(self.lock_file):
            try:
                if transaction_id:
                    with open(self.lock_file, "r", encoding="utf-8") as f:
                        data = json.load(f)
                    if data.get("transaction_id") != transaction_id:
                        # Don't release someone else's lock
                        return
                os.remove(self.lock_file)
            except Exception:
                pass
        self.current_lock = None

    @staticmethod
    def _pid_exists(pid: int) -> bool:
        """Checks whether process ID is currently running."""
        if pid <= 0:
            return False
        try:
            # On Linux/POSIX, signal 0 tests existence without killing
            os.kill(pid, 0)
            return True
        except ProcessLookupError:
            return False
        except PermissionError:
            return True  # Exists but lacks permission
        except Exception:
            return False
