<?php
/**
 * Weather Report Solutions S.L.
 * Production Configuration & Database Layer
 * Internal — Confidential
 */

// ── Database ──────────────────────────────────────────────────
define('DB_HOST', getenv('DB_HOST') ?: '10.0.21.10');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'weather_db');
define('DB_USER', getenv('DB_USER') ?: 'wr_user');
define('DB_PASS', getenv('DB_PASS') ?: 'WrSecureP@ss2026!');

// ── AWS / Cloud Integration ───────────────────────────────────
define('AWS_REGION',     'eu-west-1');
define('AWS_ACCESS_KEY', 'AKIAIOSFODNN7EXAMPLE');          // VULN-08 exposed secrets
define('AWS_SECRET_KEY', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY');
define('S3_BUCKET',      'wr-reports-prod-eu');

// ── App Settings ──────────────────────────────────────────────
define('APP_NAME',    'Weather Report Solutions');
define('APP_VERSION', '4.2.1');
define('APP_URL',     '');                                 // Relative base URL for Nginx proxy compatibility
define('UPLOAD_DIR',  __DIR__ . '/uploads/');

// ── Session (cookie flags - intentionally weak) ───────────────
ini_set('session.cookie_httponly', 0);        // VULN-02: No HttpOnly flag
ini_set('session.cookie_secure',   0);
ini_set('session.use_only_cookies', 0);        // Permite sesión vía parámetro GET para WR-RT-08
ini_set('session.gc_maxlifetime', 86400);

// Compatibilidad con PHP 8.3 para Session Fixation (reemplaza _ por - en IDs antes de session_start)
if (session_status() === PHP_SESSION_NONE) {
    if (!empty($_GET['sid'])) {
        $_GET['sid'] = str_replace('_', '-', $_GET['sid']);
        session_id($_GET['sid']);
    } elseif (!empty($_COOKIE['PHPSESSID']) && str_contains($_COOKIE['PHPSESSID'], '_')) {
        $_COOKIE['PHPSESSID'] = str_replace('_', '-', $_COOKIE['PHPSESSID']);
        session_id($_COOKIE['PHPSESSID']);
    }
}

// ── Database Connection (PostgreSQL 16) ───────────────────────
$pdo = null;
try {
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;',
        DB_HOST, DB_PORT, DB_NAME
    );
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => true,   // VULN-01: emulate_prepares=true allows SQLi bypass in some drivers
    ]);
} catch (PDOException $e) {
    // VULN-09: Verbose error exposed to client
    http_response_code(503);
    die('<pre style="background:#111;color:#f87171;padding:20px;font-family:monospace;">'
        . 'Weather Report Solutions — Database Connection Error<br>'
        . 'DSN: pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . '<br>'
        . 'Error: ' . $e->getMessage()
        . '</pre>');
}

// ── Auth Helper Functions ─────────────────────────────────────

/**
 * Returns the currently logged-in user array or null.
 */
function current_user(): ?array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!empty($_SESSION['user_id'])) {
        global $pdo;
        $stmt = $pdo->prepare('SELECT u.*, c.company_name, c.plan_type FROM users u LEFT JOIN companies c ON u.company_id = c.id WHERE u.id = ? AND u.is_active = 1');
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    }
    return null;
}

/**
 * Redirect to login if user is not authenticated.
 */
function require_auth(): array {
    $user = current_user();
    if (!$user) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
    return $user;
}

/**
 * Redirect to dashboard if user is already logged in.
 */
function redirect_if_auth(): void {
    if (current_user()) {
        header('Location: ' . APP_URL . '/dashboard.php');
        exit;
    }
}
