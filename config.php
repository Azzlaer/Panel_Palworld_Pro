<?php
/**
 * config.php
 * Configuración principal del Panel 7 Days to Die
 */

// ---- INICIO DE SESIÓN ----
if (session_status() === PHP_SESSION_NONE) {
    // Ajusta el nombre de la sesión si quieres aislarla
    session_name('panel_palworld');
    session_start();
}

define('ADMIN_USER', getenv('ADMIN_USER') ?: 'Azzlaer');
define('ADMIN_PASS', getenv('ADMIN_PASS') ?: '35027595');
define('FOOTER_TEXT', 'Panel Palworld © ' . date('Y'));

define('PAL_MODS_JSON', 'D:/Servidores/Palworld/pal1/Pal/Binaries/Win64/Mods/mods.json');
define('PAL_PLAYERS_DIR', 'D:/Servidores/Palworld/pal1/Pal/Saved/SaveGames/0/D67F990A4E251B54D90A6C8E6DDE0062/Players');
define('PAL_PAKS_DIR', 'D:/Servidores/Palworld/pal1/Pal/Content/Paks');
define('PAL_UE4SS_MODS_DIR', 'D:/Servidores/Palworld/pal1/Pal/Binaries/Win64/ue4ss/Mods');
define('PALDEFENDER_DIR', 'D:/Servidores/Palworld/pal1/Pal/Binaries/Win64/PalDefender');

/* ================= PALWORLD REST API ================= */
define('PAL_REST_URL', 'http://127.0.0.1:8212');
define('PAL_ADMIN_USER', 'Admin');
define('PAL_ADMIN_PASS', '35027595*'); // PalSettings.ini
define('PAL_REST_TIMEOUT', 5);


define('USERS_AUTO_REFRESH_DEFAULT', false);
define('USERS_AUTO_REFRESH_INTERVAL', 15);


// Palworld server config (shared via JSON)
define('PALWORLD_JSON', __DIR__ . '/palworld_server.json');
define('PALWORLD_SERVER_JSON', __DIR__ . '/palworld_server.json');

// Python
define('PYTHON_EXE', 'C:\\Program Files\\Python312\\python.exe');



define('PAL_SERVER_NAME', 'Palworld Dedicated Server');
define('PAL_EXE', 'D:/Servidores/Palworld/pal1/PalServer.exe');
define('PAL_INSTALL_DIR', dirname(PAL_EXE));
define('PAL_SERVER_DIR', 'D:/Servidores/Palworld/pal1');
define('PALWORLD_SETTINGS_FILE', 'D:\Servidores\Palworld\pal1\Pal\Saved\Config\WindowsServer\PalWorldSettings.ini');

/* ================= DISCORD BOT ================= */

define('DISCORD_BOT_NAME', 'PalMonitor_1.exe');
define('DISCORD_BOT_EXE', 'D:/Servidores/Palworld/pal1/PalMonitor_1.exe');


define('STEAMCMD_EXE', 'D:/Servidores/Steam/steamcmd.exe');
define('PAL_APP_ID', '2394010');

define('FTP_HOST', 'localhost');
define('FTP_USER', 'pal1');
define('FTP_PASS', '35027595');
define('FTP_ROOT', '/');


define('PAL_RCON_HOST', '127.0.0.1');   // o IP pública si aplica
define('PAL_RCON_PORT', 25575);         // mismo que PalSettings.ini
define('PAL_RCON_PASS', '35027595*');            // AdminPassword del PalSettings.ini
define('PAL_RCON_TIMEOUT', 3);          // segundos






/**
 * Redirige a una URL de forma segura.
 */
function redirect(string $url) {
    header("Location: " . $url);
    exit;
}

/**
 * Verifica si el usuario está logueado.
 */
function is_logged_in(): bool {
    return !empty($_SESSION['logged_in']);
}
