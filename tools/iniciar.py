import json
import subprocess
import time
import os
import sys

BASE_DIR = os.path.dirname(os.path.dirname(__file__))

CONFIG_FILE = os.path.join(BASE_DIR, "palworld_server.json")
PID_FILE = os.path.join(BASE_DIR, "server.pid")

# ===============================
# LEER CONFIG JSON
# ===============================
if not os.path.isfile(CONFIG_FILE):
    print("ERROR: palworld_server.json no encontrado")
    sys.exit(1)

with open(CONFIG_FILE, "r", encoding="utf-8") as f:
    cfg = json.load(f)

exe = cfg.get("exe")
params = cfg.get("params", "")

if not exe or not os.path.isfile(exe):
    print("ERROR: Ejecutable inválido")
    sys.exit(1)

# ===============================
# INICIAR SERVIDOR
# ===============================

cmd = f'"{exe}" {params}'

process = subprocess.Popen(
    cmd,
    cwd=os.path.dirname(exe),
    shell=True
)

# esperar creación real
time.sleep(3)

pid = process.pid

with open(PID_FILE, "w") as f:
    f.write(str(pid))

print(f"Servidor iniciado PID={pid}")
