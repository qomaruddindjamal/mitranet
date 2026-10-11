"""
pytest root configuration and namespace initialization for MitraNet test suite.
Injects 'mitranet', 'mitranet.core', 'mitranet.src', 'mitranet.migration',
and 'mitranet.tests' package modules mapping to repository directories
so tests run natively without requiring pip install.
"""
import sys
import types
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parent

if str(REPO_ROOT) not in sys.path:
    sys.path.insert(0, str(REPO_ROOT))

# 1. mitranet top-level package
if "mitranet" not in sys.modules:
    mitranet_pkg = types.ModuleType("mitranet")
    mitranet_pkg.__path__ = []
    sys.modules["mitranet"] = mitranet_pkg
else:
    mitranet_pkg = sys.modules["mitranet"]

# 2. mitranet.core package
if "mitranet.core" not in sys.modules:
    core_pkg = types.ModuleType("mitranet.core")
    core_pkg.__path__ = [str(REPO_ROOT / "core")]
    sys.modules["mitranet.core"] = core_pkg
    setattr(mitranet_pkg, "core", core_pkg)

# 3. mitranet.src package
if "mitranet.src" not in sys.modules:
    src_pkg = types.ModuleType("mitranet.src")
    src_pkg.__path__ = [str(REPO_ROOT / "src")]
    sys.modules["mitranet.src"] = src_pkg
    setattr(mitranet_pkg, "src", src_pkg)

# 4. mitranet.migration package
if "mitranet.migration" not in sys.modules:
    mig_pkg = types.ModuleType("mitranet.migration")
    mig_pkg.__path__ = [str(REPO_ROOT / "migration")]
    sys.modules["mitranet.migration"] = mig_pkg
    setattr(mitranet_pkg, "migration", mig_pkg)

# 5. mitranet.tests package (points to split tests directories)
if "mitranet.tests" not in sys.modules:
    test_pkg = types.ModuleType("mitranet.tests")
    test_pkg.__path__ = [
        str(REPO_ROOT / "tests" / "unit"),
        str(REPO_ROOT / "tests" / "integration"),
        str(REPO_ROOT / "tests" / "audit"),
    ]
    sys.modules["mitranet.tests"] = test_pkg
    setattr(mitranet_pkg, "tests", test_pkg)
