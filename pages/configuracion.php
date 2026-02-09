<?php
require_once "../config.php";

if (!is_logged_in()) {
    echo '<div class="alert alert-danger">Sesión expirada. Vuelve a iniciar sesión.</div>';
    return;
}

$archivo = PALWORLD_SETTINGS_FILE;

/**
 * Convierte OptionSettings=() a array
 */
function parseOptionSettings($cadena) {
    $cadena = trim($cadena);
    $cadena = trim($cadena, "OptionSettings=(");
    $cadena = rtrim($cadena, ")");

    $resultado = [];
    $pares = explode(",", $cadena);

    foreach ($pares as $p) {
        $tmp = explode("=", $p, 2);
        if (count($tmp) == 2) {
            $resultado[$tmp[0]] = $tmp[1];
        }
    }

    unset($resultado["CrossplayPlatforms"]);

    return $resultado;
}

/**
 * Leer valores actuales
 */
$contenido = file($archivo);
$settings = [];

foreach ($contenido as $linea) {
    if (strpos($linea, "OptionSettings=(") !== false) {
        $settings = parseOptionSettings($linea);
        break;
    }
}
?>

<div class="container text-light">
    <h2>⚙️ Configuración del Servidor Palworld</h2>

    <div id="msg"></div>

    <form id="formConfig">
        <div class="row">

            <?php foreach ($settings as $clave => $valor): ?>
                <div class="col-md-4 mb-3">
                    <label class="form-label"><?= htmlspecialchars($clave) ?></label>
                    <input 
                        type="text" 
                        class="form-control" 
                        name="<?= htmlspecialchars($clave) ?>" 
                        value="<?= htmlspecialchars($valor) ?>">
                </div>
            <?php endforeach; ?>

        </div>

        <button class="btn btn-primary mt-3">💾 Guardar Cambios</button>
    </form>
</div>

<script>
$(document).off("submit", "#formConfig");
$(document).on("submit", "#formConfig", function(e) {
    e.preventDefault();

    let datos = $(this).serialize();

    $("#msg").html('<div class="alert alert-info">Guardando cambios...</div>');

    $.post("pages/configuracion_save.php", datos, function(resp){
        
        $("#msg").html('<div class="alert alert-success">✔ Configuración guardada correctamente</div>');

        // Recargar la UI sin perder diseño
        $("#main").load("pages/configuracion.php");

    }).fail(function(){
        $("#msg").html('<div class="alert alert-danger">❌ Error al guardar</div>');
    });

});
</script>
