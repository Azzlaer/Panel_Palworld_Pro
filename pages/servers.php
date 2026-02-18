<?php
require_once __DIR__ . "/../config.php";

if (!is_logged_in()) {
    http_response_code(403);
    exit("Acceso denegado");
}

/* ====================================================
   CONFIG
==================================================== */

define("PID_FILE", __DIR__ . "/../server.pid");
define("TOOLS_DIR", __DIR__ . "/../tools");

/* ====================================================
   CARGAR CONFIG JSON
==================================================== */

$server = [];

if (file_exists(PALWORLD_SERVER_JSON)) {
    $server = json_decode(
        file_get_contents(PALWORLD_SERVER_JSON),
        true
    ) ?: [];
}

/* ====================================================
   HELPERS
==================================================== */

function getPID(): int {
    if (!file_exists(PID_FILE)) return 0;
    return intval(trim(file_get_contents(PID_FILE)));
}

function isOnline(): bool {
    $pid = getPID();
    if ($pid <= 0) return false;

    exec("tasklist /FI \"PID eq $pid\"", $out);

    foreach ($out as $line) {
        if (strpos($line, (string)$pid) !== false) {
            return true;
        }
    }

    return false;
}

/* ====================================================
   START SERVER (🔥 GOD MODE)
==================================================== */

function startServer(): array {

    global $server;

    if (isOnline()) {
        return ["ok"=>false,"error"=>"Servidor ya iniciado"];
    }

    $python = trim(PYTHON_EXE,'"');
    $script = TOOLS_DIR . "/iniciar.py";

    if (!file_exists($script)) {
        return ["ok"=>false,"error"=>"No existe iniciar.py"];
    }

    // Ejecutar Python TOTALMENTE EN BACKGROUND
    $cmd =
        'start "" /B "' .
        $python .
        '" "' .
        $script .
        '"';

    pclose(popen($cmd, "r"));

    return [
        "ok"=>true,
        "msg"=>"Comando enviado → iniciando servidor..."
    ];
}

/* ====================================================
   STOP SERVER
==================================================== */

function stopServer(): array {

    $pid = getPID();

    if ($pid <= 0) {
        return ["ok"=>false,"error"=>"No hay PID"];
    }

    exec("taskkill /F /PID $pid 2>NUL");

    @unlink(PID_FILE);

    return ["ok"=>true,"msg"=>"Servidor detenido"];
}

/* ====================================================
   AJAX
==================================================== */

if ($_SERVER['REQUEST_METHOD']==='POST') {

    $op = $_POST['op'] ?? '';

    if ($op === 'start') {
        echo json_encode(startServer());
        exit;
    }

    if ($op === 'stop') {
        echo json_encode(stopServer());
        exit;
    }
}

/* ====================================================
   UI
==================================================== */

$online = isOnline();
$pid = getPID();
?>

<div class="container mt-4">

<div class="d-flex align-items-center mb-3">
    <h2 class="mb-0">🖥️ Servidor Palworld</h2>

    <button class="btn btn-sm btn-outline-light ms-auto"
        onclick="reloadServers()">↻ Refrescar</button>
</div>

<div class="table-responsive">

<table class="table table-dark table-striped text-center align-middle">

<thead>
<tr>
<th>Servidor</th>
<th>Ejecutable</th>
<th>PID</th>
<th>Estado</th>
<th>Parámetros</th>
<th>Acciones</th>
</tr>
</thead>

<tbody>

<?php if(empty($server)): ?>

<tr><td colspan="6">⚠️ Config no encontrada</td></tr>

<?php else: ?>

<tr>

<td><strong><?= htmlspecialchars($server['server_name']) ?></strong></td>

<td><small><?= htmlspecialchars($server['exe']) ?></small></td>

<td>
<?= $pid > 0 ? $pid : '-' ?>
</td>

<td>
<?= $online
? '<span class="badge bg-success">🟢 ONLINE</span>'
: '<span class="badge bg-danger">🔴 OFFLINE</span>' ?>
</td>

<td class="text-start">
<small><?= htmlspecialchars($server['params']) ?></small>
</td>

<td>

<button class="btn btn-success btn-sm"
onclick="serverOp('start')"
<?= $online ? 'disabled':'' ?>>
🚀 Iniciar
</button>

<button class="btn btn-danger btn-sm"
onclick="serverOp('stop')"
<?= !$online ? 'disabled':'' ?>>
🛑 Detener
</button>

</td>

</tr>

<?php endif; ?>

</tbody>
</table>

</div>
</div>

<script>

function reloadServers(){
    fetch('pages/servers.php')
    .then(r=>r.text())
    .then(html=>{
        document.getElementById('main').innerHTML = html;
    });
}

function serverOp(op){

    const data = new URLSearchParams({op});

    fetch('pages/servers.php',{
        method:'POST',
        headers:{
            'Content-Type':'application/x-www-form-urlencoded'
        },
        body:data.toString()
    })
    .then(r=>r.json())
    .then(j=>{
        alert(j.msg || j.error);
        setTimeout(reloadServers,1500);
    });
}

</script>
