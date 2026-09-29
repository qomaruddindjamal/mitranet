# MitraNet OS - Multi-Architecture Makefile
# Supports: arm64, arm, mipsbe, mmips, smips, mipsle, x86_64Bit, ppc, sbc, silicon, vm

SHELL := /bin/bash
SOURCE_ISO ?= sources/netgate-installer-amd64.iso
ISO_ROOT ?= bulid/iso_root
OUTPUT_ISO ?= images/MitraNet-OS-amd64.iso

.PHONY: all setup unpack inject build test clean help \
        arch-x86_64 arch-arm64 arch-arm arch-mipsbe arch-mmips \
        arch-smips arch-mipsle arch-ppc arch-sbc arch-silicon arch-vm all-arches

all: setup unpack inject build test
	@echo "=== [MitraNet] Complete Pipeline Executed Successfully! ==="

help:
	@echo "MitraNet OS Multi-Architecture Build System"
	@echo "Basic targets:"
	@echo "  make setup        - Install build tools and environment dependencies"
	@echo "  make unpack       - Extract base OSNetwork ISO into bulid/iso_root"
	@echo "  make inject       - Inject Xray/V2Ray, configs, CLI, and Web API"
	@echo "  make bundle-pkgs  - Bundle offline .pkg repository into installer image"
	@echo "  make build        - Repackage modified files into images/MitraNet-OS-amd64.iso"
	@echo "  make build-raw    - Generate raw disk image for 1-line VPS auto-installer"
	@echo "  make test         - Run automated unit test suite"
	@echo "  make clean        - Remove extracted files and intermediate build artifacts"
	@echo ""
	@echo "Architecture Targets:"
	@echo "  make arch-x86_64  - Build for standard 64-bit PC / VM / Server"
	@echo "  make arch-arm64   - Build for 64-bit ARM (Raspberry Pi 3/4/5, RK3588, Orange Pi)"
	@echo "  make arch-arm     - Build for 32-bit ARM (Raspberry Pi 2, Allwinner, ARM Routers)"
	@echo "  make arch-mipsbe  - Build for MIPS Big Endian (Atheros AR9344, MikroTik RB series)"
	@echo "  make arch-mmips   - Build for MediaTek MIPS (MT7621A, MikroTik hEX)"
	@echo "  make arch-smips   - Build for Small MIPS (MikroTik hAP lite, 16MB flash)"
	@echo "  make arch-mipsle  - Build for MIPS Little Endian (MT7628, MT7620)"
	@echo "  make arch-ppc     - Build for PowerPC (Freescale, MikroTik RB1000/RB1100)"
	@echo "  make arch-sbc     - Build for All Single Board Computers (SD card image)"
	@echo "  make arch-silicon - Build for Apple Silicon (M1/M2/M3/M4 UTM/Parallels VM)"
	@echo "  make arch-vm      - Build for Multi-Hypervisor VM (Proxmox, ESXi, KVM)"
	@echo "  make all-arches   - Build packages for all architectures"

setup:
	@bash bulid/setup_dev_env.sh

unpack:
	@bash bulid/unpack_iso.sh $(SOURCE_ISO) $(ISO_ROOT)

inject:
	@bash bulid/inject_features.sh $(ISO_ROOT)

bundle-pkgs:
	@bash bulid/bundle_offline_pkgs.sh $(ISO_ROOT) bulid/packages_cache

build:
	@bash bulid/build_iso.sh $(ISO_ROOT) $(OUTPUT_ISO)

build-raw:
	@bash bulid/build_raw_image.sh $(OUTPUT_ISO)

test:
	@bash test/test_v2ray.sh

# Architecture-specific targets
arch-x86_64:
	@bash bulid/build_multiarch.sh x86_64

arch-arm64:
	@bash bulid/build_multiarch.sh arm64

arch-arm:
	@bash bulid/build_multiarch.sh arm

arch-mipsbe:
	@bash bulid/build_multiarch.sh mipsbe

arch-mmips:
	@bash bulid/build_multiarch.sh mmips

arch-smips:
	@bash bulid/build_multiarch.sh smips

arch-mipsle:
	@bash bulid/build_multiarch.sh mipsle

arch-ppc:
	@bash bulid/build_multiarch.sh ppc

arch-sbc:
	@bash bulid/build_multiarch.sh sbc_all

arch-silicon:
	@bash bulid/build_multiarch.sh silicon

arch-vm:
	@bash bulid/build_multiarch.sh vm_all

all-arches:
	@bash bulid/build_multiarch.sh all

clean:
	@echo "[*] Cleaning build artifacts..."
	@rm -rf $(ISO_ROOT) bulid/boot images/*.sha256 images/releases
	@echo "[+] Clean completed."
