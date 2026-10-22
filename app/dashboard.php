<?php
/**
 * Weather Report Solutions S.L. — Corporate Operations Dashboard
 * Unified Enterprise SaaS Architecture & Operations HUD
 * VULN-01: Insecure Direct Object References en visor de informes
 * VULN-02 (WR-RT-02): Business Logic Abuse en actualización de plan
 * VULN-10: Canje de cupones de crédito satelital
 */
require_once __DIR__ . '/config.php';
session_start();
$user = require_auth();

// Estadísticas operativas
$total_reports = 0;
$total_tickets = 0;
$total_stations = 0;

try {
    $total_reports = $pdo->query('SELECT COUNT(*) FROM reports')->fetchColumn();
    $total_tickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'ABIERTO'")->fetchColumn();
    $total_stations = $pdo->query('SELECT COUNT(*) FROM weather_data')->fetchColumn();
} catch (Exception $e) {}

// Informes de la empresa (o todos si es admin)
if ($user['role'] === 'admin') {
    $my_reports = $pdo->query('SELECT r.*, c.company_name FROM reports r JOIN companies c ON r.company_id = c.id ORDER BY r.created_at DESC')->fetchAll();
} else {
    $stmt = $pdo->prepare('SELECT r.*, c.company_name FROM reports r JOIN companies c ON r.company_id = c.id WHERE r.company_id = ? ORDER BY r.created_at DESC');
    $stmt->execute([$user['company_id']]);
    $my_reports = $stmt->fetchAll();
}

// Datos de la empresa (Créditos satelitales y Plan SLA)
$comp_stmt = $pdo->prepare('SELECT * FROM companies WHERE id = ?');
$comp_stmt->execute([$user['company_id']]);
$company = $comp_stmt->fetch() ?: ['credit_balance' => 500, 'plan_type' => 'Enterprise Platinum', 'api_key' => 'wr_live_key_default'];

// Estaciones de telemetría activas
$stations = [];
try {
    $stations = $pdo->query('SELECT * FROM weather_data ORDER BY city_name ASC')->fetchAll();
} catch (Exception $e) {}

if (empty($stations)) {
    $stations = [
        ['station_code' => 'ST-MAD-01', 'city_name' => 'Madrid (Barajas)', 'temp_celsius' => 21.8, 'humidity' => 45, 'conditions' => 'Despejado', 'wind_speed_kmh' => 12.4],
        ['station_code' => 'ST-BCN-01', 'city_name' => 'Barcelona (Port)', 'temp_celsius' => 23.2, 'humidity' => 62, 'conditions' => 'Parcialmente Nublado', 'wind_speed_kmh' => 18.0],
        ['station_code' => 'ST-BER-01', 'city_name' => 'Berlín (Brandeburgo)', 'temp_celsius' => 16.5, 'humidity' => 58, 'conditions' => 'Nublado', 'wind_speed_kmh' => 14.1],
        ['station_code' => 'ST-LON-01', 'city_name' => 'Londres (Heathrow)', 'temp_celsius' => 15.1, 'humidity' => 74, 'conditions' => 'Lluvia Débil', 'wind_speed_kmh' => 22.3],
        ['station_code' => 'ST-AMS-01', 'city_name' => 'Ámsterdam (Schiphol)', 'temp_celsius' => 17.2, 'humidity' => 68, 'conditions' => 'Brisa Marina', 'wind_speed_kmh' => 20.1],
        ['station_code' => 'ST-PAR-01', 'city_name' => 'París (CDG)', 'temp_celsius' => 18.4, 'humidity' => 60, 'conditions' => 'Despejado', 'wind_speed_kmh' => 11.0],
    ];
}

// Tickets recientes de la empresa
$stmt = $pdo->prepare('SELECT * FROM tickets WHERE company_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->execute([$user['company_id']]);
$my_tickets = $stmt->fetchAll();

$page_title = 'Centro de Operaciones';
$page_heading = 'Panel de Control';

require __DIR__ . '/includes/layout_head.php';
require __DIR__ . '/includes/layout_sidebar.php';
?>

<main class="app-main">
    <?php require __DIR__ . '/includes/layout_topbar.php'; ?>

    <div class="app-content-body">
        <!-- Banner de bienvenida ejecutivo y sobrio -->
        <div style="background:var(--surface-card);border:1px solid var(--border);border-radius:12px;padding:24px 28px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 10px 30px rgba(0,0,0,0.2);flex-wrap:wrap;gap:16px;">
            <div>
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                    <h2 style="font-size:1.35rem;font-weight:700;">Bienvenido, <?= htmlspecialchars($user['full_name'] ?? $user['username']) ?></h2>
                    <span class="pill-tag pill-tag-success" style="font-size:0.68rem;">TENANT ACTIVO</span>
                </div>
                <p style="color:var(--muted);font-size:0.86rem;">
                    <?= htmlspecialchars($user['company_name'] ?? 'Weather Report Solutions') ?>
                    · Nivel SLA: <span style="color:var(--accent);font-weight:600;" id="current-plan-badge"><?= htmlspecialchars($company['plan_type'] ?? 'Enterprise SaaS') ?></span>
                    · <span class="font-mono" style="color:var(--dim);font-size:0.78rem;">TENANT_ID: #ORG-<?= str_pad($user['company_id'] ?? 1, 3, '0', STR_PAD_LEFT) ?></span>
                </p>
            </div>
            <div style="display:flex;gap:10px;align-items:center;">
                <button onclick="openUpgradeModal()" class="btn-ui btn-ui-ghost">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                    Ajustar Plan SLA
                </button>
                <a href="/api/v1/download_report.php?report_id=1" class="btn-ui btn-ui-ghost">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Ver Informes
                </a>
                <a href="#vouchers-panel" class="btn-ui btn-ui-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                    Canjear Voucher
                </a>
            </div>
        </div>

        <!-- KPI Cards Grid -->
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:24px;">
            <div class="card-ui" style="margin-bottom:0;padding:22px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <span style="font-size:0.75rem;color:var(--muted);text-transform:uppercase;font-weight:600;">Informes Clasificados</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <div class="font-mono" style="font-size:2rem;font-weight:700;color:var(--accent);"><?= count($my_reports) ?></div>
                <div style="font-size:0.75rem;color:var(--dim);margin-top:4px;">Dossiers climáticos asignados</div>
            </div>
            <div class="card-ui" style="margin-bottom:0;padding:22px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <span style="font-size:0.75rem;color:var(--muted);text-transform:uppercase;font-weight:600;">Créditos Satélite (Sentinel-3)</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m4.93 4.93 4.24 4.24"/></svg>
                </div>
                <div id="credit-display" class="font-mono" style="font-size:2rem;font-weight:700;color:var(--success);"><?= htmlspecialchars($company['credit_balance'] ?? 500) ?></div>
                <div style="font-size:0.75rem;color:var(--dim);margin-top:4px;">Saldo multiespectral disponible</div>
            </div>
            <div class="card-ui" style="margin-bottom:0;padding:22px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <span style="font-size:0.75rem;color:var(--muted);text-transform:uppercase;font-weight:600;">Nodos Telemétricos</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#a5b4fc" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M3 12h3M18 12h3M12 3v3M12 18v3"/></svg>
                </div>
                <div class="font-mono" style="font-size:2rem;font-weight:700;color:#a5b4fc;"><?= count($stations) ?></div>
                <div style="font-size:0.75rem;color:var(--dim);margin-top:4px;">Estaciones activas en red</div>
            </div>
            <div class="card-ui" style="margin-bottom:0;padding:22px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <span style="font-size:0.75rem;color:var(--muted);text-transform:uppercase;font-weight:600;">Latencia Red SLA</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </div>
                <div class="font-mono" style="font-size:2rem;font-weight:700;color:var(--accent);">42 ms</div>
                <div style="font-size:0.75rem;color:var(--dim);margin-top:4px;">AWS eu-west-1 Multi-AZ</div>
            </div>
        </div>

        <!-- Secciones Principales: Informes y Estaciones -->
        <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-bottom:24px;">
            <!-- Tabla de Informes Climáticos con Filtro Rápido -->
            <div class="card-ui" style="margin-bottom:0;">
                <div class="card-ui-header">
                    <div class="card-ui-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        Expedientes de Inteligencia Meteorológica
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <input type="text" id="report-search" placeholder="Filtrar expediente..." onkeyup="filterReportsSearch(this.value)" style="background:var(--surface);border:1px solid var(--border);color:var(--text);padding:4px 8px;border-radius:6px;font-size:0.74rem;outline:none;width:140px;">
                        <select id="report-filter" onchange="filterReports(this.value)" style="background:var(--surface);border:1px solid var(--border);color:var(--muted);padding:4px 8px;border-radius:6px;font-size:0.74rem;outline:none;">
                            <option value="ALL">Todas las clasificaciones</option>
                            <option value="CONFIDENTIAL">Confidenciales</option>
                            <option value="RESTRICTED">Restringidos</option>
                            <option value="INTERNAL">Uso Interno</option>
                        </select>
                        <span class="pill-tag pill-tag-info"><?= count($my_reports) ?> Dossiers</span>
                    </div>
                </div>
                <div style="overflow-x:auto;">
                    <table class="table-ui" id="reports-table">
                        <thead>
                            <tr>
                                <th style="width:70px;"># ID</th>
                                <th>Informe / Asunto</th>
                                <th>Empresa</th>
                                <th>Clasificación</th>
                                <th style="text-align:right;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($my_reports as $r): ?>
                            <?php
                            $cls = strtolower($r['classification']);
                            if (str_contains($cls, 'estric')) { $badge = 'pill-tag-danger'; $filter_cat = 'CONFIDENTIAL'; }
                            elseif (str_contains($cls, 'restri')) { $badge = 'pill-tag-warn'; $filter_cat = 'RESTRICTED'; }
                            else { $badge = 'pill-tag-info'; $filter_cat = 'INTERNAL'; }
                            ?>
                            <tr class="report-row" data-classification="<?= $filter_cat ?>">
                                <td class="font-mono" style="color:var(--muted);">#<?= sprintf('%04d', $r['id']) ?></td>
                                <td>
                                    <div style="font-weight:600;color:var(--text);"><?= htmlspecialchars(substr($r['title'], 0, 48)) ?>...</div>
                                    <div style="font-size:0.72rem;color:var(--dim);margin-top:2px;"><?= htmlspecialchars(substr($r['summary'] ?? '', 0, 60)) ?>...</div>
                                </td>
                                <td><?= htmlspecialchars($r['company_name']) ?></td>
                                <td>
                                    <span class="pill-tag <?= $badge ?>" style="font-size:0.68rem;"><?= htmlspecialchars($r['classification']) ?></span>
                                </td>
                                <td style="text-align:right;">
                                    <a href="/api/v1/download_report.php?report_id=<?= $r['id'] ?>" class="btn-ui btn-ui-ghost" style="padding:4px 10px;font-size:0.78rem;">Abrir Visor →</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Telemetría en Vivo de Sensores -->
            <div class="card-ui" style="margin-bottom:0;">
                <div class="card-ui-header">
                    <div class="card-ui-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="2"/><path d="M16.24 7.76a6 6 0 0 1 0 8.49m-8.48-.01a6 6 0 0 1 0-8.49"/></svg>
                        Sensores en Tiempo Real
                    </div>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <input type="text" id="station-filter" placeholder="Buscar ciudad..." onkeyup="filterStations(this.value)" style="background:var(--surface);border:1px solid var(--border);color:var(--text);padding:3px 6px;border-radius:5px;font-size:0.72rem;outline:none;width:105px;">
                        <span class="pill-tag pill-tag-success">En Directo</span>
                    </div>
                </div>
                <div id="stations-list-container" style="max-height:360px;overflow-y:auto;padding:12px;display:flex;flex-direction:column;gap:8px;">
                    <?php foreach ($stations as $s): ?>
                    <div class="station-card-item" data-city="<?= htmlspecialchars(strtolower($s['city_name'])) ?>" style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:12px 14px;display:flex;justify-content:space-between;align-items:center;transition:background 0.15s;">
                        <div>
                            <div style="font-weight:600;font-size:0.86rem;color:var(--text);display:flex;align-items:center;gap:6px;">
                                <span style="width:6px;height:6px;border-radius:50%;background:var(--success);display:inline-block;"></span>
                                <?= htmlspecialchars($s['city_name']) ?>
                            </div>
                            <div style="font-size:0.74rem;color:var(--muted);margin-top:2px;"><?= htmlspecialchars($s['conditions']) ?></div>
                        </div>
                        <div style="text-align:right;">
                            <div class="font-mono" style="font-size:1.05rem;font-weight:700;color:var(--accent);"><?= $s['temp_celsius'] ?>°C</div>
                            <div style="font-size:0.7rem;color:var(--dim);"><?= $s['humidity'] ?>% HR · <?= $s['wind_speed_kmh'] ?> km/h</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Vouchers y Operaciones Corporativas -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;" id="vouchers-panel">
            <!-- Canje de Vouchers (WR-RT-10) -->
            <div class="card-ui" style="margin-bottom:0;">
                <div class="card-ui-header">
                    <div class="card-ui-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                        Canje de Cupones de Satélite Sentinel-3
                    </div>
                    <span class="pill-tag pill-tag-warn">Un Solo Uso</span>
                </div>
                <div style="padding:24px;">
                    <p style="font-size:0.84rem;color:var(--muted);margin-bottom:16px;line-height:1.5;">
                        Acredita saldo adicional para peticiones de adquisición multiespectral de alta resolución.
                    </p>
                    <div style="display:flex;gap:10px;margin-bottom:12px;">
                        <input type="text" id="voucher-input" class="form-control-ui font-mono" placeholder="Ej: SAT-SENTINEL-500" style="flex:1;">
                        <button onclick="redeemVoucher()" class="btn-ui btn-ui-primary" style="white-space:nowrap;">Canjear</button>
                    </div>
                    <div id="voucher-status" style="font-size:0.82rem;min-height:22px;"></div>
                    <div style="margin-top:14px;padding:12px 14px;background:rgba(255,255,255,0.02);border:1px solid var(--border);border-radius:6px;font-size:0.76rem;color:var(--dim);">
                        <strong>Códigos de cupón autorizados (clic para cargar):</strong> 
                        <code onclick="setVoucherCode('SAT-SENTINEL-500')" style="color:var(--accent);cursor:pointer;text-decoration:underline;">SAT-SENTINEL-500</code> (+500), 
                        <code onclick="setVoucherCode('TURBO-RADAR-250')" style="color:var(--accent);cursor:pointer;text-decoration:underline;">TURBO-RADAR-250</code> (+250), 
                        <code onclick="setVoucherCode('CLIMATE-PASS-1000')" style="color:var(--accent);cursor:pointer;text-decoration:underline;">CLIMATE-PASS-1000</code> (+1000).
                    </div>
                </div>
            </div>

            <!-- Suscripción SLA y Exportación de Telemetría (WR-RT-02 & WR-RT-06) -->
            <div class="card-ui" style="margin-bottom:0;">
                <div class="card-ui-header">
                    <div class="card-ui-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                        Exportaciones Raw y Nivel de Suscripción
                    </div>
                </div>
                <div style="padding:24px;display:flex;flex-direction:column;gap:12px;">
                    <a href="/api/v1/raw_telemetry_export.php?file=sensor_audit_2026_q3.csv" class="btn-ui btn-ui-ghost" style="justify-content:space-between;padding:10px 14px;">
                        <span>Auditoría de Nodos Q3 (CSV)</span>
                        <span class="font-mono" style="color:var(--accent);">Descargar →</span>
                    </a>
                    <a href="/api/v1/raw_telemetry_export.php?file=telemetry_calibration_log.json" class="btn-ui btn-ui-ghost" style="justify-content:space-between;padding:10px 14px;">
                        <span>Registro de Calibración de Sensores (JSON)</span>
                        <span class="font-mono" style="color:var(--accent);">Descargar →</span>
                    </a>
                    <div style="margin-top:8px;padding-top:14px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <div style="font-size:0.84rem;font-weight:600;color:var(--text);">Plan Activo: <?= htmlspecialchars($company['plan_type'] ?? 'Standard') ?></div>
                            <div style="font-size:0.74rem;color:var(--dim);">SLA Empresarial 99.99% Multi-Tenant</div>
                        </div>
                        <button onclick="openUpgradeModal()" class="btn-ui btn-ui-primary" style="padding:6px 14px;font-size:0.8rem;">Mejorar Nivel SLA</button>
                    </div>
                    <div id="plan-status" style="font-size:0.8rem;"></div>
                </div>
            </div>
        </div>

        <!-- Historial de Tickets SLA -->
        <?php if ($my_tickets): ?>
        <div class="card-ui">
            <div class="card-ui-header">
                <div class="card-ui-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Tickets de Soporte e Incidencias Técnicas
                </div>
                <a href="/feedback.php?tab=incidencias" class="btn-ui btn-ui-ghost" style="padding:4px 10px;font-size:0.78rem;">+ Nueva Incidencia</a>
            </div>
            <div style="overflow-x:auto;">
                <table class="table-ui">
                    <thead>
                        <tr>
                            <th style="width:70px;"># ID</th>
                            <th>Asunto</th>
                            <th style="width:120px;">Prioridad</th>
                            <th style="width:130px;">Estado</th>
                            <th style="width:140px;">Fecha de Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_tickets as $t): ?>
                        <tr>
                            <td class="font-mono" style="color:var(--muted);">#<?= sprintf('%04d', $t['id']) ?></td>
                            <td><strong><?= htmlspecialchars(substr($t['subject'], 0, 55)) ?>...</strong></td>
                            <td><span class="pill-tag <?= $t['priority'] === 'ALTA' ? 'pill-tag-danger' : 'pill-tag-info' ?>"><?= $t['priority'] ?></span></td>
                            <td><span class="pill-tag <?= $t['status'] === 'ABIERTO' ? 'pill-tag-warn' : 'pill-tag-success' ?>"><?= $t['status'] ?></span></td>
                            <td class="font-mono" style="font-size:0.78rem;color:var(--muted);"><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── MODAL INTERACTIVO DE MEJORA DE PLAN SLA (WR-RT-02) ── -->
        <div id="upgrade-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);backdrop-filter:blur(8px);z-index:200;align-items:center;justify-content:center;padding:20px;">
            <div style="background:var(--surface-card);border:1px solid var(--border);border-radius:14px;max-width:520px;width:100%;box-shadow:0 25px 50px rgba(0,0,0,0.5);overflow:hidden;">
                <div style="padding:20px 24px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                    <div style="font-size:1.1rem;font-weight:700;color:var(--text);display:flex;align-items:center;gap:8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                        Actualización de Plan y Nivel SLA
                    </div>
                    <button onclick="closeUpgradeModal()" style="background:transparent;border:none;color:var(--muted);font-size:1.3rem;cursor:pointer;">&times;</button>
                </div>
                <div style="padding:24px;">
                    <div class="form-group-ui">
                        <label class="form-label-ui">Seleccione el Nivel SLA Deseado</label>
                        <select id="plan-select" class="form-control-ui" onchange="updatePlanPricing()">
                            <option value="Professional Doppler">Professional Doppler — 499 €/mes (SLA 99.9%)</option>
                            <option value="Enterprise VIP SLA" selected>Enterprise VIP SLA — 1.999 €/mes (SLA 99.99% Multi-AZ)</option>
                            <option value="Standard SaaS">Standard SaaS — 199 €/mes (Nivel Básico)</option>
                        </select>
                    </div>
                    <div class="form-group-ui">
                        <label class="form-label-ui">Puestos de Operador Asignados</label>
                        <input type="number" id="seats-input" class="form-control-ui font-mono" value="10" min="1" max="100">
                    </div>
                    <div class="form-group-ui">
                        <label class="form-label-ui">Cupón de Facturación Corporativa / Código Promocional</label>
                        <input type="text" id="coupon-input" class="form-control-ui font-mono" placeholder="Ej. WR-FREE-PASS-2026 o WR-VIP-PASS-2026">
                        <div style="font-size:0.72rem;color:var(--dim);margin-top:4px;">
                            Permite aplicar convenios de partners europeos autorizados para exención de pasarela.
                        </div>
                    </div>
                    <div id="modal-feedback" style="min-height:22px;font-size:0.82rem;margin-bottom:12px;"></div>
                    <div style="display:flex;gap:10px;justify-content:flex-end;">
                        <button type="button" onclick="closeUpgradeModal()" class="btn-ui btn-ui-ghost">Cancelar</button>
                        <button type="button" onclick="submitPlanUpgrade()" class="btn-ui btn-ui-primary">Confirmar Nivel SLA</button>
                    </div>
                </div>
            </div>
        </div>

    <script>
    function filterReports(category) {
        document.querySelectorAll('.report-row').forEach(function(row) {
            if (category === 'ALL' || row.getAttribute('data-classification') === category) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function filterReportsSearch(query) {
        const q = query.toLowerCase();
        document.querySelectorAll('.report-row').forEach(function(row) {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(q) ? '' : 'none';
        });
    }

    function filterStations(query) {
        const q = query.toLowerCase();
        document.querySelectorAll('.station-card-item').forEach(function(item) {
            const city = item.getAttribute('data-city') || '';
            item.style.display = city.includes(q) ? 'flex' : 'none';
        });
    }

    function setVoucherCode(code) {
        const inp = document.getElementById('voucher-input');
        if (inp) {
            inp.value = code;
            inp.focus();
        }
    }

    function updatePlanPricing() {
        const sel = document.getElementById('plan-select').value;
        const seats = parseInt(document.getElementById('seats-input').value) || 10;
        let base = 199;
        if (sel.includes('Enterprise')) base = 1999;
        else if (sel.includes('Professional')) base = 499;
        const total = base + (seats > 5 ? (seats - 5) * 45 : 0);
        const fb = document.getElementById('modal-feedback');
        if (fb) {
            fb.innerHTML = `<span style="color:var(--accent)">Importe estimado: <strong>${total.toLocaleString('es-ES')} €</strong> / mes (${seats} asientos)</span>`;
        }
    }

    function openUpgradeModal() {
        document.getElementById('upgrade-modal').style.display = 'flex';
        updatePlanPricing();
    }
    function closeUpgradeModal() {
        document.getElementById('upgrade-modal').style.display = 'none';
    }

    async function submitPlanUpgrade() {
        const plan = document.getElementById('plan-select').value;
        const seats = parseInt(document.getElementById('seats-input').value) || 10;
        const coupon = document.getElementById('coupon-input').value.trim();
        const fb = document.getElementById('modal-feedback');

        fb.innerHTML = '<span style="color:var(--muted)">Procesando actualización en motor transaccional...</span>';

        try {
            const res = await fetch('/api/v1/upgrade_subscription.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    plan: plan,
                    coupon_code: coupon,
                    seats: seats
                })
            });
            const data = await res.json();
            if (data.status === 'success') {
                fb.innerHTML = `<span style="color:var(--success)">✓ ${data.message}</span>`;
                document.getElementById('current-plan-badge').innerText = plan;
                setTimeout(() => location.reload(), 1200);
            } else {
                fb.innerHTML = `<span style="color:var(--danger)">✕ ${data.message}</span>`;
            }
        } catch (e) {
            fb.innerHTML = `<span style="color:var(--danger)">Error de conexión con la pasarela.</span>`;
        }
    }

    async function redeemVoucher() {
        const code = document.getElementById('voucher-input').value.trim();
        const statusDiv = document.getElementById('voucher-status');
        if (!code) { statusDiv.innerHTML = '<span style="color:var(--danger)">Introduzca un código de cupón.</span>'; return; }
        statusDiv.innerHTML = '<span style="color:var(--muted)">Validando con el motor transaccional...</span>';
        try {
            const res = await fetch('/api/v1/redeem_voucher.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({voucher_code: code})
            });
            const data = await res.json();
            if (data.status === 'success') {
                statusDiv.innerHTML = `<span style="color:var(--success)">✓ ${data.message} — Saldo actualizado: ${data.credit_balance} créditos</span>`;
                document.getElementById('credit-display').innerText = data.credit_balance;
                document.getElementById('voucher-input').value = '';
            } else {
                statusDiv.innerHTML = `<span style="color:var(--danger)">✕ ${data.message}</span>`;
            }
        } catch (e) {
            statusDiv.innerHTML = `<span style="color:var(--danger)">Error de conexión con el servidor.</span>`;
        }
    }
    </script>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
