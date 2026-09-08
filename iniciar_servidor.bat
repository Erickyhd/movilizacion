@echo off
title Servidor de Movilizacion HSEQ
echo ===================================================
echo   INICIANDO SERVIDOR DE MOVILIZACION HSEQ
echo ===================================================
echo.
echo URL Local:   http://localhost:8000
echo.
php artisan serve --host=0.0.0.0 --port=8000
pause