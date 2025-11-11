#!/bin/bash
################################################################################
# Zırhlı Teşhis Çekirdeği v2.0
# Görev: Kritik sistem verilerini, hatalara karşı dirençli bir şekilde,
#        temiz "Anahtar:Değer" formatında sunmak.
################################################################################

# --- FONKSİYON: Dirençli Hız Testi ---
# Birden fazla sunucuyu dener ve ilk başarılı sonucu döndürür.
get_resilient_speed() {
    local speed_mbps="N/A"
    
    # Güvenilir test sunucuları listesi (yedekli)
    local test_urls=(
        "http://ipv4.download.thinkbroadband.com/20MB.zip"
        "http://speedtest.tele2.net/20MB.zip"
        "https://ash-us-ping.vultr.com/vultr.com.100MB.bin"
    )

    for url in "${test_urls[@]}"; do
        # `curl` çıktısını yakala, timeout (15sn) ayarla.
        local speed_bps
        speed_bps=$(curl --connect-timeout 5 -s -w '%{speed_download}' -o /dev/null "$url")
        
        # Sonucun geçerli bir sayı olup olmadığını kontrol et (0'dan büyük).
        if [[ "$speed_bps" =~ ^[1-9][0-9]*\.?[0-9]*$ ]]; then
            speed_mbps=$(echo "scale=2; ${speed_bps} * 8 / 1000000" | bc)
            # Başarılı sonuç bulununca döngüyü kır.
            break
        fi
        # Başarısız olursa, bir sonrakini denemeden önce kısa bir süre bekle.
        sleep 0.5
    done
    
    echo "$speed_mbps"
}

# --- FONKSİYON: Güvenilir Frekans Ölçümü ---
# Birden fazla yöntemle CPU frekansını bulmaya çalışır.
get_cpu_ghz() {
    local freq_ghz="N/A"
    
    # Yöntem 1: En güvenilir anlık frekans (modern çekirdekler)
    if [ -f "/sys/devices/system/cpu/cpu0/cpufreq/scaling_cur_freq" ]; then
        local freq_khz
        freq_khz=$(cat /sys/devices/system/cpu/cpu0/cpufreq/scaling_cur_freq)
        freq_ghz=$(echo "scale=2; $freq_khz / 1000000" | bc)
    
    # Yöntem 2: lscpu (birçok sistemde çalışır)
    elif command -v lscpu &> /dev/null; then
        local freq_mhz
        freq_mhz=$(lscpu | grep "CPU MHz" | awk '{print $3}' | cut -d. -f1)
        if [[ -n "$freq_mhz" ]]; then
            freq_ghz=$(echo "scale=2; $freq_mhz / 1000" | bc)
        fi

    # Yöntem 3: /proc/cpuinfo (en eski yöntem)
    elif grep "cpu MHz" /proc/cpuinfo &> /dev/null; then
        local freq_mhz
        freq_mhz=$(grep "cpu MHz" /proc/cpuinfo | uniq | awk '{print $4}' | cut -d. -f1)
        freq_ghz=$(echo "scale=2; $freq_mhz / 1000" | bc)
    fi

    echo "$freq_ghz"
}


# --- Ana Raporlama Bölümü ---

# Sabit Değerler (Hızlıca alınabilenler)
DISTRO=$(cat /etc/os-release | grep PRETTY_NAME | cut -d'=' -f2 | tr -d '"')
KERNEL=$(uname -r)
MIMARI=$(lscpu | grep Architecture | awk '{print $2}')
ISLEMCI_MODELI=$(grep "model name" /proc/cpuinfo | uniq | cut -d':' -f2 | sed 's/^ *//')
CEKIRDEK_SAYISI=$(nproc)
RAM_KULLANILAN_MB=$(free -m | awk '/^Mem:/ {print $3}')
RAM_TOPLAM_MB=$(free -m | awk '/^Mem:/ {print $2}')
DISK_KOK_KULLANIM=$(df -h / | awk 'END{print $5 " (" $3 "/" $2 ")"}')
CALISMA_SURESI=$(uptime -p | cut -d' ' -f2-)
IP_ADRESI=$(hostname -I | cut -d' ' -f1)

# Dinamik ve Hesaplama Gerektiren Değerler
ANLIK_FREKANS_GHZ=$(get_cpu_ghz)
INDIRME_HIZI_MBPS=$(get_resilient_speed)


# Çıktıyı "Anahtar:Değer" formatında, temiz bir şekilde bas
echo "DISTRO:$DISTRO"
echo "KERNEL:$KERNEL"
echo "MIMARI:$MIMARI"
echo "ISLEMCI_MODELI:$ISLEMCI_MODELI"
echo "CEKIRDEK_SAYISI:$CEKIRDEK_SAYISI"
echo "ANLIK_FREKANS_GHZ:$ANLIK_FREKANS_GHZ"
echo "RAM_KULLANILAN_MB:$RAM_KULLANILAN_MB"
echo "RAM_TOPLAM_MB:$RAM_TOPLAM_MB"
echo "DISK_KOK_KULLANIM:$DISK_KOK_KULLANIM"
echo "CALISMA_SURESI:$CALISMA_SURESI"
echo "IP_ADRESI:$IP_ADRESI"
echo "INDIRME_HIZI_MBPS:$INDIRME_HIZI_MBPS"
