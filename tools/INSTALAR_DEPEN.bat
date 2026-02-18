@echo off
title Instalador dependencias Palworld Panel

echo ===============================
echo Instalando dependencias Python
echo ===============================

"C:\Program Files\Python312\python.exe" -m pip install --upgrade pip

"C:\Program Files\Python312\python.exe" -m pip install requests

echo.
echo ===============================
echo Instalacion finalizada
echo ===============================
pause
