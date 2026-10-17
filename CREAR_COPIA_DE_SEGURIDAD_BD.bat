@echo off
title Copia de Seguridad - Base de Datos Sistema Movilizacion HSEQ
cls
echo ========================================================
echo   CREANDO COPIA DE SEGURIDAD (BACKUP) DE LA BASE DE DATOS
echo ========================================================
echo.

:: Obtener fecha y hora formateada (formato YYYY-MM-DD_HH-MM)
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set dt=%%I
set YYYY=%dt:~0,4%
set MM=%dt:~4,2%
set DD=%dt:~6,2%
set HH=%dt:~8,2%
set Min=%dt:~10,2%
set Sec=%dt:~12,2%
set TIMESTAMP=%YYYY%-%MM%-%DD%_%HH%-%Min%-%Sec%

set BACKUP_DIR=%~dp0backups
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

set SOURCE_DB=%~dp0database\movilizacion.sqlite
set DEST_DB=%BACKUP_DIR%\movilizacion_backup_%TIMESTAMP%.sqlite

if not exist "%SOURCE_DB%" (
    echo [ERROR] No se encontro el archivo de base de datos en:
    echo %SOURCE_DB%
    echo.
    pause
    exit /b
)

copy /y "%SOURCE_DB%" "%DEST_DB%" >nul

echo [OK] Copia de seguridad creada exitosamente:
echo.
echo Archivo: movilizacion_backup_%TIMESTAMP%.sqlite
echo Carpeta: %BACKUP_DIR%
echo.
echo ========================================================
echo   COPIA LISTA: Puedes guardar este archivo en un USB
echo   o en la nube para maxima seguridad.
echo ========================================================
echo.
pause
exit
