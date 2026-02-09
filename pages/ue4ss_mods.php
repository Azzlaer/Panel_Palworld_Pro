<?php
require_once "../config.php";

if (!is_logged_in()) {
    echo "<div class='alert alert-danger'>Sesión expirada. Inicia sesión nuevamente.</div>";
    return;
}

$MODS_DIR = PAL_UE4SS_MODS_DIR;

/* Crear carpeta si no existe */
if (!is_dir($MODS_DIR)) {
    mkdir($MODS_DIR, 0777, true);
}

$msg = "";

/* ======================================================
   SUBIR ZIP → EXTRAER → ELIMINAR ZIP
====================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "upload") {

    if (!isset($_FILES["archivo"]) || $_FILES["archivo"]["error"] !== 0) {
        echo "<div class='alert alert-danger'>No se pudo subir el archivo ZIP.</div>";
        return;
    }

    $zipName = basename($_FILES["archivo"]["name"]);
    $ext = strtolower(pathinfo($zipName, PATHINFO_EXTENSION));

    if ($ext !== "zip") {
        echo "<div class='alert alert-danger'>Solo se permiten archivos .ZIP</div>";
        return;
    }

    // Ruta temporal donde se guardará el ZIP
    $zipPath = $MODS_DIR . "/" . $zipName;

    if (!move_uploaded_file($_FILES["archivo"]["tmp_name"], $zipPath)) {
        echo "<div class='alert alert-danger'>Error al guardar archivo ZIP temporal.</div>";
        return;
    }

    // Extraer ZIP
    $zip = new ZipArchive;
    if ($zip->open($zipPath) === TRUE) {

        $folderName = trim($zip->getNameIndex(0), "/");

        if (!$folderName) {
            unlink($zipPath);
            echo "<div class='alert alert-danger'>El ZIP no contiene una carpeta principal válida.</div>";
            return;
        }

        $destination = $MODS_DIR . "/" . $folderName;

        // Si ya existe la carpeta, eliminarla completamente
        if (is_dir($destination)) {
            system('rd /s /q "' . $destination . '"');
        }

        $zip->extractTo($MODS_DIR);
        $zip->close();

        unlink($zipPath);

        echo "<div class='alert alert-success'>Mod instalado correctamente: <b>$folderName</b></div>";
        return;
    } else {
        echo "<div class='alert alert-danger'>No se pudo abrir el ZIP.</div>";
        return;
    }
}

/* ======================================================
   ELIMINAR CARPETA COMPLETA DEL MOD
====================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "delete_mod") {

    $folder = basename($_POST["folder"]);
    $path = $MODS_DIR . "/" . $folder;

    if (is_dir($path)) {
        system('rd /s /q "' . $path . '"');
        echo "<div class='alert alert-success'>Mod eliminado: <b>$folder</b></div>";
        return;
    }

    echo "<div class='alert alert-danger'>No se pudo eliminar el mod.</div>";
    return;
}

/* ======================================================
   CARGAR EDITOR DE mods.json
====================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "load_json_editor") {

    $modsJson = PAL_MODS_JSON;

    if (!file_exists($modsJson)) {
        echo "<div class='alert alert-danger'>No existe el archivo mods.json</div>";
        exit;
    }

    $contenido = htmlspecialchars(file_get_contents($modsJson));

    ?>

    <style>
    .editor-box {
        background: rgba(25,25,25,0.93);
        padding: 20px;
        border-radius: 15px;
        border: 1px solid rgba(255,255,255,0.07);
    }
    textarea.mods-editor-area {
        width: 100%;
        height: 400px;
        background: #0f0f0f;
        color: #00ffbf;
        padding: 12px;
        border-radius: 8px;
        border: 1px solid #444;
        font-family: monospace;
        font-size: 15px;
    }
    </style>

    <div class="editor-box mt-4">
        <h4 class="fw-bold mb-3">📝 Editar mods.json</h4>

        <form id="saveModsJsonForm">
            <textarea name="contenido" class="mods-editor-area"><?= $contenido ?></textarea>
            <input type="hidden" name="action" value="save_json">
            <button class="btn btn-primary mt-3">Guardar Cambios</button>
        </form>
    </div>

    <script>
    $("#saveModsJsonForm").on("submit", function(e){
        e.preventDefault();
        $.post("pages/ue4ss_mods.php", $(this).serialize(), function(res){
            $("#modsJsonEditor").html(res);
        });
    });
    </script>

    <?php
    exit;
}

/* ======================================================
   GUARDAR mods.json
====================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "save_json") {

    $modsJson = PAL_MODS_JSON;
    $contenido = $_POST["contenido"] ?? "";

    file_put_contents($modsJson, $contenido);

    echo "<div class='alert alert-success'>mods.json guardado correctamente.</div>";
    exit;
}

/* ======================================================
   LISTAR MODS INSTALADOS (carpetas dentro de Mods/)
====================================================== */
$mods = array_filter(glob($MODS_DIR . "/*"), 'is_dir');

?>

<style>
.mod-box {
    background: rgba(25,25,25,0.93);
    border-radius: 15px;
    padding: 20px;
    border: 1px solid rgba(255,255,255,0.08);
    backdrop-filter: blur(5px);
}
.mod-row:hover {
    background: rgba(255,255,255,0.05);
}
.mod-btn {
    padding: 6px 10px;
    border-radius: 8px;
    font-weight: 600;
}
.progress {
    display: none;
    height: 7px;
}
</style>

<div class="container-fluid text-light">

    <h2 class="fw-bold mb-4">🧩 UE4SS Mods Manager</h2>

    <?= $msg ?>

    <div class="mod-box">

        <div class="alert alert-info">
            <b>Cómo instalar un mod UE4SS:</b><br>
            • Debe estar comprimido en <b>ZIP</b><br>
            • Debe contener una carpeta con el nombre del mod<br>
            • Debe contener una carpeta <b>Scripts</b><br>
            • Ejemplo:<br>
            <code>PalKillRewards.zip → /Mods/PalKillRewards/Scripts/...</code>
        </div>

        <!-- SUBIR ZIP -->
        <form id="modUploadForm" class="mb-4" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload">
            <div class="input-group">
                <input type="file" name="archivo" accept=".zip" class="form-control" required>
                <button class="btn btn-primary mod-btn">Subir Mod ZIP</button>
            </div>

            <div class="progress mt-2" id="modUploadProgressWrap">
                <div class="progress-bar bg-info" id="modUploadProgress"></div>
            </div>
        </form>

        <!-- LISTA DE MODS -->
        <h4 class="fw-bold my-3">📁 Mods Instalados</h4>

        <table class="table table-dark table-hover">
            <thead>
                <tr>
                    <th>Carpeta del Mod</th>
                    <th style="width:150px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($mods): foreach ($mods as $folderPath): ?>
                <?php $name = basename($folderPath); ?>
                <tr class="mod-row">
                    <td>🧩 <?= htmlspecialchars($name) ?></td>
                    <td>
                        <form class="d-inline modDeleteForm">
                            <input type="hidden" name="action" value="delete_mod">
                            <input type="hidden" name="folder" value="<?= htmlspecialchars($name) ?>">
                            <button class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="2">No hay mods instalados.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- EDITOR JSON -->
        <button class="btn btn-warning" onclick="loadModsJson()">Editar mods.json</button>
        <div id="modsJsonEditor" class="mt-4"></div>

    </div>
</div>

<script>
// =====================
// Subir ZIP
// =====================
$("#modUploadForm").on("submit", function(e){
    e.preventDefault();

    let data = new FormData(this);

    $("#modUploadProgressWrap").show();
    $("#modUploadProgress").css("width","0%");

    $.ajax({
        url: "pages/ue4ss_mods.php",
        type: "POST",
        data: data,
        contentType: false,
        processData: false,
        xhr: function(){
            const xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener("progress", evt => {
                if (evt.lengthComputable) {
                    let percent = (evt.loaded / evt.total)*100;
                    $("#modUploadProgress").css("width", percent+"%");
                }
            });
            return xhr;
        },
        success: res => $("#main").html(res)
    });
});

// =====================
// Eliminar mod
// =====================
$(".modDeleteForm").on("submit", function(e){
    e.preventDefault();
    $.post("pages/ue4ss_mods.php", $(this).serialize(), res => $("#main").html(res));
});

// =====================
// Abrir editor mods.json
// =====================
function loadModsJson(){
    $.post("pages/ue4ss_mods.php", { action:"load_json_editor" }, res => {
        $("#modsJsonEditor").html(res);
    });
}
</script>
