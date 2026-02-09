<?php
require_once "../config.php";

if (!is_logged_in()) {
    echo '<div class="alert alert-danger">Sesión expirada. Vuelve a iniciar sesión.</div>';
    return;
}

/* ========== Helpers de rutas ========== */

function ftp_normalize_path(string $path): string {
    $path = str_replace('\\', '/', trim($path));
    if ($path === '') return FTP_ROOT;
    if ($path[0] !== '/') $path = FTP_ROOT . '/' . $path;
    // eliminar // repetidos
    $path = preg_replace('#/+#','/',$path);

    // resolver .. y .
    $parts = [];
    foreach (explode('/', $path) as $seg) {
        if ($seg === '' || $seg === '.') continue;
        if ($seg === '..') {
            array_pop($parts);
        } else {
            $parts[] = $seg;
        }
    }
    $norm = '/' . implode('/', $parts);
    // asegurar que no salga de la raíz
    if (strpos($norm, rtrim(FTP_ROOT,'/')) !== 0) {
        $norm = FTP_ROOT;
    }
    return $norm === '' ? '/' : $norm;
}

function ftp_connect_safe(&$error = null) {
    $conn = @ftp_connect(FTP_HOST);
    if (!$conn) {
        $error = "No se pudo conectar al servidor FTP.";
        return false;
    }
    if (!@ftp_login($conn, FTP_USER, FTP_PASS)) {
        $error = "Error de autenticación en el servidor FTP.";
        return false;
    }
    ftp_pasv($conn, true);
    return $conn;
}

/* ========== conexion inicial ========== */

$ftp_error = null;
$conn = ftp_connect_safe($ftp_error);
if (!$conn) {
    echo "<div class='alert alert-danger'>$ftp_error</div>";
    return;
}

$current_dir = isset($_GET['dir']) ? $_GET['dir'] : FTP_ROOT;
$current_dir = ftp_normalize_path($current_dir);

/* ========== Acciones POST (subir, crear, borrar, comandos) ========== */

$msg_html = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // SUBIR ARCHIVO
    if (isset($_POST['action']) && $_POST['action'] === 'upload' && isset($_FILES['archivo'])) {
        $fileName = basename($_FILES['archivo']['name']);
        $target   = $current_dir . '/' . $fileName;
        if (@ftp_put($conn, $target, $_FILES['archivo']['tmp_name'], FTP_BINARY)) {
            $msg_html = "<div class='alert alert-success mb-3'>Archivo subido correctamente.</div>";
        } else {
            $msg_html = "<div class='alert alert-danger mb-3'>Error al subir el archivo.</div>";
        }
    }

    // CREAR CARPETA
    if (isset($_POST['action']) && $_POST['action'] === 'mkdir' && !empty($_POST['folder_name'])) {
        $folder = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['folder_name']);
        $path   = $current_dir . '/' . $folder;
        if (@ftp_mkdir($conn, $path)) {
            $msg_html = "<div class='alert alert-success mb-3'>Carpeta creada correctamente.</div>";
        } else {
            $msg_html = "<div class='alert alert-danger mb-3'>Error al crear carpeta.</div>";
        }
    }

    // ELIMINAR ARCHIVO O CARPETA (recursivo)
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && !empty($_POST['delete_path'])) {
        $del = ftp_normalize_path($_POST['delete_path']);
        if ($del === FTP_ROOT) {
            $msg_html = "<div class='alert alert-danger mb-3'>No puedes eliminar la raíz.</div>";
        } else {
            if (ftp_delete_recursive($conn, $del)) {
                $msg_html = "<div class='alert alert-success mb-3'>Eliminado correctamente.</div>";
            } else {
                $msg_html = "<div class='alert alert-danger mb-3'>No se pudo eliminar.</div>";
            }
        }
    }

    // COMANDO SIMPLE
    if (isset($_POST['action']) && $_POST['action'] === 'command' && !empty($_POST['command'])) {
        $cmd = trim($_POST['command']);
        $msg_html = ejecutar_comando_ftp($conn, $cmd, $current_dir);
    }
}

/* ========== funciones de delete recursivo y comandos ========== */

function ftp_delete_recursive($conn, string $path): bool {
    // probar si es archivo
    if (@ftp_delete($conn, $path)) {
        return true;
    }

    // si no, tratar como carpeta
    $list = @ftp_rawlist($conn, $path);
    if ($list === false) {
        return false;
    }

    foreach ($list as $item) {
        $parts = preg_split('/\s+/', $item, 9);
        if (count($parts) < 9) continue;
        $name  = $parts[8];
        if ($name === '.' || $name === '..') continue;
        $isDir = ($parts[0][0] === 'd');

        $itemPath = rtrim($path, '/') . '/' . $name;
        if ($isDir) {
            ftp_delete_recursive($conn, $itemPath);
        } else {
            @ftp_delete($conn, $itemPath);
        }
    }

    return @ftp_rmdir($conn, $path);
}

function ejecutar_comando_ftp($conn, string $cmd, string $current_dir): string {
    // comandos soportados simples:
    // rm <ruta>, rmdir <ruta>, mv <origen> <destino>, chmod <mod> <ruta>
    $parts = preg_split('/\s+/', $cmd);
    $op = strtolower($parts[0] ?? '');

    if ($op === 'rm' && !empty($parts[1])) {
        $target = ftp_normalize_path($current_dir . '/' . $parts[1]);
        if (@ftp_delete($conn, $target)) {
            return "<div class='alert alert-success mb-3'>Archivo eliminado (rm).</div>";
        }
        return "<div class='alert alert-danger mb-3'>No se pudo eliminar (rm).</div>";
    }

    if ($op === 'rmdir' && !empty($parts[1])) {
        $target = ftp_normalize_path($current_dir . '/' . $parts[1]);
        if (ftp_delete_recursive($conn, $target)) {
            return "<div class='alert alert-success mb-3'>Carpeta eliminada (rmdir).</div>";
        }
        return "<div class='alert alert-danger mb-3'>No se pudo eliminar carpeta (rmdir).</div>";
    }

    if ($op === 'mv' && !empty($parts[1]) && !empty($parts[2])) {
        $src = ftp_normalize_path($current_dir . '/' . $parts[1]);
        $dst = ftp_normalize_path($current_dir . '/' . $parts[2]);
        if (@ftp_rename($conn, $src, $dst)) {
            return "<div class='alert alert-success mb-3'>Movido/renombrado correctamente (mv).</div>";
        }
        return "<div class='alert alert-danger mb-3'>No se pudo mover/renombrar (mv).</div>";
    }

    if ($op === 'chmod' && !empty($parts[1]) && !empty($parts[2])) {
        $mod  = octdec($parts[1]); // p.ej. 755
        $file = ftp_normalize_path($current_dir . '/' . $parts[2]);
        if (function_exists('ftp_chmod') && ftp_chmod($conn, $mod, $file) !== false) {
            return "<div class='alert alert-success mb-3'>Permisos cambiados (chmod).</div>";
        }
        return "<div class='alert alert-warning mb-3'>chmod no soportado o fallo.</div>";
    }

    return "<div class='alert alert-warning mb-3'>Comando no reconocido o incompleto.</div>";
}

/* ========== LISTAR ========== */

$items = @ftp_rawlist($conn, $current_dir);
ftp_close($conn);

/* ========= Helpers de UI (breadcrumb) ========= */

function ftp_breadcrumb(string $dir): string {
    $parts = explode('/', trim($dir, '/'));
    $crumb = '<a href="#" onclick="ftpLoadDir(\'/\')">/</a>';
    $path  = '';
    foreach ($parts as $p) {
        if ($p === '') continue;
        $path .= '/' . $p;
        $crumb .= ' / <a href="#" onclick="ftpLoadDir(\'' . $path . '\')">' . htmlspecialchars($p) . '</a>';
    }
    return $crumb;
}
?>
<style>
.ftp-box {
    background: rgba(20,20,20,0.92);
    border-radius: 15px;
    padding: 20px;
    border: 1px solid rgba(255,255,255,0.08);
    backdrop-filter: blur(6px);
}
.ftp-btn {
    padding: 6px 12px;
    border-radius: 10px;
    font-weight: 600;
}
.ftp-row:hover {
    background: rgba(255,255,255,0.05);
}
.ftp-command {
    font-family: monospace;
    font-size: 0.85rem;
}
</style>

<div class="container-fluid text-light">
    <h2 class="fw-bold mb-4">🌐 FTP Manager</h2>

    <?= $msg_html ?>

    <div class="ftp-box mb-4">

        <!-- BREADCRUMB / DIRECTORIO ACTUAL -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <b>Directorio actual:</b>
                <span class="text-info ftp-command">
                    <?= ftp_breadcrumb($current_dir) ?>
                </span>
            </div>
            <button class="btn btn-secondary ftp-btn" onclick="ftpLoadDir('/')">Ir a raíz</button>
        </div>

        <!-- SUBIR ARCHIVO -->
        <form id="ftpUploadForm" class="mb-3" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload">
            <div class="input-group">
                <input type="file" name="archivo" class="form-control" required>
                <button class="btn btn-primary ftp-btn">Subir archivo</button>
            </div>
            <div class="progress mt-2" style="height: 6px; display:none;" id="ftpUploadProgressWrap">
                <div class="progress-bar" role="progressbar" style="width: 0%" id="ftpUploadProgress"></div>
            </div>
        </form>

        <!-- CREAR CARPETA -->
        <form id="ftpMkdirForm" class="mb-3">
            <input type="hidden" name="action" value="mkdir">
            <div class="input-group">
                <input type="text" name="folder_name" class="form-control" placeholder="Nombre de la carpeta" required>
                <button class="btn btn-success ftp-btn">Crear carpeta</button>
            </div>
        </form>

        <!-- MINI TERMINAL -->
        <form id="ftpCmdForm" class="mb-3">
            <input type="hidden" name="action" value="command">
            <div class="input-group">
                <span class="input-group-text ftp-command">cmd&gt;</span>
                <input type="text" name="command" class="form-control ftp-command" placeholder="rm log.txt | rmdir carpeta | mv old new | chmod 755 archivo">
                <button class="btn btn-outline-info ftp-btn">Ejecutar</button>
            </div>
        </form>

        <!-- LISTA -->
        <table class="table table-dark table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Tamaño</th>
                    <th>Fecha</th>
                    <th style="width:220px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($items): ?>
                <?php foreach ($items as $line):
                    $parts = preg_split('/\s+/', $line, 9);
                    if (count($parts) < 9) continue;
                    $perms = $parts[0];
                    $num   = $parts[1];
                    $owner = $parts[2];
                    $group = $parts[3];
                    $size  = $parts[4];
                    $month = $parts[5];
                    $day   = $parts[6];
                    $time  = $parts[7];
                    $name  = $parts[8];
                    if ($name === '.' || $name === '..') continue;
                    $isDir = ($perms[0] === 'd');
                    $full  = rtrim($current_dir,'/').'/'.$name;
                    $sizeHuman = $isDir ? '-' : ( $size > 1024*1024 ? round($size/1024/1024,2).' MB' : ($size > 1024 ? round($size/1024,2).' KB' : $size.' B') );
                    $dateStr   = $day.' '.$month.' '.$time;
                ?>
                <tr class="ftp-row">
                    <td>
                        <?php if ($isDir): ?>
                            📁 <a href="#" class="text-info" onclick="ftpLoadDir('<?= $full ?>')"><?= htmlspecialchars($name) ?></a>
                        <?php else: ?>
                            📄 <?= htmlspecialchars($name) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= $sizeHuman ?></td>
                    <td><?= htmlspecialchars($dateStr) ?></td>
                    <td>
                        <!-- Eliminar -->
                        <form class="d-inline ftpFormDelete">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="delete_path" value="<?= htmlspecialchars($full) ?>">
                            <button class="btn btn-danger btn-sm">Eliminar</button>
                        </form>

                        <?php if (!$isDir): ?>
                            <!-- Editar -->
                            <button class="btn btn-warning btn-sm" onclick="ftpEditFile('<?= htmlspecialchars($full, ENT_QUOTES) ?>')">Editar</button>

                            <!-- Descargar -->
                            <a class="btn btn-primary btn-sm" href="pages/ftp_download.php?file=<?= urlencode($full) ?>" target="_blank">Descargar</a>

                            <!-- Extraer ZIP -->
                            <?php $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION)); ?>
                            <?php if ($ext === 'zip'): ?>
                                <a class="btn btn-info btn-sm" href="pages/ftp_extract.php?file=<?= urlencode($full) ?>" target="_blank">Extraer ZIP</a>
                            <?php endif; ?>

                            <!-- Comprimir como ZIP -->
                            <a class="btn btn-secondary btn-sm" href="pages/ftp_zip.php?path=<?= urlencode($full) ?>" target="_blank">ZIP</a>
                        <?php else: ?>
                            <!-- ZIP de carpeta -->
                            <a class="btn btn-secondary btn-sm" href="pages/ftp_zip.php?path=<?= urlencode($full) ?>" target="_blank">ZIP carpeta</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4">No hay archivos en este directorio.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Cargar un directorio vía AJAX
function ftpLoadDir(dir) {
    $('#main').html('<div class="p-5 text-center text-light">Cargando FTP...</div>');
    $('#main').load('pages/ftp_manager.php?dir=' + encodeURIComponent(dir));
}

// Editar archivo (carga ftp_edit en #main)
function ftpEditFile(path) {
    $('#main').html('<div class="p-5 text-center text-light">Cargando editor...</div>');
    $('#main').load('pages/ftp_edit.php?file=' + encodeURIComponent(path));
}

// Envíos AJAX con progreso para subir
$(document).on('submit', '#ftpUploadForm', function(e){
    e.preventDefault();
    let form = this;
    let formData = new FormData(form);
    let url = 'pages/ftp_manager.php?dir=<?= urlencode($current_dir) ?>';

    $('#ftpUploadProgressWrap').show();
    $('#ftpUploadProgress').css('width','0%');

    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        xhr: function(){
            let xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener('progress', function(evt){
                if (evt.lengthComputable) {
                    let percent = Math.round((evt.loaded / evt.total) * 100);
                    $('#ftpUploadProgress').css('width', percent + '%');
                }
            }, false);
            return xhr;
        },
        success: function(res){
            $('#main').html(res);
        }
    });
});

// Crear carpeta
$(document).on('submit', '#ftpMkdirForm', function(e){
    e.preventDefault();
    let formData = $(this).serialize();
    $.post('pages/ftp_manager.php?dir=<?= urlencode($current_dir) ?>', formData, function(res){
        $('#main').html(res);
    });
});

// Comandos
$(document).on('submit', '#ftpCmdForm', function(e){
    e.preventDefault();
    let formData = $(this).serialize();
    $.post('pages/ftp_manager.php?dir=<?= urlencode($current_dir) ?>', formData, function(res){
        $('#main').html(res);
    });
});

// Eliminar
$(document).on('submit', '.ftpFormDelete', function(e){
    e.preventDefault();
    if (!confirm('¿Seguro que deseas eliminar? (Recursivo si es carpeta)')) return;
    let formData = $(this).serialize();
    $.post('pages/ftp_manager.php?dir=<?= urlencode($current_dir) ?>', formData, function(res){
        $('#main').html(res);
    });
});
</script>
