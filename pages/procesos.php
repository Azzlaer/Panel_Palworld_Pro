<?php
require_once "../config.php";

if (!is_logged_in()) {
    echo '<div class="alert alert-danger">Sesión expirada.</div>';
    return;
}

/* ================= PROCESOS DESDE CONFIG ================= */

$procesos = PAL_PROCESSES;

/**
 * Verifica si un proceso está activo usando tasklist (estable)
 */
function proceso_activo(string $exe): bool {
    $out = [];
    exec('tasklist /FI "IMAGENAME eq ' . $exe . '" /NH', $out);
    foreach ($out as $line) {
        if (stripos($line, $exe) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Mata un proceso por nombre
 */
function kill_process(string $exe): void {
    exec('taskkill /F /IM "' . $exe . '" 2>&1');
}

/* ================= ACCIONES ================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kill'])) {
    $exe = basename($_POST['kill']);
    if (in_array($exe, PAL_PROCESSES, true)) {
        kill_process($exe);
        echo json_encode(['ok' => true, 'msg' => "$exe detenido"]);
    } else {
        echo json_encode(['ok' => false, 'msg' => "Proceso no permitido"]);
    }
    exit;
}
?>

<style>
.process-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 20px;
}

.process-card {
    background: linear-gradient(145deg, #1c1c1c, #141414);
    border-radius: 18px;
    padding: 22px;
    border: 1px solid rgba(255,255,255,.06);
    box-shadow: 0 0 18px rgba(0,0,0,.6);
    transition: .35s;
    position: relative;
}
.process-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 0 30px rgba(0,0,0,.8);
}

.proc-name {
    font-size: 1.15rem;
    font-weight: 700;
    color: #fff;
}

.proc-type {
    font-size: .85rem;
    color: #aaa;
}

.status-online {
    background: #2ecc71;
    color: #0e2b17;
    padding: 6px 14px;
    border-radius: 999px;
    font-weight: 700;
    display: inline-block;
    margin-top: 10px;
}

.status-offline {
    background: #e74c3c;
    color: #2a0e0e;
    padding: 6px 14px;
    border-radius: 999px;
    font-weight: 700;
    display: inline-block;
    margin-top: 10px;
}

.btn-kill {
    margin-top: 18px;
    width: 100%;
    border-radius: 12px;
    font-weight: 700;
    padding: 10px;
    border: none;
    background: #c0392b;
    color: white;
    transition: .25s;
}
.btn-kill:hover {
    background: #a93226;
}
.btn-kill:disabled {
    background: #444;
    cursor: not-allowed;
}

.proc-icon {
    position: absolute;
    top: 18px;
    right: 18px;
    font-size: 2rem;
    opacity: .25;
}
</style>

<div class="container-fluid text-light py-3">

    <h2 class="fw-bold mb-4">⚙️ Procesos del Servidor Palworld</h2>

    <div class="process-grid">

        <?php foreach ($procesos as $exe):
            $activo = proceso_activo($exe);
        ?>
            <div class="process-card">

                <div class="proc-icon">
                    <?= str_contains($exe, 'Shipping') ? '🧩' : '🖥️' ?>
                </div>

                <div class="proc-name"><?= htmlspecialchars($exe) ?></div>
                <div class="proc-type">
                    <?= str_contains($exe, 'Shipping')
                        ? 'Proceso auxiliar (motor UE)'
                        : 'Proceso principal del servidor' ?>
                </div>

                <?php if ($activo): ?>
                    <div class="status-online">ONLINE</div>
                <?php else: ?>
                    <div class="status-offline">OFFLINE</div>
                <?php endif; ?>

                <button
                    class="btn-kill"
                    data-exe="<?= htmlspecialchars($exe) ?>"
                    <?= !$activo ? 'disabled' : '' ?>
                >
                    🛑 Terminar proceso
                </button>

            </div>
        <?php endforeach; ?>

    </div>
</div>

<script>
$('.btn-kill').on('click', function(){

    const exe = $(this).data('exe');
    if (!confirm('¿Terminar el proceso ' + exe + '?')) return;

    $.post('pages/procesos.php', { kill: exe }, function(resp){
        alert(resp.msg);
        $('#main').load('pages/procesos.php');
    }, 'json');

});
</script>
