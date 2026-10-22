<?php
/**
 * Weather Report Solutions S.L. — Recuperación de Clave de Acceso
 * Unified Web App Layout Architecture
 * VULN-10 (WR-RT-10): Token de recuperación predecible basado en timestamp truncado y username
 */
require_once __DIR__ . '/config.php';
session_start();

$message = '';
$type    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email) {
        $stmt = $pdo->prepare('SELECT id, username, email FROM users WHERE email = ? AND is_active = 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // WR-RT-10: Token predecible generado con md5(username + time bucket de 10 min)
            $time_bucket = floor(time() / 600) * 600;
            $token = md5($user['username'] . $time_bucket . 'wr_salt_2026');

            // Guardar token en BD (expira en 1 hora, compatible PostgreSQL)
            $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$user['id']]);
            $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, NOW() + INTERVAL '1 hour')")
                ->execute([$user['id'], $token]);

            $reset_url = APP_URL . '/reset_password.php?token=' . $token;
            $message = 'Se ha generado la instrucción de restablecimiento para <strong>' . htmlspecialchars($email) . '</strong>. Compruebe su bandeja de entrada corporativa.';
            $type = 'success';

            // DEBUG DEV MODE para pruebas y entorno de laboratorio pentest
            if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
                $message .= '<br><small style="color:var(--dim);font-size:0.75rem;"><!-- DEV MODE: reset_token=' . $token . ' url=' . htmlspecialchars($reset_url) . ' --></small>';
            }
        } else {
            // User enumeration
            $message = 'No existe ninguna cuenta activa asociada a esa dirección de correo electrónico.';
            $type = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Acceso — Weather Report Solutions S.L.</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:            #070a12;
            --surface-card:  rgba(14, 22, 38, 0.9);
            --surface:       #0e1626;
            --border:        rgba(255, 255, 255, 0.08);
            --primary:       #0284c7;
            --accent:        #38bdf8;
            --text:          #f8fafc;
            --muted:         #94a3b8;
            --dim:           #475569;
            --danger:        #ef4444;
            --success:       #10b981;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background-image:
                radial-gradient(ellipse at 20% 30%, rgba(2, 132, 199, 0.12) 0%, transparent 55%),
                radial-gradient(ellipse at 80% 70%, rgba(14, 22, 38, 0.5) 0%, transparent 55%);
        }

        .auth-card {
            width: 100%;
            max-width: 440px;
            background: var(--surface-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(16px);
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text);
            margin-bottom: 32px;
            justify-content: center;
        }

        .brand-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            box-shadow: 0 2px 10px rgba(2, 132, 199, 0.35);
        }

        .brand-name {
            font-family: 'Outfit', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .card-heading {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 6px;
            text-align: center;
        }

        .card-subheading {
            color: var(--muted);
            font-size: 0.85rem;
            margin-bottom: 28px;
            text-align: center;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .input-wrap {
            position: relative;
        }

        .inp-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--dim);
            display: flex;
            align-items: center;
        }

        input[type="email"] {
            width: 100%;
            background: rgba(7, 10, 18, 0.8);
            border: 1px solid var(--border);
            padding: 12px 16px 12px 42px;
            border-radius: 8px;
            color: var(--text);
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.12);
        }

        .btn-submit {
            width: 100%;
            background: var(--primary);
            color: #ffffff;
            border: none;
            padding: 13px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: background 0.15s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background: #0369a1;
        }

        .alert-box {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.84rem;
            margin-bottom: 20px;
            line-height: 1.5;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.25);
            color: #6ee7b7;
        }

        .footer-note {
            text-align: center;
            margin-top: 24px;
            font-size: 0.82rem;
            color: var(--dim);
        }

        .footer-note a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
        }

        .footer-note a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <a href="/index.php" class="brand-header">
            <img src="/favicon.svg" alt="Weather Report Logo" style="width:36px;height:36px;border-radius:8px;display:block;">
            <span class="brand-name">Weather Report Solutions</span>
        </a>

        <h1 class="card-heading">Restablecer Clave</h1>
        <p class="card-subheading">Indique su correo corporativo para recibir el enlace de recuperación seguro</p>

        <?php if ($message): ?>
            <div class="alert-box <?= $type === 'success' ? 'alert-success' : 'alert-danger' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:2px;">
                    <?php if ($type === 'success'): ?>
                    <polyline points="20 6 9 17 4 12"/>
                    <?php else: ?>
                    <circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/>
                    <?php endif; ?>
                </svg>
                <span><?= $message ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="/forgot_password.php">
            <div class="form-group">
                <label for="email">Correo Corporativo Registrado</label>
                <div class="input-wrap">
                    <span class="inp-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    </span>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="ej. aitor@weather-report.com"
                        required
                        autofocus
                    >
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <span>Generar Enlace de Recuperación</span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
        </form>

        <div class="footer-note">
            <a href="/login.php">← Volver al Acceso de Clientes</a>
        </div>
    </div>
</body>
</html>
