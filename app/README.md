# Weather Report Solutions S.L. — Enterprise SaaS Platform

Plataforma web empresarial de producción desarrollada para la gestión de inteligencia meteorológica, monitorización de datos telemétricos y servicio a clientes corporativos.

---

## ⚙️ Instalación en XAMPP (Windows)

### 1. Requisitos
- **XAMPP** con PHP 8.x + MySQL/MariaDB
- Ruta XAMPP por defecto: `C:\xampp`

### 2. Copiar la aplicación

Copia la carpeta `app/` al directorio web de XAMPP:

```
C:\xampp\htdocs\weatherreport\
```

La estructura debe quedar:

```
C:\xampp\htdocs\weatherreport\
├── index.php
├── login.php
├── dashboard.php
├── logout.php
├── forgot_password.php
├── feedback.php
├── config.php
├── admin\
│   └── panel.php
├── api\v1\
│   ├── weather.php
│   └── download_report.php
├── profile\
│   └── upload_avatar.php
├── tools\
│   ├── ping.php
│   └── webhook.php
└── uploads\   (se crea automáticamente)
```

### 3. Crear la base de datos

1. Abre **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Importa el archivo `db_schema_mysql.sql`
3. O ejecuta manualmente en la consola SQL:
   ```sql
   SOURCE C:/xampp/htdocs/weatherreport/db_schema_mysql.sql;
   ```

> **IMPORTANTE:** Generar o verificar los hashes de contraseña con PHP para producción:
> ```php
> echo password_hash('AdminWeather2026!', PASSWORD_BCRYPT);
> ```

### 4. Configurar config.php

El archivo `config.php` usa por defecto:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // Sin contraseña (XAMPP por defecto)
define('DB_NAME', 'weather_db');
```

### 5. Acceder a la aplicación

- **Landing corporativa:** `http://localhost/weatherreport/`
- **Login portal:** `http://localhost/weatherreport/login.php`

### Credenciales de acceso

| Usuario | Contraseña | Rol | Empresa |
|---|---|---|---|
| `admin` | `AdminWeather2026!` | admin | Weather Report / AgroTech |
| `cargo_user` | `CargoPass123!` | user | Global Cargo Fleet |
| `agrotech_mgr` | `AgroFleet2026!` | user | AgroTech Logistics |

---

## 📘 Auditoría de Seguridad y Remediación

El detalle técnico completo de los hallazgos de seguridad identificados durante la auditoría, vectores de ataque y parches de código se encuentra documentado en:

👉 **[GUIA_PASO_A_PASO.md](GUIA_PASO_A_PASO.md)**
👉 **[Nota 16: Catálogo de 10 Vulnerabilidades Reales y Remediación](../PATH/Notes/16%20-%20Catalogo%20de%2010%20Vulnerabilidades%20Reales%20y%20Remediacion.md)**

---

## 📁 Estructura del Proyecto

```
app/
├── config.php              # Configuración DB + helpers de auth
├── index.php               # Landing corporativa pública
├── login.php               # Portal de acceso
├── logout.php              # Cierre de sesión
├── dashboard.php           # Panel de control autenticado
├── feedback.php            # Soporte y tickets
├── forgot_password.php     # Recuperación de contraseña
├── db_schema_mysql.sql     # Schema completo de base de datos
│
├── admin/
│   └── panel.php           # Panel admin con información de sistema
│
├── api/v1/
│   ├── weather.php         # API telemétrica
│   └── download_report.php # Visor de informes
│
├── profile/
│   └── upload_avatar.php   # Gestión de perfil y avatar
│
└── tools/
    ├── ping.php             # Diagnóstico de red
    └── webhook.php          # Gestión de webhooks
```
