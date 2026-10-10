# Panduan Konfigurasi: 08 - Advanced System Tunables & TCP BBR

Modul ini menjelaskan cara memaksimalkan throughput jaringan, meminimalkan bufferbloat, dan mengatur parameter performa kernel Linux pada MitraNet Rinjani 1.0.2.

---

## 1. Metode A: Melalui WebUI (Browser)

1. Buka menu **System** ➔ **Advanced** ➔ Tab **System Tunables & BBR** (`/system/advanced_sysctl.php`).
2. Pada form pengaturan:
   - **TCP Congestion Control**: Pilih `BBR (Bottleneck Bandwidth and RTT)`.
   - **Default Queuing Discipline (qdisc)**: Pilih `fq` (Fair Queuing) atau `fq_codel`.
   - **IP Forwarding**: Pastikan berstatus `Enabled (1)`.
   - **Max Socket Buffer**: Pilih preset `High Throughput (16MB Window)`.
3. Klik tombol **Simpan & Terapkan Tuning**.
4. Sistem akan menerapkan nilai langsung ke kernel runtime dan menyimpannya secara persisten di `/etc/sysctl.d/99-mitranet-tuning.conf`.

---

## 2. Metode B: Melalui Terminal / CLI (Linux Shell)

1. **Memeriksa Status Kernel Saat Ini**:
   ```bash
   sysctl net.ipv4.tcp_congestion_control
   sysctl net.core.default_qdisc
   sysctl net.ipv4.ip_forward
   ```

2. **Menerapkan Tuning Langsung (Runtime)**:
   ```bash
   # Aktifkan BBR dan FQ qdisc
   sysctl -w net.core.default_qdisc=fq
   sysctl -w net.ipv4.tcp_congestion_control=bbr

   # Aktifkan IP Forwarding
   sysctl -w net.ipv4.ip_forward=1

   # Optimasi Buffer TCP Socket
   sysctl -w net.core.rmem_max=16777216
   sysctl -w net.core.wmem_max=16777216
   sysctl -w net.ipv4.tcp_rmem="4096 87380 16777216"
   sysctl -w net.ipv4.tcp_wmem="4096 65536 16777216"
   ```

3. **Menyimpan Secara Permanen (Persisten Setelah Reboot)**:
   ```bash
   cat << 'EOF' > /etc/sysctl.d/99-mitranet-tuning.conf
   net.ipv4.ip_forward = 1
   net.core.default_qdisc = fq
   net.ipv4.tcp_congestion_control = bbr
   net.ipv4.fib_multipath_hash_policy = 1
   net.core.rmem_max = 16777216
   net.core.wmem_max = 16777216
   net.ipv4.tcp_rmem = 4096 87380 16777216
   net.ipv4.tcp_wmem = 4096 65536 16777216
   EOF

   sysctl --system
   ```

4. **Verifikasi Algoritma Aktif**:
   ```bash
   lsmod | grep bbr
   sysctl net.ipv4.tcp_congestion_control
   ```
