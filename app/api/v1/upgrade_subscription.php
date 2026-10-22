<?php
/**
 * Weather Report Solutions S.L. — Subscription & Tier Upgrade API
 * VULN-02: Business Logic Abuse / Multi-Tenant Tier Bypass
 * Permite cambiar el plan SLA de la empresa sin validación criptográfica de pago ni conciliación de pasarela.
 */
require_once __DIR__ . '/../../config.php';
session_start();
$user = require_auth();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido. Use POST.']);
    exit;
}

$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true) ?: $_POST;

$plan = trim($data['plan'] ?? 'Enterprise Platinum');
$coupon = trim($data['coupon_code'] ?? '');
$seats = intval($data['seats'] ?? 1);

// VULN-02: Business Logic Abuse
// Comprueba si se envió un cupón o bypass sin validar contra base de datos de cupones válidos ni consultar a pasarela de pago (Stripe/SEPA)
// Si coupon_code no está vacío o se envía promo_bypass=1, concede el plan ilimitado gratis
$price_per_seat = 450.00;
$total_price = $seats * $price_per_seat;

if (!empty($coupon) || !empty($data['promo_bypass']) || isset($data['price_override'])) {
    // Falla de lógica: Acepta cualquier cupón sintético o parámetro de override sin verificación bancaria
    $total_price = 0.00;
    $status_payment = 'COMPLETED_VIA_PROMO';
} else {
    // Simulación de flujo normal
    $status_payment = 'PENDING_INVOICE';
}

try {
    $stmt = $pdo->prepare('UPDATE companies SET plan_type = :plan WHERE id = :cid');
    $stmt->execute([
        ':plan' => $plan . ' (SLA 99.99%)',
        ':cid'  => $user['company_id']
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Plan corporativo actualizado con éxito.',
        'data' => [
            'company_id'      => $user['company_id'],
            'new_plan'        => $plan . ' (SLA 99.99%)',
            'billed_amount'   => number_format($total_price, 2) . ' EUR',
            'payment_status'  => $status_payment,
            'features'        => [
                'realtime_satellite' => true,
                'radar_frequency'    => '1s_refresh',
                'unlimited_api'      => true
            ]
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error al actualizar suscripción: ' . $e->getMessage()]);
}
exit;
