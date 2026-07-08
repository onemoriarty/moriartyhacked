<?php
/**
 * Joomla! 3.x Index Entry Point - Bot Detection + AMP Router
 *
 * Gerçek kullanıcılar → Normal Joomla teması
 * Doğrulanmış Googlebot → AMP sürümü (amp.php)
 * Sahte botlar → Normal Joomla sayfası
 *
 * @package    Joomla
 * @copyright  (C) 2005 - 2023 Open Source Matters, Inc.
 * @license    GNU General Public License version 2 or later
 */

// ============================================================
// 1. SABİTLER
// ============================================================
define('_JEXEC', 1);
define('JPATH_BASE', __DIR__);

// ============================================================
// 2. BOT TESPİT SİSTEMİ
// ============================================================

/**
 * İstemci IP'sini al
 */
function getClientIP(): string {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = trim(explode(',', $_SERVER['HTTP_CF_CONNECTING_IP'])[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $ip;
        }
    }
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = trim($_SERVER['HTTP_X_REAL_IP']);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
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

    foreach ($googleRanges as $range) {
        if (strpos($ip, $range) === 0) {
            $hostname = @gethostbyaddr($ip);
            if ($hostname && preg_match('/\.(googlebot\.com|google\.com)$/', $hostname)) {
                $resolvedIP = @gethostbyname($hostname);
                if ($resolvedIP && $resolvedIP === $ip) {
                    return true;
                }
            }
            return true;
        }
    }

    $hostname = @gethostbyaddr($ip);
    if ($hostname && preg_match('/\.(googlebot\.com|google\.com)$/', $hostname)) {
        $resolvedIP = @gethostbyname($hostname);
        if ($resolvedIP && $resolvedIP === $ip) {
            return true;
        }
    }

    return false;
}

/**
 * Kapsamlı bot tespiti
 */
function detectGoogleBot(): array {
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
    $ip = getClientIP();

    $suspiciousTools = [
        'curl', 'wget', 'python', 'scrapy', 'selenium', 'phantomjs',
        'headless', 'puppeteer', 'playwright', 'axios', 'node-fetch',
        'postman', 'insomnia', 'go-http-client', 'java/', 'okhttp',
    ];

    $isGoogleUA = (strpos($userAgent, 'googlebot') !== false ||
                   strpos($userAgent, 'google-structured') !== false ||
                   strpos($userAgent, 'google-inspection') !== false);

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
// 3. AMP YÖNLENDİRME KARARI
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

    if (file_exists(JPATH_BASE . '/amp.php')) {
        require_once JPATH_BASE . '/amp.php';
        exit;
    }

    // Fallback basit AMP
    $host = isset($_SERVER['HTTP_HOST']) ? htmlspecialchars($_SERVER['HTTP_HOST']) : 'Site';
    $uri  = isset($_SERVER['REQUEST_URI']) ? htmlspecialchars($_SERVER['REQUEST_URI']) : '/';
    $canonical = 'https://' . $host . $uri;
    ?>
    <!DOCTYPE html>
    <html amp lang="tr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script async src="https://cdn.ampproject.org/v0.js"></script>
        <title><?php echo $host; ?></title>
        <link rel="canonical" href="<?php echo $canonical; ?>">
        <style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
    </head>
    <body>
        <h1><?php echo $host; ?></h1>
        <p>AMP sürümü yükleniyor...</p>
    </body>
    </html>
    <?php
    exit;
}

// ============================================================
// 4. NORMAL KULLANICI / ŞÜPHELİ BOT → JOOMLA 3.x
// ============================================================

if ($botInfo['is_google_ua'] && !$botInfo['is_verified']) {
    header('X-Bot-Status: fake-googlebot');
} else {
    header('X-Bot-Status: ' . ($botInfo['is_suspicious'] ? 'suspicious-tool' : 'human'));
}

// ============================================================
// 5. JOOMLA 3.x BAŞLAT
// ============================================================
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

$app = JFactory::getApplication('site');
$app->execute();
