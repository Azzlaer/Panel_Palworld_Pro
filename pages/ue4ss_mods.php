<?php
require_once "../config.php";

if (!is_logged_in()) {
    echo "<div class='alert alert-danger'>Sesión expirada.</div>";
    return;
}

$MODS_DIR = PAL_UE4SS_MODS_DIR;
$modsJsonFile = PAL_MODS_JSON;

/* ======================================================
   CREAR CARPETA SI NO EXISTE
====================================================== */
if (!is_dir($MODS_DIR)) {
    mkdir($MODS_DIR, 0777, true);
}

/* ======================================================
   OBTENER MODS ACTIVOS (mod_enabled=true)
====================================================== */
function getEnabledMods($file) {

    if (!file_exists($file)) return [];

    $data = json_decode(file_get_contents($file), true);

    if (!is_array($data)) return [];

    $enabled = [];

    foreach ($data as $m) {
        if (!empty($m['mod_enabled'])) {
            $enabled[] = $m['mod_name'];
        }
    }

    return $enabled;
}

/* ======================================================
   SUBIR ZIP
====================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST"
    && (($_POST["action"] ?? "") === "upload")) {

    if (!isset($_FILES["archivo"])) exit;

    $zipName = basename($_FILES["archivo"]["name"]);
    $ext = strtolower(pathinfo($zipName, PATHINFO_EXTENSION));

    if ($ext !== "zip") {
        echo "<div class='alert alert-danger'>Solo archivos ZIP.</div>";
        exit;
    }

    $zipPath = $MODS_DIR . "/" . $zipName;

    if (!move_uploaded_file($_FILES["archivo"]["tmp_name"], $zipPath)) {
        echo "<div class='alert alert-danger'>Error al subir ZIP.</div>";
        exit;
    }

    $zip = new ZipArchive;

    if ($zip->open($zipPath) === TRUE) {

        $folderName = trim($zip->getNameIndex(0), "/");
        $dest = $MODS_DIR . "/" . $folderName;

        if (is_dir($dest)) {
            system('rd /s /q "' . $dest . '"');
        }

        $zip->extractTo($MODS_DIR);
        $zip->close();

        unlink($zipPath);

        echo "<div class='alert alert-success'>
            Mod instalado correctamente: <b>$folderName</b>
        </div>";
        exit;
    }

    echo "<div class='alert alert-danger'>No se pudo abrir ZIP.</div>";
    exit;
}

/* ======================================================
   ELIMINAR MOD
====================================================== */
if (($_POST["action"] ?? "") === "delete_mod") {

    $folder = basename($_POST["folder"] ?? "");
    $path = $MODS_DIR . "/" . $folder;

    if (is_dir($path)) {
        system('rd /s /q "' . $path . '"');
        echo "<div class='alert alert-success'>Mod eliminado.</div>";
    } else {
        echo "<div class='alert alert-danger'>No existe el mod.</div>";
    }

    exit;
}

/* ======================================================
   CARGAR EDITOR mods.json
====================================================== */
if (($_POST["action"] ?? "") === "load_json_editor") {

    if (!file_exists($modsJsonFile)) {
        echo "<div class='alert alert-danger'>mods.json no existe.</div>";
        exit;
    }

    $contenido = htmlspecialchars(file_get_contents($modsJsonFile));
?>

<div class="mt-4">
    <h4>📝 Editar mods.json</h4>

    <form id="saveModsJsonForm">

        <textarea name="contenido"
        style="width:100%;height:400px;background:#000;color:#0f0;
        font-family:monospace;border-radius:8px;padding:10px;">
<?= $contenido ?>
        </textarea>

        <input type="hidden" name="action" value="save_json">

        <button class="btn btn-primary mt-2">
            Guardar Cambios
        </button>
    </form>
</div>

<script>
$("#saveModsJsonForm").on("submit", function(e){
    e.preventDefault();

    $.post("pages/ue4ss_mods.php",
        $(this).serialize(),
        function(){
            $("#main").load("pages/ue4ss_mods.php");
        }
    );
});
</script>

<?php
exit;
}

/* ======================================================
   GUARDAR mods.json
====================================================== */
if (($_POST["action"] ?? "") === "save_json") {

    file_put_contents(
        $modsJsonFile,
        $_POST["contenido"] ?? ""
    );

    echo "<div class='alert alert-success'>
        mods.json guardado correctamente.
    </div>";
    exit;
}

/* ======================================================
   LISTADOS
====================================================== */

$mods = array_filter(
    glob($MODS_DIR . "/*"),
    'is_dir'
);

$enabledMods = getEnabledMods($modsJsonFile);

?>

<style>
.mod-box {
    background: rgba(25,25,25,.93);
    border-radius: 15px;
    padding: 20px;
}
</style>

<div class="container-fluid text-light">

<h2 class="fw-bold mb-4">🧩 UE4SS Mods Manager</h2>

<div class="mod-box">

<div class="alert alert-info">
ZIP → carpeta del mod → Scripts
</div>

<!-- ================= MODS ACTIVOS ================= -->
<h4 class="fw-bold">🟢 Mods Activos (mods.json)</h4>

<ul class="list-group mb-4">

<?php if ($enabledMods): ?>
<?php foreach ($enabledMods as $m): ?>
<li class="list-group-item bg-dark text-light">
🧩 <?= htmlspecialchars($m) ?>
</li>
<?php endforeach; ?>
<?php else: ?>
<li class="list-group-item bg-dark text-muted">
No hay mods activos
</li>
<?php endif; ?>

</ul>

<!-- ================= SUBIR ================= -->
<form id="modUploadForm" enctype="multipart/form-data">

<input type="hidden" name="action" value="upload">

<div class="input-group mb-4">
<input type="file" name="archivo" accept=".zip"
class="form-control" required>

<button class="btn btn-primary">
Subir ZIP
</button>
</div>

</form>

<!-- ================= MODS INSTALADOS ================= -->
<h4>📁 Mods Instalados</h4>

<table class="table table-dark">
<tbody>

<?php foreach ($mods as $m):
$name = basename($m); ?>

<tr>
<td>🧩 <?= htmlspecialchars($name) ?></td>
<td width="120">

<form class="modDeleteForm">
<input type="hidden" name="action" value="delete_mod">
<input type="hidden" name="folder"
value="<?= htmlspecialchars($name) ?>">
<button class="btn btn-danger btn-sm">
Eliminar
</button>
</form>

</td>
</tr>

<?php endforeach; ?>

</tbody>
</table>

<button class="btn btn-warning"
onclick="loadModsJson()">
Editar mods.json
</button>

<div id="modsJsonEditor"></div>

</div>
</div>

<script>

$("#modUploadForm").on("submit", function(e){
    e.preventDefault();

    let data = new FormData(this);

    $.ajax({
        url:"pages/ue4ss_mods.php",
        type:"POST",
        data:data,
        contentType:false,
        processData:false,
        success:()=>$("#main").load("pages/ue4ss_mods.php")
    });
});

$(".modDeleteForm").on("submit", function(e){
    e.preventDefault();

    $.post("pages/ue4ss_mods.php",
        $(this).serialize(),
        ()=>$("#main").load("pages/ue4ss_mods.php"));
});

function loadModsJson(){
    $.post("pages/ue4ss_mods.php",
        {action:"load_json_editor"},
        res=>$("#modsJsonEditor").html(res));
}

</script>
