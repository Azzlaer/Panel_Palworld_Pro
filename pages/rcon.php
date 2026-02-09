<?php
require_once "../config.php";

if (!is_logged_in()) {
    echo '<div class="alert alert-danger">Sesión expirada. Vuelve a iniciar sesión.</div>';
    return;
}

/* ============================================================
   HELPER PALWORLD REST (cURL + BASIC AUTH)
============================================================ */
function palworld_curl(string $endpoint, string $method = 'GET', array $payload = null): array {

    $url = rtrim(PAL_REST_URL, '/') . $endpoint;

    $ch = curl_init($url);

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json'
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_USERPWD        => PAL_ADMIN_USER . ':' . PAL_ADMIN_PASS,
    ]);

    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    $body = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($err) {
        return [
            'ok' => false,
            'error' => 'cURL error: ' . $err
        ];
    }

    if ($code !== 200) {
        return [
            'ok' => false,
            'error' => "HTTP $code",
            'raw' => $body
        ];
    }

    return [
        'ok' => true,
        'data' => json_decode($body, true)
    ];
}

/* ============================================================
   ACTION HANDLER (POST)
============================================================ */
$action = $_POST['action'] ?? '';

if ($action === 'players') {
    echo json_encode(palworld_curl('/v1/api/players'));
    exit;
}

if ($action === 'broadcast') {
    $msg = trim($_POST['message'] ?? '');
    if ($msg === '') {
        echo json_encode(['ok'=>false,'error'=>'Mensaje vacío']);
        exit;
    }

    echo json_encode(
        palworld_curl('/v1/api/announce', 'POST', [
            'message' => $msg
        ])
    );
    exit;
}

if ($action === 'save') {
    echo json_encode(palworld_curl('/v1/api/save', 'POST'));
    exit;
}

if ($action === 'shutdown') {
    echo json_encode(
        palworld_curl('/v1/api/shutdown', 'POST', [
            'waittime' => 60,
            'message'  => 'Servidor se apagará en 60 segundos'
        ])
    );
    exit;
}
?>

<style>
.pw-card {
    background: rgba(20,20,20,.92);
    border-radius: 18px;
    padding: 22px;
    border: 1px solid rgba(255,255,255,.06);
    box-shadow: 0 0 18px rgba(0,0,0,.55);
}

.pw-btn {
    border-radius: 12px;
    font-weight: 700;
}

#pwOutput {
    white-space: pre-wrap;
    font-family: Consolas, monospace;
    font-size: 13px;
}
</style>

<div class="container-fluid text-light py-3">
    <div class="pw-card">

        <h2 class="fw-bold mb-3">🕹️ Palworld – Control REST (cURL)</h2>
        <p class="text-muted">
            Panel basado en la <b>REST API oficial de Palworld</b>.<br>
            Autenticación <b>Admin / AdminPassword</b>, igual que el navegador.
        </p>

        <div class="d-flex flex-wrap gap-3 mb-4">
            <button class="btn btn-primary pw-btn" onclick="getPlayers()">👥 Ver jugadores</button>
            <button class="btn btn-success pw-btn" onclick="saveWorld()">💾 Guardar mundo</button>
            <button class="btn btn-warning pw-btn" onclick="openBroadcast()">📢 Broadcast</button>
            <button class="btn btn-danger pw-btn" onclick="shutdownServer()">⛔ Shutdown</button>
        </div>

        <div id="pwOutput" class="alert alert-secondary">
            Selecciona una acción…
        </div>
    </div>
</div>

<!-- MODAL BROADCAST -->
<div class="modal fade" id="broadcastModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-light">
      <div class="modal-header">
        <h5 class="modal-title">📢 Broadcast</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="text" id="broadcastMsg" class="form-control"
               placeholder="Mensaje para todos los jugadores">
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="sendBroadcast()">Enviar</button>
      </div>
    </div>
  </div>
</div>

<script>
function post(action, data = {}) {
    const form = new URLSearchParams();
    form.set('action', action);
    for (const k in data) form.set(k, data[k]);

    return fetch('pages/rcon.php', {
        method: 'POST',
        headers: { 'Content-Type':'application/x-www-form-urlencoded' },
        body: form.toString()
    }).then(r => r.json());
}

function setOut(o) {
    document.getElementById('pwOutput').textContent =
        JSON.stringify(o, null, 2);
}

function getPlayers() {
    post('players').then(setOut);
}

function saveWorld() {
    if (!confirm('¿Guardar mundo ahora?')) return;
    post('save').then(setOut);
}

function shutdownServer() {
    if (!confirm('¿Apagar servidor en 60 segundos?')) return;
    post('shutdown').then(setOut);
}

function openBroadcast() {
    new bootstrap.Modal(document.getElementById('broadcastModal')).show();
}

function sendBroadcast() {
    const msg = document.getElementById('broadcastMsg').value.trim();
    if (!msg) return alert('Mensaje vacío');

    post('broadcast', { message: msg }).then(setOut);
    bootstrap.Modal.getInstance(
        document.getElementById('broadcastModal')
    ).hide();
}
</script>
