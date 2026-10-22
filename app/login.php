<?php
/**
 * Weather Report Solutions S.L. — Acceso al Portal Corporativo
 * Unified Web App Layout Architecture
 * VULN-01 (WR-RT-03): SQL Injection en autenticación
 * VULN-08 (WR-RT-08): Session Fixation mediante parámetro ?sid=
 */
require_once __DIR__ . '/config.php';

// WR-RT-08 Session Fixation: se acepta session_id desde parámetro GET sin regeneración
if (!empty($_GET['sid'])) {
    session_id($_GET['sid']);
}
session_start();
if (!empty($_GET['sid'])) {
    setcookie(session_name(), $_GET['sid'], 0, '/');
}

redirect_if_auth();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        // WR-RT-03 SQL Injection: concatenación directa en la consulta de autenticación
        $sql = "SELECT * FROM users WHERE username = '" . $username . "' AND password_hash = '" . md5($password) . "'";
        try {
            $stmt = $pdo->query($sql);
            $user = $stmt->fetch();

            // Fallback para contraseñas Bcrypt estándar
            if (!$user) {
                $check_stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
                $check_stmt->execute([$username, $username]);
                $check_user = $check_stmt->fetch();
                if ($check_user && password_verify($password, $check_user['password_hash'])) {
                    $user = $check_user;
                }
            }

            if ($user) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['role']      = $user['role'];
                $_SESSION['company_id']= $user['company_id'];

                $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);

                header('Location: ' . APP_URL . '/dashboard.php');
                exit;
            } else {
                $error = 'Credenciales no válidas. Por favor, compruebe usuario y contraseña.';
            }
        } catch (PDOException $e) {
            // Exposición verbosa de error SQL
            $error = 'Error de sintaxis / consulta SQL: ' . $e->getMessage();
        }
    } else {
        $error = 'Por favor, complete todos los campos obligatorios.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Portal — Weather Report Solutions S.L.</title>
    <meta name="description" content="Portal de acceso para clientes corporativos y analistas meteorológicos de Weather Report Solutions.">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
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

        .login-card {
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

        input[type="text"], input[type="password"] {
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

        .row-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            font-size: 0.82rem;
        }

        .remember-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            cursor: pointer;
        }

        .link-forgot {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
        }

        .link-forgot:hover {
            text-decoration: underline;
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
            margin-top: 28px;
            font-size: 0.78rem;
            color: var(--dim);
            line-height: 1.6;
            border-top: 1px solid var(--border);
            padding-top: 20px;
        }

        .footer-note a {
            color: var(--muted);
            text-decoration: none;
        }

        .footer-note a:hover {
            color: var(--accent);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <a href="/index.php" class="brand-header">
            <img src="/favicon.svg" alt="Weather Report Logo" style="width:36px;height:36px;border-radius:8px;display:block;">
            <span class="brand-name">Weather Report Solutions</span>
        </a>

        <h1 class="card-heading">Portal Corporativo</h1>
        <p class="card-subheading">Inicie sesión para acceder a telemetría y dossiers confidenciales</p>

        <?php if ($error): ?>
            <div class="alert-box alert-danger">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:2px;"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                <span><?= $error ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert-box alert-success">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:2px;"><polyline points="20 6 9 17 4 12"/></svg>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="/login.php" autocomplete="off">
            <div class="form-group">
                <label for="username">Identificador de Usuario</label>
                <div class="input-wrap">
                    <span class="inp-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="ej. aitor.navarro"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        required
                        autofocus
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="password">Contraseña Corporativa</label>
                <div class="input-wrap">
                    <span class="inp-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="••••••••••••"
                        required
                    >
                </div>
            </div>

            <div class="row-options">
                <label class="remember-checkbox">
                    <input type="checkbox" name="remember" id="remember" style="accent-color:var(--primary);">
                    Recordar terminal
                </label>
                <a href="/forgot_password.php" class="link-forgot">¿Olvidó su clave?</a>
            </div>

            <button type="submit" class="btn-submit" id="btn-login">
                <span>Ingresar al Sistema</span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
        </form>

        <div class="footer-note">
            Acceso restringido a personal autorizado y clientes suscritos.<br>
            <a href="/index.php">← Volver al Portal Público</a>
        </div>
    </div>
</body>
</html>
