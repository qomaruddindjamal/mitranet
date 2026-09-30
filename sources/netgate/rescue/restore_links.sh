#!/bin/sh
# Recreate FreeBSD rescue crunchgen hardlinks before ISO compilation
DIR="$(cd "$(dirname "$0")" && pwd)"
if [ -f "$DIR/links.txt" ] && [ -f "$DIR/rescue" ]; then
    echo "[*] Restoring rescue hardlinks from links.txt..."
    while IFS= read -r cmd || [ -n "$cmd" ]; do
        cmd=$(echo "$cmd" | tr -d '\r')
        if [ -n "$cmd" ]; then
            ln -f "$DIR/rescue" "$DIR/$cmd"
        fi
    done < "$DIR/links.txt"
    echo "[+] Rescue links restored successfully."
fi
