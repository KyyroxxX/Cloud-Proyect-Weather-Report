<?php
/**
 * Weather Report Solutions S.L. — User Profile & Settings API
 * VULN-07: Mass Assignment / Property-Level Authorization
 * Permite actualizar preferencias de usuario deserializando parámetros sin lista blanca.
 */
require_once __DIR__ . '/../../config.php';
session_start();
$user = require_auth();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido. Use POST o PUT.']);
    exit;
}

$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true) ?: $_POST;

if (empty($data)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No se enviaron campos para actualizar.']);
    exit;
}

// VULN-07: Mass Assignment
// Construye dinámicamente la cláusula SET a partir de todas las propiedades recibidas en el payload
// No filtra propiedades de seguridad como 'role', 'company_id', 'is_active'
$updates = [];
$params = [':id' => $user['id']];

foreach ($data as $column => $value) {
    // Sanitización básica de nombre de columna contra caracteres no alfanuméricos
    $clean_col = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
    if (!empty($clean_col) && $clean_col !== 'id') {
        $updates[] = "$clean_col = :$clean_col";
        $params[":$clean_col"] = $value;
    }
}

if (empty($updates)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Campos inválidos.']);
    exit;
}

try {
    $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Si se modificó el role o company_id en la sesión, reflejarlo
    if (isset($data['role'])) {
        $_SESSION['role'] = $data['role'];
    }

    // Obtener usuario refrescado
    $stmt_fresh = $pdo->prepare('SELECT id, username, email, full_name, role, company_id FROM users WHERE id = ?');
    $stmt_fresh->execute([$user['id']]);
    $updated_user = $stmt_fresh->fetch();

    echo json_encode([
        'status' => 'success',
        'message' => 'Perfil corporativo actualizado correctamente.',
        'user' => $updated_user
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error de persistencia: ' . $e->getMessage()]);
}
exit;
