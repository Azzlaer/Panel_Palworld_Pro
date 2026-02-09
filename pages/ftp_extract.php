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

if (empty($_GET['file'])) {
    exit("Archivo ZIP no especificado");
}
$file = ftp_normalize_path($_GET['file']);

if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'zip') {
    exit("Solo se soporta ZIP en este extractor.");
}

$conn = @ftp_connect(FTP_HOST);
if (!$conn || !@ftp_login($conn, FTP_USER, FTP_PASS)) {
    exit("No se pudo conectar al FTP");
}
ftp_pasv($conn, true);

$tmpZip = tempnam(sys_get_temp_dir(), 'ftpextract_') . ".zip";
if (!@ftp_get($conn, $tmpZip, $file, FTP_BINARY)) {
    ftp_close($conn);
    unlink($tmpZip);
    exit("No se pudo descargar ZIP desde FTP");
}

// Extraer
$zip = new ZipArchive();
if ($zip->open($tmpZip) !== true) {
    unlink($tmpZip);
    ftp_close($conn);
    exit("No se pudo abrir ZIP");
}

// Destino: carpeta donde está el ZIP
$dirDestino = rtrim(dirname($file), '/');

$tmpDir = sys_get_temp_dir() . '/ftpzip_' . uniqid();
mkdir($tmpDir);

$zip->extractTo($tmpDir);
$zip->close();
unlink($tmpZip);

// Subir contenido extraído
$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($tmpDir, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($it as $path => $info) {
    $rel = str_replace($tmpDir, '', $path);
    $rel = str_replace('\\','/',$rel);
    $dest = $dirDestino . $rel;

    if ($info->isDir()) {
        @ftp_mkdir($conn, $dest);
    } else {
        @ftp_put($conn, $dest, $path, FTP_BINARY);
    }
}

// limpiar temporal
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
rrmdir_local($tmpDir);

ftp_close($conn);

echo "Extracción completada correctamente.";
