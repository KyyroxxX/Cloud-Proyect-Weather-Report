# 🛠️ Guía Práctica Paso a Paso — Instalación XAMPP, Auditoría y Parcheo Web
## Weather Report Solutions S.L. — Plataforma SaaS Corporativa

> [!important] **Manual de Ejecución Práctica Paso a Paso (Local & Cloud)**
> Este manual contiene las instrucciones exactas paso a paso para desplegar la plataforma en local (XAMPP / MySQL), auditar los endpoints web (Red Team), aplicar el parche de código seguro (Blue Team) y verificar la corrección en las **10 vulnerabilidades del proyecto**.

---

## ⚙️ FASE 1: Despliegue Paso a Paso en XAMPP (Windows)

- [ ] **1.1. Copiar Archivos al Servidor Web**
  1. Abrir File Explorer en Windows.
  2. Copiar todo el contenido de la carpeta `app/` a la ruta:
     ```text
     C:\xampp\htdocs\weatherreport\
     ```

- [ ] **1.2. Inicializar Servicios e Importar Base de Datos**
  1. Abrir **XAMPP Control Panel** e iniciar los módulos **Apache** y **MySQL**.
  2. Abrir el navegador en `http://localhost/phpmyadmin/`.
  3. Crear una nueva base de datos llamada **`weather_db`** (Cotejo: `utf8mb4_unicode_ci`).
  4. Ir a la pestaña **Importar** -> Seleccionar archivo `C:\xampp\htdocs\weatherreport\db_schema_mysql.sql` -> Hacer clic en **Continuar**.

- [ ] **1.3. Verificar Accesos y Cuentas de Prueba**
  - **Landing Corporativa:** `http://localhost/weatherreport/`
  - **Portal de Login:** `http://localhost/weatherreport/login.php`
  - **Administrador:** `admin` / `AdminWeather2026!`
  - **Usuario Logística:** `cargo_user` / `CargoPass123!`
  - **Gestor AgroTech:** `agrotech_mgr` / `AgroFleet2026!`

---

## ☣️ FASE 2: Auditoría Paso a Paso (Red Team) vs. Parcheo (Blue Team)

---

- [ ] **2.1. VULN-01: Bypass de Autenticación mediante SQL Injection (`login.php`)**
  - **Paso 1 (Auditoría Red Team):**
    1. Ir al navegador: `http://localhost/weatherreport/login.php`
    2. En el campo **Usuario**, introducir: `admin'#`
    3. En el campo **Contraseña**, introducir cualquier texto.
    4. Pulsar **Iniciar Sesión**.
    - *Resultado:* Acceso concedido redirigiendo a `dashboard.php` como `admin`.
  - **Paso 2 (Remediación Blue Team):**
    1. Abrir `login.php` en el editor de código.
    2. Sustituir la consulta SQL directa por PDO Prepared Statements:
       ```php
       $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
       $stmt->execute([$username]);
       $user = $stmt->fetch();
       if ($user && password_verify($password, $user['password_hash'])) {
           // Login correcto
       }
       ```
  - **Paso 3 (Re-Test):** Volver a enviar `admin'#`. *Resultado:* Mensaje "Credenciales incorrectas".

---

- [ ] **2.2. VULN-02: SQL Injection en API Telemétrica (`/api/v1/weather.php`)**
  - **Paso 1 (Auditoría Red Team):**
    1. Ejecutar en terminal:
       ```bash
       curl -s "http://localhost/weatherreport/api/v1/weather.php?city=MADRID%27+UNION+SELECT+1,%27ST%27,%27PWN%27,99,100,%27INJ%27,150,1000,50,10,NOW()--+-"
       ```
    - *Resultado:* Devuelve JSON con el registro inyectado o detalles del error SQL.
  - **Paso 2 (Remediación Blue Team):**
    1. Abrir `api/v1/weather.php`.
    2. Usar sentencias preparadas PDO:
       ```php
       $stmt = $pdo->prepare("SELECT * FROM weather_data WHERE city_name = ?");
       $stmt->execute([$city]);
       ```
  - **Paso 3 (Re-Test):** Ejecutar de nuevo el `curl`. *Resultado:* Consulta vacía sin ejecutar código SQL inyectado.

---

- [ ] **2.3. VULN-03: Session Fixation (`login.php?sid=`)**
  - **Paso 1 (Auditoría Red Team):**
    1. Acceder a `http://localhost/weatherreport/login.php?sid=SESION_FIJADA_999`.
    2. Iniciar sesión con `cargo_user` / `CargoPass123!`.
    - *Resultado:* La cookie `PHPSESSID` mantiene la sesión forzada.
  - **Paso 2 (Remediación Blue Team):**
    1. En `login.php`, añadir tras validar la contraseña:
       ```php
       session_start();
       session_regenerate_id(true); // Genera una nueva ID de sesión y destruye la previa
       ```

---

- [ ] **2.4. VULN-04: Subida Arbitraria de Archivos (`/profile/upload_avatar.php`)**
  - **Paso 1 (Auditoría Red Team):**
    1. Ir a `http://localhost/weatherreport/profile/upload_avatar.php`.
    2. Subir un archivo script `.php` y consultar `http://localhost/weatherreport/uploads/avatar.php`.
  - **Paso 2 (Remediación Blue Team):**
    1. Validar extensión permitida y tipo MIME real en `upload_avatar.php`:
       ```php
       $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
       $ext = strtolower(pathinfo($_FILES['fileToUpload']['name'], PATHINFO_EXTENSION));
       if (!in_array($ext, $allowed_ext)) die("Extensión no autorizada.");

       $finfo = finfo_open(FILEINFO_MIME_TYPE);
       $mime = finfo_file($finfo, $_FILES['fileToUpload']['tmp_name']);
       if (!str_starts_with($mime, 'image/')) die("Tipo de archivo no permitido.");
       ```

---

- [ ] **2.5. VULN-05: IDOR en Informes Climáticos (`/api/v1/download_report.php`)**
  - **Paso 1 (Auditoría Red Team):**
    1. Acceder con `cargo_user` e ir a `http://localhost/weatherreport/api/v1/download_report.php?report_id=2`.
    - *Resultado:* Descarga informes asignados a otras empresas.
  - **Paso 2 (Remediación Blue Team):**
    1. En `download_report.php`, añadir filtro de pertenencia:
       ```php
       $stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ? AND company_id = ?");
       $stmt->execute([$report_id, $_SESSION['company_id']]);
       ```

---

- [ ] **2.6. VULN-06: Stored XSS en Panel de Soporte (`/feedback.php`)**
  - **Paso 1 (Auditoría Red Team):**
    1. Ir a `http://localhost/weatherreport/feedback.php`.
    2. Introducir en el mensaje: `<script>alert(document.cookie)</script>` y enviar.
  - **Paso 2 (Remediación Blue Team):**
    1. Sanitizar la salida en `feedback.php`:
       ```php
       echo htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8');
       ```

---

- [ ] **2.7. VULN-07: OS Command Injection (`/tools/ping.php`)**
  - **Paso 1 (Auditoría Red Team):**
    1. Ir a `http://localhost/weatherreport/tools/ping.php`.
    2. En el campo **Host**, escribir: `127.0.0.1 & dir` y ejecutar.
  - **Paso 2 (Remediación Blue Team):**
    1. En `tools/ping.php`, validar dirección IP:
       ```php
       if (!filter_var($host, FILTER_VALIDATE_IP)) {
           die("Error: Dirección IP no válida.");
       }
       exec("ping -n 2 " . escapeshellarg($host), $output);
       ```

---

- [ ] **2.8. VULN-08: Secretos y AWS Keys Expuestas (`config.php` & `admin/panel.php`)**
  - **Paso 1 (Auditoría Red Team):** Escaneo estático de credenciales en código con `gitleaks`.
  - **Paso 2 (Remediación Blue Team):** Cargar credenciales mediante variables `.env` fuera del directorio público.

---

- [ ] **2.9. VULN-09: Exposición de Información del Sistema (`/admin/panel.php`)**
  - **Paso 1 (Auditoría Red Team):** Visitar `/admin/panel.php` observando versiones exactas del servidor.
  - **Paso 2 (Remediación Blue Team):** Configurar `ini_set('display_errors', '0'); error_reporting(0);` y exigir rol `admin`.

---

- [ ] **2.10. VULN-10: Token Reset Predecible (`/forgot_password.php`)**
  - **Paso 1 (Auditoría Red Team):** Calcular token determinista `md5(username + timestamp)` e ingresar a `forgot_password.php?token=...`.
  - **Paso 2 (Remediación Blue Team):** Generar token seguro con entropía criptográfica:
    ```php
    $token = bin2hex(random_bytes(32));
    ```
