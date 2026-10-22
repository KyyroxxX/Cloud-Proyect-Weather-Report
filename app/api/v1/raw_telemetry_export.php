<?php
/**
 * Weather Report Solutions S.L. — Telemetry Raw Log Export API
 * VULN-06: Path Traversal / Arbitrary File Read
 * Permite descargar archivos brutos de sensores meteorológicos.
 */
require_once __DIR__ . '/../../config.php';
session_start();
$user = require_auth();

$file = $_GET['file'] ?? '';

if (empty($file)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Parámetro ?file requerido. Archivos disponibles: sensor_audit_2026_q3.csv, telemetry_calibration_log.json'
    ]);
    exit;
}

// VULN-06: Construcción insegura de ruta sin basename() ni validación de realpath()
$base_dir = realpath(__DIR__ . '/../../exports');
$target_path = $base_dir . '/' . $file;

if (!file_exists($target_path)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Archivo no encontrado en el repositorio de telemetría.'
    ]);
    exit;
}

// Determinar Content-Type
$ext = pathinfo($target_path, PATHINFO_EXTENSION);
$mime_types = [
    'csv'  => 'text/csv',
    'json' => 'application/json',
    'txt'  => 'text/plain',
    'php'  => 'text/plain',
    'conf' => 'text/plain'
];
$content_type = $mime_types[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $content_type . '; charset=utf-8');
header('Content-Disposition: inline; filename="' . basename($target_path) . '"');
header('Content-Length: ' . filesize($target_path));
readfile($target_path);
exit;
