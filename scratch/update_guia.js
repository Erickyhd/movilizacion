const fs = require('fs');

const md = `# 🚀 Guía Maestra de Instalación, Configuración y Despliegue
## Sistema de Gestión y Control de Movilización HSEQ

---

### 📋 Tabla de Contenidos
1. [Requisitos Previos y Verificación de Herramientas](#1-requisitos-previos-y-verificación-de-herramientas)
2. [Instalación Rápida en un Nuevo Equipo](#2-instalación-rápida-en-un-nuevo-equipo)
3. [Alternativas de Despliegue y Empaquetado](#3-alternativas-de-despliegue-y-empaquetado)
   - [🟢 Opción 1: Servicio Windows 24/7 en Segundo Plano (NSSM) - Máxima Robustez](#-opción-1-servicio-windows-247-en-segundo-plano-nssm---máxima-robustez)
   - [💻 Opción 2: Compilar como Aplicación de Escritorio (.EXE con NativePHP / Electron)](#-opción-2-compilar-como-aplicación-de-escritorio-exe-con-nativephp--electron)
   - [📱 Opción 3: Despliegue Móvil (.APK Android y PWA Nativa)](#-opción-3-despliegue-móvil-apk-android-y-pwa-nativa)
   - [🌐 Opción 4: Servidor Web Local (Laragon / Apache / Nginx)](#-opción-4-servidor-web-local-laragon--apache--nginx)
4. [Cuadro Comparativo: ¿Cuál Alternativa Elegir?](#4-cuadro-comparativo-cuál-alternativa-elegir)
5. [Mantenimiento y Copias de Seguridad (Backup)](#5-mantenimiento-y-copias-de-seguridad-backup)

---

## 1. Requisitos Previos y Verificación de Herramientas

El equipo anfitrión debe contar con las siguientes herramientas mínimas:

| Herramienta | Versión Mínima | Propósito | Enlace de Descarga |
| :--- | :--- | :--- | :--- |
| **PHP** | \`8.2\` o \`8.3+\` | Motor de backend Laravel | [windows.php.net](https://windows.php.net/download/) / [Laragon](https://laragon.org/) |
| **Composer** | \`2.6+\` | Gestor de librerías PHP | [getcomposer.org](https://getcomposer.org/download/) |
| **Node.js & NPM** | \`Node 18+\` / \`NPM 9+\` | Compilador de Vue 3, Vite y Tailwind | [nodejs.org](https://nodejs.org/) |
| **SQLite3** | \`3.35+\` | Base de datos local (incluida en PHP) | Preintegrada en PHP |

### 🔍 Comandos de Verificación en Consola
Ejecute en PowerShell o CMD para validar que las herramientas existen en el equipo:
\`\`\`powershell
php -v
composer -v
node -v
npm -v
php -m | findstr -i "sqlite gd mbstring fileinfo zip"
\`\`\`

---

## 2. Instalación Rápida en un Nuevo Equipo

### A. Método Automático (1 Clic)
Ejecute el archivo [\`instalar_dependencias.bat\`](file:///c:/Users/USUARIO/Desktop/movilizacion/instalar_dependencias.bat) ubicado en la raíz del proyecto. Este script:
1. Crea el archivo \`.env\` configurado.
2. Crea el archivo SQLite \`database/movilizacion.sqlite\`.
3. Instala paquetes con Composer y NPM.
4. Ejecuta las migraciones de base de datos.
5. Compila el frontend a producción (\`npm run build\`).
6. Optimiza las cachés de Laravel.

### B. Método Manual por Comandos
*(Escriba o pegue los siguientes comandos en PowerShell situado en la carpeta del proyecto:)*

\`\`\`powershell
# 1. Abrir la carpeta del proyecto en PowerShell
cd C:\\Users\\USUARIO\\Desktop\\movilizacion

# 2. Configurar entorno y base de datos
copy .env.example .env
php -r "file_exists('database/movilizacion.sqlite') || touch('database/movilizacion.sqlite');"

# 3. Instalar librerías y compilar
composer install --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
npm install
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
\`\`\`

---

## 3. Alternativas de Despliegue y Empaquetado

---

### 🟢 Opción 1: Servicio Windows 24/7 en Segundo Plano (NSSM) - Máxima Robustez

Esta es la solución corporativa **más rápida, estable y profesional** para que el sistema corra 24/7 sin ventanas de consola visibles:

- **Ventajas:**
  - Inicia automáticamente al encender la PC/Servidor (incluso antes del login).
  - No tiene ventanas negras de consola que alguien pueda cerrar por error.
  - Windows supervisa el proceso: si se cae, se reinicia de inmediato.

#### Pasos de Configuración:
1. Descargue \`nssm.exe\` (64 bits) desde [nssm.cc/download](https://nssm.cc/download) y colóquelo en \`C:\\Windows\\System32\` (o en la carpeta del proyecto).
2. Abra PowerShell como **Administrador** y ejecute:
   \`\`\`powershell
   # 1. Crear el servicio de Windows
   nssm install MovilizacionHSEQ "C:\\php\\php.exe" "artisan serve --host=0.0.0.0 --port=8000"
   nssm set MovilizacionHSEQ AppDirectory "C:\\Users\\USUARIO\\Desktop\\movilizacion"
   nssm set MovilizacionHSEQ DisplayName "Sistema de Movilización HSEQ"
   nssm set MovilizacionHSEQ Description "Servidor Web 24/7 de Manifiestos de Movilización"
   nssm set MovilizacionHSEQ Start SERVICE_AUTO_START

   # 2. Iniciar el servicio
   nssm start MovilizacionHSEQ
   \`\`\`
3. **Acceso como Aplicación de Escritorio en las PCs:**
   Cree un acceso directo en el escritorio de los usuarios con el siguiente destino:
   \`"C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe" --app=http://localhost:8000\`
   *(Esto abre el sistema en su propia ventana limpia sin barra de navegación, idéntica a una aplicación de escritorio nativa).* 

---

### 💻 Opción 2: Compilar como Aplicación de Escritorio (.EXE con NativePHP / Electron)

Convierte todo el proyecto Laravel + Vue en un software instalador ejecutable \`.exe\` independiente para Windows.

#### ¿Cómo funciona?
**NativePHP** empaqueta un binario ultra-ligero de PHP, la base de datos SQLite y el runtime de Electron dentro de un instalador ejecutable de Windows.

---

#### 📌 Paso 0: ¿Dónde y Cómo Abrir PowerShell en el Proyecto? (¡Muy Importante!)
Para que ningún comando falle, **la consola PowerShell debe estar abierta dentro de la carpeta del proyecto**:
1. Abra el **Explorador de Archivos** de Windows y navegue a: \`C:\\Users\\USUARIO\\Desktop\\movilizacion\`
2. En la **barra de direcciones superior** (donde se ve la ruta de la carpeta), haga un clic, escriba \`powershell\` y presione **Enter**.
3. Se abrirá una ventana azul de PowerShell que debe iniciar exactamente con:
   \`\`\`powershell
   PS C:\\Users\\USUARIO\\Desktop\\movilizacion>
   \`\`\`
4. *(Opcional pero recomendado)* Habilite la ejecución de scripts en PowerShell para evitar bloqueos de seguridad de Windows:
   \`\`\`powershell
   Set-ExecutionPolicy RemoteSigned -Scope CurrentUser
   \`\`\`

> ⚠️ **IMPORTANTE ANTES DE INICIAR:** Si tiene ejecutándose \`php artisan serve\` o \`npm run dev\` en otra ventana de terminal, **ciérrelas o deténgalas con \`Ctrl + C\`**, ya que NativePHP gestiona su propio servidor interno y de lo contrario habrá conflicto de puertos.

---

#### 📌 Paso 1: Instalar el paquete NativePHP Electron
En la consola de PowerShell del proyecto, ejecute:
\`\`\`powershell
composer require nativephp/electron
\`\`\`

---

#### 📌 Paso 2: Inicializar la Configuración de Escritorio
Ejecute el comando de instalación de NativePHP:
\`\`\`powershell
php artisan native:install
\`\`\`
*(Este comando creará el archivo de configuración \`config/nativephp.php\` y el proveedor de servicio \`app/Providers/NativeAppServiceProvider.php\`).*

---

#### 📌 Paso 3: Configurar la Ventana de la Aplicación
Abra el archivo recién generado: [\`app/Providers/NativeAppServiceProvider.php\`](file:///c:/Users/USUARIO/Desktop/movilizacion/app/Providers/NativeAppServiceProvider.php) y verifique que el método \`boot()\` tenga configurada la ventana principal:

\`\`\`php
<?php

namespace App\\Providers;

use Native\\Laravel\\Facades\\Window;
use Native\\Laravel\\Contracts\\ProvidesPhpIni;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    public function boot(): void
    {
        Window::open()
            ->title('Sistema de Control y Gestión de Movilización HSEQ')
            ->width(1366)
            ->height(850)
            ->minWidth(1024)
            ->minHeight(700)
            ->rememberState()
            ->showDevTools(false);
    }

    public function phpIni(): array
    {
        return [];
    }
}
\`\`\`

---

#### 📌 Paso 4: Compilar los Recursos de Frontend
Antes de iniciar NativePHP, compile la interfaz gráfica de Vue 3 a producción:
\`\`\`powershell
npm run build
\`\`\`

---

#### 📌 Paso 5: Probar la Aplicación en Modo Desarrollo
Ejecute el siguiente comando para levantar la aplicación de escritorio y probar su funcionamiento:
\`\`\`powershell
php artisan native:serve
\`\`\`
*(Se abrirá automáticamente la ventana de escritorio de Windows con el sistema listo para interactuar).*

---

#### 📌 Paso 6: Compilar el Instalador \`.EXE\` de Windows para Distribución
Cuando desee generar el instalador final para llevarlo a cualquier otra computadora:
\`\`\`powershell
php artisan native:build
\`\`\`
- **Resultado:** El archivo instalador \`.exe\` se generará automáticamente en la carpeta:
  \`C:\\Users\\USUARIO\\Desktop\\movilizacion\\dist\\\`
- Este \`.exe\` se puede copiar a una memoria USB e instalar en cualquier equipo con Windows 10 u 11 sin necesidad de que dicho equipo tenga instalado PHP, Composer o Node.js.

---

#### 🛠️ Solución a Errores Frecuentes en NativePHP (Windows)

| Mensaje de Error | Causa Principal | Solución Rápida |
| :--- | :--- | :--- |
| \`Could not open input file: artisan\` | PowerShell se abrió en \`C:\\Users\\USUARIO\` y no en la carpeta del proyecto. | Ejecutar \`cd C:\\Users\\USUARIO\\Desktop\\movilizacion\` antes de cualquier comando. |
| \`Execution of scripts is disabled on this system\` | Restricción de seguridad por defecto de Windows PowerShell. | Ejecutar: \`Set-ExecutionPolicy RemoteSigned -Scope CurrentUser\` |
| \`Port 8000 already in use\` o conflicto al iniciar | Hay otro \`php artisan serve\` corriendo en segundo plano. | Detener el servidor previo con \`Ctrl + C\` o cerrar esa terminal. |
| Pantalla en blanco al abrir la ventana | Falta compilar los archivos de Vue / Vite. | Ejecutar \`npm run build\` y volver a lanzar \`php artisan native:serve\`. |

---

### 📱 Opción 3: Despliegue Móvil (.APK Android y PWA Nativa)

Para conductores, supervisores en ruta o personal de campo:

#### A. PWA Nativa (Instalación Instantánea en 1 Clic):
El sistema ya incluye \`manifest.webmanifest\`, soporte táctil y diseño responsive completo.
1. El celular o tablet se conecta a la misma red Wi-Fi que el servidor.
2. Abre en Google Chrome de Android la URL del servidor: \`http://192.168.1.XX:8000\`.
3. Aparece el botón automático: **"Agregar a la pantalla principal" / "Instalar App"**.
4. Se crea el icono en el celular y se abre a **pantalla completa (Full Screen)** como una App nativa.

#### B. Generar un instalador \`.APK\` con Capacitor:
1. Instalar Capacitor en el proyecto:
   \`\`\`powershell
   npm install @capacitor/core @capacitor/cli @capacitor/android
   npx cap init "Movilizacion HSEQ" "com.movilizacion.hseq" --web-dir public
   npx cap add android
   \`\`\`
2. En \`capacitor.config.json\` configurar la URL del servidor:
   \`\`\`json
   {
     "appId": "com.movilizacion.hseq",
     "appName": "Movilizacion HSEQ",
     "webDir": "public",
     "server": {
       "url": "http://192.168.1.50:8000",
       "cleartext": true
     }
   }
   \`\`\`
3. Generar el APK en Android Studio:
   \`\`\`powershell
   npx cap open android
   \`\`\`
   *(En Android Studio: \`Build > Build Bundle(s) / APK(s) > Build APK(s)\`)*.

---

### 🌐 Opción 4: Servidor Web Local (Laragon / Apache / Nginx)

Si la empresa cuenta con un servidor dedicado con Apache o Nginx:
1. Apunte el \`DocumentRoot\` del servidor web a la carpeta \`public/\` del proyecto.
2. Configure el Virtual Host \`http://movilizacion.local\`.

---

## 4. Cuadro Comparativo: ¿Cuál Alternativa Elegir?

| Criterio | Opción 1: Servicio NSSM + Acceso Directo | Opción 2: .EXE con NativePHP | Opción 3: PWA / APK Móvil | Opción 4: Laragon / Apache |
| :--- | :--- | :--- | :--- | :--- |
| **Tiempo de Configuración** | ⚡ **2 minutos** | ⏱️ 15 minutos | ⚡ Instantáneo | ⏱️ 10 minutos |
| **Disponibilidad 24/7** | 🛡️ **Excelente (Auto-restart)** | ⚠️ Requiere abrir la app | 📶 Conectado al servidor | 🛡️ Excelente |
| **Multiusuario en Red** | 🌐 **Sí (PCs + Celulares)** | 💻 Solo PC local | 📱 Celulares y Tablets | 🌐 Sí (PCs + Celulares) |
| **Facilidad de Uso** | 🖥️ **Icono de Escritorio** | 📦 Instalador .exe | 📲 Icono en Celular | 🌐 Vía Navegador |
| **Recomendado Para** | **Servidor / PC Principal Oficina** | **Uso individual en 1 PC** | **Conductores / Campo** | **Servidor Web Dedicado** |

---

## 5. Mantenimiento y Copias de Seguridad (Backup)

Toda la base de datos (manifiestos, trabajadores, empresas, flota y rutas) está centralizada en:

📁 **\`database/movilizacion.sqlite\`**

### 💾 Backup en 1 Paso:
Copie el archivo \`database/movilizacion.sqlite\` a un disco externo, pendrive o carpeta en la nube.

### 🔄 Restauración en 1 Paso:
Copie el archivo de respaldo sobre \`database/movilizacion.sqlite\` y el sistema recuperará instantáneamente toda la información histórica.
`;

fs.writeFileSync('c:/Users/USUARIO/Desktop/movilizacion/GUIA_INSTALACION_Y_DESPLIEGUE.md', md, 'utf8');
console.log('Update complete!');