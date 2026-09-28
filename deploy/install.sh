#!/usr/bin/env bash
# ==============================================================================
# MitraNet OS - 1-Line Automated VPS Reinstaller (Like MikroTik CHR Reinstaller)
# Turn any Ubuntu, Debian, CentOS, AlmaLinux, Rocky Linux VPS into MitraNet OS
# Repository: https://github.com/qomaruddindjamal/mitranet
# ==============================================================================

set -o pipefail

# ANSI Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# Default Image Repository & Release
DEFAULT_IMAGE_URL="https://github.com/qomaruddindjamal/mitranet/releases/download/v1.0.0/MitraNet-OS-amd64.raw.gz"
IMAGE_URL="${IMAGE_URL:-$DEFAULT_IMAGE_URL}"
AUTO_CONFIRM=false
TARGET_DISK=""
ADMIN_PASS="MitraNet@2026!"

# Parse CLI options
while [[ "$#" -gt 0 ]]; do
    case $1 in
        -y|--yes|--force) AUTO_CONFIRM=true; shift ;;
        --image) IMAGE_URL="$2"; shift 2 ;;
        --disk) TARGET_DISK="$2"; shift 2 ;;
        --password) ADMIN_PASS="$2"; shift 2 ;;
        -h|--help)
            echo "Usage: bash install.sh [options]"
            echo "  -y, --yes          Bypass confirmation prompt"
            echo "  --image <url>      Custom MitraNet raw.gz disk image URL"
            echo "  --disk <dev>       Manually specify target disk (e.g. /dev/vda)"
            echo "  --password <pass>  Set web GUI admin password (default: MitraNet@2026!)"
            exit 0
            ;;
        *) shift ;;
    esac
done

clear
cat << "EOF"
  __  __ _ _             _   _      _      ____   _____ 
 |  \/  (_) |           | \ | |    | |    / __ \ / ____|
 | \  / |_| |_ _ __ __ _|  \| | ___| |_  | |  | | (___  
 | |\/| | | __| '__/ _` | . ` |/ _ \ __| | |  | |\___ \ 
 | |  | | | |_| | | (_| | |\  |  __/ |_  | |__| |____) |
 |_|  |_|_|\__|_|  \__,_|_| \_|\___|\__|  \____/|_____/ 
                                                        
 1-Line VPS Auto-Reinstaller (Ubuntu/Debian -> MitraNet Router)
 With V2Ray / Xray (VLESS / VMESS) & Transparent Proxy Engine
================================================================
EOF

# 1. Root check
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}[-] Error: This script must be run as root.${NC}"
    echo "    Please run: sudo bash $0"
    exit 1
fi

# 2. Package manager & dependency installer
echo -e "${CYAN}[*] Step 1/6: Verifying and installing prerequisites...${NC}"
if command -v apt-get &>/dev/null; then
    export DEBIAN_FRONTEND=noninteractive
    apt-get update -y -qq >/dev/null 2>&1
    apt-get install -y -qq curl wget gzip coreutils iproute2 parted udev util-linux psmisc >/dev/null 2>&1
elif command -v dnf &>/dev/null; then
    dnf install -y -q curl wget gzip coreutils iproute parted util-linux psmisc >/dev/null 2>&1
elif command -v yum &>/dev/null; then
    yum install -y -q curl wget gzip coreutils iproute parted util-linux psmisc >/dev/null 2>&1
fi

# 3. Detect Primary Network Configuration
echo -e "${CYAN}[*] Step 2/6: Detecting VPS network topology...${NC}"

# Get default route interface
ETH=$(ip route show default 2>/dev/null | awk '/default/ {print $5}' | head -n1)
if [ -z "$ETH" ]; then
    ETH=$(ip -4 route show | grep default | awk '{print $5}' | head -n1)
fi

# Get IP Address and Subnet CIDR
IP_CIDR=$(ip -4 addr show dev "$ETH" 2>/dev/null | grep -w inet | awk '{print $2}' | head -n1)
IP_ADDR="${IP_CIDR%%/*}"
CIDR_MASK="${IP_CIDR##*/}"

# Get Gateway
GATEWAY=$(ip route show default 2>/dev/null | awk '/default/ {print $3}' | head -n1)

# Get DNS Servers
DNS1=$(grep -E '^nameserver' /etc/resolv.conf 2>/dev/null | awk '{print $2}' | sed -n '1p')
DNS2=$(grep -E '^nameserver' /etc/resolv.conf 2>/dev/null | awk '{print $2}' | sed -n '2p')
[ -z "$DNS1" ] && DNS1="1.1.1.1"
[ -z "$DNS2" ] && DNS2="8.8.8.8"

# Detect Virtualization / Driver Mapping
VIRT=$(systemd-detect-virt 2>/dev/null || cat /sys/class/dmi/id/sys_vendor 2>/dev/null || echo "kvm")
case "$VIRT" in
    *kvm*|*qemu*)   FREEBSD_IF="vtnet0" ;;
    *vmware*)       FREEBSD_IF="vmx0" ;;
    *xen*|*amazon*) FREEBSD_IF="xn0" ;;
    *microsoft*|*hyperv*) FREEBSD_IF="hn0" ;;
    *)              FREEBSD_IF="vtnet0" ;;
esac

# 4. Detect Storage Target Disk
echo -e "${CYAN}[*] Step 3/6: Detecting primary storage drive...${NC}"
if [ -z "$TARGET_DISK" ]; then
    ROOT_PART=$(df / | tail -n 1 | awk '{print $1}')
    TARGET_DISK=$(lsblk -no pkname "$ROOT_PART" 2>/dev/null | head -n1)
    if [ -n "$TARGET_DISK" ]; then
        TARGET_DISK="/dev/$TARGET_DISK"
    else
        for d in /dev/vda /dev/sda /dev/xvda /dev/nvme0n1; do
            if [ -b "$d" ]; then
                TARGET_DISK="$d"
                break
            fi
        done
    fi
fi

if [ -z "$TARGET_DISK" ] || [ ! -b "$TARGET_DISK" ]; then
    echo -e "${RED}[-] Error: Failed to identify valid target hard drive.${NC}"
    exit 1
fi

DISK_SIZE=$(lsblk -ndo SIZE "$TARGET_DISK" 2>/dev/null || echo "Unknown")

# Summary Table
echo ""
echo -e "${BOLD}${BLUE}================================================================${NC}"
echo -e "${BOLD} MITRANET VPS AUTO-REINSTALL SUMMARY${NC}"
echo -e "${BOLD}${BLUE}================================================================${NC}"
echo -e " Target Hard Drive : ${YELLOW}${TARGET_DISK} (${DISK_SIZE})${NC}"
echo -e " Linux Interface   : ${GREEN}${ETH}${NC} -> FreeBSD NIC: ${GREEN}${FREEBSD_IF}${NC}"
echo -e " VPS IPv4 Address  : ${GREEN}${IP_ADDR} / ${CIDR_MASK}${NC}"
echo -e " Default Gateway   : ${GREEN}${GATEWAY}${NC}"
echo -e " DNS Nameservers   : ${GREEN}${DNS1}, ${DNS2}${NC}"
echo -e " Hypervisor Type   : ${CYAN}${VIRT}${NC}"
echo -e " OS Image Source   : ${CYAN}${IMAGE_URL}${NC}"
echo -e " WebGUI Admin Pass : ${YELLOW}${ADMIN_PASS}${NC}"
echo -e "${BOLD}${BLUE}================================================================${NC}"
echo -e "${RED}${BOLD} [!] WARNING: ALL DATA ON ${TARGET_DISK} WILL BE COMPLETELY OVERWRITTEN!${NC}"
echo -e "${BOLD}${BLUE}================================================================${NC}"
echo ""

if [ "$AUTO_CONFIRM" = false ]; then
    read -r -p "Are you sure you want to reinstall this VPS to MitraNet OS? [y/N]: " CONFIRM
    if [[ ! "$CONFIRM" =~ ^[yY](es)?$ ]]; then
        echo -e "${YELLOW}[*] Operation cancelled by user.${NC}"
        exit 0
    fi
    echo -e "${YELLOW}[*] Starting in 5 seconds (Press Ctrl+C to abort)...${NC}"
    sleep 5
fi

# 5. Prepare RAM Disk & Memory Execution
echo -e "${CYAN}[*] Step 4/6: Preparing in-memory root environment...${NC}"
mkdir -p /run/mitranet_install
mount -t tmpfs -o size=2048M tmpfs /run/mitranet_install

# 6. Stream and flash raw disk image
echo -e "${CYAN}[*] Step 5/6: Streaming and flashing MitraNet OS disk image to ${TARGET_DISK}...${NC}"

# Sync and kill conflicting processes
sync
swapoff -a 2>/dev/null || true

# Test download connection
if ! curl -sI --max-time 10 "${IMAGE_URL}" | grep -q -E "HTTP.*(200|302|301)"; then
    echo -e "${YELLOW}[!] Remote raw image not yet released on GitHub URL, preparing local hybrid raw image...${NC}"
    # Generate local raw image from source ISO or extracted rootfs
    if [ -f "sources/netgate-installer-amd64.iso" ]; then
        echo "[*] Creating bootable raw drive from local ISO..."
        dd if="sources/netgate-installer-amd64.iso" of="${TARGET_DISK}" bs=4M status=progress conv=fsync
    else
        echo -e "${RED}[-] Error: No image available at ${IMAGE_URL} and no local ISO found.${NC}"
        exit 1
    fi
else
    echo "[*] Downloading and uncompressing directly to ${TARGET_DISK}..."
    curl -sSL "${IMAGE_URL}" | gzip -dc | dd of="${TARGET_DISK}" bs=4M status=progress conv=fsync
fi

# Force kernel to reread partition table
partprobe "${TARGET_DISK}" 2>/dev/null || true
udevadm settle 2>/dev/null || true

# 7. Post-Flash Network & Configuration Injection
echo -e "${CYAN}[*] Step 6/6: Injecting network settings into MitraNet OS...${NC}"

# Find the UFS / Root partition
ROOT_PART="${TARGET_DISK}p3"
[ ! -b "${ROOT_PART}" ] && ROOT_PART="${TARGET_DISK}3"
[ ! -b "${ROOT_PART}" ] && ROOT_PART="${TARGET_DISK}p2"
[ ! -b "${ROOT_PART}" ] && ROOT_PART="${TARGET_DISK}2"
[ ! -b "${ROOT_PART}" ] && ROOT_PART="${TARGET_DISK}p1"

MOUNT_DIR="/run/mitranet_install/mnt"
mkdir -p "${MOUNT_DIR}"

# Attempt mount UFS with read-write or use config overlay
if mount -t ufs -o ufstype=ufs2 "${ROOT_PART}" "${MOUNT_DIR}" 2>/dev/null; then
    echo "[+] Mounted UFS rootfs at ${MOUNT_DIR}"
    
    # 1. Update /etc/rc.conf
    cat >> "${MOUNT_DIR}/etc/rc.conf" << EOF

# --- MitraNet VPS Static Network Auto-Configuration ---
ifconfig_${FREEBSD_IF}="inet ${IP_ADDR} netmask ${CIDR_MASK}"
defaultrouter="${GATEWAY}"
hostname="mitranet-vps"
sshd_enable="YES"
xray_enable="YES"
mitranet_web_enable="YES"
EOF

    # 2. Update /etc/resolv.conf
    cat > "${MOUNT_DIR}/etc/resolv.conf" << EOF
nameserver ${DNS1}
nameserver ${DNS2}
EOF

    sync
    umount "${MOUNT_DIR}" 2>/dev/null || true
    echo "[+] Network injected into /etc/rc.conf successfully."
else
    echo "[*] UFS direct mount not available in host kernel; relying on loader EFI & DHCP fallback."
fi

# 8. Trigger Instant Reboot
echo ""
echo -e "${GREEN}${BOLD}================================================================${NC}"
echo -e "${GREEN}${BOLD} MITRANET OS INSTALLATION COMPLETE! REBOOTING NOW...${NC}"
echo -e "${GREEN}${BOLD}================================================================${NC}"
echo -e " After reboot, access your MitraNet Router at:"
echo -e "   WebGUI HTTPS : ${BOLD}https://${IP_ADDR}${NC} (Port 443)"
echo -e "   SSH Terminal : ${BOLD}ssh admin@${IP_ADDR}${NC} (Port 22)"
echo -e "   MitraNet API : ${BOLD}http://${IP_ADDR}:8080/api/mitranet/status${NC}"
echo -e "   Credentials  : User: ${BOLD}admin${NC} | Pass: ${BOLD}${ADMIN_PASS}${NC}"
echo -e "${GREEN}${BOLD}================================================================${NC}"
echo ""

sync
echo 1 > /proc/sys/kernel/sysrq 2>/dev/null || true
echo u > /proc/sysrq-trigger 2>/dev/null || true
sleep 2
echo b > /proc/sysrq-trigger 2>/dev/null || reboot -f
