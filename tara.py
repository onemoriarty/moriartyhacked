import socket
import threading
from concurrent.futures import ThreadPoolExecutor
import time
import sys
import os
import ipaddress
from queue import Queue

# --- OPERASYONEL KONFİGÜRASYON ---
SOURCE_FILE = "targets.txt"
OUTPUT_FILE = "hedefler.txt"
PORTS_TO_CHECK = [22, 23]
DNS_WORKERS = 70
SCAN_WORKERS = 2000 
SCAN_TIMEOUT = 0.6

# --- RENK KODLARI ---
GREEN, YELLOW, CYAN, RED, BOLD, ENDC = '\033[92m', '\033[93m', '\033[96m', '\033[91m', '\033[1m', '\033[0m'

# --- PAYLAŞILAN NESNELER ---
file_lock = threading.Lock()
# İki ana faz arasındaki veri akışı için kuyruklar
ip_queue = Queue(maxsize=SCAN_WORKERS * 10) # Taranacak IP'ler için
task_queue = Queue(maxsize=SCAN_WORKERS * 100) # Atomik (IP:PORT) görevleri için

def resolve_target_worker():
    """FAZ 1: Domainleri IP'ye çevirip IP kuyruğuna atar."""
    # Bu işçi, ana program sonlandığında duracak.
    while True:
        domain = domain_queue.get()
        try:
            _, _, ip_list = socket.gethostbyname_ex(domain)
            for ip in ip_list:
                # Bloklamadan kuyruğa koy, eğer kuyruk doluysa üretimi yavaşlat.
                ip_queue.put(ip)
        except socket.gaierror:
            pass # Domain çözülemezse sessizce devam et.
        finally:
            domain_queue.task_done()

def ip_processor_worker():
    """FAZ 2: IP kuyruğundan IP alıp, tarama görevleri oluşturur."""
    processed_subnets = set()
    while True:
        ip = ip_queue.get()
        try:
            # IP'nin /24 subnet'ini hesapla.
            subnet = ipaddress.ip_network("{}/24".format(ip), strict=False)
            # Eğer bu subnet daha önce işlenmediyse
            if subnet not in processed_subnets:
                processed_subnets.add(subnet)
                for host in subnet.hosts():
                    for port in PORTS_TO_CHECK:
                        task_queue.put((str(host), port))
        except Exception:
            pass
        finally:
            ip_queue.task_done()

def scan_worker():
    """FAZ 3: Görev kuyruğundan (IP:PORT) alıp tarama yapar."""
    while True:
        ip, port = task_queue.get()
        s = None
        try:
            s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            s.settimeout(SCAN_TIMEOUT)
            if s.connect_ex((ip, port)) == 0:
                result = "{}:{}".format(ip, port)
                with file_lock:
                    # Anlık olarak dosyaya yaz, ekrana değil. Ekrana sadece ilerleme basılır.
                    with open(OUTPUT_FILE, "a") as f:
                        f.write(result + "\n")
        except socket.error:
            pass
        finally:
            if s: s.close()
            task_queue.task_done()

def main():
    print("{}{}[*] KRYPTON VERİ AKIŞ TARAYICI (Streamflow Edition){}{}".format(BOLD, CYAN, ENDC, ENDC))
    start_time = time.time()
    
    if os.path.exists(OUTPUT_FILE): os.remove(OUTPUT_FILE)

    if not os.path.exists(SOURCE_FILE):
        print("{}[!] Hata: Kaynak dosya '{}' bulunamadı.{}".format(RED, SOURCE_FILE, ENDC))
        return

    # --- ÜRETİM HATTINI (PIPELINE) KUR ---
    print("[*] Üretim hattı kuruluyor...")
    global domain_queue
    domain_queue = Queue()

    # Faz 1 işçilerini başlat (Domain -> IP)
    for _ in range(DNS_WORKERS):
        threading.Thread(target=resolve_target_worker, daemon=True).start()

    # Faz 2 işçilerini başlat (IP -> IP:PORT Görevleri)
    for _ in range(os.cpu_count() or 4): # Bu CPU-yoğun bir iş olabilir, o yüzden az sayıda.
        threading.Thread(target=ip_processor_worker, daemon=True).start()

    # Faz 3 işçilerini başlat (Port Tarama)
    for _ in range(SCAN_WORKERS):
        threading.Thread(target=scan_worker, daemon=True).start()
    
    print(f"[*] {DNS_WORKERS} DNS, {os.cpu_count() or 4} İşlemci, {SCAN_WORKERS} Tarama askeri görevde.")

    # --- ÜRETİMİ BAŞLAT ---
    print("[*] Operasyon başlıyor. Kaynak dosya okunuyor ve işleniyor...")
    
    line_count = 0
    with open(SOURCE_FILE, 'r', encoding='utf-8') as f:
        for line in f:
            line_count += 1
            target = line.strip()
            if not target: continue
            
            # Anında işle, bellekte tutma.
            try:
                if ipaddress.ip_address(target).is_global:
                    ip_queue.put(target)
                else: # Özel IP ise atla
                    pass
            except ValueError:
                domain_queue.put(target)

            # Arayüzü çok sık boğmamak için
            if line_count % 10000 == 0:
                sys.stdout.write(f"\r[*] Kaynak okundu: {line_count:,} | "
                                 f"DNS Kuyruğu: {domain_queue.qsize():,} | "
                                 f"IP Kuyruğu: {ip_queue.qsize():,} | "
                                 f"Tarama Kuyruğu: {task_queue.qsize():,}")
                sys.stdout.flush()

    print(f"\n[*] Kaynak dosyanın tamamı ({line_count:,} satır) üretim hattına beslendi.")
    print("[*] Tüm görevlerin tamamlanması bekleniyor...")

    domain_queue.join()
    ip_queue.join()
    task_queue.join()
    
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
