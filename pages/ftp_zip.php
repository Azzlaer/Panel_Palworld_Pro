<?php
require_once "../config.php";

if (!is_logged_in()) {
    exit("No autorizado");
}

function ftp_normalize_path(string $path): string {
    $path = str_replace('\\', '/', trim($path));
    if ($path === '') return FTP_ROOT;
    if ($path[0] !== '/') $path = FTP_ROOT . '/' . $path;
    $path = preg_replace('#/+#','/',$path);
    $parts = [];
    foreach (explode('/', $path) as $seg) {
        if ($seg === '' || $seg === '.') continue;
        if ($seg === '..') array_pop($parts);
        else $parts[] = $seg;
    }
    $norm = '/' . implode('/', $parts);
    if (strpos($norm, rtrim(FTP_ROOT,'/')) !== 0) {
        $norm = FTP_ROOT;
    }
    return $norm;
}

if (empty($_GET['path'])) {
    exit("Ruta no especificada");
}
$path = ftp_normalize_path($_GET['path']);
$name = basename($path);
if ($name === '') $name = 'root';

$conn = @ftp_connect(FTP_HOST);
if (!$conn || !@ftp_login($conn, FTP_USER, FTP_PASS)) {
    exit("No se pudo conectar al FTP");
}
ftp_pasv($conn, true);

// Ver si es archivo o directorio
$list = @ftp_rawlist($conn, $path);
$isDir = false;
if ($list && count($list) === 1) {
    $parts = preg_split('/\s+/', $list[0], 9);
    if ($parts[0][0] === 'd') $isDir = true;
} else {
    // si hay muchos, asumimos dir
    $isDir = true;
}

$tmpBase = sys_get_temp_dir() . '/ftpzip_' . uniqid();
mkdir($tmpBase);

if ($isDir) {
    // descargar recursivo
    ftp_download_recursive($conn, $path, $tmpBase);
} else {
    $tmpFile = $tmpBase . '/' . $name;
    @ftp_get($conn, $tmpFile, $path, FTP_BINARY);
}

$zipPath = $tmpBase . '.zip';
$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
    exit("No se pudo crear ZIP");
}

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($tmpBase, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($it as $file) {
    $filePath = $file->getRealPath();
    $localRel = substr($filePath, strlen($tmpBase) + 1);
    if ($file->isDir()) {
        $zip->addEmptyDir($localRel);
    } else {
        $zip->addFile($filePath, $localRel);
    }
}
$zip->close();

ftp_close($conn);

// enviar zip al navegador
header("Content-Type: application/zip");
header("Content-Disposition: attachment; filename=\"{$name}.zip\"");
header("Content-Length: " . filesize($zipPath));
readfile($zipPath);

// limpiar
function rrmdir_local($dir) {
    if (!is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $file) {
        $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
    }
    rmdir($dir);
}
rrmdir_local($tmpBase);
unlink($zipPath);
exit;

function ftp_download_recursive($conn, string $remoteDir, string $localBase) {
    $list = @ftp_rawlist($conn, $remoteDir);
    if ($list === false) return;
    foreach ($list as $line) {
        $parts = preg_split('/\s+/', $line, 9);
        if (count($parts) < 9) continue;
        $name = $parts[8];
        if ($name === '.' || $name === '..') continue;
        $isDir = ($parts[0][0] === 'd');
        $remotePath = rtrim($remoteDir,'/').'/'.$name;
        $localPath  = $localBase . '/' . $name;

        if ($isDir) {
            @mkdir($localPath);
            ftp_download_recursive($conn, $remotePath, $localPath);
        } else {
            @ftp_get($conn, $localPath, $remotePath, FTP_BINARY);
        }
    }
}
