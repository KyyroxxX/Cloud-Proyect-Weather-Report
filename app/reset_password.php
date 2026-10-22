<?php
/**
 * Weather Report Solutions S.L. — Formulario de Restablecimiento de Clave
 * Unified Web App Layout Architecture
 */
require_once __DIR__ . '/config.php';
session_start();

$token = trim($_GET['token'] ?? ($_POST['token'] ?? ''));
$message = '';
$type = '';
$token_valid = false;
$user_id = null;

if (!empty($token)) {
    $stmt = $pdo->prepare('SELECT pr.*, u.username, u.email FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()');
    $stmt->execute([$token]);
    $reset_record = $stmt->fetch();

    if ($reset_record) {
        $token_valid = true;
        $user_id = $reset_record['user_id'];
    } else {
        $message = 'El token de recuperación es inválido o ha caducado. Por favor solicite un nuevo enlace.';
        $type = 'danger';
    }
} else {
    $message = 'No se ha proporcionado ningún token de recuperación válido.';
    $type = 'danger';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || strlen($new_password) < 6) {
        $message = 'La nueva clave debe tener al menos 6 caracteres.';
        $type = 'danger';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Las claves indicadas no coinciden.';
        $type = 'danger';
    } else {
        // Actualizar clave del usuario en MD5 y marcar token como consumido
        $hash_md5 = md5($new_password);
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash_md5, $user_id]);
        $pdo->prepare('UPDATE password_resets SET used = 1 WHERE token = ?')->execute([$token]);

        $message = 'Contraseña corporativa actualizada satisfactoriamente. Proceda a iniciar sesión.';
        $type = 'success';
        $token_valid = false;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Establecer Nueva Contraseña — Weather Report Solutions S.L.</title>
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

        input[type="password"] {
            width: 100%;
            background: rgba(7, 10, 18, 0.8);
            border: 1px solid var(--border);
            padding: 12px 16px;
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

        <h1 class="card-heading">Nueva Contraseña</h1>
        <p class="card-subheading">Establezca una nueva clave de acceso para su cuenta corporativa</p>

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

        <?php if ($token_valid): ?>
        <form method="POST" action="/reset_password.php">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            
            <div class="form-group">
                <label for="new_password">Nueva Contraseña</label>
                <input type="password" id="new_password" name="new_password" placeholder="Mínimo 6 caracteres" required autofocus>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmar Nueva Contraseña</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Repita la nueva contraseña" required>
            </div>

            <button type="submit" class="btn-submit">
                <span>Actualizar Contraseña</span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
        </form>
        <?php endif; ?>

        <div class="footer-note">
            <a href="/login.php">← Ir a Inicio de Sesión</a>
        </div>
    </div>
</body>
</html>
