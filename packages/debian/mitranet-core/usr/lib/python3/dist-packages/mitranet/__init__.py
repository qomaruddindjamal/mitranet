# MitraNet OS Package Namespace
# pkgutil-style namespace: allows multiple Debian packages to contribute
# sub-modules under the 'mitranet.*' namespace (PEP 328 / pkgutil convention)
from pkgutil import extend_path
__path__ = extend_path(__path__, __name__)
