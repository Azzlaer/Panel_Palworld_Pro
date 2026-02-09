@echo off
title Instalador Python + psutil para Panel Palworld
color 0A

echo ==========================================
echo  Instalador Python para Panel Palworld
echo ==========================================
echo.

:: ------------------------------------------
:: Verificar Python
:: ------------------------------------------
python --version >nul 2>&1
if %errorlevel%==0 (
    echo [OK] Python ya esta instalado.
    goto CHECK_PIP
)

echo [INFO] Python NO encontrado. Descargando...
echo.

:: ------------------------------------------
:: Descargar Python (64 bits)
:: ------------------------------------------
set PY_URL=https://www.python.org/ftp/python/3.12.2/python-3.12.2-amd64.exe
set PY_EXE=%TEMP%\python_installer.exe

powershell -Command "Invoke-WebRequest -Uri '%PY_URL%' -OutFile '%PY_EXE%'"
if not exist "%PY_EXE%" (
    echo [ERROR] No se pudo descargar Python.
    pause
    exit /b 1
)

echo [INFO] Instalando Python...
"%PY_EXE%" /quiet InstallAllUsers=1 PrependPath=1 Include_test=0
if %errorlevel% neq 0 (
    echo [ERROR] Fallo la instalacion de Python.
    pause
    exit /b 1
)

echo [OK] Python instalado correctamente.
echo.

:: ------------------------------------------
:: Refrescar PATH
:: ------------------------------------------
setx PATH "%PATH%" >nul

:CHECK_PIP
echo [INFO] Verificando pip...
python -m pip --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [INFO] Instalando pip...
    python -m ensurepip --upgrade
)

echo [OK] pip listo.
echo.

:: ------------------------------------------
:: Instalar psutil
:: ------------------------------------------
echo [INFO] Instalando psutil...
python -m pip install --upgrade pip
python -m pip install psutil

if %errorlevel% neq 0 (
    echo [ERROR] No se pudo instalar psutil.
    pause
    exit /b 1
)

echo [OK] psutil instalado correctamente.
echo.

:: ------------------------------------------
:: Test final
:: ------------------------------------------
echo [TEST] Verificando Python y psutil...
python - <<EOF
import sys, psutil
print("Python:", sys.version)
print("psutil:", psutil.__version__)
EOF

echo.
echo ==========================================
echo  INSTALACION COMPLETADA CON EXITO
echo ==========================================
echo.
echo Ya puedes usar el panel Palworld sin problemas.
echo Si Apache/XAMPP estaba abierto, REINICIALO.
echo.

pause
