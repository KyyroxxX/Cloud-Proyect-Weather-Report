<?php
/**
 * Weather Report Solutions S.L. — Visor de Informes Climáticos Confidenciales
 * Unified Web App Layout Architecture
 * VULN-01: IDOR — recupera informe por ID sin verificar pertenencia de empresa
 */
require_once __DIR__ . '/../../config.php';
session_start();
$user = require_auth();

$report_id = isset($_GET['report_id']) ? intval($_GET['report_id']) : 1;

try {
    // VULN-01 (IDOR): No valida company_id del usuario
    $stmt = $pdo->prepare(
        'SELECT r.id, r.title, r.summary, r.content, r.classification, r.created_at, r.file_hash,
                c.company_name, c.plan_type
         FROM reports r
         JOIN companies c ON r.company_id = c.id
         WHERE r.id = ?'
    );
    $stmt->execute([$report_id]);
    $report = $stmt->fetch();

    if (!$report) {
        $report_id = 1;
        $stmt->execute([1]);
        $report = $stmt->fetch();
    }
} catch (PDOException $e) {
    http_response_code(500);
    die('Error al recuperar informe: ' . $e->getMessage());
}

// Cargar lista de informes para navegación
$reports_nav = [];
try {
    $reports_nav = $pdo->query('SELECT id, title, classification FROM reports ORDER BY id ASC')->fetchAll();
} catch (Exception $e) {}

$page_title = 'Dossier #' . sprintf('%04d', $report['id']) . ' — ' . $report['title'];
$page_heading = 'Visor de Informes Clasificados';

require __DIR__ . '/../../includes/layout_head.php';
require __DIR__ . '/../../includes/layout_sidebar.php';
?>

<style>
/* ── ESTILOS ESPECÍFICOS DEL DOCUMENTO CONFIDENCIAL ── */
.doc-sheet {
    background: var(--surface-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
    max-width: 960px;
    margin: 0 auto 30px;
}
.doc-header-strip {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding-bottom: 24px;
    border-bottom: 1px solid var(--border);
    margin-bottom: 28px;
}
.meta-grid-report {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    background: rgba(8, 12, 20, 0.6);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 18px;
    margin-bottom: 24px;
}
.meta-item-report label {
    display: block;
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    color: var(--dim);
    margin-bottom: 4px;
    letter-spacing: 0.05em;
}
.meta-item-report div {
    font-size: 0.86rem;
    font-weight: 600;
    color: var(--text);
}
.summary-box-report {
    background: rgba(2, 132, 199, 0.05);
    border-left: 3px solid var(--primary);
    border-radius: 0 8px 8px 0;
    padding: 16px 20px;
    font-size: 0.88rem;
    color: var(--text);
    margin-bottom: 28px;
    line-height: 1.6;
}
.content-body-report {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.84rem;
    line-height: 1.7;
    color: var(--muted);
    white-space: pre-wrap;
    word-break: break-word;
    background: rgba(8, 12, 20, 0.4);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 24px;
    margin-bottom: 28px;
}
.seal-box-report {
    border-top: 1px solid var(--border);
    padding-top: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 0.76rem;
    color: var(--dim);
}

/* ── ESTILOS DE IMPRESIÓN A4 LIMPIA (PDF) ── */
@media print {
    @page { size: A4 portrait; margin: 15mm; }
    .app-sidebar, .app-topbar, .report-actions-bar { display: none !important; }
    .app-main { margin: 0 !important; }
    .app-content-body { padding: 0 !important; }
    body { background: #fff !important; color: #0f172a !important; display: block !important; }
    .doc-sheet { background: #fff !important; border: none !important; box-shadow: none !important; padding: 0 !important; color: #0f172a !important; }
    .content-body-report { background: #f8fafc !important; color: #0f172a !important; border-color: #cbd5e1 !important; }
    .meta-grid-report { background: #f8fafc !important; border-color: #cbd5e1 !important; }
    .meta-item-report div { color: #0f172a !important; }
    .summary-box-report { background: #f0f9ff !important; color: #0f172a !important; border-color: #0284c7 !important; }
}
</style>

<main class="app-main">
    <?php require __DIR__ . '/../../includes/layout_topbar.php'; ?>

    <div class="app-content-body">
        <!-- Barra de selección y herramientas de informe -->
        <div class="report-actions-bar" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="font-size:0.8rem;color:var(--dim);font-weight:600;text-transform:uppercase;">Dossier:</span>
                <?php foreach ($reports_nav as $nav): ?>
                <a href="/api/v1/download_report.php?report_id=<?= $nav['id'] ?>" class="btn-ui <?= $nav['id'] == $report['id'] ? 'btn-ui-primary' : 'btn-ui-ghost' ?>" style="padding:6px 12px;font-size:0.78rem;">
                    #<?= sprintf('%04d', $nav['id']) ?>
                </a>
                <?php endforeach; ?>
            </div>
            <div style="display:flex;gap:10px;">
                <button onclick="window.print()" class="btn-ui btn-ui-ghost">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                    Imprimir Informe (A4 / PDF)
                </button>
                <a href="/dashboard.php" class="btn-ui btn-ui-ghost">← Volver al Dashboard</a>
            </div>
        </div>

        <!-- Hoja de informe confidencial -->
        <div class="doc-sheet">
            <div class="doc-header-strip">
                <div>
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                        <img src="/favicon.svg" alt="Weather Report Logo" style="width:28px;height:28px;border-radius:6px;display:block;">
                        <span style="font-family:'Outfit',sans-serif;font-size:1.1rem;font-weight:700;">Weather Report Solutions S.L.</span>
                    </div>
                    <div style="font-size:0.75rem;color:var(--dim);">División de Inteligencia y Modelización de Riesgo Atmosférico</div>
                </div>
                <div style="text-align:right;">
                    <?php
                    $cls = strtolower($report['classification']);
                    if (str_contains($cls, 'estric')) $badge = 'pill-danger';
                    elseif (str_contains($cls, 'restri')) $badge = 'pill-warn';
                    else $badge = 'pill-info';
                    ?>
                    <span class="pill-tag <?= $badge ?>" style="font-size:0.76rem;padding:4px 10px;"><?= htmlspecialchars($report['classification']) ?></span>
                    <div class="font-mono" style="font-size:0.72rem;color:var(--dim);margin-top:4px;">ID: #WR-DOC-<?= sprintf('%04d', $report['id']) ?>-Q4</div>
                </div>
            </div>

            <h2 style="font-size:1.6rem;font-weight:700;margin-bottom:20px;"><?= htmlspecialchars($report['title']) ?></h2>

            <div class="meta-grid-report">
                <div class="meta-item-report">
                    <label>Entidad Destinataria</label>
                    <div><?= htmlspecialchars($report['company_name']) ?></div>
                </div>
                <div class="meta-item-report">
                    <label>Nivel de Suscripción</label>
                    <div style="color:var(--accent);"><?= htmlspecialchars($report['plan_type']) ?></div>
                </div>
                <div class="meta-item-report">
                    <label>Fecha y Hora de Emisión</label>
                    <div class="font-mono"><?= date('d/m/Y H:i', strtotime($report['created_at'])) ?> UTC</div>
                </div>
            </div>

            <div class="summary-box-report">
                <strong>Resumen Ejecutivo:</strong> <?= htmlspecialchars($report['summary']) ?>
            </div>

            <div class="content-body-report"><?= htmlspecialchars($report['content']) ?></div>

            <div class="seal-box-report">
                <div>
                    <div><strong>Sello de Integridad Criptográfica:</strong></div>
                    <div class="font-mono" style="color:var(--accent);font-size:0.72rem;"><?= htmlspecialchars($report['file_hash'] ?? hash('sha256', $report['content'])) ?></div>
                </div>
                <div style="text-align:right;">
                    <div>Protocolo de Seguridad: ISO/IEC 27001 · TLP 2.0</div>
                    <div>Weather Report Operations Grid</div>
                </div>
            </div>
        </div>

<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
