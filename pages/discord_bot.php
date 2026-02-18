<?php
require_once "../config.php";

if (!is_logged_in()) {
    echo "<div class='alert alert-danger'>Sesión expirada</div>";
    return;
}

/* ====================================================
   HELPERS
==================================================== */

function bot_is_running(): bool {

    $exe = DISCORD_BOT_NAME;

    exec('tasklist /FI "IMAGENAME eq '.$exe.'"', $out);

    foreach ($out as $line) {
        if (stripos($line,$exe)!==false) {
            return true;
        }
    }

    return false;
}

/* ====================================================
   START BOT
==================================================== */

function start_bot(): array {

    if (bot_is_running()) {
        return ['ok'=>false,'msg'=>'El bot ya está en ejecución'];
    }

    $exe = DISCORD_BOT_EXE;

    if (!file_exists($exe)) {
        return ['ok'=>false,'msg'=>'No se encontró el ejecutable'];
    }

    $cmd = 'start "" /B "' . $exe . '"';

    pclose(popen($cmd,"r"));

    return ['ok'=>true,'msg'=>'Bot iniciado correctamente'];
}

/* ====================================================
   STOP BOT
==================================================== */

function stop_bot(): array {

    $exe = DISCORD_BOT_NAME;

    exec('taskkill /F /IM "'.$exe.'" 2>NUL');

    return ['ok'=>true,'msg'=>'Bot detenido'];
}

/* ====================================================
   AJAX
==================================================== */

if ($_SERVER['REQUEST_METHOD']==='POST') {

    $op = $_POST['op'] ?? '';

    if ($op === 'start') {
        echo json_encode(start_bot());
        exit;
    }

    if ($op === 'stop') {
        echo json_encode(stop_bot());
        exit;
    }
}

$running = bot_is_running();
?>

<style>
.bot-card{
    background: rgba(20,20,20,.92);
    border-radius:18px;
    padding:24px;
    border:1px solid rgba(255,255,255,.06);
    box-shadow:0 0 18px rgba(0,0,0,.55);
}
</style>

<div class="container mt-4 text-light">

<div class="bot-card">

<h2 class="fw-bold mb-3">🤖 Discord Bot Manager</h2>

<p class="text-muted">
Control del bot Discord integrado al servidor.
</p>

<div class="mb-3">
<b>Proceso:</b> <?= DISCORD_BOT_NAME ?>
</div>

<div class="mb-3">
<b>Ejecutable:</b><br>
<small><?= htmlspecialchars(DISCORD_BOT_EXE) ?></small>
</div>

<div class="mb-4">
Estado:
<?= $running
? '<span class="badge bg-success">🟢 ONLINE</span>'
: '<span class="badge bg-danger">🔴 OFFLINE</span>'
?>
</div>

<div class="d-flex gap-3">

<button class="btn btn-success"
onclick="botOp('start')"
<?= $running ? 'disabled':'' ?>>
▶ Iniciar Bot
</button>

<button class="btn btn-danger"
onclick="botOp('stop')"
<?= !$running ? 'disabled':'' ?>>
⏹ Detener Bot
</button>

</div>

</div>
</div>

<script>

function reloadBot(){
    fetch('pages/discord_bot.php')
    .then(r=>r.text())
    .then(html=>{
        document.getElementById('main').innerHTML=html;
    });
}

function botOp(op){

    const data=new URLSearchParams({op});

    fetch('pages/discord_bot.php',{
        method:'POST',
        headers:{
            'Content-Type':'application/x-www-form-urlencoded'
        },
        body:data.toString()
    })
    .then(r=>r.json())
    .then(j=>{
        alert(j.msg);
        setTimeout(reloadBot,1000);
    });
}

</script>
