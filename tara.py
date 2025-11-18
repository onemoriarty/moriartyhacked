import socket
import threading
from concurrent.futures import ThreadPoolExecutor, as_completed
import time
import sys
import os
import ipaddress
from queue import Queue

# --- OPERASYONEL KONFİGÜRASYON ---
SOURCE_FILE = "targets.txt"
OUTPUT_FILE = "hedefler.txt"
PORTS_TO_CHECK = [22, 23]
# TEK BİR MERKEZİ ORDU BÜYÜKLÜĞÜ. SİSTEMİN KALDIRABİLECEĞİ MAKSİMUM.
MAX_WORKERS = 1000 
SCAN_TIMEOUT = 0.7

# --- RENK KODLARI ---
GREEN, YELLOW, CYAN, RED, BOLD, ENDC = '\033[92m', '\033[93m', '\033[96m', '\033[91m', '\033[1m', '\033[0m'

# --- PAYLAŞILAN NESNELER ---
file_lock = threading.Lock()
# Sadece istatistik için
processed_count = 0
found_count = 0
total_tasks = 0

def resolve_and_process(target, executor):
    """
    Tek bir hedefi (domain/IP) alır. Eğer domain ise çözer,
    sonra bulunan TÜM IP'lerin /24 alt ağları için tarama görevleri oluşturur.
    """
    initial_ips = set()
    try:
        if ipaddress.ip_address(target).is_global:
            initial_ips.add(target)
    except ValueError:
        try:
            _, _, ip_list = socket.gethostbyname_ex(target)
            initial_ips.update(ip_list)
        except socket.gaierror:
            pass # Domain çözülemezse atla

    # Her bir çözülmüş IP için alt ağ tarama görevlerini oluştur ve havuza gönder.
    subnets_to_scan = {ipaddress.ip_network("{}/24".format(ip), strict=False) for ip in initial_ips}
    for net in subnets_to_scan:
        for host in net.hosts():
            for port in PORTS_TO_CHECK:
                executor.submit(scan_port, str(host), port)

def scan_port(ip, port):
    """En atomik görev: Tek bir IP:PORT hedefini tarar."""
    global processed_count, found_count
    s = None
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        s.settimeout(SCAN_TIMEOUT)
        if s.connect_ex((ip, port)) == 0:
            result = "{}:{}".format(ip, port)
            with file_lock:
                found_count += 1
                with open(OUTPUT_FILE, "a") as f:
                    f.write(result + "\n")
    except socket.error:
        pass
    finally:
        if s: s.close()
        with file_lock:
            processed_count += 1 # Her denemeden sonra sayacı artır

def main():
    print("{}{}[*] KRYPTON DAYANIKLI TARAYICI (Resilient Edition){}{}".format(BOLD, CYAN, ENDC, ENDC))
    global total_tasks
    start_time = time.time()
    
    if os.path.exists(OUTPUT_FILE): os.remove(OUTPUT_FILE)

    if not os.path.exists(SOURCE_FILE):
        print("{}[!] Hata: Kaynak dosya '{}' bulunamadı.{}".format(RED, SOURCE_FILE, ENDC))
        return

    # --- TEK MERKEZİ ORDUYU (THREAD POOL) OLUŞTUR ---
    print(f"[*] {MAX_WORKERS} askerden oluşan elit bir birlik (ThreadPool) kuruluyor...")
    
    with ThreadPoolExecutor(max_workers=MAX_WORKERS) as executor:
        print("[*] Operasyon başlıyor. Kaynak dosya okunuyor ve görevler anında dağıtılıyor...")

        # Bellek hatasını önlemek için dosyayı satır satır oku.
        with open(SOURCE_FILE, 'r', encoding='utf-8') as f:
            for line in f:
                target = line.strip()
                if not target: continue
                # Her bir hedef için BİRİNCİL görevi (çözümleme ve ikincil görev oluşturma) havuza gönder.
                executor.submit(resolve_and_process, target, executor)

        print("[*] Kaynak dosyanın tamamı okundu ve ilk görevler dağıtıldı.")
        print("[*] Tüm ikincil tarama görevlerinin tamamlanması bekleniyor... (CTRL+C ile durdurabilirsin)")
        
        # Executor'ın tüm görevleri (hem birincil hem de ikincil) bitirmesini bekle.
        # Bu, `shutdown()` çağrısı `with` bloğunun sonunda otomatik olarak yapılır.
        
    print("\n{}[✓] Tüm görevler tamamlandı.{}".format(GREEN, ENDC))
    
    # --- NİHAİ RAPOR ---
    print("\n{}[*] Sonuçlar tekilleştiriliyor...{}".format(CYAN, ENDC))
    unique_count = 0
    if os.path.exists(OUTPUT_FILE):
        with open(OUTPUT_FILE, 'r') as f:
            lines = set(f.read().splitlines())
        unique_count = len(lines)
        with open(OUTPUT_FILE, 'w') as f:
            f.write('\n'.join(sorted(list(lines))))
            
    elapsed_total = time.time() - start_time
    print("\n-----------------------------------------------------")
    print("{}{}[✓] OPERASYON BAŞARIYLA TAMAMLANDI{}".format(GREEN, BOLD, ENDC, ENDC))
    # format_eta fonksiyonu
    hours, rem = divmod(elapsed_total, 3600)
    minutes, seconds = divmod(rem, 60)
    print("⏱️  Toplam Süre: {:02d}s {:02d}d {:02d}sn".format(int(hours), int(minutes), int(seconds)))
    print(f"📁 {unique_count:,} benzersiz hedef '{OUTPUT_FILE}' dosyasına kaydedildi.")

if __name__ == "__main__":
    main()
