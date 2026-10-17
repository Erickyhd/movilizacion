@echo off
:: Comprobar si se ejecuta como Administrador
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo.
    echo ========================================================
    echo   ATENCION: SE REQUIEREN PERMISOS DE ADMINISTRADOR
    echo ========================================================
    echo.
    echo   Por favor, haga CLIC DERECHO sobre este archivo y elija:
    echo   "EJECUTAR COMO ADMINISTRADOR"
    echo.
    pause
    exit /b
)

title Habilitar Acceso en Red Local - Sistema Movilizacion HSEQ
cls
echo ========================================================
echo   CONFIGURANDO FIREWALL DE WINDOWS PARA RED LOCAL
echo ========================================================
echo.
echo 1. Habilitando puerto 8000 (TCP) de entrada...
powershell -Command "New-NetFirewallRule -DisplayName 'Sistema Movilizacion HSEQ (Puerto 8000)' -Direction Inbound -LocalPort 8000 -Protocol TCP -Action Allow -Profile Any -ErrorAction SilentlyContinue | Out-Null"

echo.
echo ========================================================
echo   RESULTADO: LISTO Y HABILITADO
echo ========================================================
echo.
echo   El puerto 8000 ha sido permitido en el Firewall de Windows.
echo   Ahora tu Laptop podra conectarse a esta computadora sin bloqueos.
echo.
pause
exit
