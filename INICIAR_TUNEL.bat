@echo off
title MARK1 - Tunnel Cloudflare
color 0A
echo.
echo  ╔══════════════════════════════════════════════════╗
echo  ║         MARK1 · SSO - Iniciando Túnel           ║
echo  ╚══════════════════════════════════════════════════╝
echo.
echo  Asegúrate de que XAMPP Apache esté corriendo.
echo.

REM Verificar que cloudflared existe
where cloudflared >nul 2>&1
if %errorlevel% neq 0 (
    echo  ERROR: cloudflared no está instalado o no está en el PATH.
    echo  Descárgalo desde: https://github.com/cloudflare/cloudflared/releases
    pause
    exit /b 1
)

echo  Iniciando túnel en http://localhost:8080 ...
echo  La URL pública aparecerá en unos segundos.
echo  Comparte esa URL con quien necesite acceder.
echo.
echo  Presiona Ctrl+C para detener el túnel.
echo.

cloudflared tunnel --url http://localhost:8080 --protocol http2

pause
