import requests
import re
import os
import gzip
import threading
from bs4 import BeautifulSoup
from urllib.parse import urljoin
from concurrent.futures import ThreadPoolExecutor, as_completed
import time
from requests.adapters import HTTPAdapter
from urllib3.util.retry import Retry
import warnings
import sys

warnings.filterwarnings('ignore', message='Unverified HTTPS request')

# ═══════════════════════════════════════════════════════════════════════════
#  🎯 ULTRA HIGH-PERFORMANCE JUGGERNAUT CONFIGURATION
# ═══════════════════════════════════════════════════════════════════════════

BASE_URL = "https://commoncrawl.org/get-started"
OUTPUT_DIR = "gzfiles"

# 🔥 PERFORMANCE TUNING
MAX_WORKERS = 100
PARALLEL_ARCHIVE_SCAN = 50
CHUNK_SIZE = 1024 * 1024 # 1 MB
CONNECT_TIMEOUT = 15
READ_TIMEOUT = 300
MAX_RETRIES = 5
BACKOFF_FACTOR = 0.5
BATCH_SIZE = 1000

# 🎨 RENK KODLARI
GREEN = '\033[92m'
YELLOW = '\033[93m'
CYAN = '\033[96m'
RED = '\033[91m'
MAGENTA = '\033[95m'
BOLD = '\033[1m'
ENDC = '\033[0m'

thread_local = threading.local()

def create_optimized_session():
    """Her thread için özel, optimize edilmiş ve retry mekanizmalı session oluşturur."""
    if not hasattr(thread_local, "session"):
        session = requests.Session()
        retry_strategy = Retry(
            total=MAX_RETRIES,
            backoff_factor=BACKOFF_FACTOR,
            status_forcelist=[429, 500, 502, 503, 504],
            allowed_methods=["HEAD", "GET"]
        )
        adapter = HTTPAdapter(
            pool_connections=MAX_WORKERS,
            pool_maxsize=MAX_WORKERS * 2,
            max_retries=retry_strategy
        )
        session.mount("http://", adapter)
        session.mount("https://", adapter)
        session.headers.update({
            'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
            'Accept-Encoding': 'gzip, deflate',
            'Connection': 'keep-alive'
        })
        thread_local.session = session
    return thread_local.session

class ThreadSafeCounter:
    def __init__(self):
        self.lock = threading.Lock()
        self.stats = {'success': 0, 'failed': 0, 'skipped': 0}
    def increment(self, key, value=1):
        with self.lock:
            self.stats[key] += value
    def get_stats(self):
        with self.lock:
            return self.stats.copy()

def find_crawl_archives(start_url):
    print(f"{CYAN}{BOLD}[*] FAZ 1: Ana arşiv listesi taranıyor...{ENDC}")
    session = create_optimized_session()
    try:
        response = session.get(start_url, timeout=CONNECT_TIMEOUT)
        response.raise_for_status()
        soup = BeautifulSoup(response.text, 'html.parser')
        crawl_links = soup.find_all('a', href=re.compile(r'CC-MAIN-'))
        urls = sorted(list(set([link['href'] for link in crawl_links])), reverse=True)
        print(f"{GREEN}[✓] FAZ 1 Tamamlandı: {len(urls)} arşiv bulundu{ENDC}")
        return urls
    except Exception as e:
        print(f"{RED}[!] FAZ 1 Hatası: {e}{ENDC}")
        return []

def process_single_archive(archive_url):
    session = create_optimized_session()
    archive_name = archive_url.split('/')[-2]
    try:
        response = session.get(archive_url, timeout=CONNECT_TIMEOUT)
        response.raise_for_status()
        soup = BeautifulSoup(response.text, 'html.parser')
        link = soup.find('a', href=re.compile(r'cc-index\.paths\.gz'))
        if not link: return []
        index_paths_url = urljoin(archive_url, link['href'])
        response = session.get(index_paths_url, stream=True, timeout=READ_TIMEOUT)
        response.raise_for_status()
        decompressed = gzip.decompress(response.content)
        paths = decompressed.decode('utf-8').strip().split('\n')
        tasks = []
        for path in paths:
            if not path: continue
            final_url = urljoin("https://data.commoncrawl.org/", path)
            save_path = os.path.join(OUTPUT_DIR, f"{archive_name}_{os.path.basename(path)}")
            tasks.append((final_url, save_path))
        return tasks
    except Exception:
        return []

def collect_all_download_tasks(archive_urls):
    print(f"\n{CYAN}{BOLD}[*] FAZ 2: CDX dosya listesi toplanıyor...{ENDC}")
    all_tasks = []
    with ThreadPoolExecutor(max_workers=PARALLEL_ARCHIVE_SCAN) as executor:
        futures = {executor.submit(process_single_archive, url): url for url in archive_urls}
        pbar = tqdm(as_completed(futures), total=len(archive_urls), desc="Arşivler Taranıyor", unit="arsiv")
        for future in pbar:
            tasks = future.result()
            all_tasks.extend(tasks)
            pbar.set_postfix(total_files=f'{len(all_tasks):,}')
    print(f"{GREEN}[✓] FAZ 2 Tamamlandı: {len(all_tasks):,} CDX dosyası bulundu{ENDC}")
    return all_tasks

def download_file_threaded(url, save_path, stats_counter):
    if os.path.exists(save_path) and os.path.getsize(save_path) > 0:
        stats_counter.increment('skipped')
        return
    session = create_optimized_session()
    try:
        with session.get(url, stream=True, timeout=(CONNECT_TIMEOUT, READ_TIMEOUT), verify=False) as r:
            r.raise_for_status()
            temp_path = save_path + ".tmp"
            with open(temp_path, 'wb') as f:
                for chunk in r.iter_content(chunk_size=CHUNK_SIZE):
                    f.write(chunk)
            os.rename(temp_path, save_path)
        stats_counter.increment('success')
    except Exception:
        if 'temp_path' in locals() and os.path.exists(temp_path):
            os.remove(temp_path)
        stats_counter.increment('failed')

def download_all_files_batched(tasks):
    print(f"\n{CYAN}{BOLD}[*] FAZ 3: Hızlı indirme başlıyor...{ENDC}")
    print(f"{YELLOW}[*] {MAX_WORKERS} eşzamanlı thread, {BATCH_SIZE} dosya/batch{ENDC}")
    batches = [tasks[i:i + BATCH_SIZE] for i in range(0, len(tasks), BATCH_SIZE)]
    total_batches = len(batches)
    print(f"{YELLOW}[*] Toplam {total_batches} batch işlenecek{ENDC}\n")
    
    overall_stats = ThreadSafeCounter()
    
    with ThreadPoolExecutor(max_workers=MAX_WORKERS) as executor:
        for i, batch in enumerate(batches, 1):
            batch_pbar = tqdm(total=len(batch), desc=f"Batch {i}/{total_batches}", unit="dosya", leave=False)
            futures = []
            for url, save_path in batch:
                future = executor.submit(download_file_threaded, url, save_path, overall_stats)
                future.add_done_callback(lambda p: batch_pbar.update(1))
                futures.append(future)
            
            # Wait for the batch to complete
            for future in as_completed(futures):
                try:
                    future.result()
                except Exception:
                    pass
            batch_pbar.close()

    final_stats = overall_stats.get_stats()
    print(f"\n{GREEN}{BOLD}[✓] FAZ 3 Tamamlandı{ENDC}")
    print(f"  {GREEN}✓ Başarılı: {final_stats['success']}{ENDC}")
    print(f"  {YELLOW}⊘ Atlandı: {final_stats['skipped']}{ENDC}")
    print(f"  {RED}✗ Başarısız: {final_stats['failed']}{ENDC}")

def main():
    print(f"{MAGENTA}{BOLD}")
    print("╔═══════════════════════════════════════════════════════════════╗")
    print("║  🚀 KRYPTON CDX EXTRACTION JUGGERNAUT (Optimized)             ║")
    print("║  ⚡ Massively Parallel | Resilient | High-Throughput           ║")
    print("╚═══════════════════════════════════════════════════════════════╝")
    print(ENDC)
    start_time = time.time()
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    
    try:
        archive_urls = find_crawl_archives(BASE_URL)
        if not archive_urls:
            print(f"{RED}[!] Arşiv bulunamadı. İşlem sonlandırılıyor.{ENDC}")
            return

        all_tasks = collect_all_download_tasks(archive_urls)
        if not all_tasks:
            print(f"{YELLOW}[!] İndirilecek dosya bulunamadı.{ENDC}")
            return

        download_all_files_batched(all_tasks)

    except KeyboardInterrupt:
        print(f"\n{YELLOW}{BOLD}[!] KULLANICI TARAFINDAN DURDURULDU{ENDC}")
    except Exception as e:
        print(f"\n{RED}{BOLD}[!] KRİTİK HATA: {e}{ENDC}")
    finally:
        elapsed = time.time() - start_time
        hours, rem = divmod(elapsed, 3600)
        minutes, seconds = divmod(rem, 60)
        print(f"\n{GREEN}{BOLD}╔═══════════════════════════════════════════════════════════════╗{ENDC}")
        print(f"{GREEN}{BOLD}║  ✓ OPERASYON TAMAMLANDI                                      ║{ENDC}")
        print(f"{GREEN}{BOLD}╚═══════════════════════════════════════════════════════════════╝{ENDC}")
        print(f"{CYAN}⏱️  Toplam Süre: {int(hours):02d}s {int(minutes):02d}d {int(seconds):02d}sn{ENDC}")
        print(f"{CYAN}📁 Kayıt Yeri: ./{OUTPUT_DIR}{ENDC}")

if __name__ == "__main__":
    main()
