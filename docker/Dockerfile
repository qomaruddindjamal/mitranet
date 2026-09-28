# Multi-purpose Dockerfile for MitraNet OS Development, Reverse Engineering & ISO Building
FROM ubuntu:24.04

LABEL maintainer="Qomaruddin Djamal <jamalpancur@gmail.com>"
LABEL description="MitraNet OS Build & Codespace Development Environment"

ENV DEBIAN_FRONTEND=noninteractive
ENV LANG=C.UTF-8

# Install foundational build tools, ISO manipulation, QEMU, and networking tools
RUN apt-get update && apt-get install -y --no-install-recommends \
    bash \
    ca-certificates \
    curl \
    wget \
    git \
    make \
    jq \
    python3 \
    python3-pip \
    python3-venv \
    golang-go \
    p7zip-full \
    xorriso \
    isolinux \
    syslinux-utils \
    mtools \
    dosfstools \
    fdisk \
    qemu-system-x86 \
    qemu-system-arm \
    qemu-system-mips \
    qemu-user-static \
    binfmt-support \
    qemu-utils \
    ovmf \
    net-tools \
    iptables \
    iproute2 \
    socat \
    vim \
    nano \
    sudo \
    && rm -rf /var/lib/apt/lists/*

# Install Python requirements for API & CLI testing
RUN python3 -m pip install --no-cache-dir --break-system-packages \
    requests \
    fastapi \
    uvicorn \
    pydantic

# Create workspace directory
WORKDIR /workspace

# Install Xray-core for Linux (for local dev/testing) and prepare FreeBSD fetching tools
ENV XRAY_VERSION=v24.9.30
RUN mkdir -p /opt/xray-linux /opt/xray-freebsd && \
    curl -sSL "https://github.com/XTLS/Xray-core/releases/download/${XRAY_VERSION}/Xray-linux-64.zip" -o /tmp/xray-linux.zip && \
    7z x /tmp/xray-linux.zip -o/opt/xray-linux -y && \
    chmod +x /opt/xray-linux/xray && \
    ln -sf /opt/xray-linux/xray /usr/local/bin/xray && \
    rm -f /tmp/xray-linux.zip && \
    curl -sSL "https://github.com/XTLS/Xray-core/releases/download/${XRAY_VERSION}/Xray-freebsd-64.zip" -o /tmp/xray-freebsd.zip && \
    7z x /tmp/xray-freebsd.zip -o/opt/xray-freebsd -y && \
    chmod +x /opt/xray-freebsd/xray && \
    rm -f /tmp/xray-freebsd.zip

EXPOSE 80 443 8080 9000 10808 10809

CMD ["/bin/bash"]
