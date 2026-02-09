<?php
require_once "../config.php";

if (!is_logged_in()) {
    echo '<div class="alert alert-danger">Sesión expirada</div>';
    return;
}

error_reporting(0);

/* ================= CONFIG ================= */

$BASE_DIR   = realpath(__DIR__ . "/..");
$LOG_DIR    = $BASE_DIR . "/logs";
$LOG_FILE   = $LOG_DIR . "/servers.log";
$PID_FILE   = $BASE_DIR . "/server.pid.json";

$PYTHON     = defined('PYTHON_EXE') ? PYTHON_EXE : 'python';
$PY_SCRIPT  = defined('PALWORLD_START_SCRIPT')
    ? basename(PALWORLD_START_SCRIPT)
    : 'start_palworld.py';

if (!is_dir($LOG_DIR)) {
    mkdir($LOG_DIR, 0777, true);
}

/* ================= HELPERS ================= */

function log_event(string $msg): string {
    global $LOG_FILE;
    $line = "[" . date("H:i:s") . "] " . $msg . PHP_EOL;
    file_put_contents($LOG_FILE, $line, FILE_APPEND);
    return $line;
}

function get_pid(): ?int {
    global $PID_FILE;
    if (!file_exists($PID_FILE)) return null;
    $j = json_decode(@file_get_contents($PID_FILE), true);
    return $j['pid'] ?? null;
}

function is_running(): bool {
    $pid = get_pid();
    if (!$pid) return false;

    exec("tasklist /FI \"PID eq $pid\"", $out);
    foreach ($out as $line) {
        if (strpos($line, (string)$pid) !== false) {
            return true;
        }
    }
    return false;
}

/* ================= AJAX ================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    $action = $_POST['action'];
    $log = "";

    /* ---------- INICIAR ---------- */
    if ($action === 'start') {

        if (is_running()) {
            echo json_encode([
                'ok'  => false,
                'log' => log_event("⚠ El servidor ya está en ejecución")
            ]);
            exit;
        }

        $cmd = sprintf(
            'cmd /c "cd /d %s && %s %s"',
            $BASE_DIR,
            $PYTHON,
            $PY_SCRIPT
        );

        log_event("▶ Iniciando servidor...");
        exec($cmd . " 2>&1", $out, $code);

        foreach ($out as $line) {
            $log .= log_event($line);
        }

        if ($code !== 0) {
            $log .= log_event("❌ Error al iniciar servidor");
            echo json_encode(['ok' => false, 'log' => $log]);
            exit;
        }

        $log .= log_event("✅ Comando de inicio ejecutado");
        echo json_encode(['ok' => true, 'log' => $log]);
        exit;
    }

    /* ---------- DETENER ---------- */
    if ($action === 'stop') {

        $pid = get_pid();
        if (!$pid) {
            echo json_encode([
                'ok'  => false,
                'log' => log_event("⚠ No hay PID registrado")
            ]);
            exit;
        }

        log_event("⏹ Deteniendo servidor (PID $pid)...");
        exec("taskkill /F /PID $pid 2>&1", $out);

        foreach ($out as $line) {
            $log .= log_event($line);
        }

        @unlink($PID_FILE);
        $log .= log_event("🛑 Servidor detenido correctamente");

        echo json_encode(['ok' => true, 'log' => $log]);
        exit;
    }

    echo json_encode([
        'ok'  => false,
        'log' => log_event("❌ Acción inválida")
    ]);
    exit;
}

/* ================= UI ================= */

$status = is_running() ? "Activo" : "Detenido";
$statusClass = $status === "Activo" ? "text-success" : "text-danger";
?>

<div class="container-fluid text-light p-4">
    <h2 class="fw-bold mb-4">🖥️ Servidor Palworld</h2>

    <div class="mb-3">
        <b>Estado:</b>
        <span class="<?= $statusClass ?>">
            <?= $status ?>
        </span>
    </div>

    <div class="d-flex gap-3 mb-4">
        <button
            class="btn btn-success"
            onclick="doAction('start')"
            <?= $status === 'Activo' ? 'disabled' : '' ?>
        >
            ▶ Iniciar
        </button>

        <button
            class="btn btn-danger"
            onclick="doAction('stop')"
            <?= $status === 'Detenido' ? 'disabled' : '' ?>
        >
            ⏹ Detener
        </button>
    </div>

    <h5>🧾 Registro de eventos</h5>
    <pre id="log"
         style="background:#000;color:#0f0;height:240px;
                overflow-y:auto;padding:10px;border-radius:10px;">
    </pre>

    <small class="text-muted">
        El log completo se guarda en <code>/logs/servers.log</code>
    </small>
</div>

<script>
function doAction(action) {
    fetch('pages/servers.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'action=' + action
    })
    .then(r => r.json())
    .then(j => {
        if (j.log) {
            const box = document.getElementById('log');
            box.textContent += j.log;
            box.scrollTop = box.scrollHeight;
        }
        if (j.ok) {
            setTimeout(() => location.reload(), 1500);
        }
    })
    .catch(() => {
        alert("Error de red al comunicarse con el servidor");
    });
}
</script>
