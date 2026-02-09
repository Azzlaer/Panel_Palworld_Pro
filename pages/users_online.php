<?php
require_once "../config.php";

if (!is_logged_in()) {
    echo '<div class="alert alert-danger">Sesión expirada. Vuelve a iniciar sesión.</div>';
    return;
}
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
    background: #000;
    color: #0f0;
    padding: 12px;
    height: 140px;
    overflow-y: auto;
    border-radius: 10px;
    font-family: monospace;
    font-size: 0.9rem;
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
            <button class="btn btn-outline-light btn-sm" onclick="loadPlayers()">
                🔄 Actualizar
            </button>
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
function logConsole(msg) {
    const box = document.getElementById('pwConsole');
    const time = new Date().toLocaleTimeString();
    box.textContent += `\n[${time}] ${msg}`;
    box.scrollTop = box.scrollHeight;
}

function api(action) {
    return fetch(`api.php?action=${action}`, {
        method: 'POST',
        credentials: 'same-origin'
    }).then(r => r.json());
}

function loadPlayers() {

    const tbody = document.getElementById('playersTable');
    tbody.innerHTML = `
        <tr><td colspan="8">⏳ Cargando jugadores...</td></tr>
    `;

    logConsole('Consultando jugadores online...');

    api('players').then(resp => {

        if (!resp.ok) {
            logConsole('❌ Error al obtener jugadores');
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-danger">
                        Error: ${resp.error || 'Desconocido'}
                    </td>
                </tr>
            `;
            return;
        }

        const players = resp.response.players || [];

        if (players.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-muted">
                        No hay jugadores conectados
                    </td>
                </tr>
            `;
            logConsole('No hay jugadores conectados');
            return;
        }

        tbody.innerHTML = '';
        logConsole(`Jugadores detectados: ${players.length}`);

        players.forEach(p => {

            let platform = 'Desconocido';
            if (p.userId.startsWith('steam_')) platform = 'Steam';
            if (p.userId.startsWith('ps5_'))   platform = 'PS5';

            tbody.insertAdjacentHTML('beforeend', `
                <tr>
                    <td><b>${p.name}</b></td>
                    <td>${p.accountName}</td>
                    <td>${platform}</td>
                    <td>${p.ip}</td>
                    <td>${Math.round(p.ping)} ms</td>
                    <td>${p.level}</td>
                    <td>
                        X: ${Math.round(p.location_x)}<br>
                        Y: ${Math.round(p.location_y)}
                    </td>
                    <td>
                        <span class="badge bg-success">Online</span>
                    </td>
                </tr>
            `);
        });

    }).catch(err => {
        logConsole('❌ Error de red al consultar jugadores');
        console.error(err);
    });
}

// Auto-cargar al abrir
loadPlayers();

// Auto-refresh cada 15s
setInterval(loadPlayers, 15000);
</script>
