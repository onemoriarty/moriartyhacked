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
#  🎯 ULTRA HIGH-PERFORMANCE JUGGERNAUT CONFIGURATION (Legacy Compatible)
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
    """Her thread için özel, optimize edilmiş ve Python 3.6 uyumlu retry mekanizmalı session oluşturur."""
    if not hasattr(thread_local, "session"):
        session = requests.Session()
        
        # --- PYTHON 3.6 UYUMLULUK DÜZELTMESİ ---
        # `allowed_methods` yerine, eski versiyonların anladığı `method_whitelist` kullanılır.
        retry_strategy = Retry(
            total=MAX_RETRIES,
            backoff_factor=BACKOFF_FACTOR,
            status_forcelist=[429, 500, 502, 503, 504],
            method_whitelist=["HEAD", "GET"]  # 'allowed_methods' yerine bu kullanılır.
        )
        # -----------------------------------------
        
        adapter = HTTPAdapter(
            pool_connections=MAX_WORKERS,
            pool_maxsize=MAX_WORKERS * 2,
            max_retries=retry_strategy
        )
        session.mount("http://", adapter)
        session.mount("https://", adapter)
        session.headers.update({
            'User-Agent': f'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.0.0 Safari/537.36',
            'Accept-Encoding': 'gzip, deflate',
            'Connection': 'keep-alive'
        })
        thread_local.session = session
    return thread_local.session

# tqdm kütüphanesi olmadan çalışan basit bir ilerleme çubuğu sınıfı
class SimpleProgress:
    def __init__(self, total, desc="Progress"):
        self.total = total
        self.current = 0
        self.desc = desc
        self.lock = threading.Lock()
        self.start_time = time.time()

    def update(self, n=1):
        with self.lock:
            self.current += n
            elapsed = time.time() - self.start_time
            rate = self.current / elapsed if elapsed > 0 else 0
            percent = (self.current / self.total) * 100 if self.total > 0 else 0
            
            bar_len = 30
            filled_len = int(round(bar_len * self.current / float(self.total)))
            
            bar = '█' * filled_len + '-' * (bar_len - filled_len)
            
            # \r ile satır başına dönerek aynı satırı güncelle
            sys.stdout.write(f'\r{self.desc}: |{bar}| {self.current}/{self.total} [{percent:.1f}%] @ {rate:.2f} items/s')
            sys.stdout.flush()

    def finish(self):
        sys.stdout.write('\n')

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

def process_single_archive(archive_url, progress):
    session = create_optimized_session()
    try:
        response = session.get(archive_url, timeout=CONNECT_TIMEOUT)
        soup = BeautifulSoup(response.text, 'html.parser')
        link = soup.find('a', href=re.compile(r'cc-index\.paths\.gz'))
        if not link: return []
        index_paths_url = urljoin(archive_url, link['href'])
        response_gz = session.get(index_paths_url, timeout=READ_TIMEOUT)
        decompressed = gzip.decompress(response_gz.content)
        paths = decompressed.decode('utf-8').strip().split('\n')
        tasks = []
        archive_name = archive_url.split('/')[-2]
        for path in paths:
            if not path: continue
            final_url = urljoin("https://data.commoncrawl.org/", path)
            save_path = os.path.join(OUTPUT_DIR, f"{archive_name}_{os.path.basename(path)}")
            tasks.append((final_url, save_path))
        return tasks
    except Exception:
        return []
    finally:
        if progress:
            progress.update()

def collect_all_download_tasks(archive_urls):
    print(f"\n{CYAN}{BOLD}[*] FAZ 2: CDX dosya listesi toplanıyor...{ENDC}")
    all_tasks = []
    progress = SimpleProgress(len(archive_urls), "Arşivler Taranıyor")
    with ThreadPoolExecutor(max_workers=PARALLEL_ARCHIVE_SCAN) as executor:
        futures = {executor.submit(process_single_archive, url, progress): url for url in archive_urls}
        for future in as_completed(futures):
            tasks = future.result()
            all_tasks.extend(tasks)
    progress.finish()
    print(f"{GREEN}[✓] FAZ 2 Tamamlandı: {len(all_tasks):,} CDX dosyası bulundu{ENDC}")
    return all_tasks

def download_file_threaded(task, progress, stats):
    url, save_path = task
    if os.path.exists(save_path) and os.path.getsize(save_path) > 0:
        stats.increment('skipped')
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
        stats.increment('success')
    except Exception:
        if 'temp_path' in locals() and os.path.exists(temp_path):
            os.remove(temp_path)
        stats.increment('failed')
    finally:
        if progress:
            progress.update()

def download_all_files(tasks):
    print(f"\n{CYAN}{BOLD}[*] FAZ 3: Hızlı indirme başlıyor...{ENDC}")
    print(f"{YELLOW}[*] {MAX_WORKERS} eşzamanlı asker görevde...{ENDC}")
    
    stats = ThreadSafeCounter()
    progress = SimpleProgress(len(tasks), "CDX Dosyaları İndiriliyor")
    
    with ThreadPoolExecutor(max_workers=MAX_WORKERS) as executor:
        futures = {executor.submit(download_file_threaded, task, progress, stats) for task in tasks}
        for future in as_completed(futures):
            try:
                future.result()
            except Exception:
                pass
    progress.finish()

    final_stats = stats.get_stats()
    print(f"\n{GREEN}{BOLD}[✓] FAZ 3 Tamamlandı{ENDC}")
    print(f"  {GREEN}✓ Başarılı: {final_stats['success']}{ENDC}")
    print(f"  {YELLOW}⊘ Atlandı: {final_stats['skipped']}{ENDC}")
    print(f"  {RED}✗ Başarısız: {final_stats['failed']}{ENDC}")

def main():
    print(f"{MAGENTA}{BOLD}")
    print("╔═══════════════════════════════════════════════════════════════╗")
    print("║  🚀 KRYPTON CDX EXTRACTOR (Python 3.6 Compatible)            ║")
    print("║  ⚡ Massively Parallel | Resilient | Legacy Systems Ready      ║")
    print("╚═══════════════════════════════════════════════════════════════╝")
    print(ENDC)
    start_time = time.time()
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    
    try:
        archive_urls = find_crawl_archives(BASE_URL)
        if not archive_urls:
            return

        all_tasks = collect_all_download_tasks(archive_urls)
        if not all_tasks:
            return

        download_all_files(all_tasks)

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
