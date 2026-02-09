<?php
require_once "../config.php";

if (!is_logged_in()) {
    echo '<div class="alert alert-danger">Sesión expirada. Vuelve a iniciar sesión.</div>';
    return;
}

/* ================== RUTAS ================== */
$PAL_EXE       = PAL_EXE;
$INSTALL_DIR   = PAL_INSTALL_DIR;
$MODS_JSON     = PAL_MODS_JSON;
$PLAYERS_DIR   = PAL_PLAYERS_DIR;
$PAKS_DIR      = PAL_PAKS_DIR;

/* ================== FUNCIONES ================== */

/**
 * Servidor activo si CUALQUIERA de los procesos Palworld está vivo
 */
function palserver_esta_activo(): bool {
    foreach (PAL_PROCESSES as $proc) {
        $out = [];
        exec('tasklist /NH /FI "IMAGENAME eq ' . $proc . '"', $out);
        foreach ($out as $line) {
            if (stripos($line, $proc) !== false) {
                return true;
            }
        }
    }
    return false;
}

/**
 * CPU TOTAL (%) sumando ambos procesos
 */
function obtener_cpu_palserver_total(): ?float {

    $totalCpu = 0.0;
    $found = false;

    foreach (PAL_PROCESSES as $proc) {

        // PerfProc usa el nombre sin ".exe"
        $name = str_replace('.exe', '', $proc);

        $out = [];
        exec(
            'wmic path Win32_PerfFormattedData_PerfProc_Process ' .
            'where "Name=\'' . $name . '\'" get PercentProcessorTime /value',
            $out
        );

        foreach ($out as $line) {
            $line = trim($line);
            if (strpos($line, "PercentProcessorTime=") === 0) {
                $val = trim(explode("=", $line)[1]);
                if (is_numeric($val)) {
                    $totalCpu += (float)$val;
                    $found = true;
                }
            }
        }
    }

    return $found ? round($totalCpu, 1) : null;
}

/**
 * RAM TOTAL (MB) sumando ambos procesos
 */
function obtener_ram_palserver_total_mb(): ?float {

    $totalMb = 0.0;
    $found = false;

    foreach (PAL_PROCESSES as $proc) {
        $out = [];
        exec(
            'wmic process where name="' . $proc . '" get WorkingSetSize /value',
            $out
        );

        foreach ($out as $line) {
            $line = trim($line);
            if (strpos($line, "WorkingSetSize=") === 0) {
                $bytes = trim(explode("=", $line)[1]);
                if (is_numeric($bytes) && $bytes > 0) {
                    $totalMb += $bytes / 1024 / 1024;
                    $found = true;
                }
            }
        }
    }

    return $found ? round($totalMb, 1) : null;
}

/**
 * Información de mods UE4SS desde mods.json
 */
function obtener_info_mods(string $rutaJson): array {
    if (!file_exists($rutaJson)) return ['total' => 0, 'habilitados' => 0];

    $data = json_decode(@file_get_contents($rutaJson), true);
    if (!is_array($data)) return ['total' => 0, 'habilitados' => 0];

    return [
        'total' => count($data),
        'habilitados' => count(array_filter($data, fn($m) => !empty($m['mod_enabled'])))
    ];
}

/**
 * Contar archivos por extensión
 */
function contar_archivos(string $path, string $ext): int {
    if (!is_dir($path)) return 0;
    return count(glob(rtrim($path, "/\\") . "/*.$ext"));
}

/* ================== CÁLCULOS ================== */

$activo  = palserver_esta_activo();
$cpu     = $activo ? obtener_cpu_palserver_total() : null;
$ramMb   = $activo ? obtener_ram_palserver_total_mb() : null;

$modsInfo      = obtener_info_mods($MODS_JSON);
$totalMods     = $modsInfo['total'];
$modsOn        = $modsInfo['habilitados'];

$totalUsuarios = contar_archivos($PLAYERS_DIR, "sav");
$totalPaks     = contar_archivos($PAKS_DIR, "pak");

?>

<style>
.dashboard-card {
    background: rgba(25,25,25,0.92);
    border-radius: 18px;
    padding: 20px;
    border: 1px solid rgba(255,255,255,0.06);
    backdrop-filter: blur(6px);
    box-shadow: 0 0 12px rgba(0,0,0,0.45);
    transition: 0.3s;
}
.dashboard-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 0 22px rgba(0,0,0,0.60);
}
.dashboard-title {
    font-size: 1.1rem;
    font-weight: 600;
}
.dashboard-value {
    font-size: 1.9rem;
    font-weight: 700;
    color: #0d6efd;
}
.dashboard-subtext {
    font-size: 0.9rem;
    color: #bbb;
}
.badge-status-online {
    background: #2ecc71;
    color: #0f2d15;
    padding: 5px 12px;
    border-radius: 20px;
    font-weight: 600;
}
.badge-status-offline {
    background: #e74c3c;
    color: #2d0f0f;
    padding: 5px 12px;
    border-radius: 20px;
    font-weight: 600;
}
</style>

<div class="container-fluid text-light py-3">

    <h2 class="fw-bold mb-4">📊 Información del Servidor Palworld</h2>

    <div class="row g-4">

        <!-- ESTADO -->
        <div class="col-md-6">
            <div class="dashboard-card h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="dashboard-title">Estado del Servidor</div>
                    <?= $activo
                        ? '<span class="badge-status-online">Activo</span>'
                        : '<span class="badge-status-offline">Detenido</span>' ?>
                </div>

                <div class="dashboard-subtext mt-3">
                    <b>Ejecutable:</b><br><?= htmlspecialchars($PAL_EXE) ?>
                </div>

                <div class="dashboard-subtext mt-2">
                    <b>Carpeta de instalación:</b><br><?= htmlspecialchars($INSTALL_DIR) ?>
                </div>
            </div>
        </div>

        <!-- CPU -->
        <div class="col-md-3 col-sm-6">
            <div class="dashboard-card text-center">
                <div class="dashboard-title mb-2">CPU TOTAL</div>
                <div class="dashboard-value">
                    <?= $activo && $cpu !== null ? $cpu.'%' : '—' ?>
                </div>
                <div class="dashboard-subtext">PalServer + Shipping</div>
            </div>
        </div>

        <!-- RAM -->
        <div class="col-md-3 col-sm-6">
            <div class="dashboard-card text-center">
                <div class="dashboard-title mb-2">RAM TOTAL</div>
                <div class="dashboard-value">
                    <?= $activo && $ramMb !== null ? $ramMb.' MB' : '—' ?>
                </div>
                <div class="dashboard-subtext">Uso combinado</div>
            </div>
        </div>

        <!-- MODS UE4SS -->
        <div class="col-md-4">
            <div class="dashboard-card text-center">
                <div class="dashboard-title mb-2">Mods UE4SS</div>
                <div class="dashboard-value"><?= $totalMods ?></div>
                <div class="dashboard-subtext">
                    Activos: <b><?= $modsOn ?></b>
                </div>
            </div>
        </div>

        <!-- USUARIOS -->
        <div class="col-md-4">
            <div class="dashboard-card text-center">
                <div class="dashboard-title mb-2">Usuarios</div>
                <div class="dashboard-value"><?= $totalUsuarios ?></div>
                <div class="dashboard-subtext">Archivos .sav detectados</div>
            </div>
        </div>

        <!-- MODS PAK -->
        <div class="col-md-4">
            <div class="dashboard-card text-center">
                <div class="dashboard-title mb-2">Mods PAK</div>
                <div class="dashboard-value"><?= $totalPaks ?></div>
                <div class="dashboard-subtext">Archivos .pak instalados</div>
            </div>
        </div>

    </div>
</div>
