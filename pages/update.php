<?php
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}

/* =======================
   HELPERS
======================= */

// Comprobar si PalServer.exe está en ejecución
function pal_is_running(): bool {
    $exe = basename(PAL_EXE);
    $out = @shell_exec('tasklist /FI "IMAGENAME eq ' . $exe . '"');
    return $out && stripos($out, $exe) !== false;
}

$running = pal_is_running();
?>

<div class="container mt-4 text-light">

    <div class="d-flex align-items-center gap-2 mb-2">
        <h2 class="mb-0">🔄 Actualización del Servidor Palworld</h2>

        <span class="badge <?= $running ? 'bg-success' : 'bg-danger' ?>">
            <?= $running ? '✅ En ejecución' : '🛑 Apagado' ?>
        </span>

        <small class="text-muted ms-2">
            (<?= htmlspecialchars(PAL_SERVER_NAME) ?>)
        </small>

        <button class="btn btn-sm btn-outline-light ms-auto" onclick="checkStatus()">
            ↻ Verificar estado
        </button>
    </div>

    <p class="text-muted">
        El servidor debe estar <strong>apagado</strong> para actualizarse con SteamCMD.<br>
        <code><?= htmlspecialchars(STEAMCMD_EXE) ?></code>
        → <code><?= htmlspecialchars(PAL_SERVER_DIR) ?></code>
    </p>

    <div class="table-responsive">
        <table class="table table-dark table-hover text-center align-middle">
            <thead>
                <tr>
                    <th>Canal</th>
                    <th>Comando SteamCMD</th>
                    <th style="width:180px">Acción</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>🎮 Normal</td>
                    <td class="text-start">
                        <small>
                            <code>
<?= htmlspecialchars(
STEAMCMD_EXE .
' +login anonymous +force_install_dir "' .
PAL_SERVER_DIR .
'" +app_update ' .
PAL_APP_ID .
' validate +quit'
) ?>
                            </code>
                        </small>
                    </td>
                    <td>
                        <button
                            class="btn btn-success btn-sm"
                            onclick="doUpdate()"
                            <?= $running ? 'disabled' : '' ?>
                        >
                            🔄 Actualizar Servidor
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div id="updMsg" class="mt-3"></div>
</div>

<script>
function msg(html, cls="info") {
    const box = document.getElementById('updMsg');
    const map = {
        info: 'alert-info',
        success: 'alert-success',
        warn: 'alert-warning',
        error: 'alert-danger'
    };
    box.className = 'alert ' + (map[cls] || 'alert-info');
    box.innerHTML = html;
}

function doUpdate() {
    if (!confirm('¿Iniciar actualización del servidor Palworld?')) return;

    fetch('api.php?action=steam_update_palworld', {
        method: 'POST',
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(j => {
        if (j && j.ok) {
            msg('✅ Actualización iniciada. Revisa la consola de SteamCMD para ver el progreso.', 'success');
        } else {
            msg('❌ ' + (j?.error || 'Error desconocido'), 'error');
        }
    })
    .catch(() => msg('⚠️ Error de red al iniciar la actualización.', 'error'));
}

function checkStatus() {
    fetch('api.php?action=status_palworld', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(j => {
            if (!j || !j.ok) {
                msg('⚠️ No se pudo consultar el estado.', 'warn');
                return;
            }
            msg(
                j.running ? '✅ Servidor activo.' : '🛑 Servidor detenido.',
                j.running ? 'success' : 'info'
            );
        })
        .catch(() => msg('⚠️ Error de red al consultar estado.', 'warn'));
}
</script>
