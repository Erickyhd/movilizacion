# 🚀 Guía Maestra de Instalación, Configuración y Despliegue
## Sistema de Gestión de Movilización y Manifiestos HSEQ

Esta guía proporciona el **paso a paso detallado**, las **verificaciones previas** y las **alternativas de despliegue en producción** para instalar y ejecutar el sistema en cualquier computadora local (Windows / Linux) o servidor corporativo.

---

## 📑 Tabla de Contenidos
1. [Requisitos Previos del Sistema](#1-requisitos-previos-del-sistema)
2. [Verificación de Herramientas Instaladas](#2-verificación-de-herramientas-instaladas)
3. [Guía de Instalación Paso a Paso (Puesta en Marcha)](#3-guía-de-instalación-paso-a-paso-puesta-en-marcha)
4. [Alternativas de Despliegue y Ejecución Permanente](#4-alternativas-de-despliegue-y-ejecución-permanente)
   - [Alternativa 1: Servicio en Segundo Plano (NSSM / Windows Service) - ⭐ Recomendada](#alternativa-1-servicio-en-segundo-plano-nssm--windows-service---recomendada)
   - [Alternativa 2: Servidor Web Local Corporativo (Laragon / Apache / Nginx)](#alternativa-2-servidor-web-local-corporativo-laragon--apache--nginx)
   - [Alternativa 3: Empaquetado como Aplicación de Escritorio (.EXE con NativePHP / Electron)](#alternativa-3-empaquetado-como-aplicación-de-escritorio-exe-con-nativephp--electron)
   - [Alternativa 4: Acceso en Red Local y Aplicación Móvil (PWA / APK)](#alternativa-4-acceso-en-red-local-y-aplicación-móvil-pwa--apk)
5. [Mantenimiento y Copias de Seguridad (Backup)](#5-mantenimiento-y-copias-de-seguridad-backup)

---

## 1. Requisitos Previos del Sistema

El nuevo equipo donde se instalará el sistema debe contar con las siguientes herramientas instaladas:

| Herramienta | Versión Mínima | Propósito | Enlace de Descarga |
| :--- | :--- | :--- | :--- |
| **PHP** | `8.2` o `8.3+` | Motor de ejecución del Backend Laravel | [windows.php.net](https://windows.php.net/download/) / [Laragon](https://laragon.org/) |
| **Composer** | `2.6+` | Gestor de paquetes y librerías PHP | [getcomposer.org](https://getcomposer.org/download/) |
| **Node.js & NPM** | `Node 18+` / `NPM 9+` | Compilación de Vue 3, Vite y Tailwind | [nodejs.org](https://nodejs.org/) |
| **SQLite3** | `3.35+` | Base de datos local (incluida en PHP) | Viene preintegrada en PHP |
| **Git** *(Opcional)* | `2.40+` | Control de versiones y clonación | [git-scm.com](https://git-scm.com/) |

### ⚙️ Extensiones PHP Necesarias
En el archivo `php.ini` del equipo, asegúrese de tener habilitadas las siguientes extensiones (quitando el `;` inicial):
```ini
extension=curl
extension=fileinfo
extension=gd
extension=mbstring
extension=openssl
extension=pdo_sqlite
extension=sqlite3
extension=zip
```

---

## 2. Verificación de Herramientas Instaladas

Abra una consola (**PowerShell** o **Símbolo del Sistema / CMD**) y ejecute los siguientes comandos para validar que el entorno está listo:

```powershell
# 1. Verificar versión de PHP
php -v
# Salida esperada: PHP 8.2.x o PHP 8.3.x

# 2. Verificar extensiones SQLite y GD en PHP
php -m | findstr -i "sqlite gd mbstring fileinfo"
# Salida esperada: pdo_sqlite, sqlite3, gd, mbstring, fileinfo

# 3. Verificar Composer
composer -v
# Salida esperada: Composer version 2.x.x

# 4. Verificar Node.js y NPM
node -v
npm -v
# Salida esperada: v18.x.x o v20.x.x y 10.x.x
```

---

## 3. Guía de Instalación Paso a Paso (Puesta en Marcha)

Siga esta secuencia de comandos para instalar el sistema en el nuevo equipo desde cero:

### Paso 1: Copiar o Clonar el Proyecto
Copie la carpeta del proyecto a la ruta deseada (por ejemplo, `C:\Sistemas\movilizacion` o `C:\laragon\www\movilizacion`).
```powershell
cd C:\Sistemas\movilizacion
```

### Paso 2: Configurar las Variables de Entorno (`.env`)
Copie el archivo `.env.example` a `.env` si no existe:
```powershell
copy .env.example .env
```
Asegúrese de que el archivo `.env` contenga la configuración de SQLite:
```env
APP_NAME="Sistema de Movilización"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8000
APP_TIMEZONE=America/Lima

DB_CONNECTION=sqlite
DB_DATABASE=database/movilizacion.sqlite
```

### Paso 3: Crear el Archivo de Base de Datos SQLite
Asegúrese de que el archivo SQLite exista dentro de `database/`:
```powershell
# Crear el archivo SQLite si no existe
php -r "file_exists('database/movilizacion.sqlite') || touch('database/movilizacion.sqlite');"
```

### Paso 4: Instalar Dependencias de PHP (Backend)
Instale las librerías optimizadas para producción:
```powershell
composer install --optimize-autoloader --no-dev
```

### Paso 5: Generar la Clave de la Aplicación y Migraciones
```powershell
# Generar APP_KEY de seguridad
php artisan key:generate

# Ejecutar migraciones de la base de datos
php artisan migrate --force
```

### Paso 6: Compilar los Recursos Frontend (Vue 3 + Vite)
Para que el sistema funcione de forma autónoma **sin necesidad de tener `npm run dev` abierto todo el tiempo**, se compilan los archivos a producción:
```powershell
npm install
npm run build
```
*(Esto genera la carpeta optimizada `public/build` con todo el JavaScript, CSS y fuentes).*

### Paso 7: Optimizar Caché de Laravel
```powershell
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 4. Alternativas de Despliegue y Ejecución Permanente

Para evitar que el sistema se cierre si alguien cierra la ventana negra de la terminal, dispones de las siguientes alternativas corporativas:

---

### Alternativa 1: Servicio en Segundo Plano (NSSM / Windows Service) - ⭐ Recomendada

Esta alternativa convierte el comando de Laravel en un **Servicio de Windows real**, el cual:
- Inicia automáticamente al encender la computadora (incluso antes de iniciar sesión).
- Corre silenciosamente en segundo plano sin ninguna ventana abierta.
- Se reinicia automáticamente si ocurre algún fallo.

#### Pasos para configurar con NSSM (Non-Sucking Service Manager):
1. Descargue NSSM desde [nssm.cc](https://nssm.cc/download) y extraiga `nssm.exe` (versión 64-bit).
2. Abra PowerShell como **Administrador** y ejecute:
   ```powershell
   # Instalar el servicio con nombre 'MovilizacionHSEQ'
   nssm install MovilizacionHSEQ "C:\php\php.exe" "artisan serve --host=0.0.0.0 --port=8000"
   nssm set MovilizacionHSEQ AppDirectory "C:\Sistemas\movilizacion"
   nssm set MovilizacionHSEQ DisplayName "Sistema de Movilización HSEQ"
   nssm set MovilizacionHSEQ Description "Servidor Web Local para Manifiestos de Movilización"
   nssm set MovilizacionHSEQ Start SERVICE_AUTO_START

   # Iniciar el servicio
   nssm start MovilizacionHSEQ
   ```
3. ¡Listo! El sistema estará disponible 24/7 en `http://localhost:8000` o desde cualquier otra máquina en la red local `http://IP_DEL_SERVIDOR:8000`.

---

### Alternativa 2: Servidor Web Local Corporativo (Laragon / Apache / Nginx)

Si se cuenta con un servidor con **Laragon**, **XAMPP** o **Apache/Nginx**:
1. Cree un Virtual Host apuntando al directorio `public/` del proyecto.
   - **Ejemplo en Apache (`httpd-vhosts.conf`):**
     ```apache
     <VirtualHost *:80>
         ServerName movilizacion.local
         DocumentRoot "C:/Sistemas/movilizacion/public"
         <Directory "C:/Sistemas/movilizacion/public">
             AllowOverride All
             Require all granted
         </Directory>
     </VirtualHost>
     ```
2. En `hosts` de Windows (`C:\Windows\System32\drivers\etc\hosts`), agregue:
   ```text
   127.0.0.1 movilizacion.local
   ```
3. Ahora puede ingresar directamente a `http://movilizacion.local` a velocidad nativa de servidor web.

---

### Alternativa 3: Empaquetado como Aplicación de Escritorio (.EXE con NativePHP / Electron)

Si se prefiere distribuir el sistema como un **instalador de escritorio independiente (.exe)** con su propia ventana e icono (como si fuera Microsoft Teams o Spotify):
1. Se puede utilizar **NativePHP for Desktop** (`nativephp/electron`):
   ```powershell
   composer require nativephp/electron
   php artisan native:install
   php artisan native:build
   ```
2. Esto compila un archivo instalador `.exe` que incluye PHP embebido, SQLite y Electron, permitiendo instalarlo con doble clic en cualquier PC sin configurar servidores.

---

### Alternativa 4: Acceso en Red Local y Aplicación Móvil (PWA / APK)

Dado que la interfaz desarrollada con Vue 3 y Tailwind es **100% responsiva** para celulares y tablets:
1. **Acceso desde Celulares en la misma red Wi-Fi:**
   - Inicie el servidor con host abierto: `php artisan serve --host=0.0.0.0 --port=8000`
   - Desde el celular (Android/iOS) conectado a la misma red Wi-Fi, ingrese a: `http://192.168.1.XX:8000` (reemplazando `192.168.1.XX` por la IP local de la computadora).
2. **Convertir en APK Android:**
   - Se puede empaquetar utilizando **Capacitor** o una **PWA (Progressive Web App)** para instalar el icono directamente en la pantalla de inicio del teléfono del conductor o supervisor de transporte.

---

## 5. Mantenimiento y Copias de Seguridad (Backup)

Al utilizar **SQLite**, toda la información del sistema (manifiestos, trabajadores, empresas, conductores, vehículos, historial de viajes) reside en un único archivo:

📁 **`database/movilizacion.sqlite`**

### 💾 Cómo hacer Backup:
Para respaldar el sistema completo, simplemente copie el archivo `database/movilizacion.sqlite` a una memoria USB, carpeta en la nube (Google Drive / OneDrive) o disco secundario.

### 🔄 Restauración en caso de emergencia:
Reemplace el archivo `database/movilizacion.sqlite` por la copia de seguridad y el sistema volverá a estar 100% operativo de inmediato con todos sus datos intactos.