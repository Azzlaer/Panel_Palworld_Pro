<?php
require_once "../config.php";

if (!is_logged_in()) {
    echo '<div class="alert alert-danger">Sesión expirada.</div>';
    return;
}

/* ============================================================
   HELPER REST (cURL)
============================================================ */
function palworld_curl(string $endpoint,
                       string $method='GET',
                       array $payload=null): array {

    $url = rtrim(PAL_REST_URL,'/') . $endpoint;

    $ch = curl_init($url);

    curl_setopt_array($ch,[
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_TIMEOUT        => PAL_REST_TIMEOUT,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Content-Type: application/json'
        ],
        CURLOPT_USERPWD => PAL_ADMIN_USER . ':' . PAL_ADMIN_PASS,
    ]);

    if($payload!==null){
        curl_setopt($ch,CURLOPT_POSTFIELDS,
            json_encode($payload,JSON_UNESCAPED_UNICODE));
    }

    $body = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch,CURLINFO_HTTP_CODE);

    curl_close($ch);

    if($err) return ['ok'=>false,'error'=>$err];

    if($code!==200){
        return ['ok'=>false,'error'=>"HTTP $code",'raw'=>$body];
    }

    return ['ok'=>true,'data'=>json_decode($body,true)];
}

/* ============================================================
   EJECUTAR SAVE PYTHON
============================================================ */
function run_python_save(): array {

    $base = realpath(__DIR__ . "/..");
    $script = $base . "/tools/save_1.py";

    if (!file_exists($script)) {
        return ['ok'=>false,'error'=>'save_1.py no encontrado'];
    }

    $cmd = sprintf(
        'cmd /c ""%s" "%s""',
        PYTHON_EXE,
        $script
    );

    exec($cmd . " 2>&1", $out, $code);

    return [
        'ok' => ($code===0),
        'output' => implode("\n",$out)
    ];
}

/* ============================================================
   ACTION HANDLER
============================================================ */

$action = strtolower($_POST['action'] ?? '');

if($action==='players'){
    echo json_encode(palworld_curl('/v1/api/players'));
    exit;
}

if($action==='broadcast'){
    $msg = trim($_POST['message'] ?? '');
    if($msg===''){
        echo json_encode(['ok'=>false,'error'=>'Mensaje vacío']);
        exit;
    }

    echo json_encode(
        palworld_curl('/v1/api/announce','POST',[
            'message'=>$msg
        ])
    );
    exit;
}

if($action==='save'){
    echo json_encode(run_python_save());
    exit;
}

if($action==='shutdown'){
    echo json_encode(
        palworld_curl('/v1/api/shutdown','POST',[
            'waittime'=>60,
            'message'=>'Servidor se apagará en 60 segundos'
        ])
    );
    exit;
}
?>

<style>
.pw-card{
    background: rgba(20,20,20,.92);
    border-radius:18px;
    padding:22px;
}
.pw-btn{
    border-radius:12px;
    font-weight:700;
}
#pwOutput{
    height:260px;
    overflow:auto;
    background:#000;
    color:#0f0;
    font-family:Consolas;
    font-size:13px;
    padding:10px;
    border-radius:10px;
    white-space:pre-wrap;
}
</style>

<div class="container-fluid text-light py-3">
<div class="pw-card">

<h2 class="fw-bold mb-3">🕹️ Palworld REST Control</h2>

<div class="d-flex flex-wrap gap-3 mb-4">
<button class="btn btn-primary pw-btn" onclick="getPlayers()">👥 Jugadores</button>
<button class="btn btn-success pw-btn" onclick="saveWorld()">💾 Guardar Mundo</button>
<button class="btn btn-warning pw-btn" onclick="openBroadcast()">📢 Broadcast</button>
<button class="btn btn-danger pw-btn" onclick="shutdownServer()">⛔ Shutdown</button>
</div>

<div id="pwOutput">[Sistema iniciado]</div>

</div>
</div>

<!-- MODAL BROADCAST -->
<div class="modal fade" id="broadcastModal">
<div class="modal-dialog">
<div class="modal-content bg-dark text-light">
<div class="modal-header">
<h5>📢 Broadcast</h5>
<button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<input type="text" id="broadcastMsg" class="form-control"
placeholder="Mensaje para todos">
</div>
<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
<button class="btn btn-primary" onclick="sendBroadcast()">Enviar</button>
</div>
</div>
</div>
</div>

<script>
function log(msg){
    const box=document.getElementById('pwOutput');
    const t=new Date().toLocaleTimeString();
    box.textContent += `\n[${t}] ${msg}`;
    box.scrollTop=box.scrollHeight;
}

function post(action,data={}){

    const form=new URLSearchParams();
    form.set('action',action);

    for(const k in data)
        form.set(k,data[k]);

    return fetch('pages/rcon.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:form.toString()
    }).then(r=>r.json());
}

function getPlayers(){
    log("Consultando jugadores...");
    post('players').then(r=>{
        log(JSON.stringify(r,null,2));
    });
}

function saveWorld(){
    log("Ejecutando save_1.py...");
    post('save').then(r=>{
        if(r.ok){
            log("✔ Script SAVE ejecutado correctamente");
            if(r.output) log(r.output);
        }else{
            log("❌ Error ejecutando SAVE");
            if(r.output) log(r.output);
        }
    });
}

function shutdownServer(){
    log("Enviando shutdown...");
    post('shutdown').then(r=>{
        if(r.ok) log("⚠ Shutdown enviado");
        else log("❌ Error shutdown");
    });
}

function openBroadcast(){
    new bootstrap.Modal(
        document.getElementById('broadcastModal')
    ).show();
}

function sendBroadcast(){

    const msg=document.getElementById('broadcastMsg').value.trim();

    if(!msg){
        log("⚠ Mensaje vacío");
        return;
    }

    log("Enviando broadcast...");

    post('broadcast',{message:msg})
    .then(r=>{
        if(r.ok) log("📢 Broadcast enviado correctamente");
        else log("❌ Error en broadcast");
    });

    bootstrap.Modal.getInstance(
        document.getElementById('broadcastModal')
    ).hide();
}
</script>
