#!/bin/bash

################################################################################
# Net-Gauge v1.0 - Hassas Ağ Hızı Ölçüm Aracı
# Yazar:..
# Amaç: Tek bir görev, mükemmel bir şekilde: İndirme hızını doğru ve
#        anlaşılır bir şekilde ölçmek.
################################################################################

# --- Konfigürasyon Değişkenleri ---
# Bu bölümü operasyonel ihtiyaçlarına göre kolayca değiştirebilirsin.

# Güvenilir ve farklı boyutlarda dosyalar sunan bir test sunucusu.
readonly TEST_FILE_URL="http://ipv4.download.thinkbroadband.com/50MB.zip"

# Testin daha doğru bir ortalama vermesi için seçilen dosya boyutu (Megabyte).
readonly TEST_FILE_SIZE_MB=50

# --- Ana Fonksiyon: Hız Testini Gerçekleştirir ---
perform_speed_test() {
    # 1. Başlangıç mesajını göster. `echo -n` imleci aynı satırda tutar.
    echo -n "[*] Ağ hızı ölçülüyor (${TEST_FILE_SIZE_MB}MB dosya indiriliyor)..."

    # 2. `curl` komutunu çalıştır ve sadece gerekli bilgiyi al.
    #    -s: Sessiz mod (ilerleme çubuğu yok).
    #    -o /dev/null: İndirilen veriyi diske yazma, doğrudan yok et.
    #    -w '%{speed_download}': Görev bittiğinde, ortalama indirme hızını
    #                            saniyede bayt (bytes/sec) cinsinden çıktıla.
    #    Hata durumunda (URL'ye ulaşılamazsa vb.), komut sıfır döndürecektir.
    local speed_in_bytes_per_sec
    speed_in_bytes_per_sec=$(curl -s -w '%{speed_download}' -o /dev/null --url "${TEST_FILE_URL}")

    # 3. Sonucu insani birimlere (Mbps) çevir.
    #    1 Bayt = 8 bit
    #    1 Megabit = 1,000,000 bit
    #    `bc -l` ondalıklı sayı matematiği için kullanılır.
    local speed_in_mbps
    speed_in_mbps=$(echo "${speed_in_bytes_per_sec} * 8 / 1000000" | bc -l)

    # 4. Sonucu temiz ve formatlanmış bir şekilde ekrana bas.
    #    \r: İmleci satır başına alarak önceki mesajı siler.
    #    printf: Metni belirli bir formata göre düzenler. '%.2f' ondalıktan
    #            sonra 2 basamak gösterir.
    printf "\r[+] Ağ İndirme Hızı: %.2f Mbps\n" "${speed_in_mbps}"
}


# --- Script Başlangıç Noktası ---
# Gerekli araçların sistemde var olup olmadığını kontrol et.
if ! command -v curl &> /dev/null || ! command -v bc &> /dev/null; then
    echo "[!] Hata: Bu script'in çalışması için 'curl' ve 'bc' komutları gereklidir." >&2
    echo "[*] Lütfen 'sudo apt-get install -y curl bc' komutu ile kurun." >&2
    exit 1
fi

# Ana fonksiyonu çağırarak testi başlat.
perform_speed_test
