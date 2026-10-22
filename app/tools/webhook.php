<?php
/**
 * Weather Report Solutions S.L. — Gestor de Webhooks & Integraciones
 * Unified Web App Layout Architecture
 * VULN-03 (WR-RT-05): SSRF (Server-Side Request Forgery) mediante el parámetro ?url=
 */
require_once __DIR__ . '/../config.php';
session_start();
$user = require_auth();

$test_result = null;
$test_url    = '';

// WR-RT-05 SSRF: el parámetro ?url= se invoca directamente con file_get_contents() en el servidor
if (!empty($_GET['url'])) {
    $test_url = $_GET['url'];
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true,
            'user_agent' => 'WeatherReport-WebhookDispatcher/2.4'
        ]
    ]);
    $raw = @file_get_contents($test_url, false, $ctx);
    $test_result = $raw !== false ? $raw : 'No se pudo obtener respuesta del endpoint especificado (Conexión rechazada o timeout).';
}

// Webhooks registrados de la empresa
$stmt = $pdo->prepare('SELECT * FROM webhooks WHERE company_id = ? ORDER BY created_at DESC');
$stmt->execute([$user['company_id']]);
$webhooks = $stmt->fetchAll();

// Registrar nuevo webhook
$wh_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['target_url'])) {
    $name  = htmlspecialchars($_POST['name'] ?? 'Webhook');
    $url   = $_POST['target_url'];
    $event = $_POST['event_type'] ?? 'weather_alert';

    $pdo->prepare('INSERT INTO webhooks (company_id, name, target_url, event_type) VALUES (?, ?, ?, ?)')
        ->execute([$user['company_id'], $name, $url, $event]);

    header('Location: /tools/webhook.php?msg=created');
    exit;
}

if (isset($_GET['msg']) && $_GET['msg'] === 'created') {
    $wh_msg = 'Endpoint telemétrico registrado satisfactoriamente en el bus de eventos.';
}

$page_title = 'Integración de Webhooks';
$page_heading = 'Herramientas Telemétricas';

require_once __DIR__ . '/../includes/layout_head.php';
require_once __DIR__ . '/../includes/layout_sidebar.php';
?>

<main class="app-main">
    <?php require_once __DIR__ . '/../includes/layout_topbar.php'; ?>

    <div class="app-content-body">
        <!-- Sub-encabezado informativo -->
        <div style="background:var(--surface-card);border:1px solid var(--border);border-radius:12px;padding:20px 24px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 10px 30px rgba(0,0,0,0.2);flex-wrap:wrap;gap:12px;">
            <div>
                <h2 style="font-size:1.25rem;font-weight:700;margin-bottom:4px;">Canales de Notificación Telemétrica (Webhooks)</h2>
                <p style="color:var(--muted);font-size:0.85rem;">
                    Configure destinos HTTP/HTTPS para la ingesta asíncrona de alertas meteorológicas, turbulencia de aviación y riesgos de helada.
                </p>
            </div>
            <div style="display:flex;gap:8px;">
                <span class="pill-tag pill-tag-success">DISPATCHER EN VIVO</span>
                <span class="pill-tag pill-tag-info"><?= count($webhooks) ?> DESTINOS</span>
            </div>
        </div>

        <?php if ($wh_msg): ?>
            <div class="alert-ui alert-ui-success">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                <span><?= htmlspecialchars($wh_msg) ?></span>
            </div>
        <?php endif; ?>

        <!-- Módulo de comprobación de conectividad (SSRF) -->
        <div class="card-ui">
            <div class="card-ui-header">
                <div class="card-ui-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                    Verificación de Conectividad de Endpoint (SSRF Endpoint Tester)
                </div>
                <span style="font-size:0.75rem;color:var(--muted);">Motor de preflight HTTP del servidor</span>
            </div>
            <div style="padding:24px;">
                <p style="font-size:0.86rem;color:var(--muted);margin-bottom:14px;">
                    Compruebe la accesibilidad de su webhook desde el clúster central de Weather Report Solutions antes de activarlo:
                </p>

                <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;align-items:center;">
                    <span style="font-size:0.75rem;color:var(--dim);text-transform:uppercase;font-weight:600;">Objetivos Rápidos:</span>
                    <button type="button" class="btn-ui btn-ui-ghost" style="padding:4px 8px;font-size:0.74rem;" onclick="setWebhookTarget('http://127.0.0.1:8080/')">Localhost (127.0.0.1:8080)</button>
                    <button type="button" class="btn-ui btn-ui-ghost" style="padding:4px 8px;font-size:0.74rem;" onclick="setWebhookTarget('http://169.254.169.254/latest/meta-data/')">AWS Metadata (169.254.169.254)</button>
                    <button type="button" class="btn-ui btn-ui-ghost" style="padding:4px 8px;font-size:0.74rem;" onclick="setWebhookTarget('http://10.0.21.15:5432/')">RDS Cluster (10.0.21.15)</button>
                </div>

                <form method="GET" action="/tools/webhook.php" style="display:flex;gap:12px;margin-bottom:16px;">
                    <input type="text" id="webhook_url_input" name="url" class="form-control-ui font-mono" placeholder="http://su-servidor.corp/api/telemetry-hook" value="<?= htmlspecialchars($test_url) ?>" required style="flex:1;">
                    <button type="submit" class="btn-ui btn-ui-ghost" style="white-space:nowrap;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        Testear Endpoint
                    </button>
                </form>

                <?php if ($test_result !== null): ?>
                <div style="background:#03060c;border:1px solid rgba(56,189,248,0.25);border-radius:10px;overflow:hidden;box-shadow:inset 0 2px 10px rgba(0,0,0,0.5), 0 10px 30px rgba(0,0,0,0.4);margin-top:16px;">
                    <div style="background:#080d1a;padding:10px 16px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid rgba(255,255,255,0.06);">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="width:10px;height:10px;border-radius:50%;background:#ef4444;display:inline-block;"></span>
                            <span style="width:10px;height:10px;border-radius:50%;background:#f59e0b;display:inline-block;"></span>
                            <span style="width:10px;height:10px;border-radius:50%;background:#10b981;display:inline-block;"></span>
                            <span class="font-mono" style="font-size:0.75rem;color:var(--muted);margin-left:8px;">http_dispatcher: <?= htmlspecialchars($test_url) ?></span>
                        </div>
                        <span class="font-mono" style="font-size:0.72rem;color:var(--dim);background:rgba(255,255,255,0.05);padding:2px 8px;border-radius:4px;">
                            <?= strlen($test_result) ?> bytes
                        </span>
                    </div>
                    <div style="padding:18px 20px;">
                        <pre class="font-mono" style="color:#34d399;font-size:0.84rem;line-height:1.6;white-space:pre-wrap;word-break:break-all;max-height:300px;overflow-y:auto;"><?= htmlspecialchars(substr($test_result, 0, 4000)) ?></pre>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
            <!-- Formulario de nuevo webhook -->
            <div class="card-ui">
                <div class="card-ui-header">
                    <div class="card-ui-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Registrar Nuevo Webhook Corporativo
                    </div>
                </div>
                <div style="padding:24px;">
                    <form method="POST" action="/tools/webhook.php">
                        <div class="form-group-ui">
                            <label class="form-label-ui">Identificador del Webhook</label>
                            <input type="text" name="name" class="form-control-ui" placeholder="Ej. SIEM Sentinel Corp — Canal Clima" required>
                        </div>
                        <div class="form-group-ui">
                            <label class="form-label-ui">Categoría de Evento a Suscribir</label>
                            <select name="event_type" class="form-control-ui">
                                <option value="weather_alert">Alerta Meteorológica Severa (METAR / SPECI)</option>
                                <option value="turbulence_alert">Turbulencia Aérea & Windshear (Aviation)</option>
                                <option value="maritime_alert">Alerta Marítima & Temporal (Naval)</option>
                                <option value="frost_alert">Riesgo de Helada & Microclima (AgroTech)</option>
                            </select>
                        </div>
                        <div class="form-group-ui">
                            <label class="form-label-ui">URL Destino del Webhook</label>
                            <input type="text" name="target_url" class="form-control-ui font-mono" placeholder="https://api.empresa.com/v1/telemetry-inbox" required>
                        </div>
                        <button type="submit" class="btn-ui btn-ui-primary" style="width:100%;justify-content:center;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/></svg>
                            Alta en Pipeline de Telemetría
                        </button>
                    </form>
                </div>
            </div>

            <!-- Lista de Webhooks activos -->
            <div class="card-ui">
                <div class="card-ui-header">
                    <div class="card-ui-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        Webhooks Configurados
                    </div>
                    <span class="pill-tag pill-tag-info"><?= count($webhooks) ?> endpoints</span>
                </div>
                <div style="padding:0;">
                    <?php if (empty($webhooks)): ?>
                        <div style="padding:36px;text-align:center;color:var(--muted);font-size:0.86rem;">
                            No existen webhooks configurados para su organización.
                        </div>
                    <?php else: ?>
                        <table class="table-ui">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Evento</th>
                                    <th>Destino</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($webhooks as $w): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:600;color:var(--text);"><?= htmlspecialchars($w['name']) ?></div>
                                    </td>
                                    <td>
                                        <span class="pill-tag pill-tag-info" style="font-size:0.68rem;"><?= htmlspecialchars($w['event_type']) ?></span>
                                    </td>
                                    <td class="font-mono" style="font-size:0.75rem;color:var(--accent);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        <?= htmlspecialchars($w['target_url']) ?>
                                    </td>
                                    <td>
                                        <span class="pill-tag pill-tag-success">ACTIVO</span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <script>
        function setWebhookTarget(url) {
            var inp = document.getElementById('webhook_url_input');
            if (inp) {
                inp.value = url;
                inp.focus();
            }
        }
        </script>
<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
