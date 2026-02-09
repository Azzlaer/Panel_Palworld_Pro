<?php
require_once "../config.php";

if (!is_logged_in()) {
    http_response_code(403);
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
    exit("Archivo no especificado");
}
$file = ftp_normalize_path($_GET['file']);
$name = basename($file);

$conn = @ftp_connect(FTP_HOST);
if (!$conn || !@ftp_login($conn, FTP_USER, FTP_PASS)) {
    exit("No se pudo conectar al FTP");
}
ftp_pasv($conn, true);

$tmp = tempnam(sys_get_temp_dir(), 'ftpdl_');
if (!@ftp_get($conn, $tmp, $file, FTP_BINARY)) {
    ftp_close($conn);
    unlink($tmp);
    exit("No se pudo descargar desde FTP");
}
ftp_close($conn);

header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"$name\"");
header("Content-Length: " . filesize($tmp));
readfile($tmp);
unlink($tmp);
exit;
