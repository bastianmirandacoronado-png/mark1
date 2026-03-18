@echo off
title Mark 1 — DSSO

echo.
echo  ╔══════════════════════════════════════════╗
echo  ║   Mark 1 · DSSO · Servicio de Salud     ║
echo  ║   Levantando servidor y tunel...         ║
echo  ╚══════════════════════════════════════════╝
echo.

:: Iniciar Apache si no está corriendo
tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I "httpd.exe" >NUL
if errorlevel 1 (
    echo  [1/2] Iniciando Apache XAMPP...
    start "" "C:\xampp\apache\bin\httpd.exe"
    timeout /t 3 /nobreak >NUL
) else (
    echo  [1/2] Apache ya esta corriendo. OK
)

:: Verificar si cloudflared existe
if exist "C:\Program Files\cloudflared\cloudflared.exe" (
    set CLOUDFLARED="C:\Program Files\cloudflared\cloudflared.exe"
) else if exist "C:\cloudflared\cloudflared.exe" (
    set CLOUDFLARED="C:\cloudflared\cloudflared.exe"
) else (
    where cloudflared >NUL 2>&1
    if errorlevel 1 (
        echo  [2/2] cloudflared no encontrado.
        echo.
        echo  Acceso solo en red local:
        echo  http://localhost:8080/MARK1/
        echo.
        echo  Para acceso externo, descarga cloudflared desde:
        echo  https://github.com/cloudflare/cloudflared/releases
        echo.
        start "" "http://localhost:8080/MARK1/"
        pause
        exit /b
    )
    set CLOUDFLARED=cloudflared
)

echo  [2/2] Iniciando tunel Cloudflare...
echo.
echo  ════════════════════════════════════════════
echo  La URL publica aparecera en esta ventana.
echo  Comparte esa URL con quien necesites.
echo  Cierra esta ventana para detener el acceso.
echo  ════════════════════════════════════════════
echo.

start "" "http://localhost:8080/MARK1/"
%CLOUDFLARED% tunnel --url http://localhost:8080/MARK1

pause
