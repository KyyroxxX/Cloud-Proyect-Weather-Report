-- ============================================================
-- Weather Report Solutions S.L. — Enterprise Database Schema
-- MySQL / MariaDB — weather_db
-- ============================================================

CREATE DATABASE IF NOT EXISTS weather_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE weather_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS webhooks;
DROP TABLE IF EXISTS feedback;
DROP TABLE IF EXISTS tickets;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS companies;
DROP TABLE IF EXISTS weather_data;
SET FOREIGN_KEY_CHECKS = 1;

-- ─── EMPRESAS CLIENTE (Multi-tenant) ────────────────────────
CREATE TABLE companies (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(120) NOT NULL,
    plan_type    VARCHAR(60)  DEFAULT 'Enterprise SaaS',
    api_key      VARCHAR(80)  NOT NULL UNIQUE,
    contact_email VARCHAR(120),
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── USUARIOS ────────────────────────────────────────────────
CREATE TABLE users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    company_id   INT,
    username     VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email        VARCHAR(120) NOT NULL UNIQUE,
    full_name    VARCHAR(120),
    role         VARCHAR(20)  DEFAULT 'user',   -- 'admin' | 'user'
    avatar_url   VARCHAR(255) DEFAULT '/uploads/default_avatar.png',
    is_active    TINYINT(1)   DEFAULT 1,
    last_login   TIMESTAMP    NULL,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─── SESIONES PHP (token-based, para VULN-02 Session Fixation) ──
CREATE TABLE sessions (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    token      VARCHAR(128) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─── TELEMETRÍA METEOROLÓGICA ────────────────────────────────
CREATE TABLE weather_data (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    station_code    VARCHAR(20) NOT NULL,
    city_name       VARCHAR(60) NOT NULL,
    temp_celsius    DECIMAL(4,1) NOT NULL,
    humidity        INT NOT NULL,
    conditions      VARCHAR(120) NOT NULL,
    wind_speed_kmh  DECIMAL(4,1) NOT NULL,
    pressure_hpa    INT DEFAULT 1013,
    visibility_km   DECIMAL(4,1) DEFAULT 10.0,
    uv_index        INT DEFAULT 0,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── INFORMES CLIMÁTICOS CONFIDENCIALES ─────────────────────
CREATE TABLE reports (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT,
    author_id       INT,
    title           VARCHAR(180) NOT NULL,
    summary         TEXT NOT NULL,
    content         LONGTEXT NOT NULL,
    classification  VARCHAR(50) DEFAULT 'CONFIDENCIAL',
    file_hash       VARCHAR(64),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id)  REFERENCES users(id)    ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─── TICKETS DE SOPORTE ──────────────────────────────────────
CREATE TABLE tickets (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT,
    company_id INT,
    subject    VARCHAR(180) NOT NULL,
    message    LONGTEXT NOT NULL,
    priority   VARCHAR(20) DEFAULT 'NORMAL',
    status     VARCHAR(20) DEFAULT 'ABIERTO',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(id)     ON DELETE SET NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─── FEEDBACK PÚBLICO (para Stored XSS) ─────────────────────
CREATE TABLE feedback (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(100) NOT NULL,
    email      VARCHAR(120),
    comment    LONGTEXT NOT NULL,
    rating     INT DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── WEBHOOKS / INTEGRACIONES EXTERNAS (para SSRF) ──────────
CREATE TABLE webhooks (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    company_id  INT,
    name        VARCHAR(100),
    target_url  VARCHAR(500) NOT NULL,
    event_type  VARCHAR(60)  DEFAULT 'weather_alert',
    secret_key  VARCHAR(64),
    is_active   TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─── RECUPERACIÓN DE CONTRASEÑA (para VULN-10 Predictable Token) ─
CREATE TABLE password_resets (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    token      VARCHAR(64) NOT NULL,
    used       TINYINT(1) DEFAULT 0,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- ============================================================
-- DATOS INICIALES (SEED DATA)
-- ============================================================

INSERT INTO companies (company_name, plan_type, api_key, contact_email) VALUES 
('AgroTech Logistics S.L.',   'Enterprise Platinum', 'wr_live_agtk_99887766554433221100aabbcc', 'it@agrotech-logistics.es'),
('AeroFly Airlines Global',   'Enterprise VIP',      'wr_live_afly_11223344556677889900ddeeff', 'ops@aeroflyairlines.com'),
('Global Cargo Fleet S.A.',   'Standard SaaS',       'wr_live_gcfl_55667788990011223344ffaabb', 'tech@globalcargofleet.com');

-- Contraseñas en claro (para documentación del CTF):
--   admin       → AdminWeather2026!
--   cargo_user  → CargoPass123!
--   agrotech_mgr→ AgroFleet2026!
-- Los hashes abajo son bcrypt $2y$12$ generados con password_hash()

INSERT INTO users (company_id, username, password_hash, email, full_name, role) VALUES 
(1, 'admin',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'sysadmin@weatherreport.es', 'Alejandro De Luque', 'admin'),

(3, 'cargo_user',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'ops@globalcargofleet.com', 'Joan Izquierdo', 'user'),

(1, 'agrotech_mgr',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'admin@agrotech-logistics.es', 'Pablo González', 'user');

-- NOTA: el hash de arriba corresponde a "password" (hash de ejemplo bcrypt).
-- Para el entorno real XAMPP, ejecutar desde PHP:
-- echo password_hash('AdminWeather2026!', PASSWORD_BCRYPT, ['cost'=>12]);

INSERT INTO weather_data (station_code, city_name, temp_celsius, humidity, conditions, wind_speed_kmh, pressure_hpa, visibility_km, uv_index) VALUES 
('ST-BER-01', 'BERLIN',    14.5, 68, 'Parcialmente Nublado', 18.2, 1012,  9.5, 1),
('ST-MAD-01', 'MADRID',    24.0, 42, 'Soleado',              12.0, 1018, 25.0, 7),
('ST-LON-01', 'LONDON',    12.0, 85, 'Lluvia Moderada',      25.5, 1008,  4.0, 0),
('ST-BCN-01', 'BARCELONA', 21.5, 60, 'Despejado',            15.0, 1015, 20.0, 5),
('ST-PAR-01', 'PARIS',     16.0, 72, 'Nublado',              10.5, 1011, 12.0, 2),
('ST-ROM-01', 'ROME',      22.0, 55, 'Soleado con Bruma',     8.0, 1016, 18.0, 6),
('ST-AMS-01', 'AMSTERDAM', 13.0, 78, 'Lluvioso',             22.0, 1006,  6.0, 0);

INSERT INTO reports (company_id, author_id, title, summary, content, classification, file_hash) VALUES 
(1, 1, 'Boletín de Heladas Valle del Ebro — Q4 2026',
 'Alerta Agrícola Nivel 4 — Riesgo de congelación en cultivos de fruta dulce',
 'DATOS CONFIDENCIALES — USO INTERNO RESTRINGIDO\n\nAnálisis detallado de modelos predictivos ECMWF y GFS para la cuenca del río Ebro entre el 12 y el 18 de noviembre de 2026.\n\nRiesgo de helada: 85% de probabilidad de temperaturas por debajo de -3°C entre las 02:00h y las 07:00h UTC durante 6 noches consecutivas.\n\nImpacto económico estimado: 1.2M EUR en cultivos de melocotón, pera conferencia y manzana golden.\n\nMedidas recomendadas: activación de sistemas de riego antihelada y contratación de cobertura de seguro agrícola antes del 10 de noviembre.\n\n— Departamento de Análisis Climático, Weather Report Solutions S.L.',
 'CONFIDENCIAL', 'a3f8d1c4e9b2017563ac4e8d1f22b7c3a8e91d4f'),

(2, 1, 'Análisis de Turbulencia — Ruta Transatlántica A350 — Semana 44/2026',
 'Alerta de Navegación Aérea — Turbulencia severa en Atlántico Norte',
 'DATOS CONFIDENCIALES — USO INTERNO RESTRINGIDO\n\nDetección de células de turbulencia de intensidad severa (CAT - Clear Air Turbulence) en coordenadas 45.12°N, 23.45°W durante la semana del 28 de octubre al 3 de noviembre de 2026.\n\nIntensidad: 0.8g - clasificación SIGMET Severo.\nAltitudes afectadas: FL340 a FL390.\n\nSe recomienda desviación de ruta mínima de 120 millas náuticas al Sur o reducción de altitud a FL280 previo al cruce.\n\n— Departamento de Meteorología Aeronáutica, Weather Report Solutions S.L.',
 'ESTRICTAMENTE CONFIDENCIAL', 'b7c2e5f1a9d3084672be5f9c2e33c8d4b9f02e5g'),

(3, 1, 'Rutas Marítimas Estrecho de Gibraltar — Temporada Otoño 2026',
 'Alerta Marítima de Oleaje — Temporal de Levante previsto',
 'DATOS CONFIDENCIALES — USO INTERNO RESTRINGIDO\n\nPrognóstico de Temporal de Levante de intensidad grado 7-8 Beaufort en aguas del Estrecho de Gibraltar para el fin de semana del 25-26 de octubre de 2026.\n\nOlas significativas: 3.5 a 5.2 metros en zona de Tarifa-Ceuta.\nVientos: 35-45 nudos de componente Este.\nDuración estimada del temporal: 48-72 horas.\n\nSe recomienda amarre preventivo en puerto y aplazamiento de travesías de carga hasta el lunes 27 de octubre.\n\n— Departamento de Meteorología Marítima, Weather Report Solutions S.L.',
 'RESTRINGIDO', 'c1d6a2b8f4e7053781cf6a3d4f44d9e5c0a13b6h');

INSERT INTO tickets (user_id, company_id, subject, message, priority, status) VALUES 
(2, 3, 'Fallo en sincronización API telemétrica — Nodo LON-01',
 'Buenas tardes, desde ayer a las 14:30h nuestra flota de vehículos de transporte no recibe los datos meteorológicos del sensor ST-LON-01. El endpoint /api/v1/weather.php?city=LONDON devuelve error 500. ¿Pueden revisar el estado del nodo? Afecta a 12 rutas activas. Gracias.',
 'ALTA', 'ABIERTO'),
(3, 1, 'Solicitud de informe personalizado para zona Pirineos',
 'Estimado equipo, necesitamos un análisis de riesgo de nevadas para la zona pirenaica de Lleida durante los meses de diciembre y enero. Es para planificar rutas de distribución agrícola. ¿Es posible generar un informe específico?',
 'NORMAL', 'EN PROCESO');

INSERT INTO feedback (username, email, comment, rating) VALUES
('J. Martínez — AgroTech', 'j.martinez@agrotech-logistics.es', 'Excelente servicio. Los boletines de alerta de helada nos han permitido activar medidas preventivas a tiempo y evitar pérdidas millonarias esta temporada. Totalmente recomendable.', 5),
('Ops Team — AeroFly', 'ops@aeroflyairlines.com', 'La plataforma SaaS es muy intuitiva y los datos de turbulencia son precisos. Hemos integrado la API en nuestro sistema de planificación de rutas sin problemas.', 5),
('I. Ruiz — Global Cargo', 'i.ruiz@globalcargofleet.com', 'Buenos datos en general, aunque en ocasiones el tiempo de respuesta de la API puede ser algo lento en horas pico. El soporte técnico resolvió nuestra incidencia en menos de 2 horas.', 4);

INSERT INTO webhooks (company_id, name, target_url, event_type, secret_key) VALUES 
(3, 'Alerta Flota GlobalCargo', 'https://api.globalcargofleet.com/hooks/weather-alert', 'weather_alert', 'whsec_gc_9a8b7c6d5e4f3210'),
(2, 'Notificación AeroFly OPS', 'https://dispatch.aeroflyairlines.com/api/sigmet-webhook', 'turbulence_alert', 'whsec_af_1a2b3c4d5e6f7890');
