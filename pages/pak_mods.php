<?php
require_once "../config.php";

if (!is_logged_in()) {
    echo "<div class='alert alert-danger'>Sesión expirada. Inicia sesión nuevamente.</div>";
    return;
}

$PAKS_DIR = PAL_PAKS_DIR;

// Crear carpeta si no existe
if (!is_dir($PAKS_DIR)) {
    mkdir($PAKS_DIR, 0777, true);
}

$msg = "";

/* ======================================================
   SUBIR ARCHIVO .PAK CON AJAX
====================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {

    if (!isset($_FILES['archivo'])) {
        echo "<div class='alert alert-danger'>No se recibió archivo.</div>";
        return;
    }

    $nombre = basename($_FILES['archivo']['name']);
    $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));

    if ($ext !== "pak") {
        echo "<div class='alert alert-danger'>Solo se permiten archivos .pak</div>";
        return;
    }

    $destino = $PAKS_DIR . "/" . $nombre;

    if (move_uploaded_file($_FILES['archivo']['tmp_name'], $destino)) {
        echo "<div class='alert alert-success'>Archivo subido correctamente.</div>";
    } else {
        echo "<div class='alert alert-danger'>Error al subir archivo.</div>";
    }

    return; // evitar que recargue la tabla aquí
}


/* ======================================================
   ACCIONES: eliminar, habilitar, deshabilitar
====================================================== */

if (isset($_POST['action']) && isset($_POST['file'])) {
    $file = basename($_POST['file']);
    $path = $PAKS_DIR . "/" . $file;

    // ELIMINAR
    if ($_POST['action'] === 'delete') {
        if (file_exists($path)) {
            unlink($path);
            $msg = "<div class='alert alert-success'>Archivo eliminado.</div>";
        }
    }

    // DESHABILITAR (pak → disable)
    if ($_POST['action'] === 'disable') {
        $new = preg_replace('/\.pak$/i', '.disable', $file);
        rename($path, $PAKS_DIR . "/" . $new);
        $msg = "<div class='alert alert-warning'>Archivo deshabilitado.</div>";
    }

    // HABILITAR (disable → pak)
    if ($_POST['action'] === 'enable') {
        $new = preg_replace('/\.disable$/i', '.pak', $file);
        rename($path, $PAKS_DIR . "/" . $new);
        $msg = "<div class='alert alert-success'>Archivo habilitado.</div>";
    }
}

/* ======================================================
   LISTAR ARCHIVOS
====================================================== */

$pak_files = glob($PAKS_DIR . "/*.pak");
$disable_files = glob($PAKS_DIR . "/*.disable");

?>
<style>
.pak-box {
    background: rgba(25,25,25,0.93);
    border-radius: 15px;
    padding: 20px;
    border: 1px solid rgba(255,255,255,0.08);
    backdrop-filter: blur(5px);
}

.pak-btn {
    padding: 6px 10px;
    border-radius: 8px;
    font-weight: 600;
}

.pak-row:hover {
    background: rgba(255,255,255,0.05);
}

.progress {
    display: none;
    height: 7px;
}
</style>

<div class="container-fluid text-light">

    <h2 class="fw-bold mb-4">📦 PAK Mods Manager</h2>

    <?= $msg ?>

    <div class="pak-box mb-4">

        <!-- SUBIR ARCHIVO -->
        <form id="pakUploadForm" class="mb-4" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload">
            <div class="input-group">
                <input type="file" class="form-control" name="archivo" accept=".pak" required>
                <button class="btn btn-primary pak-btn">Subir .PAK</button>
            </div>

            <div class="progress mt-2" id="pakUploadProgressWrap">
                <div class="progress-bar bg-info" id="pakUploadProgress"></div>
            </div>
        </form>

        <!-- TABLA DE ARCHIVOS ACTIVOS -->
        <h4 class="fw-bold mt-4 mb-3 text-success">🟢 Mods Activos (.pak)</h4>
        <table class="table table-dark table-hover">
            <thead>
                <tr>
                    <th>Archivo</th>
                    <th style="width:150px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($pak_files): foreach ($pak_files as $file): ?>
                    <?php $name = basename($file); ?>
                    <tr class="pak-row">
                        <td>📦 <?= htmlspecialchars($name) ?></td>
                        <td>
                            <!-- Deshabilitar -->
                            <form class="d-inline pakActionForm">
                                <input type="hidden" name="action" value="disable">
                                <input type="hidden" name="file" value="<?= htmlspecialchars($name) ?>">
                                <button class="btn btn-warning btn-sm">Deshabilitar</button>
                            </form>

                            <!-- Eliminar -->
                            <form class="d-inline pakActionForm">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="file" value="<?= htmlspecialchars($name) ?>">
                                <button class="btn btn-danger btn-sm">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="2">No hay mods activos (.pak)</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- TABLA DE ARCHIVOS DESHABILITADOS -->
        <h4 class="fw-bold mt-4 mb-3 text-warning">🟡 Mods Deshabilitados (.disable)</h4>
        <table class="table table-dark table-hover">
            <thead>
                <tr>
                    <th>Archivo</th>
                    <th style="width:150px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($disable_files): foreach ($disable_files as $file): ?>
                    <?php $name = basename($file); ?>
                    <tr class="pak-row">
                        <td>⚠️ <?= htmlspecialchars($name) ?></td>
                        <td>
                            <!-- Habilitar -->
                            <form class="d-inline pakActionForm">
                                <input type="hidden" name="action" value="enable">
                                <input type="hidden" name="file" value="<?= htmlspecialchars($name) ?>">
                                <button class="btn btn-success btn-sm">Habilitar</button>
                            </form>

                            <!-- Eliminar -->
                            <form class="d-inline pakActionForm">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="file" value="<?= htmlspecialchars($name) ?>">
                                <button class="btn btn-danger btn-sm">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="2">No hay mods deshabilitados (.disable)</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>
</div>


<script>
// =====================
// SUBIR ARCHIVO PAK
// =====================
$(document).on('submit', '#pakUploadForm', function(e){
    e.preventDefault();

    let formData = new FormData(this);

    $("#pakUploadProgressWrap").show();
    $("#pakUploadProgress").css("width", "0%");

    $.ajax({
        url: "pages/pak_mods.php",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        xhr: function(){
            let xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener("progress", function(evt){
                if (evt.lengthComputable) {
                    let percent = Math.round((evt.loaded / evt.total) * 100);
                    $("#pakUploadProgress").css("width", percent + "%");
                }
            }, false);
            return xhr;
        },
        success: function(res){
            $("#main").html(res);
        }
    });
});


// =====================
// ACCIONES (enable/disable/delete)
// =====================
$(document).on('submit', '.pakActionForm', function(e){
    e.preventDefault();
    let formData = $(this).serialize();

    $.post("pages/pak_mods.php", formData, function(res){
        $("#main").html(res);
    });
});
</script>
