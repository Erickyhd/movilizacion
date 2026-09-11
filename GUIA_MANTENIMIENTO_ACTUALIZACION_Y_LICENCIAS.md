# 🔄 Guía de Mantenimiento, Actualizaciones y Sistema de Licenciamiento
## Sistema de Gestión y Control de Movilización HSEQ

---

### 📋 Tabla de Contenidos
1. [Arquitectura del Ejecutable y Persistencia de Datos](#1-arquitectura-del-ejecutable-y-persistencia-de-datos)
2. [Flujo de Actualización del Sistema (Paso a Paso)](#2-flujo-de-actualización-del-sistema-paso-a-paso)
   - [A. Realizar cambios en Frontend (Vue 3 / Vite) o Backend (PHP / Laravel)](#a-realizar-cambios-en-frontend-o-backend)
   - [B. ¿Qué pasa si agregué nuevas tablas o columnas a la base de datos?](#b-qué-pasa-si-agregué-nuevas-tablas-o-columnas)
   - [C. Recompilar y Empaquetar la Nueva Versión](#c-recompilar-y-empaquetar-la-nueva-versión)
   - [D. Despliegue en la PC del Cliente (Cero Pérdida de Datos)](#d-despliegue-en-la-pc-del-cliente-cero-pérdida-de-datos)
3. [Estrategias de Licenciamiento y Control Mensual/Anual (SaaS On-Premise)](#3-estrategias-de-licenciamiento-y-control-mensualanual)
   - [Modelo 1: Licenciamiento Offline con Token Criptográfico (Recomendado para Faenas/Mina)](#modelo-1-licenciamiento-offline-con-token-criptográfico)
   - [Modelo 2: Validación Online Automática (API Ping vía Servidor Web)](#modelo-2-validación-online-automática)
   - [Implementación de la Pantalla de Bloqueo por Licencia Vencida](#implementación-de-la-pantalla-de-bloqueo)
4. [Buenas Prácticas de Soporte Técnico y Backups](#4-buenas-prácticas-de-soporte-técnico-y-backups)

---

## 1. Arquitectura del Ejecutable y Persistencia de Datos

### ¿Por qué `win-unpacked/laravel.exe` es el método óptimo para distribuir?
Cuando compilas con NativePHP, se genera la carpeta:
📁 `nativephp/electron/dist/win-unpacked/`

* **`win-unpacked` (Modo Portable por Carpeta):** Contiene el motor ejecutable `laravel.exe` junto con sus librerías adyacentes (`resources/`, `locales/`). Abre **instantáneamente** y no es bloqueado por Windows Defender ni por restricciones de permisos de archivos temporales.
* **Separación de Código vs. Datos:**
  * **El Código del Sistema (Vistas, Controladores, Rutas):** Vive dentro de la carpeta `win-unpacked`.
  * **La Base de Datos Real del Cliente:** Vive de forma independiente en la ruta de usuario de Windows:
    `C:\Users\<Nombre_Usuario>\AppData\Roaming\nativephp\storage\database\database.sqlite` (en modo producción) o en `database/movilizacion.sqlite`.

> 💡 **Principio Fundamental:** Aunque reemplaces la carpeta `win-unpacked` con una versión nueva del software, **la base de datos del cliente NUNCA se sobreescribe ni se borra**, garantizando que todo su historial permanezca 100% a salvo.

---

## 2. Flujo de Actualización del Sistema (Paso a Paso)

Cuando necesites agregar nuevas funciones, arreglar vistas o mejorar el sistema para tus clientes, sigue este ciclo de desarrollo:

```
[1. Código en tu PC] ──> [2. Compilar Frontend] ──> [3. Compilar NativePHP] ──> [4. Entregar win-unpacked al Cliente]
```

### A. Realizar cambios en Frontend o Backend
1. Trabaja normalmente en tu entorno local (`C:\Users\USUARIO\Desktop\movilizacion`).
2. Modifica componentes en `resources/js/Pages/` o controladores en `app/Http/Controllers/`.

---

### B. ¿Qué pasa si agregué nuevas tablas o columnas?
Si creaste una nueva migración con `php artisan make:migration agregar_campo_x`:
1. NativePHP ejecuta automáticamente las migraciones pendientes en el arranque (`php artisan migrate`), por lo que **la base de datos del cliente se actualizará a la nueva estructura sin perder sus datos previos**.

---

### C. Recompilar y Empaquetar la Nueva Versión
En tu consola PowerShell del proyecto:

```powershell
# 1. Compilar los recursos de Vue 3 / Vite
npm run build

# 2. Compilar el plugin interno (si hubo cambios en Electron)
cd nativephp/electron; npm run plugin:build; cd ../..

# 3. Compilar el paquete de distribución
php artisan native:build win x64
```

---

### D. Despliegue en la PC del Cliente (Cero Pérdida de Datos)
1. Toma la carpeta recién compilada `nativephp/electron/dist/win-unpacked/`.
2. Puedes renombrar la carpeta como quieras (ej. `Sistema_Movilizacion_v1.1`).
3. En la PC del cliente:
   - Cierra la aplicación si está abierta.
   - Reemplaza la carpeta anterior con la nueva carpeta.
   - El cliente le da doble clic a `laravel.exe` y verá todas las nuevas funciones con todos sus datos y manifiestos intactos.

---

## 3. Estrategias de Licenciamiento y Control Mensual/Anual

Si vas a comercializar el sistema bajo un modelo de suscripción (ej. cobro mensual o anual por servicio de soporte y uso), existen dos alternativas para tener el control total:

---

### Modelo 1: Licenciamiento Offline con Token Criptográfico (Recomendado)
**Ideal para empresas mineras, campamentos o faenas donde no siempre hay conexión a Internet permanente.**

#### ¿Cómo funciona?
1. Cada cliente tiene un identificador único (ej. RUC de la empresa o ID de instalación).
2. Tú, como proveedor, tienes un generador de licencias en tu PC.
3. Cuando el cliente te paga el mes, tú le envías un **Código de Activación** (ej. `MOV-202610-A8F9B2C1`).
4. Ese código contiene la fecha de expiración cifrada con una clave secreta que solo tú conoces.
5. El sistema descifra la fecha:
   * Si la fecha actual de la PC es menor a la fecha de vencimiento 👉 **Permite el acceso**.
   * Si la fecha ya venció 👉 **Muestra una pantalla: "Suscripción Vencida. Ingrese su nuevo código de activación para continuar"**.

#### Estructura del Middleware de Licencia en Laravel:
```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CheckLicenseStatus
{
    public function handle(Request $request, Closure $next)
    {
        $licencia = config('app.license_expires_at'); // o guardado en BD
        
        if (Carbon::now()->greaterThan(Carbon::parse($licencia))) {
            return inertia('Licencia/Vencida', [
                'mensaje' => 'Su suscripción mensual ha expirado. Por favor, contacte a soporte para renovar su licencia.'
            ]);
        }

        return $next($request);
    }
}
```

---

### Modelo 2: Validación Online Automática (API Ping vía Servidor Web)
**Ideal si las PCs de la empresa cuentan con acceso a Internet.**

#### ¿Cómo funciona?
1. Creas un endpoint en tu propio servidor web (ej. `https://tudominio.com/api/check-license?cliente_ruc=20123456789`).
2. Al iniciar la aplicación de escritorio, esta hace una petición silenciosa en segundo plano a tu API.
3. Si el estado del cliente en tu base de datos es `ACTIVO` 👉 El sistema funciona normal.
4. Si el cliente no ha pagado y cambias su estado a `SUSPENDIDO` en tu servidor 👉 La aplicación de escritorio se bloquea automáticamente al instante.

---

### Implementación de la Pantalla de Bloqueo por Licencia Vencida
Cuando la licencia venza:
1. Se redirige al usuario a una pantalla limpia donde se muestra:
   * Estado: **"Licencia Suspendida / Vencida"**.
   * Motivo: **"El periodo de servicio contratado ha concluido"**.
   * Botón / Input: **"Ingresar Código de Renovación"**.
   * Contacto de soporte técnico: WhatsApp / Correo del desarrollador.
2. En cuanto el cliente ingresa el nuevo código de licencia válido proporcionado por ti, el sistema se reactiva automáticamente por 30 días, 1 año o el tiempo que determines.

---

## 4. Buenas Prácticas de Soporte Técnico y Backups

| Tarea | Frecuencia Recomendada | Procedimiento |
| :--- | :--- | :--- |
| **Copia de Respaldo (Backup BD)** | Semanal / Mensual | Copiar el archivo `database/movilizacion.sqlite` a una memoria externa o nube. |
| **Actualización de Versión** | Según requerimiento | Compilar `npm run build` + `php artisan native:build` y enviar la carpeta `win-unpacked`. |
| **Renovación de Licencia** | Mensual / Anual | Generar y enviar la clave criptográfica de activación al administrador. |
| **Reversión / Rollback** | En caso de emergencia | Volver a colocar la carpeta `win-unpacked` de la versión previa (los datos en BD no se tocan). |