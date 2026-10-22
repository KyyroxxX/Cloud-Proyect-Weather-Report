<?php
/**
 * Weather Report Solutions S.L. — Satellite Image Credit Voucher Redemption
 * VULN-10: Race Condition / TOCTOU (Time-of-Check to Time-of-Use)
 * Canje de cupones de crédito satelital Sentinel-3 de un solo uso.
 */
require_once __DIR__ . '/../../config.php';
session_start();
$user = require_auth();
session_write_close(); // Libera el bloqueo de fichero de sesión PHP para permitir concurrencia real

header('Content-Type: application/json; charset=utf-8');

// Soporte de restablecimiento para pruebas automatizadas repetibles
if (isset($_REQUEST['reset']) || isset($_GET['reset'])) {
    $pdo->query('UPDATE vouchers SET is_redeemed = 0, redeemed_by = NULL, redeemed_at = NULL');
    echo json_encode(['status' => 'success', 'message' => 'Vouchers reseteados correctamente.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido. Use POST.']);
    exit;
}

$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true) ?: $_POST;

$code = trim($data['voucher_code'] ?? '');

if (empty($code)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Código de cupón requerido.']);
    exit;
}

// VULN-10: Race Condition / TOCTOU
// Paso 1: Time-of-Check (Comprobación de estado sin bloqueo FOR UPDATE)
$stmt = $pdo->prepare('SELECT id, credit_amount, is_redeemed FROM vouchers WHERE code = ?');
$stmt->execute([$code]);
$voucher = $stmt->fetch();

if (!$voucher) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Código de voucher inválido o inexistente.']);
    exit;
}

if ($voucher['is_redeemed'] == 1) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Este voucher ya ha sido canjeado previamente.']);
    exit;
}

// Ventana de latencia de red / procesamiento (amplifica la condición de carrera en pruebas concurrentes)
usleep(250000); // 250 milisegundos para permitir que las peticiones concurrentes crucen el check simultáneamente

// Paso 2: Time-of-Use (Acreditar saldo a la empresa)
$update_comp = $pdo->prepare('UPDATE companies SET credit_balance = credit_balance + ? WHERE id = ?');
$update_comp->execute([$voucher['credit_amount'], $user['company_id']]);

// Paso 3: Marcar voucher como canjeado
$update_vouch = $pdo->prepare('UPDATE vouchers SET is_redeemed = 1, redeemed_by = ?, redeemed_at = CURRENT_TIMESTAMP WHERE id = ?');
$update_vouch->execute([$user['id'], $voucher['id']]);

// Obtener saldo actualizado
$stmt_bal = $pdo->prepare('SELECT credit_balance FROM companies WHERE id = ?');
$stmt_bal->execute([$user['company_id']]);
$new_bal = $stmt_bal->fetchColumn();

echo json_encode([
    'status' => 'success',
    'message' => 'Voucher canjeado con éxito. Se han añadido ' . $voucher['credit_amount'] . ' créditos de observación satelital.',
    'voucher_code'   => $code,
    'credits_added'  => $voucher['credit_amount'],
    'credit_balance' => $new_bal
]);
exit;
