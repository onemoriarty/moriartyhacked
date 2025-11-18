import socket
import threading
from concurrent.futures import ThreadPoolExecutor, as_completed
import time
import sys
import os
import ipaddress

# --- OPERASYONEL KONFİGÜRASYON (HİPER HIZ ODAKLI) ---
SOURCE_FILE = "targets.txt"
OUTPUT_FILE = "hedefler.txt"
PORTS_TO_CHECK = [22, 23]
# ASKER SAYISI: Sistemi ve ağı son limitine kadar zorla.
MAX_WORKERS = 109 
SCAN_TIMEOUT = 0.6 # Hızlı tarama için zaman aşımını düşür.

# --- RENK KODLARI ---
GREEN, YELLOW, CYAN, RED, BOLD, ENDC = '\033[92m', '\033[93m', '\033[96m', '\033[91m', '\033[1m', '\033[0m'

# --- PAYLAŞILAN NESNELER ---
file_lock = threading.Lock()
# Sayaçlar, anlık hız ve ETA hesaplaması için
processed_tasks = 0
found_targets_count = 0
total_tasks = 0
scan_start_time = 0

def resolve_target_threaded(domain_queue, resolved_ips_set, lock):
    """Kuyruktan domain alıp çözen thread işçisi."""
    while not domain_queue.empty():
        try:
            domain = domain_queue.get_nowait()
            _, _, ip_list = socket.gethostbyname_ex(domain)
            with lock:
                resolved_ips_set.update(ip_list)
            domain_queue.task_done()
        except (socket.gaierror, Exception):
            domain_queue.task_done()
            continue

def phase1_fast_resolver(targets):
    """FAZ 1: Yüksek paralellikte DNS çözümlemesi yapar."""
    print("\n{}[*] FAZ 1: DNS Çözümlemesi başlatılıyor... ({} hedef){}".format(CYAN, len(targets), ENDC))
    
    initial_ips = set()
    domains_to_resolve = set()
    for target in targets:
        try:
            if ipaddress.ip_address(target).is_global:
                initial_ips.add(target)
        except ValueError:
            domains_to_resolve.add(target)
            
    if domains_to_resolve:
        from queue import Queue
        q = Queue()
        for domain in domains_to_resolve: q.put(domain)
        
        threads = []
        for _ in range(min(500, len(domains_to_resolve))): # DNS için 500 thread'i geçme
            t = threading.Thread(target=resolve_target_threaded, args=(q, initial_ips, threading.Lock()))
            t.start()
            threads.append(t)
        
        q.join() # Tüm domainlerin işlenmesini bekle

    print("{}[✓] FAZ 1 Tamamlandı. {} benzersiz IP adresi bulundu.{}".format(GREEN, len(initial_ips), ENDC))
    return initial_ips

def check_single_target(ip, port):
    """
    En atomik görev: Tek bir IP ve tek bir portu kontrol eder.
    """
    global processed_tasks, found_targets_count
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        s.settimeout(SCAN_TIMEOUT)
        if s.connect_ex((ip, port)) == 0:
            result = "{}:{}".format(ip, port)
            with file_lock:
                found_targets_count += 1
                sys.stdout.write("\r\033[K{}[+] VEKTÖR TESPİT EDİLDİ -> {}{}\n".format(GREEN, result, ENDC))
                with open(OUTPUT_FILE, "a") as f:
                    f.write(result + "\n")
    except socket.error:
        pass
    finally:
        if 's' in locals() and s:
            s.close()
        with file_lock:
            processed_tasks += 1

def format_eta(seconds):
    if seconds is None or seconds < 0: return "Hesaplanıyor..."
    hours, remainder = divmod(seconds, 3600)
    minutes, seconds = divmod(remainder, 60)
    return "{:02d}s {:02d}d {:02d}sn".format(int(hours), int(minutes), int(seconds))

def status_reporter():
    """Anlık hız ve ETA'yı raporlayan thread."""
    global processed_tasks, total_tasks, scan_start_time
    while processed_tasks < total_tasks:
        elapsed_time = time.time() - scan_start_time
        rate = processed_tasks / elapsed_time if elapsed_time > 0 else 0
        remaining_tasks = total_tasks - processed_tasks
        eta_seconds = remaining_tasks / rate if rate > 0 else None
        
        percent = (processed_tasks / total_tasks) * 100
        
        eta_formatted = format_eta(eta_seconds)
        status_line = "\r[*] Portlar taranıyor: {}/{} ({:.1f}%) | Hız: {:,.0f} port/s | Bulunan: {} | ETA: {}".format(
            processed_tasks, total_tasks, percent, rate, found_targets_count, eta_formatted)

        sys.stdout.write(status_line)
        sys.stdout.flush()
        time.sleep(1)

def main():
    print("{}{}[*] KRYPTON HİPER HIZ TARAYICI (Hyperdrive Edition){}{}".format(BOLD, CYAN, ENDC, ENDC))
    global total_tasks, scan_start_time
    
    start_time = time.time()
    
    if os.path.exists(OUTPUT_FILE): os.remove(OUTPUT_FILE)

    if not os.path.exists(SOURCE_FILE):
        print("{}[!] Hata: Kaynak dosya '{}' bulunamadı.{}".format(RED, SOURCE_FILE, ENDC))
        return

    with open(SOURCE_FILE, 'r', encoding='utf-8') as f:
        targets = list(set(line.strip() for line in f if line.strip()))

    if not targets:
        print("{}[!] Kaynak dosya '{}' boş.{}".format(YELLOW, SOURCE_FILE, ENDC))
        return

    seed_ips = phase1_fast_resolver(targets)
    if not seed_ips: return

    print("\n{}[*] FAZ 2: Topyekûn Taarruz (Port Tarama) başlıyor...{}".format(CYAN, ENDC))
    subnets = {ipaddress.ip_network("{}/24".format(ip), strict=False) for ip in seed_ips}
    
    # --- YENİ MİMARİ: ATOMİK GÖREV OLUŞTURMA ---
    all_scan_tasks = []
    print("[*] Tüm potansiyel hedefler (IP:PORT) hesaplanıyor...")
    for net in subnets:
        for host in net.hosts():
            for port in PORTS_TO_CHECK:
                all_scan_tasks.append((str(host), port))
    
    total_tasks = len(all_scan_tasks)
    print("[*] Toplam {} adet port taranacak. {} asker (thread) görevde.".format(total_tasks, MAX_WORKERS))

    scan_start_time = time.time()
    
    # Durum raporlama thread'ini başlat
    status_thread = threading.Thread(target=status_reporter, daemon=True)
    status_thread.start()

    with ThreadPoolExecutor(max_workers=MAX_WORKERS) as executor:
        # Tüm atomik görevleri tek seferde havuza gönder.
        # `*task` ifadesi, (ip, port) ikilisini `check_single_target` fonksiyonuna
        # iki ayrı argüman olarak açar.
        executor.map(lambda task: check_single_target(*task), all_scan_tasks)

    # Durum thread'inin son çıktıyı yazmasını bekle.
    time.sleep(1.1)
    
    print("\n{}[✓] FAZ 2 Tamamlandı. Tarama bitti.{}".format(GREEN, ENDC))
    
    # ... (Final rapor kısmı aynı) ...
    elapsed_total = time.time() - start_time
    print("\n-----------------------------------------------------")
    print("{}{}[✓] OPERASYON BAŞARIYLA TAMAMLANDI{}".format(GREEN, BOLD, ENDC))
    print("⏱️  Toplam Süre: {}".format(format_eta(elapsed_total)))
    print("📁 Eyleme Geçirilebilir İstihbarat: .\\{}".format(OUTPUT_FILE))

if __name__ == "__main__":
    main()
