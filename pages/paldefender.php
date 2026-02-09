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
   CARGAR ARCHIVO PARA EDITAR (MODAL)
====================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "load_file") {

    $file = basename($_POST["file"]);
    $path = $PD_DIR . "/" . $file;

    if (!file_exists($path)) {
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
        <button class="btn btn-primary" onclick="pdSaveFile('<?= $file ?>')">Guardar</button>
    </div>

    <?php
    exit;
}

/* ======================================================
   GUARDAR ARCHIVO MODIFICADO
====================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "save_file") {

    $file = basename($_POST["file"]);
    $path = $PD_DIR . "/" . $file;
    $contenido = $_POST["contenido"] ?? "";

    if (!is_writable(dirname($path))) {
        echo "<div class='alert alert-danger'>No se puede escribir el archivo.</div>";
        exit;
    }

    file_put_contents($path, $contenido);

    echo "<div class='alert alert-success'>Archivo guardado correctamente.</div>";
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
                                onclick="pdOpenFile('<?= $file ?>')">
                                Editar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>

            </tbody>
        </table>
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
</script>
