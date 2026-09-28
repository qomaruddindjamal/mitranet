#!/bin/bash
# MitraNet - Cross-compile Xray-core from source using Go
set -e

TARGET_ARCH="${1:-all}"
OUTPUT_DIR="${2:-programs/xray/bin/arches}"

echo "=== [MitraNet] Go Multi-Architecture Cross-Compiler ==="
echo "[*] Target Architecture: ${TARGET_ARCH}"
echo "[*] Output Directory   : ${OUTPUT_DIR}"

if ! command -v go &> /dev/null; then
    echo "[-] Error: 'go' compiler is not installed."
    exit 1
fi

TMP_SRC="/tmp/xray-core-src"
if [ ! -d "${TMP_SRC}" ]; then
    echo "[*] Cloning Xray-core source repository..."
    git clone --depth 1 https://github.com/XTLS/Xray-core.git "${TMP_SRC}"
fi

cd "${TMP_SRC}"

compile_one() {
    local arch_name="$1"
    local goos="$2"
    local goarch="$3"
    local extra_env="$4"
    local ldflags="-s -w -buildid="
    
    local dest="${OUTPUT_DIR}/${arch_name}"
    mkdir -p "${dest}"
    
    echo "[*] Compiling for ${arch_name} (${goos}/${goarch} ${extra_env})..."
    env CGO_ENABLED=0 GOOS="${goos}" GOARCH="${goarch}" ${extra_env} \
        go build -v -trimpath -ldflags "${ldflags}" -o "${dest}/xray" ./main
    
    chmod +x "${dest}/xray"
    echo "[+] Done: ${dest}/xray ($(ls -lh "${dest}/xray" | awk '{print $5}'))"
}

case "${TARGET_ARCH}" in
    x86_64)
        compile_one "x86_64" "linux" "amd64" ""
        ;;
    freebsd_amd64)
        compile_one "freebsd_amd64" "freebsd" "amd64" ""
        ;;
    arm64|silicon)
        compile_one "arm64" "linux" "arm64" ""
        ;;
    arm)
        compile_one "armv7" "linux" "arm" "GOARM=7"
        compile_one "armv6" "linux" "arm" "GOARM=6"
        ;;
    mipsbe|smips)
        compile_one "mipsbe" "linux" "mips" "GOMIPS=softfloat"
        ;;
    mipsle|mmips)
        compile_one "mipsle" "linux" "mipsle" "GOMIPS=softfloat"
        ;;
    ppc|ppc64le)
        compile_one "ppc64le" "linux" "ppc64le" ""
        ;;
    all)
        compile_one "x86_64" "linux" "amd64" ""
        compile_one "freebsd_amd64" "freebsd" "amd64" ""
        compile_one "arm64" "linux" "arm64" ""
        compile_one "armv7" "linux" "arm" "GOARM=7"
        compile_one "mipsbe" "linux" "mips" "GOMIPS=softfloat"
        compile_one "mipsle" "linux" "mipsle" "GOMIPS=softfloat"
        compile_one "ppc64le" "linux" "ppc64le" ""
        ;;
    *)
        echo "[-] Unknown architecture target: ${TARGET_ARCH}"
        exit 1
        ;;
esac

echo "[+] Cross-compilation completed successfully!"
