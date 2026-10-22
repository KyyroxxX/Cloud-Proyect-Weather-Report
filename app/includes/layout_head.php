<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Weather Report Solutions S.L.') ?> — Plataforma Corporativa</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:            #070a12;
            --sidebar:       #0a0f1d;
            --surface:       #0e1626;
            --surface-hover: #141f35;
            --surface-card:  rgba(14, 22, 38, 0.85);
            --border:        rgba(255, 255, 255, 0.07);
            --border-hover:  rgba(56, 189, 248, 0.25);
            --primary:       #0284c7;
            --primary-hover: #0369a1;
            --accent:        #38bdf8;
            --text:          #f8fafc;
            --muted:         #94a3b8;
            --dim:           #475569;
            --success:       #10b981;
            --warn:          #f59e0b;
            --danger:        #ef4444;
            --sidebar-width: 260px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
            line-height: 1.5;
        }

        h1, h2, h3, h4, .font-heading {
            font-family: 'Outfit', sans-serif;
            letter-spacing: -0.02em;
            color: #ffffff;
        }

        code, pre, .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        /* ── SIDEBAR PERSISTENTE UNIFICADA ───────────────────── */
        .app-sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: var(--sidebar);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            transition: transform 0.2s ease;
        }

        .sidebar-brand-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 22px 24px;
            border-bottom: 1px solid var(--border);
            text-decoration: none;
            color: var(--text);
        }

        .sidebar-brand-icon {
            width: 34px;
            height: 34px;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(2, 132, 199, 0.3);
            flex-shrink: 0;
        }

        .sidebar-brand-name {
            font-family: 'Outfit', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            white-space: nowrap;
        }

        .sidebar-brand-badge {
            font-size: 0.65rem;
            padding: 2px 5px;
            border-radius: 4px;
            background: rgba(56, 189, 248, 0.1);
            color: var(--accent);
            border: 1px solid rgba(56, 189, 248, 0.2);
            font-weight: 600;
            text-transform: uppercase;
            margin-left: auto;
        }

        .sidebar-nav {
            flex: 1;
            padding: 16px 14px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .nav-category {
            font-size: 0.68rem;
            font-weight: 700;
            color: var(--dim);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 16px 12px 6px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 9px 12px;
            border-radius: 8px;
            color: var(--muted);
            text-decoration: none;
            font-size: 0.86rem;
            font-weight: 500;
            transition: all 0.15s ease;
        }

        .nav-link:hover {
            color: var(--text);
            background: rgba(255, 255, 255, 0.04);
        }

        .nav-link.active {
            color: var(--accent);
            background: rgba(2, 132, 199, 0.12);
            border: 1px solid rgba(56, 189, 248, 0.2);
            font-weight: 600;
        }

        .nav-link svg {
            flex-shrink: 0;
            opacity: 0.8;
        }
        .nav-link.active svg {
            opacity: 1;
            stroke: var(--accent);
        }

        /* ── FOOTER DE LA SIDEBAR (USUARIO) ──────────────────── */
        .sidebar-user-footer {
            padding: 16px 18px;
            border-top: 1px solid var(--border);
            background: rgba(8, 12, 20, 0.4);
        }

        .user-profile-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .user-avatar-circle {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: var(--surface-hover);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.88rem;
            color: var(--accent);
            overflow: hidden;
            flex-shrink: 0;
        }

        .user-meta {
            flex: 1;
            min-width: 0;
        }

        .user-display-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role-pill {
            font-size: 0.68rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: capitalize;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .role-tag {
            background: rgba(56, 189, 248, 0.1);
            color: var(--accent);
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .btn-sidebar-logout {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 8px 12px;
            border-radius: 6px;
            background: rgba(239, 68, 68, 0.06);
            border: 1px solid rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .btn-sidebar-logout:hover {
            background: rgba(239, 68, 68, 0.12);
            border-color: rgba(239, 68, 68, 0.3);
            color: #ffffff;
        }

        /* ── LAYOUT PRINCIPAL (MAIN VIEWPORT) ────────────────── */
        .app-main {
            margin-left: var(--sidebar-width);
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-image: 
                radial-gradient(ellipse at 85% 0%, rgba(2, 132, 199, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 10% 20%, rgba(16, 185, 129, 0.02) 0%, transparent 40%);
        }

        .app-topbar {
            height: 64px;
            border-bottom: 1px solid var(--border);
            background: rgba(8, 12, 20, 0.6);
            backdrop-filter: blur(12px);
            padding: 0 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .topbar-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text);
        }

        .topbar-meta {
            font-size: 0.78rem;
            color: var(--muted);
            border-left: 1px solid var(--border);
            padding-left: 16px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .telemetry-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 12px;
            border-radius: 20px;
            background: rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(16, 185, 129, 0.2);
            font-size: 0.76rem;
            font-weight: 600;
            color: var(--success);
        }

        .telemetry-pulse {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--success);
            box-shadow: 0 0 6px rgba(16, 185, 129, 0.6);
            animation: pulse 1.8s ease-in-out infinite;
        }

        .app-content-body {
            padding: 32px;
            flex: 1;
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
        }

        /* ── COMPONENTES UNIFICADOS DE INTERFAZ ──────────────── */
        .card-ui {
            background: var(--surface-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            margin-bottom: 24px;
        }

        .card-ui-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border);
            background: rgba(8, 12, 20, 0.4);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .card-ui-title {
            font-size: 0.98rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text);
        }

        .card-ui-body {
            padding: 24px;
        }

        .btn-ui {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
            border: 1px solid transparent;
        }

        .btn-ui-primary {
            background: var(--primary);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.1);
        }
        .btn-ui-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .btn-ui-ghost {
            background: rgba(255, 255, 255, 0.04);
            color: var(--muted);
            border-color: var(--border);
        }
        .btn-ui-ghost:hover {
            background: rgba(255, 255, 255, 0.07);
            color: var(--text);
            border-color: rgba(255, 255, 255, 0.15);
        }

        .btn-ui-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #fca5a5;
            border-color: rgba(239, 68, 68, 0.25);
        }
        .btn-ui-danger:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #ffffff;
        }

        /* ── TABLAS UNIFICADAS ───────────────────────────────── */
        .table-ui {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        .table-ui th {
            padding: 12px 20px;
            background: rgba(8, 12, 20, 0.5);
            border-bottom: 1px solid var(--border);
            text-align: left;
            font-size: 0.74rem;
            font-weight: 600;
            color: var(--dim);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .table-ui td {
            padding: 14px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            color: var(--muted);
        }
        .table-ui tr:hover td {
            background: rgba(2, 132, 199, 0.03);
            color: var(--text);
        }
        .table-ui td strong { color: var(--text); }

        /* ── BADGES & PILLS ─────────────────────────────────── */
        .pill-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.72rem;
            font-weight: 600;
            line-height: 1.3;
        }
        .pill-danger, .pill-tag-danger   { background: rgba(239, 68, 68, 0.12); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.25); }
        .pill-warn,   .pill-tag-warn     { background: rgba(245, 158, 11, 0.12); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.25); }
        .pill-info,   .pill-tag-info     { background: rgba(56, 189, 248, 0.12); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.25); }
        .pill-success,.pill-tag-success  { background: rgba(16, 185, 129, 0.12); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.25); }

        /* ── ALERTAS UNIFICADAS ─────────────────────────────── */
        .alert-ui {
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 0.86rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            line-height: 1.5;
        }
        .alert-ui-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.25);
            color: #6ee7b7;
        }
        .alert-ui-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: #fca5a5;
        }
        .alert-ui-warn {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.25);
            color: #fde047;
        }
        .alert-ui-info {
            background: rgba(56, 189, 248, 0.1);
            border: 1px solid rgba(56, 189, 248, 0.25);
            color: #7dd3fc;
        }

        /* ── SCROLLBAR PERSONALIZADO ────────────────────────── */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.12); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(56, 189, 248, 0.35); }

        /* ── FORMULARIOS UNIFICADOS ─────────────────────────── */
        .form-group-ui {
            margin-bottom: 18px;
        }
        .form-label-ui {
            display: block;
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--muted);
            margin-bottom: 7px;
        }
        .form-control-ui {
            width: 100%;
            background: rgba(8, 12, 20, 0.8);
            border: 1px solid var(--border);
            padding: 10px 14px;
            border-radius: 6px;
            color: var(--text);
            font-family: inherit;
            font-size: 0.88rem;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .form-control-ui:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.25);
        }

        /* ── RESPONSIVE MOBILE / TABLET ─────────────────────── */
        @media (max-width: 900px) {
            body { flex-direction: column; }
            .app-sidebar {
                position: static;
                width: 100%;
                min-height: auto;
                border-right: none;
                border-bottom: 1px solid var(--border);
            }
            .sidebar-nav {
                display: flex;
                flex-direction: row;
                flex-wrap: wrap;
                padding: 10px 12px;
                gap: 6px;
            }
            .nav-category { display: none; }
            .app-main { margin-left: 0; }
            .app-content-body { padding: 18px 14px; }
            .app-topbar { padding: 0 16px; }
            .table-ui { display: block; overflow-x: auto; white-space: nowrap; }
        }
    </style>
</head>
<body>
