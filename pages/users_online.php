<?php
require_once "../config.php";

if (!is_logged_in()) {
    echo '<div class="alert alert-danger">Sesión expirada.</div>';
    return;
}

$AUTO_DEFAULT = USERS_AUTO_REFRESH_DEFAULT ? 'true' : 'false';
$AUTO_SECONDS = USERS_AUTO_REFRESH_INTERVAL;
?>

<style>
.pw-card {
    background: rgba(25,25,25,0.92);
    border-radius: 18px;
    padding: 20px;
    border: 1px solid rgba(255,255,255,0.06);
    backdrop-filter: blur(6px);
    box-shadow: 0 0 15px rgba(0,0,0,0.45);
}

.pw-console {
    background:#000;
    color:#0f0;
    padding:12px;
    height:160px;
    overflow-y:auto;
    border-radius:10px;
    font-family:monospace;
    font-size:.9rem;
}
</style>

<div class="container-fluid text-light py-3">

<h2 class="fw-bold mb-4">🕹️ Consola Palworld (REST API)</h2>

<!-- Consola -->
<div class="pw-card mb-4">
    <div class="pw-console" id="pwConsole">
[Inicializado] Consola Palworld REST
    </div>
</div>

<!-- Jugadores -->
<div class="pw-card">

<div class="d-flex justify-content-between align-items-center mb-3">

    <h4 class="fw-bold mb-0">👥 Jugadores Online</h4>

    <div class="d-flex gap-2">

        <button class="btn btn-outline-light btn-sm"
            onclick="loadPlayers()">
            🔄 Actualizar
        </button>

        <button id="autoBtn"
            class="btn btn-outline-warning btn-sm"
            onclick="toggleAutoRefresh(this)">
            ▶ Auto OFF
        </button>

    </div>

</div>

<div class="table-responsive">
<table class="table table-dark table-hover align-middle text-center">

<thead>
<tr>
<th>Jugador</th>
<th>Cuenta</th>
<th>Plataforma</th>
<th>IP</th>
<th>Ping</th>
<th>Nivel</th>
<th>Posición</th>
<th>Estado</th>
</tr>
</thead>

<tbody id="playersTable">
<tr>
<td colspan="8" class="text-muted">
Presiona “Actualizar” para cargar jugadores
</td>
</tr>
</tbody>

</table>
</div>
</div>
</div>

<script>

let autoRefresh = <?= $AUTO_DEFAULT ?>;
let refreshTimer = null;

const AUTO_INTERVAL = <?= (int)$AUTO_SECONDS ?> * 1000;

/* ================= CONSOLE ================= */

function logConsole(msg){
    const box=document.getElementById('pwConsole');
    const t=new Date().toLocaleTimeString();
    box.textContent += `\n[${t}] ${msg}`;
    box.scrollTop=box.scrollHeight;
}

/* ================= API ================= */

function api(action){
    return fetch(`api.php?action=${action}`,{
        method:'POST',
        credentials:'same-origin'
    }).then(r=>r.json());
}

/* ================= LOAD PLAYERS ================= */

function loadPlayers(){

    const tbody=document.getElementById('playersTable');

    tbody.innerHTML=
        `<tr><td colspan="8">⏳ Cargando jugadores...</td></tr>`;

    logConsole("Consultando jugadores online...");

    api('players').then(resp=>{

        if(!resp.ok){

            logConsole("❌ Error al obtener jugadores");

            tbody.innerHTML=`
                <tr>
                    <td colspan="8" class="text-danger">
                        Error: ${resp.error||'Desconocido'}
                    </td>
                </tr>
            `;
            return;
        }

        const players = resp.response.players || [];

        if(players.length===0){

            tbody.innerHTML=`
                <tr>
                    <td colspan="8" class="text-muted">
                        No hay jugadores conectados
                    </td>
                </tr>
            `;

            logConsole("No hay jugadores conectados");
            return;
        }

        tbody.innerHTML='';
        logConsole(`Jugadores detectados: ${players.length}`);

        players.forEach(p=>{

            let platform='Desconocido';
            if(p.userId?.startsWith('steam_')) platform='Steam';
            if(p.userId?.startsWith('ps5_')) platform='PS5';

            tbody.insertAdjacentHTML('beforeend',`
                <tr>
                    <td><b>${p.name}</b></td>
                    <td>${p.accountName}</td>
                    <td>${platform}</td>
                    <td>${p.ip}</td>
                    <td>${Math.round(p.ping)} ms</td>
                    <td>${p.level}</td>
                    <td>
                        X:${Math.round(p.location_x)}<br>
                        Y:${Math.round(p.location_y)}
                    </td>
                    <td><span class="badge bg-success">Online</span></td>
                </tr>
            `);
        });

    }).catch(()=>{
        logConsole("❌ Error de red");
    });
}

/* ================= AUTO REFRESH ================= */

function enableAuto(btn){

    btn.classList.remove("btn-outline-warning");
    btn.classList.add("btn-warning");
    btn.textContent="⏸ Auto ON";

    logConsole(`Auto actualización ACTIVADA (${AUTO_INTERVAL/1000}s)`);

    refreshTimer=setInterval(loadPlayers,AUTO_INTERVAL);
}

function disableAuto(btn){

    btn.classList.remove("btn-warning");
    btn.classList.add("btn-outline-warning");
    btn.textContent="▶ Auto OFF";

    clearInterval(refreshTimer);

    logConsole("Auto actualización DESACTIVADA");
}

function toggleAutoRefresh(btn){

    autoRefresh=!autoRefresh;

    if(autoRefresh) enableAuto(btn);
    else disableAuto(btn);
}

/* ================= INIT ================= */

window.addEventListener('load',()=>{

    const btn=document.getElementById('autoBtn');

    if(autoRefresh){
        enableAuto(btn);
        loadPlayers();
    }else{
        disableAuto(btn);
    }
});

</script>
