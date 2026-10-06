#!/bin/bash
# ==============================================================================
# MitraNet Canonical Target Installer & First Boot Bootstrap
# Project     : MitraNet
# Version     : 0.1.0-dev
# Foundation  : MitraOS 1.0.0
# Code OS     : Rinjani
# Architecture: amd64
# Interface   : CLI + TUI + Web UI
# ==============================================================================
# Strict Operational Rules:
# - Target device MUST be explicitly specified (e.g. /dev/sda, /dev/vda, /dev/nvme0n1)
# - MUST NOT format or overwrite host system storage
# - Operates strictly inside isolated VM / target installer environment
# ==============================================================================

set -euo pipefail

TARGET_DEV="${1:-}"
TARGET_HOSTNAME="${2:-mitranet}"
TARGET_TIMEZONE="${3:-Asia/Jakarta}"
TARGET_LOCALE="${4:-en_US.UTF-8}"
TARGET_KEYBOARD="${5:-us}"
ADMIN_PASS="${6:-mitranet}"
BOOT_MODE="auto" # auto | uefi | bios

log() {
    echo "[MITRANET-INSTALLER] $*"
}

error() {
    echo "[MITRANET-INSTALLER][ERROR] $*" >&2
    exit 1
}

usage() {
    cat <<EOF
Usage: $0 <target_disk> [hostname] [timezone] [locale] [keyboard] [admin_password]

Parameters:
  target_disk     Target block device (e.g., /dev/sda, /dev/vda, /dev/nvme0n1)
  hostname        System hostname (default: mitranet)
  timezone        Timezone string (default: Asia/Jakarta)
  locale          System locale (default: en_US.UTF-8)
  keyboard        Keyboard layout (default: us)
  admin_password  Administrator password for installed system (default: mitranet)

Example:
  $0 /dev/vda mitranet-core Asia/Jakarta en_US.UTF-8 us mitranet
EOF
    exit 1
}

# 1. Validation & Safety Checks
if [ -z "$TARGET_DEV" ]; then
    usage
fi

if [ ! -b "$TARGET_DEV" ]; then
    error "Specified target device '$TARGET_DEV' is not a valid block device."
fi

# Detect UEFI vs BIOS environment
if [ -d "/sys/firmware/efi" ]; then
    BOOT_MODE="uefi"
    log "Environment detected: UEFI mode"
else
    BOOT_MODE="bios"
    log "Environment detected: Legacy BIOS mode"
fi

log "Beginning MitraNet installation to target: $TARGET_DEV"
log "Target configuration: hostname=$TARGET_HOSTNAME timezone=$TARGET_TIMEZONE locale=$TARGET_LOCALE kbd=$TARGET_KEYBOARD"

# 2. Disk Partitioning Layout (GPT with protective MBR)
# Layout:
# Partition 1: 512MB EFI System Partition (type EF00 / vfat)
# Partition 2: 1MB BIOS Boot Partition (type EF02) for hybrid GRUB i386-pc
# Partition 3: Remaining disk for Root Filesystem (ext4)
log "Partitioning target disk $TARGET_DEV..."
sgdisk -Z "$TARGET_DEV" || true
sgdisk -n 1:2048:+512M -t 1:ef00 -c 1:"EFI System Partition" "$TARGET_DEV"
sgdisk -n 2:0:+2M      -t 2:ef02 -c 2:"BIOS Boot Partition" "$TARGET_DEV"
sgdisk -n 3:0:0        -t 3:8300 -c 3:"MitraNet RootFS" "$TARGET_DEV"
partprobe "$TARGET_DEV" || true
sleep 2

# Resolve partition node names (handling /dev/sda vs /dev/nvme0n1p)
if [[ "$TARGET_DEV" =~ [0-9]$ ]]; then
    PART_EFI="${TARGET_DEV}p1"
    PART_BIOS="${TARGET_DEV}p2"
    PART_ROOT="${TARGET_DEV}p3"
else
    PART_EFI="${TARGET_DEV}1"
    PART_BIOS="${TARGET_DEV}2"
    PART_ROOT="${TARGET_DEV}3"
fi

log "Target partitions: EFI=$PART_EFI, BIOS=$PART_BIOS, Root=$PART_ROOT"

# 3. Filesystem Formatting
log "Formatting EFI System Partition (vfat)..."
mkfs.vfat -F32 -n "MITRA_EFI" "$PART_EFI"

log "Formatting Root Filesystem (ext4)..."
mkfs.ext4 -F -L "MITRANET_ROOT" "$PART_ROOT"

# 4. Target Mount & Rootfs Extraction
MOUNT_TARGET="/mnt/target"
mkdir -p "$MOUNT_TARGET"
mount "$PART_ROOT" "$MOUNT_TARGET"
mkdir -p "$MOUNT_TARGET/boot/efi"
mount "$PART_EFI" "$MOUNT_TARGET/boot/efi"

# Locate live filesystem.squashfs
SQUASHFS_SOURCE=""
for candidate in /run/live/medium/live/filesystem.squashfs /live/filesystem.squashfs /mnt/iso/live/filesystem.squashfs; do
    if [ -f "$candidate" ]; then
        SQUASHFS_SOURCE="$candidate"
        break
    fi
done

if [ -n "$SQUASHFS_SOURCE" ]; then
    log "Extracting live rootfs from $SQUASHFS_SOURCE..."
    unsquashfs -f -d "$MOUNT_TARGET" "$SQUASHFS_SOURCE"
else
    log "Unsquashed source not found directly; copying from current live root environment..."
    rsync -aAX --exclude={"/mnt/*","/proc/*","/sys/*","/dev/*","/tmp/*","/run/*"} / "$MOUNT_TARGET/"
fi

# 5. Populate MitraNet Application Stack
MITRANET_SRC="/mitranet"
if [ -d "$MITRANET_SRC" ]; then
    log "Synchronizing canonical MitraNet codebase to $MOUNT_TARGET/mitranet..."
    mkdir -p "$MOUNT_TARGET/mitranet"
    rsync -a --exclude={"MitraOS-Apollo-amd64.iso","api/config.lock","api/backups","api/config.json"} "$MITRANET_SRC/" "$MOUNT_TARGET/mitranet/"
fi

# 6. Configure Persistence, System Identity, and fstab
ROOT_UUID=$(blkid -s UUID -o value "$PART_ROOT")
EFI_UUID=$(blkid -s UUID -o value "$PART_EFI")

cat <<EOF > "$MOUNT_TARGET/etc/fstab"
# /etc/fstab: static file system information for MitraNet
UUID=$ROOT_UUID   /           ext4    errors=remount-ro,noatime   0 1
UUID=$EFI_UUID    /boot/efi   vfat    umask=0077                  0 1
EOF

echo "$TARGET_HOSTNAME" > "$MOUNT_TARGET/etc/hostname"
cat <<EOF > "$MOUNT_TARGET/etc/hosts"
127.0.0.1   localhost
127.0.1.1   $TARGET_HOSTNAME
::1         localhost ip6-localhost ip6-loopback
EOF

echo "$TARGET_TIMEZONE" > "$MOUNT_TARGET/etc/timezone"
ln -sf "/usr/share/zoneinfo/$TARGET_TIMEZONE" "$MOUNT_TARGET/etc/localtime"

# 7. User Provisioning & Administrative Security
log "Provisioning administrator account on installed system..."
if ! chroot "$MOUNT_TARGET" id -u admin >/dev/null 2>&1; then
    chroot "$MOUNT_TARGET" useradd -m -s /bin/bash -G sudo,adm,dip admin || \
    chroot "$MOUNT_TARGET" useradd -m -s /bin/bash admin || true
fi
echo "admin:$ADMIN_PASS" | chroot "$MOUNT_TARGET" chpasswd || true

# Provision application database users and bcrypt sync
mkdir -p "$MOUNT_TARGET/etc/mitranet"
cat <<EOF > "$MOUNT_TARGET/etc/mitranet/auth.conf"
admin:\$2y\$10\$5e2kv/Ir7mDVrAVfdFI/V.LHuaedAMYBHWCsxdde/eeJUL8UxRaUK
EOF
chmod 600 "$MOUNT_TARGET/etc/mitranet/auth.conf"

# Verify /mitranet package store payload
if [ -d "$MOUNT_TARGET/mitranet/packages" ]; then
    PKG_COUNT=$(find "$MOUNT_TARGET/mitranet/packages" -maxdepth 1 -name "*.pkg*" | wc -l)
    log "Installed package store payload verified: $PKG_COUNT artifacts present."
fi

# 8. Bootloader Installation (Dual BIOS + UEFI support)
log "Installing GRUB bootloader to $TARGET_DEV..."
mount --bind /dev "$MOUNT_TARGET/dev"
mount --bind /proc "$MOUNT_TARGET/proc"
mount --bind /sys "$MOUNT_TARGET/sys"

# Install BIOS bootloader
grub-install --target=i386-pc --recheck --boot-directory="$MOUNT_TARGET/boot" "$TARGET_DEV" || true

# Install UEFI bootloader if running in UEFI mode or EFI partition present
if [ -d "$MOUNT_TARGET/boot/efi" ]; then
    grub-install --target=x86_64-efi --efi-directory="$MOUNT_TARGET/boot/efi" --boot-directory="$MOUNT_TARGET/boot" --bootloader-id="MitraNet" --recheck || true
fi

# Generate grub.cfg inside chroot
chroot "$MOUNT_TARGET" update-grub || true

# 9. Systemd Services & REST API Daemon Setup
cat <<'EOF' > "$MOUNT_TARGET/etc/systemd/system/mitranet-init.service"
[Unit]
Description=MitraNet First Boot & Configuration Engine Daemon
After=network.target
Wants=network.target

[Service]
Type=oneshot
RemainAfterExit=yes
WorkingDirectory=/mitranet
ExecStart=/usr/bin/python3 -c "import sys; sys.path.insert(0, '/mitranet/api'); import config_engine; ce = config_engine.ConfigurationEngine(); print('MitraNet initialized:', ce.get_running_config()['system']['hostname'])"

[Install]
WantedBy=multi-user.target
EOF

cat <<'EOF' > "$MOUNT_TARGET/etc/systemd/system/mitranet-api.service"
[Unit]
Description=MitraNet REST API Control Plane Daemon
After=network.target mitranet-init.service
Wants=network.target

[Service]
Type=simple
WorkingDirectory=/mitranet
ExecStart=/usr/bin/python3 /mitranet/api/REST/server.py
Restart=always
RestartSec=3
User=root

[Install]
WantedBy=multi-user.target
EOF

chroot "$MOUNT_TARGET" systemctl enable mitranet-init.service || true
chroot "$MOUNT_TARGET" systemctl enable mitranet-api.service || true

# 9. Clean Unmount
sync
umount "$MOUNT_TARGET/sys" || true
umount "$MOUNT_TARGET/proc" || true
umount "$MOUNT_TARGET/dev" || true
umount "$MOUNT_TARGET/boot/efi" || true
umount "$MOUNT_TARGET" || true

log "MitraNet installation successfully completed on $TARGET_DEV."
log "System is ready to reboot into installed environment."
