# 🕹️ Palworld Server Control Panel (Windows)

Panel web profesional para **administrar servidores de Palworld** en **Windows 10 / 11**, desarrollado en **PHP + Python**, pensado para funcionar en **XAMPP** y servidores dedicados o domésticos.

Este panel permite **iniciar, detener, monitorear y administrar** un servidor de Palworld de forma segura, usando **PID real**, evitando conflictos con múltiples servidores en la misma máquina.

---

## 🚀 Características principales

✅ Inicio del servidor Palworld desde la web  
✅ Detención segura usando **PID real**  
✅ Prevención de cierre de otros servidores  
✅ Integración **PHP ↔ Python**  
✅ Compatible con **Windows 10 / 11**  
✅ Compatible con **XAMPP / Apache**  
✅ Logs en tiempo real + archivo persistente  
✅ Arquitectura escalable (multi-servidor futura)  
✅ Preparado para REST API / RCON  

---

## 🗂️ Árbol del proyecto

```
pal1/
│
├── pages/
│   ├── servers.php
│   ├── informacion.php
│   ├── rcon.php
│   ├── procesos.php
│
├── logs/
│   └── servers.log
│
├── config.php
├── servers.json
├── palworld_server.json
├── start_palworld.py
├── server.pid.json
├── api.php
├── index.php
├── dashboard.php
├── header.php
├── footer.php
└── README.md
```

---

## ⚙️ Requisitos

- Windows 10 / 11
- XAMPP
- Python 3.9+
- SteamCMD
- Palworld Dedicated Server

---

## 📜 Licencia

Uso privado / educativo / comunitario.
