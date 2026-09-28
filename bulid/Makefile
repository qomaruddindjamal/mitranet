# MitraNet OS Makefile
# Build automation for unpacking, injecting, and building customized ISO

SHELL := /bin/bash
SOURCE_ISO ?= sources/netgate-installer-amd64.iso
ISO_ROOT ?= bulid/iso_root
OUTPUT_ISO ?= ISO/MitraNet-OS-amd64.iso

.PHONY: all setup unpack inject build test clean help

all: setup unpack inject build test
	@echo "=== [MitraNet] Complete Pipeline Executed Successfully! ==="

help:
	@echo "MitraNet OS Build System"
	@echo "Available targets:"
	@echo "  make setup   - Install build tools and environment dependencies"
	@echo "  make unpack  - Extract base OSNetwork ISO into bulid/iso_root"
	@echo "  make inject  - Inject Xray/V2Ray, configs, CLI, and Web API"
	@echo "  make build   - Repackage modified files into ISO/MitraNet-OS-amd64.iso"
	@echo "  make test    - Test Xray configs and run VM emulation tests"
	@echo "  make clean   - Remove extracted files and intermediate build artifacts"

setup:
	@bash bulid/setup_dev_env.sh

unpack:
	@bash bulid/unpack_iso.sh $(SOURCE_ISO) $(ISO_ROOT)

inject:
	@bash bulid/inject_features.sh $(ISO_ROOT)

build:
	@bash bulid/build_iso.sh $(ISO_ROOT) $(OUTPUT_ISO)

test:
	@bash test/test_v2ray.sh

clean:
	@echo "[*] Cleaning build artifacts..."
	@rm -rf $(ISO_ROOT) bulid/boot ISO/*.sha256
	@echo "[+] Clean completed."
