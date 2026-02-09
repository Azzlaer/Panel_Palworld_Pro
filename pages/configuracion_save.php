<?php
require_once "../config.php";

if (!is_logged_in()) {
    http_response_code(403);
    exit("No autorizado");
}

$archivo = PALWORLD_SETTINGS_FILE;

/**
 * Construye OptionSettings()
 */
function buildOptionSettings($arr) {
    $partes = [];
    foreach ($arr as $k => $v) {
        $partes[] = "$k=$v";
    }
    return "OptionSettings=(" . implode(",", $partes) . ")";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $contenido = file($archivo);
    $opciones = [];

    foreach ($_POST as $k => $v) {
        $opciones[$k] = $v;
    }

    foreach ($contenido as $i => $linea) {
        if (strpos($linea, "OptionSettings=(") !== false) {
            $contenido[$i] = buildOptionSettings($opciones) . "\n";
        }
    }

    file_put_contents($archivo, implode("", $contenido));

    echo "OK";
}
