<?php
require_once "../config.php";

if (!is_logged_in()) {
    echo '<div class="alert alert-danger">Sesión expirada. Vuelve a iniciar sesión.</div>';
    return;
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

$file = isset($_GET['file']) ? ftp_normalize_path($_GET['file']) : null;
if (!$file) {
    echo '<div class="alert alert-danger">Archivo no especificado.</div>';
    return;
}

$conn = @ftp_connect(FTP_HOST);
if (!$conn || !@ftp_login($conn, FTP_USER, FTP_PASS)) {
    echo '<div class="alert alert-danger">No se pudo conectar al FTP.</div>';
    return;
}
ftp_pasv($conn, true);

$msg = "";
$content = "";

$tmp = tempnam(sys_get_temp_dir(), 'ftpedit_');

// Guardar cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contenido'])) {
    file_put_contents($tmp, $_POST['contenido']);
    if (@ftp_put($conn, $file, $tmp, FTP_BINARY)) {
        $msg = '<div class="alert alert-success mb-3">Archivo guardado correctamente.</div>';
    } else {
        $msg = '<div class="alert alert-danger mb-3">Error al guardar archivo.</div>';
    }
}

// Descargar contenido actual
@ftp_get($conn, $tmp, $file, FTP_BINARY);
$content = @file_get_contents($tmp);
unlink($tmp);
ftp_close($conn);
?>
<div class="container-fluid text-light">
    <h2 class="fw-bold mb-3">✏️ Editar archivo</h2>
    <p class="text-info"><small><?= htmlspecialchars($file) ?></small></p>
    <?= $msg ?>
    <form method="POST">
        <textarea name="contenido" class="form-control" rows="20" style="background:#111;color:#eee;font-family:monospace;"><?= htmlspecialchars($content) ?></textarea>
        <button class="btn btn-primary mt-3">💾 Guardar cambios</button>
        <button type="button" class="btn btn-secondary mt-3" onclick="ftpBackToManager()">Volver al FTP</button>
    </form>
</div>

<script>
function ftpBackToManager(){
    $('#main').load('pages/ftp_manager.php');
}
</script>
