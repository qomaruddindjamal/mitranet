#!/usr/bin/env bash
# MitraNet OS - 1-Line VPS Auto-Installer Entrypoint
# Repository: https://github.com/qomaruddindjamal/mitranet

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [ -f "${SCRIPT_DIR}/deploy/install.sh" ]; then
    exec bash "${SCRIPT_DIR}/deploy/install.sh" "$@"
else
    # Direct standalone execution
    curl -sSL "https://raw.githubusercontent.com/qomaruddindjamal/mitranet/main/deploy/install.sh" | bash -s -- "$@"
fi
