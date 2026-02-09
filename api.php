<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['logged_in'])) {
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

$action = $_GET['action'] ?? '';

/* ============================================================
   HELPER REST PALWORLD
============================================================ */
function palworld_rest(string $endpoint, string $method = 'GET', array $payload = null): array {

    $url = rtrim(PAL_REST_URL, '/') . $endpoint;

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_TIMEOUT        => PAL_REST_TIMEOUT,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Content-Type: application/json'
        ],
        CURLOPT_USERPWD => PAL_ADMIN_USER . ':' . PAL_ADMIN_PASS
    ]);

    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $body = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($err) {
        throw new Exception('cURL: ' . $err);
    }

    if ($code !== 200) {
        throw new Exception("HTTP $code: $body");
    }

    return json_decode($body, true) ?? [];
}


/* ============================================================
   ESTADO PALWORLD (PROCESOS)
============================================================ */
if ($action === 'status_palworld') {

    $running = false;

    foreach (PAL_PROCESSES as $proc) {
        $out = @shell_exec('tasklist /FI "IMAGENAME eq ' . $proc . '"');
        if ($out && stripos($out, $proc) !== false) {
            $running = true;
            break;
        }
    }

    echo json_encode(['ok' => true, 'running' => $running]);
    exit;
}

/* ============================================================
   PLAYERS
============================================================ */
if ($action === 'players') {
    try {
        $data = palworld_rest('/v1/api/players');
        echo json_encode(['ok' => true, 'response' => $data]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

/* ============================================================
   BROADCAST
============================================================ */
if ($action === 'broadcast') {
    try {
        $msg = trim($_POST['message'] ?? '');
        if ($msg === '') {
            throw new Exception('Mensaje vacío');
        }

        $data = palworld_rest(
            '/v1/api/announce',
            'POST',
            ['message' => $msg]
        );

        echo json_encode(['ok' => true, 'response' => $data]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

/* ============================================================
   SAVE WORLD
============================================================ */
if ($action === 'save') {
    try {
        $data = palworld_rest('/v1/api/save', 'POST');
        echo json_encode(['ok' => true, 'response' => $data]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

/* ============================================================
   SHUTDOWN
============================================================ */
if ($action === 'shutdown') {
    try {
        $data = palworld_rest(
            '/v1/api/shutdown',
            'POST',
            [
                'waittime' => 60,
                'message'  => 'Servidor se apagará en 60 segundos'
            ]
        );

        echo json_encode(['ok' => true, 'response' => $data]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

/* ============================================================
   UPDATE PALWORLD
============================================================ */
if ($action === 'steam_update_palworld') {

    foreach (PAL_PROCESSES as $proc) {
        $out = @shell_exec('tasklist /FI "IMAGENAME eq ' . $proc . '"');
        if ($out && stripos($out, $proc) !== false) {
            echo json_encode([
                'ok' => false,
                'error' => 'El servidor está en ejecución. Apágalo antes.'
            ]);
            exit;
        }
    }

    $cmd =
        '"' . STEAMCMD_EXE . '" ' .
        '+login anonymous ' .
        '+force_install_dir "' . PAL_SERVER_DIR . '" ' .
        '+app_update ' . PAL_APP_ID . ' validate ' .
        '+quit';

    pclose(popen('start "" ' . $cmd, 'r'));

    echo json_encode(['ok' => true]);
    exit;
}

/* ============================================================
   FALLBACK
============================================================ */
echo json_encode(['ok' => false, 'error' => 'Acción inválida']);
