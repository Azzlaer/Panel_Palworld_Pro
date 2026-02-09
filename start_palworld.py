import subprocess
import time
import json
import os
import sys

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
CONF_FILE = os.path.join(BASE_DIR, "palworld_server.json")
PID_FILE = os.path.join(BASE_DIR, "server.pid.json")

# ================= VALIDACIÓN =================

if not os.path.exists(CONF_FILE):
    print("ERROR: No existe palworld_server.json")
    sys.exit(1)

with open(CONF_FILE, "r", encoding="utf-8") as f:
    cfg = json.load(f)

SERVER_EXE = cfg.get("exe")
SERVER_PARAMS = cfg.get("params", "")

if not SERVER_EXE or not os.path.exists(SERVER_EXE):
    print("ERROR: Ejecutable inválido")
    print(SERVER_EXE)
    sys.exit(2)

# ================= FUNCIONES =================

def find_palserver_pid():
    try:
        output = subprocess.check_output(
            [
                "powershell",
                "-Command",
                "Get-Process PalServer -ErrorAction SilentlyContinue | Select-Object -ExpandProperty Id"
            ],
            text=True
        ).strip()

        if output.isdigit():
            return int(output)
    except Exception:
        pass
    return None

# ================= EJECUCIÓN =================

subprocess.Popen(
    f'"{SERVER_EXE}" {SERVER_PARAMS}',
    shell=True,
    cwd=os.path.dirname(SERVER_EXE)
)

time.sleep(6)

pid = find_palserver_pid()
if not pid:
    print("ERROR: Servidor iniciado pero no se pudo capturar el PID")
    sys.exit(3)

data = {
    "exe": SERVER_EXE,
    "pid": pid,
    "started_at": time.strftime("%Y-%m-%d %H:%M:%S")
}

with open(PID_FILE, "w", encoding="utf-8") as f:
    json.dump(data, f, indent=4)

print(f"OK: PalServer.exe iniciado con PID {pid}")
