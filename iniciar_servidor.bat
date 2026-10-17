@echo off
title Servidor Central - Sistema de Movilizacion HSEQ
cls
echo ========================================================
echo   INICIANDO SERVIDOR CENTRAL DE MOVILIZACION HSEQ
echo ========================================================
echo.

:: Obtener la direccion IP local automaticamente
set LOCAL_IP=127.0.0.1
for /f "tokens=4" %%a in ('route print ^| findstr 0.0.0.0.*0.0.0.0') do (
    if not "%%a"=="" set LOCAL_IP=%%a
)

echo [OK] Base de Datos Activa: database\movilizacion.sqlite
echo.
echo --------------------------------------------------------
echo  1. PARA USAR EN ESTA COMPUTADORA (PC PRINCIPAL):
echo     Abre tu navegador y entra a:
echo     http://localhost:8000
echo.
echo  2. PARA USAR DESDE TU LAPTOP O CUALQUIER OTRO EQUIPO:
echo     Abre el navegador en la laptop y entra a:
echo     http://%LOCAL_IP%:8000
echo --------------------------------------------------------
echo.
echo (Mantenga esta ventana abierta mientras trabaje con el sistema)
echo.

php artisan serve --host=0.0.0.0 --port=8000
pause
