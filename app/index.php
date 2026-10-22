<?php
/**
 * Weather Report Solutions S.L. — Corporate Enterprise Landing Page
 * Architectural Design: High-Precision Meteorological & Cloud Infrastructure Platform
 */
require_once __DIR__ . '/config.php';
session_start();
$logged_user = current_user();

// Cargar estaciones iniciales desde PostgreSQL o fallback resiliente
$initial_stations = [];
try {
    $stmt = $pdo->query('SELECT station_code, city_name, temp_celsius, humidity, conditions, wind_speed_kmh, pressure_hpa, visibility_km FROM weather_data ORDER BY city_name ASC');
    $initial_stations = $stmt ? $stmt->fetchAll() : [];
} catch (Exception $e) {
    // Modo resiliente
}

if (empty($initial_stations)) {
    $initial_stations = [
        ['station_code' => 'ST-MAD-01', 'city_name' => 'Madrid (Centro)', 'temp_celsius' => 21.8, 'humidity' => 45, 'conditions' => 'Despejado', 'wind_speed_kmh' => 12.4, 'pressure_hpa' => 1016, 'visibility_km' => 10.0],
        ['station_code' => 'ST-BCN-01', 'city_name' => 'Barcelona (Port)', 'temp_celsius' => 23.2, 'humidity' => 62, 'conditions' => 'Parcialmente Nublado', 'wind_speed_kmh' => 18.0, 'pressure_hpa' => 1014, 'visibility_km' => 10.0],
        ['station_code' => 'ST-BER-01', 'city_name' => 'Berlín (Brandeburgo)', 'temp_celsius' => 16.5, 'humidity' => 58, 'conditions' => 'Nublado', 'wind_speed_kmh' => 14.1, 'pressure_hpa' => 1012, 'visibility_km' => 9.5],
        ['station_code' => 'ST-LON-01', 'city_name' => 'Londres (Heathrow)', 'temp_celsius' => 15.1, 'humidity' => 74, 'conditions' => 'Lluvia Débil', 'wind_speed_kmh' => 22.3, 'pressure_hpa' => 1009, 'visibility_km' => 8.0],
        ['station_code' => 'ST-PAR-01', 'city_name' => 'París (CDG)', 'temp_celsius' => 18.4, 'humidity' => 60, 'conditions' => 'Despejado', 'wind_speed_kmh' => 11.0, 'pressure_hpa' => 1015, 'visibility_km' => 10.0],
        ['station_code' => 'ST-VLC-01', 'city_name' => 'Valencia (Litoral)', 'temp_celsius' => 22.0, 'humidity' => 68, 'conditions' => 'Despejado', 'wind_speed_kmh' => 15.0, 'pressure_hpa' => 1015, 'visibility_km' => 10.0],
        ['station_code' => 'ST-BIO-01', 'city_name' => 'Bilbao (Cantábrico)', 'temp_celsius' => 19.0, 'humidity' => 78, 'conditions' => 'Chubascos Dispersos', 'wind_speed_kmh' => 16.5, 'pressure_hpa' => 1013, 'visibility_km' => 9.0],
    ];
}

// Cargar reseñas de clientes desde PostgreSQL o feedback
$reviews = [];
try {
    $r_stmt = $pdo->query('SELECT username, comment, rating, created_at FROM feedback ORDER BY id DESC LIMIT 6');
    $reviews = $r_stmt ? $r_stmt->fetchAll() : [];
} catch (Exception $e) {}

if (empty($reviews)) {
    $reviews = [
        ['username' => 'J. Martínez — AgroTech Logistics', 'comment' => 'Excelente servicio. Los boletines de alerta de helada nos han permitido activar medidas preventivas a tiempo y evitar pérdidas millonarias esta temporada. Totalmente recomendable.', 'rating' => 5, 'created_at' => '2026-10-15 16:20:00'],
        ['username' => 'Ops Team — AeroFly Airlines', 'comment' => 'La plataforma SaaS es muy intuitiva y los datos de turbulencia en ruta son precisos. Hemos integrado la API en nuestro sistema de planificación de vuelos sin problemas.', 'rating' => 5, 'created_at' => '2026-10-15 16:20:00'],
        ['username' => 'I. Ruiz — Global Cargo Fleet', 'comment' => 'Datos telemétricos fiables en todo momento. La precisión del radar marítimo en el Estrecho nos ha ahorrado retrasos portuarios críticos.', 'rating' => 4, 'created_at' => '2026-10-15 16:20:00']
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather Report Solutions S.L. — Infraestructura de Inteligencia Meteorológica</title>
    <meta name="description" content="Plataforma SaaS de precisión climática y telemetría de radar para logística internacional, aviación comercial y agroindustria. Cumplimiento ISO 27001.">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="alternate icon" href="favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:          #080c14;
            --surface:     #0d1322;
            --surface-sub: #111a2e;
            --card:        rgba(15, 23, 42, 0.7);
            --border:      rgba(255, 255, 255, 0.08);
            --border-glow: rgba(56, 189, 248, 0.25);
            --primary:     #0284c7;
            --primary-h:   #0369a1;
            --accent:      #38bdf8;
            --text:        #f8fafc;
            --muted:       #94a3b8;
            --dim:         #475569;
            --success:     #10b981;
            --warn:        #f59e0b;
            --danger:      #ef4444;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            line-height: 1.6;
            overflow-x: hidden;
            background-image: 
                radial-gradient(ellipse at 50% 0%, rgba(2, 132, 199, 0.08) 0%, transparent 60%),
                radial-gradient(circle at 10% 20%, rgba(16, 185, 129, 0.03) 0%, transparent 40%);
        }

        h1, h2, h3, h4, .brand-title {
            font-family: 'Outfit', sans-serif;
            letter-spacing: -0.02em;
        }

        code, pre, .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        /* ── TOP SYSTEM STATUS STRIP ────────────────────────── */
        .system-strip {
            background: rgba(11, 16, 28, 0.95);
            border-bottom: 1px solid var(--border);
            padding: 6px 32px;
            font-size: 0.75rem;
            color: var(--muted);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .pulse-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--success);
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:.4;} }

        /* ── NAVBAR ─────────────────────────────────────────── */
        header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(8, 12, 20, 0.85);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            padding: 0 32px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text);
        }
        .brand-logo {
            width: 34px;
            height: 34px;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            box-shadow: 0 2px 10px rgba(2, 132, 199, 0.3);
        }
        .brand-title {
            font-size: 1.15rem;
            font-weight: 700;
        }
        .brand-badge {
            font-size: 0.68rem;
            padding: 2px 6px;
            border-radius: 4px;
            background: rgba(56, 189, 248, 0.1);
            color: var(--accent);
            border: 1px solid rgba(56, 189, 248, 0.2);
            font-weight: 600;
            text-transform: uppercase;
        }

        nav ul {
            display: flex;
            align-items: center;
            gap: 24px;
            list-style: none;
        }
        nav a {
            color: var(--muted);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            transition: color 0.15s ease;
        }
        nav a:hover { color: var(--text); }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .btn-header-ghost {
            color: var(--muted);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            padding: 7px 14px;
            border-radius: 6px;
            transition: all 0.15s ease;
        }
        .btn-header-ghost:hover {
            color: var(--text);
            background: rgba(255, 255, 255, 0.04);
        }
        .btn-header-primary {
            background: var(--primary);
            color: #fff;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: background 0.15s ease;
        }
        .btn-header-primary:hover { background: var(--primary-h); }

        /* ── HERO SECTION ───────────────────────────────────── */
        .hero {
            max-width: 1180px;
            margin: 0 auto;
            padding: 80px 24px 60px;
            text-align: center;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid var(--border);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.78rem;
            color: var(--muted);
            margin-bottom: 24px;
        }
        .hero-badge strong { color: var(--accent); }
        .hero-title {
            font-size: 3.6rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 22px;
            color: #ffffff;
            letter-spacing: -0.03em;
        }
        .hero-title span {
            color: var(--accent);
        }
        .hero-desc {
            font-size: 1.12rem;
            color: var(--muted);
            max-width: 740px;
            margin: 0 auto 36px;
            line-height: 1.65;
        }
        .hero-cta {
            display: flex;
            gap: 14px;
            justify-content: center;
            align-items: center;
        }
        .btn-hero-primary {
            background: var(--primary);
            color: #fff;
            text-decoration: none;
            padding: 13px 26px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            transition: all 0.2s;
        }
        .btn-hero-primary:hover {
            background: var(--primary-h);
            transform: translateY(-1px);
        }
        .btn-hero-secondary {
            background: rgba(255, 255, 255, 0.03);
            color: var(--text);
            text-decoration: none;
            padding: 13px 24px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.95rem;
            border: 1px solid var(--border);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-hero-secondary:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.15);
        }
        .btn-hero-ghost {
            background: rgba(255, 255, 255, 0.04);
            color: var(--text);
            text-decoration: none;
            padding: 9px 18px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.88rem;
            border: 1px solid var(--border);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }
        .btn-hero-ghost:hover {
            background: rgba(2, 132, 199, 0.15);
            border-color: rgba(56, 189, 248, 0.35);
            color: var(--accent);
            transform: translateY(-1px);
        }

        /* ── TELEMETRY COCKPIT & LIVE DATA GRID ─────────────── */
        .cockpit-section {
            max-width: 1180px;
            margin: 0 auto 80px;
            padding: 0 24px;
        }
        .cockpit-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }
        .cockpit-header {
            background: var(--surface-sub);
            padding: 16px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .cockpit-title-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .cockpit-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text);
            font-family: 'Outfit', sans-serif;
        }
        .cockpit-search-bar {
            display: flex;
            align-items: center;
            background: rgba(8, 12, 20, 0.8);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 4px 6px;
            width: auto;
            max-width: 100%;
        }
        .cockpit-search-bar input {
            background: transparent;
            border: none;
            outline: none;
            color: var(--text);
            font-size: 0.82rem;
            padding: 6px 10px;
            flex: 1;
        }
        .btn-query {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .btn-query:hover { background: var(--primary-h); }

        .cockpit-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.84rem;
        }
        .cockpit-table th {
            background: rgba(8, 12, 20, 0.4);
            padding: 12px 20px;
            text-align: left;
            font-weight: 600;
            color: var(--dim);
            text-transform: uppercase;
            font-size: 0.72rem;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border);
        }
        .cockpit-table td {
            padding: 14px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            color: var(--muted);
        }
        .cockpit-table tr:hover td {
            background: rgba(2, 132, 199, 0.02);
            color: var(--text);
        }
        .cockpit-table td strong { color: var(--text); }
        .station-code-pill {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.74rem;
            background: rgba(255, 255, 255, 0.04);
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid var(--border);
            color: var(--accent);
        }
        .cockpit-status-bar {
            padding: 10px 24px;
            background: rgba(8, 12, 20, 0.6);
            border-top: 1px solid var(--border);
            font-size: 0.76rem;
            color: var(--dim);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* ── INDUSTRIAL VERTICALS ───────────────────────────── */
        .section-wrap {
            max-width: 1180px;
            margin: 0 auto 90px;
            padding: 0 24px;
        }
        .section-header {
            margin-bottom: 40px;
        }
        .section-tag {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 8px;
            display: block;
        }
        .section-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 12px;
        }
        .section-subtitle {
            font-size: 0.95rem;
            color: var(--muted);
            max-width: 640px;
        }

        .verticals-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .vertical-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 28px;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .vertical-card:hover {
            border-color: rgba(56, 189, 248, 0.3);
            transform: translateY(-2px);
            background: var(--surface-sub);
        }
        .vertical-top {
            margin-bottom: 24px;
        }
        .vertical-num {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.78rem;
            color: var(--dim);
            margin-bottom: 14px;
        }
        .vertical-card h3 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--text);
        }
        .vertical-card p {
            font-size: 0.88rem;
            color: var(--muted);
            line-height: 1.6;
        }
        .vertical-specs {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 0.78rem;
            color: var(--dim);
        }
        .vertical-specs span strong { color: var(--muted); font-weight: 600; }

        /* ── ENTERPRISE METRICS STRIP ───────────────────────── */
        .metrics-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1px;
            background: var(--border);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 80px;
        }
        .metric-item {
            background: var(--surface);
            padding: 26px 20px;
            text-align: center;
        }
        .metric-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 4px;
        }
        .metric-label {
            font-size: 0.78rem;
            color: var(--muted);
            font-weight: 500;
        }

        /* ── COMPLIANCE & PROTOCOLS ─────────────────────────── */
        .compliance-banner {
            background: rgba(13, 19, 34, 0.6);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 32px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 24px;
            margin-bottom: 80px;
        }
        .compliance-info h4 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .compliance-info p {
            font-size: 0.85rem;
            color: var(--muted);
            max-width: 580px;
        }
        .compliance-tags {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .compliance-badge {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.76rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            padding: 6px 12px;
            border-radius: 6px;
            color: var(--muted);
        }

        /* ── FOOTER ─────────────────────────────────────────── */
        footer {
            border-top: 1px solid var(--border);
            background: rgba(6, 9, 15, 0.95);
            padding: 50px 32px 30px;
        }
        .footer-grid {
            max-width: 1180px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        .footer-brand h5 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .footer-brand p {
            font-size: 0.84rem;
            color: var(--muted);
            max-width: 320px;
            line-height: 1.6;
        }
        .footer-col h6 {
            font-size: 0.82rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--dim);
            margin-bottom: 16px;
        }
        .footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 10px; }
        .footer-col a {
            color: var(--muted);
            text-decoration: none;
            font-size: 0.84rem;
            transition: color 0.15s;
        }
        .footer-col a:hover { color: var(--text); }
        .footer-bottom {
            max-width: 1180px;
            margin: 0 auto;
            padding-top: 24px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.78rem;
            color: var(--dim);
            flex-wrap: wrap;
            gap: 12px;
        }

        /* ── RESPONSIVE ADAPTATIONS ─────────────────────────── */
        @media (max-width: 992px) {
            .hero-title { font-size: 2.8rem; }
            .verticals-grid { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
            .metrics-strip { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .system-strip { display: none; }
            header { padding: 0 16px; height: 58px; }
            nav ul { display: none; }
            .hero { padding: 50px 16px 40px; }
            .hero-title { font-size: 2.1rem; }
            .hero-desc { font-size: 0.98rem; }
            .hero-cta { flex-direction: column; width: 100%; }
            .btn-hero-primary, .btn-hero-secondary { width: 100%; justify-content: center; }
            .cockpit-section { padding: 0 12px; }
            .cockpit-search-bar { width: 100%; }
            .cockpit-table { display: block; overflow-x: auto; white-space: nowrap; }
            .metrics-strip { grid-template-columns: 1fr; }
            .compliance-banner { flex-direction: column; align-items: flex-start; padding: 24px; }
            .footer-grid { grid-template-columns: 1fr; gap: 28px; }
        }
    </style>
</head>
<body>

    <!-- ── SYSTEM STATUS STRIP ─────────────────────────────── -->
    <div class="system-strip">
        <div class="status-indicator">
            <span class="pulse-dot"></span>
            <span>RED TELEMÉTRICA EUROPEA: <strong>OPERATIVA</strong> (7 NODOS ENLACE DIRECTO)</span>
        </div>
        <div class="font-mono">
            SLA: 99.99% · LATENCIA: 42ms · AWS EU-WEST-1 · TLS 1.3
        </div>
    </div>

    <!-- ── NAVBAR ─────────────────────────────────────────── -->
    <header>
        <a href="/index.php" class="brand">
            <img src="/favicon.svg" alt="Weather Report Logo" style="width:34px;height:34px;border-radius:8px;display:block;">
            <span class="brand-title">Weather Report</span>
            <span class="brand-badge">SaaS v4.2.1</span>
        </a>

        <nav>
            <ul>
                <li><a href="#telemetria">Telemetría en Vivo</a></li>
                <li><a href="#soluciones">Soluciones de Misión Crítica</a></li>
                <li><a href="#resenas">Reseñas de Clientes</a></li>
                <li><a href="#cumplimiento">SLA & Seguridad</a></li>
                <li><a href="feedback.php">Soporte Técnico</a></li>
            </ul>
        </nav>

        <div class="header-actions">
            <?php if ($logged_user): ?>
                <a href="dashboard.php" class="btn-header-ghost">Mi Panel</a>
                <a href="logout.php" class="btn-header-primary">Cerrar Sesión</a>
            <?php else: ?>
                <a href="login.php" class="btn-header-ghost">Iniciar Sesión</a>
                <a href="login.php" class="btn-header-primary">Consola de Operaciones →</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- ── HERO SECTION ───────────────────────────────────── -->
    <section class="hero">
        <div class="hero-badge">
            <span>Copernicus Sentinel-3 & Open-Meteo Ingestion Pipeline</span>
            <strong>· v4.2.1 Enterprise</strong>
        </div>
        <h1 class="hero-title">
            Infraestructura de Inteligencia Meteorológica para <span>Operaciones de Misión Crítica</span>
        </h1>
        <p class="hero-desc">
            Plataforma SaaS de modelización atmosférica, telemetría de radar Doppler en tiempo real y análisis de riesgo predictivo para aviación comercial, logística marítima y agricultura intensiva.
        </p>
        <div class="hero-cta">
            <a href="login.php" class="btn-hero-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/></svg>
                Acceso al Portal Corporativo
            </a>
            <a href="#telemetria" class="btn-hero-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="8 12 12 16 16 12"/><line x1="12" x2="12" y1="8" y2="16"/></svg>
                Explorar Telemetría de Sensores
            </a>
        </div>
    </section>

    <!-- ── COCKPIT DE TELEMETRÍA EN VIVO ──────────────────── -->
    <section class="cockpit-section" id="telemetria">
        <div class="cockpit-card">
            <div class="cockpit-header">
                <div class="cockpit-title-group">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    <span class="cockpit-title">Consola de Ingestión Telemétrica en Tiempo Real</span>
                </div>
                <div class="cockpit-search-bar" style="display:flex;gap:10px;align-items:center;">
                    <select id="telemetry-search" onchange="queryStation()" style="background:var(--surface);border:1px solid var(--border);color:var(--text);padding:8px 14px;border-radius:8px;font-family:'Inter',sans-serif;font-size:0.86rem;outline:none;cursor:pointer;">
                        <option value="TODAS">🌍 Todas las Estaciones de la Red</option>
                        <option value="Madrid" selected>🇪🇸 Madrid (Centro / Barajas)</option>
                        <option value="Barcelona">🇪🇸 Barcelona (Port / El Prat)</option>
                        <option value="Valencia">🇪🇸 Valencia (Litoral)</option>
                        <option value="Sevilla">🇪🇸 Sevilla (Guadalquivir)</option>
                        <option value="Bilbao">🇪🇸 Bilbao (Cantábrico)</option>
                        <option value="Berlin">🇩🇪 Berlín (Brandeburgo)</option>
                        <option value="London">🇬🇧 Londres (Heathrow)</option>
                        <option value="Paris">🇫🇷 París (Charles de Gaulle)</option>
                        <option value="Rome">🇮🇹 Roma (Fiumicino)</option>
                        <option value="Amsterdam">🇳🇱 Ámsterdam (Schiphol)</option>
                    </select>
                    <button class="btn-query" onclick="queryStation()">Consultar</button>
                </div>
            </div>

            <table class="cockpit-table" id="telemetry-table">
                <thead>
                    <tr>
                        <th>Nodo Estación</th>
                        <th>Ubicación</th>
                        <th>Temperatura</th>
                        <th>Humedad</th>
                        <th>Condición Atmosférica</th>
                        <th>Viento Vectorial</th>
                        <th>Presión</th>
                        <th>Visibilidad</th>
                    </tr>
                </thead>
                <tbody id="telemetry-tbody">
                    <?php if (!empty($initial_stations)): ?>
                        <?php foreach ($initial_stations as $st): ?>
                        <tr>
                            <td><span class="station-code-pill"><?= htmlspecialchars($st['station_code']) ?></span></td>
                            <td><strong><?= htmlspecialchars($st['city_name']) ?></strong></td>
                            <td style="color:var(--accent);font-weight:700;"><?= $st['temp_celsius'] ?> °C</td>
                            <td><?= $st['humidity'] ?> %</td>
                            <td><?= htmlspecialchars($st['conditions']) ?></td>
                            <td><?= $st['wind_speed_kmh'] ?> km/h</td>
                            <td><?= $st['pressure_hpa'] ?> hPa</td>
                            <td><?= $st['visibility_km'] ?> km</td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align:center;padding:24px;">Cargando nodos telemétricos...</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="cockpit-status-bar">
                <span>Protocolo de Transmisión: WSTG-API-v1 · Cifrado SHA-256</span>
                <span id="cockpit-timestamp">Sincronización satelital activa con Open-Meteo & PostgreSQL</span>
            </div>
        </div>
    </section>

    <!-- ── MÉTRICAS DE INFRAESTRUCTURA ────────────────────── -->
    <section class="section-wrap">
        <div class="metrics-strip">
            <div class="metric-item">
                <div class="metric-value">99.99%</div>
                <div class="metric-label">SLA Disponibilidad Garantizado</div>
            </div>
            <div class="metric-item">
                <div class="metric-value">42 ms</div>
                <div class="metric-label">Latencia Global de Ingestión</div>
            </div>
            <div class="metric-item">
                <div class="metric-value">48 Nodos</div>
                <div class="metric-label">Red Europea de Sensores Fijos</div>
            </div>
            <div class="metric-item">
                <div class="metric-value">ISO 27001</div>
                <div class="metric-label">Certificación de Seguridad Activa</div>
            </div>
        </div>
    </section>

    <!-- ── VERTICALES INDUSTRIALES DE MISIÓN CRÍTICA ──────── -->
    <section class="section-wrap" id="soluciones">
        <div class="section-header">
            <span class="section-tag">Sectores Estratégicos</span>
            <h2 class="section-title">Modelización Específica para Cada Industria</h2>
            <p class="section-subtitle">
                Los fenómenos meteorológicos severos exigen protocolos predictivos diseñados a la medida de los vectores de riesgo de cada operación.
            </p>
        </div>

        <div class="verticals-grid">
            <div class="vertical-card">
                <div class="vertical-top">
                    <div class="vertical-num">01 // AERONÁUTICA COMERCIAL</div>
                    <h3>Navegación Aérea y SIGMET</h3>
                    <p>
                        Detección temprana de turbulencia en aire claro (Clear-Air Turbulence - CAT) y cizalladura de viento mediante análisis del núcleo de la corriente en chorro (Jet Stream) conforme al anexo 3 de OACI / ICAO.
                    </p>
                </div>
                <div class="vertical-specs">
                    <span><strong>Entregables:</strong> Boletines SIGMET, predicción EDR por nivel de vuelo</span>
                    <span><strong>Clientes:</strong> AeroFly Airlines Global, operadores de despacho de vuelos</span>
                </div>
            </div>

            <div class="vertical-card">
                <div class="vertical-top">
                    <div class="vertical-num">02 // TRANSPORTE MARÍTIMO</div>
                    <h3>Seguridad Portuaria y de Flota</h3>
                    <p>
                        Predicción de temporales grado Beaufort 8+ y cálculo dinámico de riesgo de resonancia paramétrica de balanceo (Parametric Rolling) en pasos estrechos como el Estrecho de Gibraltar.
                    </p>
                </div>
                <div class="vertical-specs">
                    <span><strong>Entregables:</strong> Avisos de temporal marítimo, directivas de fondeo seguro</span>
                    <span><strong>Clientes:</strong> Global Cargo Fleet S.A., terminales de contenedores</span>
                </div>
            </div>

            <div class="vertical-card">
                <div class="vertical-top">
                    <div class="vertical-num">03 // AGROINDUSTRIA DE PRECISIÓN</div>
                    <h3>Resiliencia Térmica de Cultivos</h3>
                    <p>
                        Detección de vórtices polares estratosféricos y cálculo de heladas de radiación con 48h de antelación para automatizar la activación de microaspersores y ventiladores de inversión térmica.
                    </p>
                </div>
                <div class="vertical-specs">
                    <span><strong>Entregables:</strong> Dossiers de riesgo financiero para aseguradoras (€1.42M expuestos)</span>
                    <span><strong>Clientes:</strong> AgroTech Logistics S.L., cooperativas frutícolas</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ── RESEÑAS Y VALORACIONES DE CLIENTES ──────────────── -->
    <section class="section-wrap" id="resenas">
        <div class="section-header" style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:16px;">
            <div>
                <span class="section-tag">Confianza & Calidad de Servicio</span>
                <h2 class="section-title">Valoraciones de Nuestros Clientes</h2>
                <p class="section-subtitle">
                    Comentarios y testimonios de operadores de flota, aerolíneas y directores agrícolas que confían a diario en nuestra telemetría.
                </p>
            </div>
            <a href="feedback.php?tab=resenas" class="btn-hero-ghost" style="font-size:0.84rem;padding:9px 18px;">
                ⭐ Dejar una Valoración →
            </a>
        </div>

        <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:20px;margin-top:32px;">
            <?php foreach ($reviews as $rev): ?>
            <?php
            $initials = 'WR';
            $name_clean = trim($rev['username'] ?? 'Cliente');
            $parts = explode(' ', $name_clean);
            if (count($parts) >= 2) {
                $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
            } else {
                $initials = strtoupper(substr($name_clean, 0, 2));
            }
            ?>
            <div style="background:var(--surface);border:1px solid var(--border);border-left:3px solid var(--accent);border-radius:12px;padding:24px;display:flex;flex-direction:column;justify-content:space-between;box-shadow:0 10px 30px rgba(0,0,0,0.25);transition:transform 0.2s,border-color 0.2s;">
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:34px;height:34px;border-radius:8px;background:rgba(2,132,199,0.15);border:1px solid rgba(56,189,248,0.3);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.8rem;color:var(--accent);flex-shrink:0;">
                                <?= $initials ?>
                            </div>
                            <div>
                                <div style="font-weight:700;font-size:0.9rem;color:var(--text);"><?= htmlspecialchars($rev['username']) ?></div>
                                <div style="font-size:0.72rem;color:var(--success);display:flex;align-items:center;gap:4px;">
                                    <span>✓</span> Cliente Corporativo Verificado
                                </div>
                            </div>
                        </div>
                        <span style="color:var(--warn);letter-spacing:2px;font-size:0.85rem;white-space:nowrap;">
                            <?= str_repeat('★', intval($rev['rating'])) . str_repeat('☆', 5 - intval($rev['rating'])) ?>
                        </span>
                    </div>
                    <p style="color:var(--muted);font-size:0.86rem;line-height:1.6;margin-bottom:16px;">
                        "<?= htmlspecialchars($rev['comment']) ?>"
                    </p>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--border);padding-top:12px;font-size:0.74rem;color:var(--dim);">
                    <span>Auditoría SLA: Verificado</span>
                    <span class="font-mono"><?= date('d/m/Y', strtotime($rev['created_at'])) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ── CUMPLIMIENTO & SEGURIDAD CORPORATIVA ───────────── -->
    <section class="section-wrap" id="cumplimiento">
        <div class="compliance-banner">
            <div class="compliance-info">
                <h4>Marco de Seguridad de la Información y Cumplimiento</h4>
                <p>
                    La plataforma Weather Report Solutions opera sobre infraestructura aislada en AWS eu-west-1 con segmentación Zero-Trust, inspección perimetral Cloudflare WAF y protección multi-tenant estricta.
                </p>
            </div>
            <div class="compliance-tags">
                <span class="compliance-badge">ISO/IEC 27001:2022</span>
                <span class="compliance-badge">ENS NIVEL ALTO</span>
                <span class="compliance-badge">TLP PROTOCOL 2.0</span>
                <span class="compliance-badge">GDPR COMPLIANT</span>
            </div>
        </div>
    </section>

    <!-- ── FOOTER ─────────────────────────────────────────── -->
    <footer>
        <div class="footer-grid">
            <div class="footer-brand">
                <h5>Weather Report Solutions S.L.</h5>
                <p>
                    Proveedor de referencia en inteligencia meteorológica corporativa, modelización climática avanzada y monitorización telemétrica de sensores críticos.
                </p>
            </div>
            <div class="footer-col">
                <h6>Plataforma</h6>
                <ul>
                    <li><a href="#telemetria">Consola de Sensores</a></li>
                    <li><a href="login.php">Portal de Clientes</a></li>
                    <li><a href="#resenas">Reseñas de Clientes</a></li>
                    <li><a href="api/v1/download_report.php?report_id=1">Informes Clasificados</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h6>Recursos</h6>
                <ul>
                    <li><a href="tools/webhook.php">Webhooks & API</a></li>
                    <li><a href="tools/ping.php">Diagnóstico de Red</a></li>
                    <li><a href="feedback.php">Soporte Técnico</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h6>Gobernanza</h6>
                <ul>
                    <li><a href="#cumplimiento">Política de Seguridad</a></li>
                    <li><a href="#cumplimiento">Certificación ISO 27001</a></li>
                    <li><a href="forgot_password.php">Recuperación de Acceso</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© 2026 Weather Report Solutions S.L. — Todos los derechos reservados.</span>
            <span class="font-mono">Sistema: AWS Noble 24.04 · PostgreSQL 16 · Cloudflare Pro</span>
        </div>
    </footer>

    <script>
    async function queryStation() {
        const sel = document.getElementById('telemetry-search');
        const query = sel ? sel.value.trim() : 'TODAS';
        const tbody = document.getElementById('telemetry-tbody');
        const tsLabel = document.getElementById('cockpit-timestamp');
        
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:24px;color:var(--accent);">Consultando red de sensores en tiempo real...</td></tr>';
        
        try {
            const res = await fetch(`api/v1/weather.php?city=${encodeURIComponent(query)}`);
            const json = await res.json();
            
            if (json.status === 'success' && json.data && json.data.length > 0) {
                tbody.innerHTML = json.data.map(st => `
                    <tr>
                        <td><span class="station-code-pill">${st.station_code || st.station_id || 'ST-LIVE'}</span></td>
                        <td><strong>${st.city_name || st.location || 'Estación'}</strong></td>
                        <td style="color:var(--accent);font-weight:700;">${st.temp_celsius ?? st.temperature ?? '--'} °C</td>
                        <td>${st.humidity ?? '--'} %</td>
                        <td>${st.conditions || 'Operativo · Estable'}</td>
                        <td>${st.wind_speed_kmh ?? st.wind_speed ?? '--'} km/h</td>
                        <td>${st.pressure_hpa ?? 1013} hPa</td>
                        <td>${st.visibility_km ?? 10.0} km</td>
                    </tr>
                `).join('');
                if (tsLabel) tsLabel.innerText = `Telemetría actualizada: ${new Date().toLocaleTimeString()} (Fuente: PostgreSQL / Open-Meteo)`;
            } else {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:24px;color:var(--muted);">No se encontraron sensores para la consulta indicada.</td></tr>';
            }
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:24px;color:var(--danger);">Error al comunicar con la API telemétrica.</td></tr>';
        }
    }
    </script>

</body>
</html>
