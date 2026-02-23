<?php
require_once "../config.php";

if (!is_logged_in()) {
    echo "<div class='alert alert-danger'>La sesión expiró. Inicia sesión nuevamente.</div>";
    return;
}

$PD_DIR = PALDEFENDER_DIR;

// Archivos editables dentro del directorio
$editableFiles = [
    "config.json",
    "WhiteList.json"
];

// Asegurar que la carpeta existe
if (!is_dir($PD_DIR)) {
    mkdir($PD_DIR, 0777, true);
}

/* ======================================================
   LOGS: RUTAS (DERIVADAS DESDE PALDEFENDER_DIR)
====================================================== */

$LOG_BASE = rtrim(str_replace('\\','/',$PD_DIR), '/') . "/Logs";

$logFolders = [
    "Cheats"  => $LOG_BASE . "/Cheats",
    "Consola" => $LOG_BASE,
    "Network" => $LOG_BASE . "/Network",
    "RESTAPI" => $LOG_BASE . "/RESTAPI",
];

/* ======================================================
   HELPERS SEGURIDAD / PATHS
====================================================== */

function norm_path(string $p): string {
    return str_replace('\\','/',$p);
}

function safe_join(string $dir, string $file): string {
    $dir = rtrim(norm_path($dir), '/');
    $file = basename($file); // evita ../
    return $dir . "/" . $file;
}

function is_within_dir(string $file, string $dir): bool {
    $rf = realpath($file);
    $rd = realpath($dir);
    if (!$rf || !$rd) return false;
    $rf = norm_path($rf);
    $rd = rtrim(norm_path($rd), '/') . '/';
    return str_starts_with($rf, $rd);
}

/* ======================================================
   CARGAR ARCHIVO PARA EDITAR (MODAL)
====================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "load_file") {

    $file = basename($_POST["file"] ?? "");
    $path = norm_path($PD_DIR) . "/" . $file;

    if (!$file || !file_exists($path)) {
        echo "<div class='alert alert-danger'>No se pudo cargar el archivo.</div>";
        exit;
    }

    $contenido = htmlspecialchars(file_get_contents($path));
    ?>

    <!-- MODAL -->
    <div class="modal-header">
        <h5 class="modal-title">Editar <?= htmlspecialchars($file) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>

    <div class="modal-body">
        <textarea id="pdEditorArea" class="form-control" style="height:400px; background:#111; color:#0dfabf; font-family:monospace;"><?= $contenido ?></textarea>
    </div>

    <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-primary" onclick="pdSaveFile('<?= htmlspecialchars($file) ?>')">Guardar</button>
    </div>

    <?php
    exit;
}

/* ======================================================
   GUARDAR ARCHIVO MODIFICADO
====================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "save_file") {

    $file = basename($_POST["file"] ?? "");
    $path = norm_path($PD_DIR) . "/" . $file;
    $contenido = $_POST["contenido"] ?? "";

    if (!$file) {
        echo "<div class='alert alert-danger'>Archivo inválido.</div>";
        exit;
    }

    if (!is_writable(dirname($path))) {
        echo "<div class='alert alert-danger'>No se puede escribir el archivo.</div>";
        exit;
    }

    file_put_contents($path, $contenido);

    echo "<div class='alert alert-success'>Archivo guardado correctamente.</div>";
    exit;
}

/* ======================================================
   LISTAR LOGS (MODAL)
====================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "load_logs") {

    $key = $_POST["folder"] ?? "";

    if (!isset($logFolders[$key])) {
        echo "<div class='alert alert-danger'>Carpeta inválida.</div>";
        exit;
    }

    $dir = $logFolders[$key];

    if (!is_dir($dir)) {
        echo "<div class='alert alert-warning'>Carpeta no encontrada: <code>" . htmlspecialchars($dir) . "</code></div>";
        exit;
    }

    $files = glob(rtrim($dir, "/\\") . "/*.log") ?: [];

    // Ordenar por nombre DESC (más “nuevo” primero según nombre)
    usort($files, function($a, $b){
        return strcmp(basename($b), basename($a));
    });
    ?>

    <div class="modal-header">
        <h5 class="modal-title">Logs - <?= htmlspecialchars($key) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>

    <div class="modal-body">

        <?php if (empty($files)): ?>
            <div class="alert alert-info">No hay archivos LOG en esta carpeta.</div>
        <?php else: ?>

        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle">
                <thead>
                    <tr>
                        <th>Archivo</th>
                        <th style="width:200px;">Fecha</th>
                        <th style="width:220px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($files as $full): ?>
                    <?php $name = basename($full); ?>
                    <tr>
                        <td><?= htmlspecialchars($name) ?></td>
                        <td><small><?= date("Y-m-d H:i:s", @filemtime($full)) ?></small></td>
                        <td>
                            <button class="btn btn-info btn-sm"
                                    onclick="pdViewLog('<?= htmlspecialchars($key) ?>','<?= htmlspecialchars($name) ?>')">
                                Ver
                            </button>

                            <a class="btn btn-success btn-sm"
                               href="pages/paldefender.php?action=download_log&folder=<?= urlencode($key) ?>&file=<?= urlencode($name) ?>">
                               Descargar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php endif; ?>

    </div>

    <?php
    exit;
}

/* ======================================================
   VER LOG (SOLO LECTURA)
====================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "view_log") {

    $key  = $_POST["folder"] ?? "";
    $file = basename($_POST["file"] ?? "");

    if (!isset($logFolders[$key])) {
        echo "<div class='alert alert-danger'>Carpeta inválida.</div>";
        exit;
    }

    if (!$file || strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'log') {
        echo "<div class='alert alert-danger'>Archivo inválido.</div>";
        exit;
    }

    $dir  = $logFolders[$key];
    $path = safe_join($dir, $file);

    if (!file_exists($path) || !is_within_dir($path, $dir)) {
        echo "<div class='alert alert-danger'>No se pudo abrir el archivo.</div>";
        exit;
    }

    $contenido = htmlspecialchars(@file_get_contents($path));
    ?>

    <div class="modal-header">
        <h5 class="modal-title">Visualizar LOG</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>

    <div class="modal-body">
        <textarea class="form-control"
                  style="height:520px; background:#000; color:#0f0; font-family:monospace;"
                  readonly><?= $contenido ?></textarea>
    </div>

    <?php
    exit;
}

/* ======================================================
   DESCARGAR LOG
====================================================== */

if (($_GET["action"] ?? "") === "download_log") {

    $key  = $_GET["folder"] ?? "";
    $file = basename($_GET["file"] ?? "");

    if (!isset($logFolders[$key])) {
        exit("Carpeta inválida");
    }

    if (!$file || strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'log') {
        exit("Archivo inválido");
    }

    $dir  = $logFolders[$key];
    $path = safe_join($dir, $file);

    if (!file_exists($path) || !is_within_dir($path, $dir)) {
        exit("No se pudo descargar el archivo");
    }

    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"" . basename($path) . "\"");
    header("Content-Length: " . filesize($path));
    readfile($path);
    exit;
}
?>

<style>
.pd-box {
    background: rgba(25,25,25,0.93);
    padding: 20px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.1);
    backdrop-filter: blur(5px);
}
.pd-row:hover {
    background: rgba(255,255,255,0.05);
}
</style>

<div class="container-fluid text-light">

    <h2 class="fw-bold mb-3">🛡 PalDefender</h2>

    <div class="alert alert-info">
        Para usar esta sección debes tener instalado:<br>
        ✔ <b>UE4SS</b> → <a href="https://github.com/Okaetsu/RE-UE4SS/releases" target="_blank">Descargar</a><br>
        ✔ <b>PalDefender</b> → <a href="https://github.com/Ultimeit/PalDefender" target="_blank">Descargar</a>
    </div>

    <div class="pd-box mt-3">
        <h4 class="fw-bold mb-3">📂 Archivos configurables</h4>

        <table class="table table-dark table-hover">
            <thead>
                <tr>
                    <th>Archivo</th>
                    <th style="width:120px;">Acciones</th>
                </tr>
            </thead>
            <tbody>

                <?php foreach ($editableFiles as $file): ?>
                    <tr class="pd-row">
                        <td><?= htmlspecialchars($file) ?></td>
                        <td>
                            <button class="btn btn-primary btn-sm"
                                onclick="pdOpenFile('<?= htmlspecialchars($file) ?>')">
                                Editar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>

            </tbody>
        </table>
    </div>

    <!-- ✅ NUEVO: SECCIÓN LOGS (sin cambiar tu texto/diseño base) -->
    <div class="pd-box mt-4">
        <h4 class="fw-bold mb-3">📜 Logs</h4>

        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle text-center">
                <thead>
                    <tr>
                        <th>Sección</th>
                        <th style="width:200px;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logFolders as $name => $path): ?>
                        <tr class="pd-row">
                            <td class="text-start">
                                <b><?= htmlspecialchars($name) ?></b><br>
                                <small class="text-muted"><?= htmlspecialchars($path) ?></small>
                            </td>
                            <td>
                                <button class="btn btn-secondary btn-sm"
                                        onclick="pdLoadLogs('<?= htmlspecialchars($name) ?>')">
                                    Abrir
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="text-muted">
            <small>Los archivos se muestran ordenados del más reciente al más antiguo (por nombre).</small>
        </div>
    </div>

</div>

<!-- MODAL -->
<div class="modal fade" id="pdEditorModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content" id="pdModalContent">
      <!-- Contenido AJAX -->
    </div>
  </div>
</div>

<script>

// Abrir editor en modal
function pdOpenFile(archivo) {

    $.post("pages/paldefender.php", { action:"load_file", file:archivo }, function(html){
        $("#pdModalContent").html(html);
        let modal = new bootstrap.Modal(document.getElementById('pdEditorModal'));
        modal.show();
    });

}

// Guardar archivo editado
function pdSaveFile(archivo) {

    let contenido = $("#pdEditorArea").val();

    $.post("pages/paldefender.php", {
        action:"save_file",
        file:archivo,
        contenido:contenido
    }, function(resp){
        $("#pdModalContent").html(resp);
    });

}

// ✅ Abrir lista de logs
function pdLoadLogs(folder){
    $.post("pages/paldefender.php",
        {action:"load_logs", folder:folder},
        function(html){
            $("#pdModalContent").html(html);
            let modal = new bootstrap.Modal(document.getElementById('pdEditorModal'));
            modal.show();
        }
    );
}

// ✅ Ver log en el mismo modal
function pdViewLog(folder, file){
    $.post("pages/paldefender.php",
        {action:"view_log", folder:folder, file:file},
        function(html){
            $("#pdModalContent").html(html);
        }
    );
}

</script>
