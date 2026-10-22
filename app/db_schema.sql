-- ============================================================
-- Weather Report Solutions S.L. — Enterprise Database Schema
-- PostgreSQL 16 — weather_db
-- ============================================================

DROP TABLE IF EXISTS vouchers CASCADE;
DROP TABLE IF EXISTS password_resets CASCADE;
DROP TABLE IF EXISTS sessions CASCADE;
DROP TABLE IF EXISTS webhooks CASCADE;
DROP TABLE IF EXISTS feedback CASCADE;
DROP TABLE IF EXISTS tickets CASCADE;
DROP TABLE IF EXISTS reports CASCADE;
DROP TABLE IF EXISTS users CASCADE;
DROP TABLE IF EXISTS companies CASCADE;
DROP TABLE IF EXISTS weather_data CASCADE;

-- ─── EMPRESAS CLIENTE (Multi-tenant) ────────────────────────
CREATE TABLE companies (
    id SERIAL PRIMARY KEY,
    company_name VARCHAR(120) NOT NULL,
    plan_type VARCHAR(60) DEFAULT 'Enterprise SaaS',
    credit_balance INT DEFAULT 500,
    radar_quota INT DEFAULT 50,
    api_key VARCHAR(80) NOT NULL UNIQUE,
    contact_email VARCHAR(120),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─── USUARIOS ────────────────────────────────────────────────
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    company_id INT REFERENCES companies(id) ON DELETE CASCADE,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    full_name VARCHAR(120),
    role VARCHAR(20) DEFAULT 'user',
    avatar_url VARCHAR(255) DEFAULT '/uploads/default_avatar.png',
    is_active SMALLINT DEFAULT 1,
    last_login TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─── SESIONES PHP ────────────────────────────────────────────
CREATE TABLE sessions (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token VARCHAR(128) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─── TELEMETRÍA METEOROLÓGICA ────────────────────────────────
CREATE TABLE weather_data (
    id SERIAL PRIMARY KEY,
    station_code VARCHAR(20) NOT NULL,
    city_name VARCHAR(60) NOT NULL,
    temp_celsius DECIMAL(4,1) NOT NULL,
    humidity INT NOT NULL,
    conditions VARCHAR(120) NOT NULL,
    wind_speed_kmh DECIMAL(4,1) NOT NULL,
    pressure_hpa INT DEFAULT 1013,
    visibility_km DECIMAL(4,1) DEFAULT 10.0,
    uv_index INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─── INFORMES CLIMÁTICOS CONFIDENCIALES ─────────────────────
CREATE TABLE reports (
    id SERIAL PRIMARY KEY,
    company_id INT REFERENCES companies(id) ON DELETE CASCADE,
    author_id INT REFERENCES users(id) ON DELETE SET NULL,
    title VARCHAR(180) NOT NULL,
    summary TEXT NOT NULL,
    content TEXT NOT NULL,
    classification VARCHAR(50) DEFAULT 'CONFIDENCIAL',
    file_hash VARCHAR(64),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─── TICKETS DE SOPORTE ──────────────────────────────────────
CREATE TABLE tickets (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE SET NULL,
    company_id INT REFERENCES companies(id) ON DELETE CASCADE,
    subject VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    priority VARCHAR(20) DEFAULT 'NORMAL',
    status VARCHAR(20) DEFAULT 'ABIERTO',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─── FEEDBACK PÚBLICO (Stored XSS) ───────────────────────────
CREATE TABLE feedback (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(120),
    comment TEXT NOT NULL,
    rating INT DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─── WEBHOOKS / INTEGRACIONES EXTERNAS (SSRF) ────────────────
CREATE TABLE webhooks (
    id SERIAL PRIMARY KEY,
    company_id INT REFERENCES companies(id) ON DELETE CASCADE,
    name VARCHAR(100),
    target_url VARCHAR(500) NOT NULL,
    event_type VARCHAR(60) DEFAULT 'weather_alert',
    secret_key VARCHAR(64),
    is_active SMALLINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─── RECUPERACIÓN DE CONTRASEÑA ──────────────────────────────
CREATE TABLE password_resets (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token VARCHAR(64) NOT NULL,
    used SMALLINT DEFAULT 0,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─── CUPONES DE CRÉDITO SATELITAL (Race Condition TOCTOU) ───
CREATE TABLE vouchers (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    credit_amount INT NOT NULL,
    is_redeemed SMALLINT DEFAULT 0,
    redeemed_by INT REFERENCES users(id) ON DELETE SET NULL,
    redeemed_at TIMESTAMP
);

-- ============================================================
-- DATOS INICIALES (SEED DATA)
-- ============================================================

INSERT INTO companies (company_name, plan_type, credit_balance, radar_quota, api_key, contact_email) VALUES 
('AgroTech Logistics S.L.',   'Enterprise Platinum', 1500, 100, 'wr_live_agtk_99887766554433221100aabbcc', 'it@agrotech-logistics.es'),
('AeroFly Airlines Global',   'Enterprise VIP',      2500, 200, 'wr_live_afly_11223344556677889900ddeeff', 'ops@aeroflyairlines.com'),
('Global Cargo Fleet S.A.',   'Standard SaaS',        300,  20, 'wr_live_gcfl_55667788990011223344ffaabb', 'tech@globalcargofleet.com');

INSERT INTO vouchers (code, credit_amount, is_redeemed) VALUES
('SAT-SENTINEL-500', 500, 0),
('TURBO-RADAR-250', 250, 0),
('CLIMATE-PASS-1000', 1000, 0);

-- Credenciales:
--   admin        / AdminWeather2026!  (Hash MD5: 7e8a8e37281a7ead4c57aca916557000)
--   cargo_user   / CargoPass123!      (Hash MD5: c1c13a1acd796bc076864cef319b3ac5)
--   agrotech_mgr / AgroFleet2026!     (Hash MD5: 1e76ef49e64b9bff199e1709b35e936c)

INSERT INTO users (company_id, username, password_hash, email, full_name, role) VALUES 
(1, 'admin',        '7e8a8e37281a7ead4c57aca916557000', 'sysadmin@weatherreport.es', 'Alejandro De Luque', 'admin'),
(3, 'cargo_user',   'c1c13a1acd796bc076864cef319b3ac5', 'ops@globalcargofleet.com', 'Joan Izquierdo', 'user'),
(1, 'agrotech_mgr', '1e76ef49e64b9bff199e1709b35e936c', 'admin@agrotech-logistics.es', 'Pablo González', 'user');

INSERT INTO weather_data (station_code, city_name, temp_celsius, humidity, conditions, wind_speed_kmh, pressure_hpa, visibility_km, uv_index) VALUES 
('ST-BER-01', 'BERLIN',    14.5, 68, 'Parcialmente Nublado', 18.2, 1012,  9.5, 1),
('ST-MAD-01', 'MADRID',    24.0, 42, 'Soleado',              12.0, 1018, 25.0, 7),
('ST-LON-01', 'LONDON',    12.0, 85, 'Lluvia Moderada',      25.5, 1008,  4.0, 0),
('ST-BCN-01', 'BARCELONA', 21.5, 60, 'Despejado',            15.0, 1015, 20.0, 5),
('ST-PAR-01', 'PARIS',     16.0, 72, 'Nublado',              10.5, 1011, 12.0, 2),
('ST-ROM-01', 'ROME',      22.0, 55, 'Soleado con Bruma',     8.0, 1016, 18.0, 6),
('ST-AMS-01', 'AMSTERDAM', 13.0, 78, 'Lluvioso',             22.0, 1006,  6.0, 0);

INSERT INTO reports (company_id, author_id, title, summary, content, classification, file_hash) VALUES 
(1, 1, 'Boletín de Inteligencia Climática SIGMET-AGR-402 — Helada Severa Valle del Ebro',
 'Alerta Agrícola Nivel 4 // Vórtice Polar Estratosférico — Riesgo Crítico en 340 ha de Frutal Dulce (Lleida-Fraga)',
 'WEATHER REPORT SOLUTIONS S.L. — DIVISIÓN DE MODELIZACIÓN CLIMÁTICA AVANZADA
DOCUMENTO CLASIFICADO: TLP:RED // PROPIEDAD INDUSTRIAL CONFIDENCIAL
AUDITORÍA DE RIESGO: #AGR-2026-9912-Q4 | FECHA DE EMISIÓN: 15/10/2026 14:56 UTC
ENTIDAD DESTINATARIA: AgroTech Logistics S.L. (ID: 001) | PLAN: Enterprise Platinum

1. RESUMEN EJECUTIVO & VECTOR DE AMENAZA
--------------------------------------------------------------------------------
Detección por sensorística telemétrica infrarroja y modelo numérico ECMWF-HRES de un vórtice polar estratosférico con intrusión de masa de aire ártico continental seco directo sobre la cuenca del Río Ebro (Lleida, Fraga, Caspe y Bajo Cinca).
Riesgo de helada de radiación severa estimado en 88.4% entre el 12 y el 18 de Noviembre de 2026.
Descenso térmico proyectado: Tmin alcanzará los -4.2 °C con punto de rocío a -5.1 °C durante 6 noches consecutivas entre las 01:30 y las 07:15 UTC.

2. IMPACTO FINANCIERO Y PATRIMONIAL ESTIMADO (DATOS DE NEGOCIO SENSIBLES)
--------------------------------------------------------------------------------
Superficie bajo cobertura de sensores: 340 hectáreas de cultivo intensivo frutal.
Cultivos críticos expuestos:
- Prunus persica (Melocotón tardío y Nectarina): 180 ha | Pérdida potencial: 82%
- Pyrus communis (Pera Conferencia D.O.P.): 110 ha | Pérdida potencial: 74%
- Malus domestica (Manzana Golden): 50 ha | Pérdida potencial: 65%

EXPOSICIÓN FINANCIERA TOTAL ESTIMADA: 1.420.000,00 EUR
Reserva técnica indemnizable Agroseguro (Póliza COOP-9921): Máx. 1.050.000,00 EUR.
Franquicia no asegurable asumida por AgroTech: 370.000,00 EUR.

3. PROTOCOLO OPERATIVO DIRECTIVO DE MITIGACIÓN ACTIVA
--------------------------------------------------------------------------------
[PASO 1 - T-48h]: Activación forzada de estaciones de riego por aspersión antihelada (microaspersores sobre copa) a partir de las 00:45 UTC cuando la temperatura de bulbo húmedo alcance los +0.5 °C. Caudal requerido: 42 m³/h por sector.
[PASO 2 - T-24h]: Despliegue de torres de ventilación FrostBoss en parcelas B-04 a B-18 para forzar la inversión térmica (>4 °C a 10m de altitud).
[PASO 3 - T-0h]: Pre-activación de peritaje telemétrico mediante drones termográficos multiespectrales Mavic 3T coordinados con el endpoint /api/v1/telemetry de Weather Report.

4. MATRIZ DE TELEMETRÍA DE SENSORES EN TIEMPO REAL (NODO N-EBR-04)
--------------------------------------------------------------------------------
Sensor ID        | Parámetro Registrado         | Valor Umbral | Estado Sensor
--------------------------------------------------------------------------------
SN-EBR-401-TEMP  | Temp. Aire (2m AGL)         | -3.8 °C      | ALERTA CRÍTICA
SN-EBR-402-DEW   | Temp. Punto de Rocío        | -4.9 °C      | CONGELACIÓN
SN-EBR-403-BARO  | Presión Barométrica (QNH)   | 1024.5 hPa   | ANTICICLÓN SECO
SN-EBR-404-WIND  | Anemómetro Vectorial 10m    | 2.1 km/h     | CALMA TOTAL
SN-EBR-405-SOIL  | Temp. Suelo Radicular (-15cm)| +1.2 °C     | VULNERABLE
--------------------------------------------------------------------------------

Cláusula de Confidencialidad: La presente modelización contiene propiedad intelectual protegida por secreto comercial industrial (Directiva UE 2016/943). Prohibida su distribución a terceros ajenos a AgroTech Logistics S.L.',
 'TLP:RED // ESTRICTAMENTE CONFIDENCIAL', 'a3f8d1c4e9b2017563ac4e8d1f22b7c3a8e91d4f01837492cba3e2f14028374c'),

(2, 1, 'Boletín Meteorológico SIGMET-AERO-09 — Turbulencia Severa CAT Transatlántica',
 'Alerta Operativa ICAO // Turbulencia en Aire Claro (CAT) en Corredor NAT-Track Charlie — FL340 a FL390',
 'WEATHER REPORT SOLUTIONS S.L. — DIVISIÓN DE NAVEGACIÓN Y METEOROLOGÍA AERONÁUTICA
DOCUMENTO TÉCNICO CLASIFICADO: ICAO ANNEX 3 / EASA AMC-MET // CONFIDENCIAL
REFERENCIA VUELO: #AFY-NAT-2026-X4 | EMISIÓN: 15/10/2026 15:10 UTC
DESTINATARIO: AeroFly Airlines Global (ID: 002) | FLOTA AFECTADA: Airbus A350-900 / Boeing 787-9

1. ALERTA DE TURBULENCIA EN AIRE CLARO (CLEAR AIR TURBULENCE - CAT)
--------------------------------------------------------------------------------
Detección satelital de cizalladura vertical de viento extrema en el corredor oceánico del Atlántico Norte (North Atlantic Tracks - NAT Organised Track System).
Zona de severidad extrema: Coordenadas 45°12N a 48°30N, 23°45W a 28°10W.
Niveles de vuelo afectados: FL340 hasta FL390 (10.300m a 11.800m).
Índice EDR (Eddy Dissipation Rate): 0.54 m^(2/3) s^(-1) [Clasificación: SEVERA].
Aceleración vertical proyectada: -0.4g a +2.3g (Riesgo inminente de lesiones en cabina de pasaje y estrés estructural en fuselaje).

2. PARÁMETROS DINÁMICOS DE LA CORRIENTE EN CHORRO (JET STREAM CORE)
--------------------------------------------------------------------------------
- Velocidad máxima núcleo Jet Stream: 168 nudos (311 km/h) dirección 265°
- Gradiente de cizalladura vertical: 9.2 kt / 1.000 ft (Supera límite crítico de 6 kt/1.000 ft)
- Gradiente térmico horizontal: 6.8 °C / 100 NM (Ruptura baroclina acelerada)

3. PLAN DE VUELO TÁCTICO RECOMENDADO Y DISPATCH MEMO
--------------------------------------------------------------------------------
Se instruye a Despacho de Vuelos de AeroFly suspender la entrada por NAT-Track Charlie.
DESVIACIÓN OBLIGATORIA:
Opción Alpha: Desviación táctica meridional de 140 NM al Sur ingresando por NAT-Track Echo (vía waypoint 41N030W).
Penalización de combustible estimada: +1.840 kg Jet-A1 por aeronave (Sobrecoste: 1.480,00 EUR/vuelo).
Opción Bravo: Reducción preventiva de crucero de FL380 a FL280 previo a 20°W.
Incremento de consumo de combustible: +12.4% con tiempo de vuelo +18 min.

4. CÓDIGO METEOROLÓGICO AERONÁUTICO FORMATO OACI / ICAO
--------------------------------------------------------------------------------
WS NAT 151510 EGGX SIGMET 04 VALID 151530/152130 EGRR-
EGGX SHANWICK FIR SEV CAT FCST WI N4512 W02345 - N4830 W02810 - N4620 W03100
FL340/390 MOV E 25KT NC=

Registro de validación de seguridad de vuelo: EASA-OPS-CAT-A350-VERIFIED',
 'RESTRICTED // ICAO LEVEL 4', 'b7c2e5f1a9d3084672be5f9c2e33c8d4b9f02e5g847192847cba028374829102'),

(3, 1, 'Dossier Hidrometeorológico MAR-2026-77 — Temporal de Levante Grado 8 Beaufort',
 'Alerta Marítima de Oleaje Extremo // Resonancia Paramétrica y Amarre Forzado en Estrecho de Gibraltar',
 'WEATHER REPORT SOLUTIONS S.L. — DIVISIÓN DE METEOROLOGÍA MARÍTIMA & PUERTOS
DOSSIER DE NAVEGACIÓN RESTRINGIDO: TLP:AMBER // COMMERCIAL CONFIDENTIAL
REFERENCIA OPERATIVA: #GCF-LEVANTE-2026-08 | EMISIÓN: 15/10/2026 15:20 UTC
DESTINATARIO: Global Cargo Fleet S.A. (ID: 003) | BUQUES: Portacontenedores Clase Post-Panamax

1. CONDICIONES OCEANOGRÁFICAS EXTREMAS EN ESTRECHO DE GIBRALTAR
--------------------------------------------------------------------------------
Gradiente barométrico adverso de 14 hPa entre el Golfo de Cádiz (1012 hPa) y el Mar de Alborán (1026 hPa), generando efecto Venturi severo en el paso de Tarifa-Punta Almina.
Viento sostenido de componente Este (Levante): 38 a 44 nudos con rachas de 58 nudos (Fuerza 8-9 en Escala Beaufort).
Altura Significativa de Ola (Hs): 4.8 a 5.6 metros en canal central.
Altura Máxima de Ola (Hmax): 8.4 metros (Registrado por Boya de Tarifa - Puertos del Estado).
Periodo de pico de ola (Tp): 10.8 segundos.

2. EVALUACIÓN DE RIESGO DE CORTE DE ESTIBA & RESONANCIA PARAMÉTRICA
--------------------------------------------------------------------------------
Para buques de más de 300 metros de eslora con calado superior a 12.5 metros:
- Periodo natural de balanceo del buque (Roll Period): 11.2 segundos.
- Coincidencia crítica con el periodo del oleaje (Tp 10.8s): Riesgo extremo de RESONANCIA PARAMÉTRICA DE BALANCEO (Parametric Rolling) con ángulos de escora repentinos superiores a 28°.
- Riesgo directo de ruptura de trincaje y pérdida por la borda de contenedores en cubiertas 3 a 5 de popa.

3. DIRECTIVA DE AMARRE Y FONDEO PREVENTIVO
--------------------------------------------------------------------------------
- Cancelación de todas las maniobras de práctico y atraque en APM Terminals Algeciras y Tanger-Med a partir de las 18:00 UTC.
- Buques en tránsito en aproximación al Estrecho (Ruta Suez -> Rotterdam): Reducir velocidad a 10 nudos y mantener espera activa en zona de fondeo seguro al abrigo de Bahía de Algeciras (Zona Fondeo Bravo).
- Coste diario de paralización estimado (Demurrage): 48.000,00 USD por buque / día.

Firma de Autoridad Marítima Certificada: MAR-WR-FLEET-SAFE-9988',
 'TLP:AMBER // COMMERCIAL RESTRICTED', 'c1d6a2b8f4e7053781cf6a3d4f44d9e5c0a13b6h728192847281928472910283');

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
