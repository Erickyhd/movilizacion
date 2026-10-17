# 🌐 Guía Maestra: Compartir Una Sola Base de Datos en Red Local (PC + Laptop)
## Sistema Integrado de Gestión y Control de Movilización HSEQ

---

### 📌 ¿Por qué antes cada equipo tenía una base de datos diferente?
Cuando copiabas la carpeta `win-unpacked` a la laptop y abrías `laravel.exe`, **cada máquina creaba una base de datos independiente y aislada** dentro de su propio usuario de Windows (`%AppData%\Laravel\database\database.sqlite`).  
Por eso, lo que registraban en la PC no se veía en la laptop y viceversa.

### 💡 La Solución Definitiva y Profesional
Para que la PC y la Laptop **vean y compartan exactamente la misma información en tiempo real**, se utiliza la arquitectura de red local:
1. **La Computadora Principal (PC)** actúa como el **Servidor Central**: aquí está instalada la base de datos única y el proyecto.
2. **La Laptop** actúa como **Terminal de Control**: se conecta por Wi-Fi o cable a la PC principal.
3. Todo lo que se registre en la laptop o en la PC se guarda al instante en la misma base de datos.
4. **Funciona 100% sin Internet**: no consume datos ni requiere conexión externa. Solo necesitan estar conectadas al mismo router o switch.

---

```text
                       ┌────────────────────────────────────────┐
                       │      ROUTER / WI-FI DE LA OFICINA      │
                       └───────────────────┬────────────────────┘
                                           │
                    ┌──────────────────────┴──────────────────────┐
                    ▼                                             ▼
       ┌────────────────────────┐                    ┌────────────────────────┐
       │   PC PRINCIPAL         │                    │   LAPTOP / EQUIPO 2    │
       │   (SERVIDOR CENTRAL)   │                    │   (TERMINAL EN RED)    │
       │                        │                    │                        │
       │ • Base de Datos Única  │◄═══════════════════┤ • Abre el Sistema      │
       │   (database/movilizacion) Consulta y Guarda │ • Guarda en la PC      │
       │ • Corre el Servidor    │  en Tiempo Real    │   Principal            │
       └────────────────────────┘                    └────────────────────────┘
```

---

## 🚀 PASO A PASO: PUESTA EN MARCHA (DESDE CERO)

---

### 🖥️ PARTE 1: En la Computadora Principal (PC Servidor)

En la computadora donde está la carpeta del proyecto (`C:\Users\USUARIO\Desktop\movilizacion`):

#### Paso 1.1: Habilitar el Firewall de Windows (Solo se hace 1 vez)
Para que el Firewall de Windows no bloquee las conexiones que vengan de la laptop:
1. Abre la carpeta del proyecto: `C:\Users\USUARIO\Desktop\movilizacion`.
2. Busca el archivo que te creamos llamado:  
   👉 **`HABILITAR_FIREWALL_RED_LOCAL.bat`**
3. Haz **CLIC DERECHO** sobre él y selecciona **"Ejecutar como administrador"**.
4. Te saldrá una ventana confirmando: *"El puerto 8000 ha sido permitido en el Firewall de Windows"*. Presiona cualquier tecla para cerrar.

---

#### Paso 1.2: Iniciar el Servidor Central
1. En la misma carpeta del proyecto, haz doble clic sobre:  
   👉 **`iniciar_servidor.bat`**
2. Se abrirá una ventana negra que detectará automáticamente tu IP y te mostrará algo como esto:
   ```text
   ========================================================
     INICIANDO SERVIDOR CENTRAL DE MOVILIZACION HSEQ
   ========================================================

   [OK] Base de Datos Activa: database\movilizacion.sqlite

   --------------------------------------------------------
    1. PARA USAR EN ESTA COMPUTADORA (PC PRINCIPAL):
       Abre tu navegador y entra a:
       http://localhost:8000

    2. PARA USAR DESDE TU LAPTOP O CUALQUIER OTRO EQUIPO:
       Abre el navegador en la laptop y entra a:
       http://192.168.1.22:8000
   --------------------------------------------------------

   (Mantenga esta ventana abierta mientras trabaje con el sistema)
   ```
3. **¡Listo!** Anota la dirección que dice en el punto 2 (por ejemplo: `http://192.168.1.22:8000`).
   > ⚠️ **REGLA DE ORO:** Esta ventana negra debe permanecer abierta (puedes minimizarla) mientras alguien esté usando el sistema. Si la cierras, el servidor se apaga.

---

### 💻 PARTE 2: En la Laptop (Equipo Secundario)

En la laptop no necesitas instalar PHP, ni Composer, ni Node.js, ni copiar carpetas gigantes:

#### Paso 2.1: Conectarte a la misma red Wi-Fi
* Asegúrate de que la laptop esté conectada al **mismo Wi-Fi** o al mismo cable de red que la PC principal.

#### Paso 2.2: Abrir el Sistema en el Navegador
1. Abre **Google Chrome** o **Microsoft Edge**.
2. En la barra superior (donde escribes páginas web como *www.google.com*), escribe la dirección que te dio la PC principal:
   ```text
   http://192.168.1.22:8000
   ```
   *(Sustituye `192.168.1.22` por el número que te mostró la ventana de tu PC).*
3. Presiona **Enter**.
4. ¡El sistema cargará de inmediato con todos tus manifiestos y trabajadores!

---

#### Paso 2.3: Convertirlo en una "App de Escritorio" en la Laptop (Sin barra de navegador)
Para que en la laptop no parezca una página web, sino un programa nativo de Windows con su propio icono:

* **Método Automático con Microsoft Edge o Google Chrome:**
  1. Con el sistema abierto en la laptop, haz clic en los **tres puntos** (`...`) de la esquina superior derecha del navegador.
  2. Selecciona **Aplicaciones** -> Haz clic en **"Instalar este sitio como una aplicación"**.
  3. Ponle de nombre: `Movilizacion HSEQ` y haz clic en **Instalar**.
  4. Se creará un acceso directo en el Escritorio de tu laptop. Al abrirlo, se abrirá en una ventana limpia e independiente, exactamente igual a un programa de escritorio.

* **Método con el archivo de Acceso Directo de 1 Clic:**
  * Puedes copiar el archivo [`CONECTAR_DESDE_OTRO_EQUIPO.bat`](file:///c:/Users/USUARIO/Desktop/movilizacion/CONECTAR_DESDE_OTRO_EQUIPO.bat) al escritorio de la laptop. Al hacerle doble clic, abrirá directamente la ventana en la IP de la PC.

---

## 🛠️ CASUÍSTICAS Y SOLUCIÓN DE PROBLEMAS (¿QUÉ HACER SI ALGO FALLA?)

### ❓ Casuística 1: En la Laptop dice "No se puede acceder a este sitio" o "Tiempo de espera agotado"

Si al escribir `http://192.168.1.22:8000` en la laptop no carga:

1. **¿Está abierto el servidor en la PC?**  
   Verifica que la ventana negra de `iniciar_servidor.bat` en la PC principal siga abierta. Si se cerró, ábrela nuevamente.
2. **¿Se ejecutó el Firewall como Administrador?**  
   En la PC principal, haz clic derecho en `HABILITAR_FIREWALL_RED_LOCAL.bat` y elige **"Ejecutar como administrador"**.
3. **¿La red de Windows está en modo "Público" en vez de "Privado"?**  
   Windows bloquea conexiones si la red está en "Pública". Para cambiarla a Privada:
   * En la PC principal, ve a: **Configuración de Windows** -> **Red e Internet** -> **Wi-Fi** (o Ethernet).
   * Haz clic sobre el nombre de tu red conectada.
   * Donde dice "Tipo de perfil de red", selecciona **"Red privada"** (esto le dice a Windows que es una red segura de oficina/hogar).
4. **¿Están en la misma red Wi-Fi?**  
   Verifica que la laptop no esté conectada a la red de "Invitados" o compartiendo datos del celular. Deben estar en el mismo nombre de red Wi-Fi.

---

### ❓ Casuística 2: ¿Cómo fijar la IP de la PC para que NUNCA cambie al reiniciar el Router?

Los routers a veces cambian las direcciones IP de los equipos (un día tu PC es `.22` y al reiniciar el router pasa a ser `.25`).  
Para que tu PC principal mantenga SIEMPRE la misma IP fija:

1. En la PC Principal, presiona las teclas <kbd>Windows</kbd> + <kbd>R</kbd>.
2. Escribe: `ncpa.cpl` y presiona **Enter**.
3. Verás tus conexiones de red. Haz **clic derecho** sobre tu conexión activa (**Ethernet** o **Wi-Fi**) y elige **Propiedades**.
4. En la lista, haz doble clic sobre:  
   **"Protocolo de Internet versión 4 (TCP/IPv4)"**.
5. Marca la casilla: **"Usar la siguiente dirección IP"**:
   * **Dirección IP:** Escribe tu IP actual (ejemplo: `192.168.1.22`).
   * **Máscara de subred:** Al hacer clic se llenará sola: `255.255.255.0`.
   * **Puerta de enlace predeterminada:** La dirección de tu router (normalmente: `192.168.1.1`).
6. En la parte inferior, marca **"Usar las siguientes direcciones de servidor DNS"**:
   * **Servidor DNS preferido:** `8.8.8.8`
   * **Servidor DNS alternativo:** `8.8.4.4`
7. Haz clic en **Aceptar** y luego en **Aceptar**.
8. ¡Listo! A partir de ese momento, tu PC mantendrá esa IP para siempre.

---

## 💾 COPIAS DE SEGURIDAD (BACKUP) DE LA BASE DE DATOS

### ⚠️ Respuesta a tu duda sobre `%AppData%`:
* **¿Qué pasa si limpian el caché o borran la carpeta `%AppData%`?**  
  **¡TUS DATOS NO SE PIERDEN!**  
  Al usar el servidor central (`iniciar_servidor.bat`), la base de datos **NO se guarda en `%AppData%`**.  
  Se guarda en la carpeta física de tu proyecto:  
  📁 `C:\Users\USUARIO\Desktop\movilizacion\database\movilizacion.sqlite`  
  Cualquier limpieza de archivos temporales de Windows o de `%AppData%` no toca ni borra este archivo.

---

### 📦 ¿Cómo hacer una Copia de Seguridad (Backup) en 1 Clic?

Para tener un respaldo seguro por si se daña el disco duro o para guardarlo en una memoria USB:

1. Ve a la carpeta del proyecto: `C:\Users\USUARIO\Desktop\movilizacion`.
2. Haz doble clic en el archivo que te creamos:  
   👉 **`CREAR_COPIA_DE_SEGURIDAD_BD.bat`**
3. El sistema creará instantáneamente una copia completa de tu base de datos dentro de la carpeta:  
   📁 `C:\Users\USUARIO\Desktop\movilizacion\backups\`
4. El archivo llevará la fecha y hora exacta, por ejemplo:  
   `movilizacion_backup_2026-10-16_22-44.sqlite`
5. Puedes copiar ese archivo a una memoria USB o subirlo a Google Drive / OneDrive para tener respaldo total.

---

### 🔄 ¿Cómo restaurar una copia de seguridad si fuera necesario?
Si alguna vez formateas la PC o necesitas regresar a una versión anterior:
1. Asegúrate de cerrar la ventana negra de `iniciar_servidor.bat`.
2. Ve a la carpeta `backups\` y copia el archivo de respaldo que quieras restaurar.
3. Pégalo en la carpeta `database\` y cámbiale el nombre a `movilizacion.sqlite` (reemplazando al archivo anterior).
4. Vuelve a abrir `iniciar_servidor.bat` y el sistema cargará exactamente con los datos de esa fecha.
