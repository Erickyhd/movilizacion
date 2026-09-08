@echo off
title Instalador Automatico - Sistema de Movilizacion
echo ===================================================
echo   CONFIGURANDO ENTORNO EN ESTE EQUIPO
echo ===================================================
echo.

if not exist .env (
    echo [*] Copiando .env.example a .env...
    copy .env.example .env
)

echo [*] Verificando base de datos SQLite...
php -r "file_exists('database/movilizacion.sqlite') || touch('database/movilizacion.sqlite');"

echo [*] Instalando dependencias de PHP...
call composer install --optimize-autoloader

echo [*] Generando clave de aplicacion...
php artisan key:generate --force

echo [*] Ejecutando migraciones de base de datos...
php artisan migrate --force

echo [*] Instalando paquetes de Node.js...
call npm install

echo [*] Compilando frontend para produccion...
call npm run build

echo [*] Optimizando cache de Laravel...
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo.
echo ===================================================
echo   INSTALACION COMPLETADA EXITOSAMENTE!
echo   Para iniciar el sistema, ejecute: iniciar_servidor.bat
echo ===================================================
pause