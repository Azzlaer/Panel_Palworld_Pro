import requests

# ===============================
# CONFIG (igual que config.php)
# ===============================
URL = "http://localhost:8212/v1/api/save"

USER = "Admin"
PASS = "35027595*"   # <- tu AdminPassword

# ===============================
# REQUEST
# ===============================
try:
    r = requests.post(
        URL,
        auth=(USER, PASS),
        timeout=5
    )

    print("STATUS:", r.status_code)
    print("RESPONSE:")
    print(r.text)

except Exception as e:
    print("ERROR:", e)
