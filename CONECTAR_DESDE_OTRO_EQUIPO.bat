@echo off
title Conectar a Sistema de Movilizacion HSEQ
echo ===================================================
echo   CONECTANDO AL SISTEMA CENTRAL DE MOVILIZACION
echo ===================================================
echo.
echo Conectando al servidor principal (192.168.1.22:8000)...
echo (Asegurese de que la PC Servidor tenga iniciado el servidor)
echo.
start msedge --app=http://192.168.1.22:8000 2>nul || start chrome --app=http://192.168.1.22:8000 2>nul || start http://192.168.1.22:8000
exit
