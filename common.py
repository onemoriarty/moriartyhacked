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
import warnings
import sys

warnings.filterwarnings('ignore', message='Unverified HTTPS request')

# ═══════════════════════════════════════════════════════════════════════════
#  🎯 FOCUSED CONFIGURATION (CC-MAIN-2025 ONLY)
# ═══════════════════════════════════════════════════════════════════════════

BASE_URL = "https://commoncrawl.org/get-started"
OUTPUT_DIR = "gzfiles_2025" # Çıktı klasörünü de buna göre isimlendirelim.
MAX_WORKERS = 100
PARALLEL_ARCHIVE_SCAN = 50
CONNECT_TIMEOUT = 20
READ_TIMEOUT = 300
TARGET_YEAR = "2025" # HEDEF YIL

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
    """Her thread için özel, optimize edilmiş session oluşturur."""
    if not hasattr(thread_local, "session"):
        session = requests.Session()
        adapter = HTTPAdapter(pool_connections=MAX_WORKERS, pool_maxsize=MAX_WORKERS * 2)
        session.mount("http://", adapter)
        session.mount("https://", adapter)
        session.headers.update({
            'User-Agent': f'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
            'Connection': 'keep-alive'
        })
        thread_local.session = session
    return thread_local.session

class RobustProgress:
    def __init__(self, total, desc="Progress"):
        self.total = total
        self.current = 0
        self.desc = desc
        self.lock = threading.Lock()
        self.report_interval = int(total * 0.1) or 1
        self.last_report = 0
    def update(self, n=1):
        with self.lock:
            self.current += n
            if self.current - self.last_report >= self.report_interval or self.current == self.total:
                percent = (self.current / self.total) * 100 if self.total > 0 else 0
                print(f"[*] {self.desc}: {self.current}/{self.total} tamamlandı ({percent:.1f}%)")
                self.last_report = self.current
    def finish(self):
        print(f"{GREEN}[✓] {self.desc} tamamlandı: {self.current}/{self.total}{ENDC}")

def find_crawl_archives(start_url):
    """
    Ana sayfadan SADECE HEDEF YILA AİT crawl arşivlerinin linklerini bulur.
    """
    print(f"{CYAN}{BOLD}[*] FAZ 1: Ana arşiv listesi {TARGET_YEAR} yılı için taranıyor...{ENDC}")
    session = create_optimized_session()
    try:
        response = session.get(start_url, timeout=CONNECT_TIMEOUT)
        response.raise_for_status()
        soup = BeautifulSoup(response.text, 'html.parser')
        
        # --- STRATEJİK DEĞİŞİKLİK BURADA ---
        # Regex'i, 'CC-MAIN-YYYY-' formatını arayacak şekilde güncelliyoruz.
        regex_pattern = re.compile(r'CC-MAIN-' + TARGET_YEAR + r'-')
        crawl_links = soup.find_all('a', href=regex_pattern)
        # ------------------------------------

        urls = sorted(list(set([link['href'] for link in crawl_links])), reverse=True)
        if not urls:
            print(f"{YELLOW}[!] Uyarı: {TARGET_YEAR} yılına ait arşiv bulunamadı. Belki Common Crawl henüz yayınlamamıştır.{ENDC}")
        else:
            print(f"{GREEN}[✓] FAZ 1 Tamamlandı: {len(urls)} adet {TARGET_YEAR} arşivi bulundu.{ENDC}")
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
        if progress: progress.update()

def collect_all_download_tasks(archive_urls):
    print(f"\n{CYAN}{BOLD}[*] FAZ 2: CDX dosya listesi toplanıyor...{ENDC}")
    all_tasks = []
    progress = RobustProgress(len(archive_urls), "Arşivler Taranıyor")
    with ThreadPoolExecutor(max_workers=PARALLEL_ARCHIVE_SCAN) as executor:
        futures = {executor.submit(process_single_archive, url, progress): url for url in archive_urls}
        for future in as_completed(futures):
            tasks = future.result()
            all_tasks.extend(tasks)
    progress.finish()
    print(f"{GREEN}[✓] FAZ 2 Tamamlandı: {len(all_tasks):,} CDX dosyası bulundu{ENDC}")
    return all_tasks

def download_file_threaded(task, stats):
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
                for chunk in r.iter_content(chunk_size=1024*1024):
                    f.write(chunk)
            os.rename(temp_path, save_path)
        stats.increment('success')
    except Exception:
        if 'temp_path' in locals() and os.path.exists(temp_path):
            os.remove(temp_path)
        stats.increment('failed')

def main():
    print(f"{MAGENTA}{BOLD}KRYPTON CDX EXTRACTOR (Focused Edition: {TARGET_YEAR}){ENDC}")
    start_time = time.time()
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    
    try:
        archive_urls = find_crawl_archives(BASE_URL)
        if not archive_urls: return
        all_tasks = collect_all_download_tasks(archive_urls)
        if not all_tasks: return

        print(f"\n{CYAN}{BOLD}[*] FAZ 3: Hızlı indirme başlıyor...{ENDC}")
        stats = ThreadSafeCounter()
        progress = RobustProgress(len(all_tasks), "CDX Dosyaları İndiriliyor")
        
        with ThreadPoolExecutor(max_workers=MAX_WORKERS) as executor:
            futures = {executor.submit(download_file_threaded, task, stats): task for task in all_tasks}
            for future in as_completed(futures):
                progress.update()
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

    except KeyboardInterrupt:
        print(f"\n{YELLOW}{BOLD}[!] KULLANICI TARAFINDAN DURDURULDU{ENDC}")
    except Exception as e:
        print(f"\n{RED}{BOLD}[!] KRİTİK HATA: {e}{ENDC}")
    finally:
        elapsed = time.time() - start_time
        hours, rem = divmod(elapsed, 3600)
        minutes, seconds = divmod(rem, 60)
        print(f"\n{CYAN}⏱️  Toplam Süre: {int(hours):02d}s {int(minutes):02d}d {int(seconds):02d}sn{ENDC}")
        print(f"{CYAN}📁 Kayıt Yeri: ./{OUTPUT_DIR}{ENDC}")

if __name__ == "__main__":
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

    main()
