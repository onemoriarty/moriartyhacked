<?php
/*
 * Siyah-Beyaz Minimal File Manager
 * Sadece Dosya İşlemleri (CRUD) - Komut Çalıştırma (Shell) içermez.
 */
error_reporting(0);
$root = getcwd();
$path = isset($_GET['d']) ? realpath($_GET['d']) : $root;

// Güvenlik: Root dışına çıkışı engellemek istersen burayı düzenleyebilirsin
if (!$path || !is_dir($path)) $path = $root;

// DOSYA SİLME
if (isset($_GET['delete'])) {
    $target = $path . DIRECTORY_SEPARATOR . $_GET['delete'];
    is_dir($target) ? rmdir($target) : unlink($target);
    header("Location: ?d=$path");
}

// YENİ DOSYA/KLASÖR OLUŞTURMA
if ($_POST['new_name']) {
    $target = $path . DIRECTORY_SEPARATOR . $_POST['new_name'];
    $_POST['type'] == 'dir' ? mkdir($target) : file_put_contents($target, '');
    header("Location: ?d=$path");
}
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        body { background: #000; color: #fff; font-family: monospace; padding: 20px; }
        a { color: #aaa; text-decoration: none; }
        a:hover { color: #fff; border-bottom: 1px solid #fff; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { text-align: left; padding: 8px; border: 1px solid #333; }
        input, select, textarea { background: #111; color: #fff; border: 1px solid #333; padding: 5px; }
        .btn { cursor: pointer; background: #222; }
    </style>
    <title>PHP Manager</title>
</head>
<body>
    <h3>Dizin: <?php echo $path; ?></h3>
    
    <form method="POST">
        <input type="text" name="new_name" placeholder="İsim..." required>
        <select name="type">
            <option value="file">Dosya</option>
            <option value="dir">Klasör</option>
        </select>
        <input type="submit" class="btn" value="Oluştur">
    </form>

    <table>
        <thead>
            <tr>
                <th>Ad</th>
                <th>Tür</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <tr><td><a href="?d=<?php echo dirname($path); ?>">.. (Geri)</a></td><td>-</td><td>-</td></tr>
            <?php
            $items = scandir($path);
            foreach ($items as $item) {
                if ($item == '.' || $item == '..') continue;
                $fullPath = $path . DIRECTORY_SEPARATOR . $item;
                $isDir = is_dir($fullPath);
                echo "<tr>
                    <td>" . ($isDir ? "<a href='?d=$fullPath'>[ $item ]</a>" : $item) . "</td>
                    <td>" . ($isDir ? "Klasör" : "Dosya") . "</td>
                    <td>
                        <a href='?d=$path&delete=$item' onclick=\"return confirm('Emin misin?')\">[Sil]</a>
                    </td>
                </tr>";
            }
            ?>
        </tbody>
    </table>

    <p style="margin-top: 50px; color: #444; font-size: 10px;">Safe File Manager - No Execution Mode</p>
</body>
</html>
