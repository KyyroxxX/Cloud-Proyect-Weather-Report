<?php
/**
 * Weather Report Solutions S.L. — Centro de Incidencias Técnicas & Reseñas de Clientes
 * Unified Web App Layout Architecture
 * VULN-06 (WR-RT-04): Stored XSS en el muro público de reseñas corporativas
 */
require_once __DIR__ . '/config.php';
session_start();
$user = require_auth();

$msg = '';
$msg_type = '';
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'incidencias';

// Procesamiento de envíos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. REGISTRAR INCIDENCIA TÉCNICA (Tickets de Soporte)
    if ($action === 'ticket' || (!empty($_POST['subject']) && !empty($_POST['message']))) {
        $subject  = trim($_POST['subject'] ?? 'Incidencia técnica');
        $priority = $_POST['priority'] ?? 'NORMAL';
        $category = $_POST['category'] ?? 'Plataforma';
        $message  = trim($_POST['message'] ?? '');

        $full_message = "[Categoría: {$category}]\n" . $message;

        $stmt = $pdo->prepare('INSERT INTO tickets (user_id, company_id, subject, message, priority) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$user['id'], $user['company_id'], $subject, $full_message, $priority]);

        $msg = 'Incidencia técnica #' . $pdo->lastInsertId() . ' registrada correctamente en el centro de operaciones SLA.';
        $msg_type = 'success';
        $active_tab = 'incidencias';
    }
    // 2. PUBLICAR RESEÑA / VALORACIÓN DE CLIENTE (Feedback de Satisfacción)
    elseif ($action === 'review' || !empty($_POST['comment'])) {
        $username = trim($_POST['username'] ?? ($user['full_name'] ?? $user['username']));
        $rating   = intval($_POST['rating'] ?? 5);
        $comment  = $_POST['comment'];  // WR-RT-04 Stored XSS: persistencia sin sanitizar

        $stmt = $pdo->prepare('INSERT INTO feedback (username, email, comment, rating) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            $username,
            $user['email'],
            $comment,
            $rating
        ]);

        $msg = 'Su reseña corporativa ha sido publicada en el muro de satisfacción de clientes.';
        $msg_type = 'success';
        $active_tab = 'resenas';
    }
}

// Cargar reseñas públicas (WR-RT-04 Stored XSS: salida directa sin htmlspecialchars())
$comments = $pdo->query('SELECT username, comment, rating, created_at FROM feedback ORDER BY id DESC LIMIT 15')->fetchAll();

// Cargar tickets de soporte de la empresa
$stmt = $pdo->prepare('SELECT * FROM tickets WHERE company_id = ? ORDER BY created_at DESC LIMIT 15');
$stmt->execute([$user['company_id']]);
$my_tickets = $stmt->fetchAll();

$page_title = 'Centro de Soporte & Incidencias';
$page_heading = 'Soporte & Valoraciones';

require_once __DIR__ . '/includes/layout_head.php';
require_once __DIR__ . '/includes/layout_sidebar.php';
?>

<main class="app-main">
    <?php require_once __DIR__ . '/includes/layout_topbar.php'; ?>

    <div class="app-content-body">
        <!-- Sub-encabezado informativo -->
        <div style="background:var(--surface-card);border:1px solid var(--border);border-radius:12px;padding:20px 24px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 10px 30px rgba(0,0,0,0.2);">
            <div>
                <h2 style="font-size:1.25rem;font-weight:700;margin-bottom:4px;">Centro de Operaciones: Incidencias Técnicas & Reseñas</h2>
                <p style="color:var(--muted);font-size:0.85rem;">
                    Gestione tickets de mantenimiento de telemetría y consulte las valoraciones de calidad de servicio certificadas.
                </p>
            </div>
            <div style="display:flex;gap:8px;">
                <span class="pill-tag" style="background:rgba(2,132,199,0.1);color:var(--accent);border:1px solid rgba(56,189,248,0.25);">
                    SLA ACTIVO: &lt; 4H
                </span>
                <span class="pill-tag pill-tag-info">
                    <?= count($my_tickets) ?> TICKETS ACTIVOS
                </span>
            </div>
        </div>

        <?php if ($msg): ?>
            <div class="alert-ui <?= $msg_type === 'success' ? 'alert-ui-success' : 'alert-ui-danger' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                <span><?= htmlspecialchars($msg) ?></span>
            </div>
        <?php endif; ?>

        <!-- SELECTOR DE PESTAÑAS: INCIDENCIAS VS RESEÑAS -->
        <div style="display:flex;gap:8px;background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:24px;max-width:540px;">
            <button type="button" id="tab-btn-incidencias" class="btn-tab <?= $active_tab === 'incidencias' ? 'active' : '' ?>" onclick="switchTab('incidencias')">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
                Incidencias Técnicas (<?= count($my_tickets) ?>)
            </button>
            <button type="button" id="tab-btn-resenas" class="btn-tab <?= $active_tab === 'resenas' ? 'active' : '' ?>" onclick="switchTab('resenas')">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                Reseñas & Valoraciones (<?= count($comments) ?>)
            </button>
        </div>

        <style>
        .btn-tab {
            flex: 1;
            padding: 9px 16px;
            border-radius: 7px;
            border: none;
            background: transparent;
            color: var(--muted);
            font-size: 0.86rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.15s ease;
        }
        .btn-tab:hover {
            color: var(--text);
            background: rgba(255,255,255,0.04);
        }
        .btn-tab.active {
            background: rgba(2, 132, 199, 0.2);
            color: var(--accent);
            border: 1px solid rgba(56, 189, 248, 0.25);
        }
        </style>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- PESTAÑA 1: INCIDENCIAS TÉCNICAS (SOPORTE)              -->
        <!-- ══════════════════════════════════════════════════════ -->
        <div id="section-incidencias" style="display:<?= $active_tab === 'incidencias' ? 'block' : 'none' ?>;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px;">
                <!-- Formulario de Apertura de Incidencia -->
                <div class="card-ui">
                    <div class="card-ui-header">
                        <div class="card-ui-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                            Reportar Nueva Incidencia de Servicio
                        </div>
                        <span style="font-size:0.75rem;color:var(--muted);">Empresa: <?= htmlspecialchars($user['company_name'] ?? 'Weather Report') ?></span>
                    </div>
                    <div style="padding:24px;">
                        <form method="POST" action="/feedback.php">
                            <input type="hidden" name="action" value="ticket">
                            <div class="form-group-ui">
                                <label class="form-label-ui">Asunto de la Incidencia</label>
                                <input type="text" name="subject" class="form-control-ui" placeholder="Ej. Caída de telemetría en estación ST-MAD-01 o timeout de API" required>
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                                <div class="form-group-ui">
                                    <label class="form-label-ui">Nivel de Severidad</label>
                                    <select name="priority" class="form-control-ui">
                                        <option value="BAJA">🟢 BAJA — Consulta técnica o ajuste menor</option>
                                        <option value="NORMAL" selected>🟡 NORMAL — Degradación sin corte total</option>
                                        <option value="ALTA">🔴 ALTA — Disrupción en producción activa</option>
                                    </select>
                                </div>
                                <div class="form-group-ui">
                                    <label class="form-label-ui">Subsistema Afectado</label>
                                    <select name="category" class="form-control-ui">
                                        <option value="Nodos Sensores">Nodos Sensores / Radar</option>
                                        <option value="API Ingestion">API de Telemetría (Ingestión)</option>
                                        <option value="Webhooks">Entrega de Webhooks</option>
                                        <option value="Reportes PDF">Generador de Dossiers PDF</option>
                                        <option value="Infraestructura AWS">Conectividad / Enlace Cloud</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group-ui">
                                <label class="form-label-ui">Trazas de Error y Descripción del Problema</label>
                                <textarea name="message" class="form-control-ui" rows="5" placeholder="Detalle el timestamp del fallo, identificador del sensor, códigos HTTP o logs relevantes observados..." required></textarea>
                            </div>
                            <button type="submit" class="btn-ui btn-ui-primary" style="width:100%;justify-content:center;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4 20-7z"/></svg>
                                Abrir Incidencia Técnica en Cola de Guardia
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Protocolo de escalado y SLA -->
                <div class="card-ui">
                    <div class="card-ui-header">
                        <div class="card-ui-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            Compromisos de Nivel de Servicio (SLA)
                        </div>
                        <span class="pill-tag pill-tag-success">ISO 27001</span>
                    </div>
                    <div style="padding:24px;display:flex;flex-direction:column;gap:18px;">
                        <div style="background:var(--surface);border:1px solid var(--border);border-left:3px solid var(--danger);border-radius:8px;padding:14px 18px;">
                            <div style="font-weight:700;font-size:0.88rem;color:#fca5a5;margin-bottom:4px;">Prioridad ALTA / CRÍTICA</div>
                            <div style="font-size:0.82rem;color:var(--muted);line-height:1.5;">
                                Tiempo de primera respuesta garantizado: <strong>&lt; 60 minutos</strong>. Asignación inmediata a un ingeniero de fiabilidad (SRE).
                            </div>
                        </div>
                        <div style="background:var(--surface);border:1px solid var(--border);border-left:3px solid var(--warn);border-radius:8px;padding:14px 18px;">
                            <div style="font-weight:700;font-size:0.88rem;color:#fde047;margin-bottom:4px;">Prioridad NORMAL</div>
                            <div style="font-size:0.82rem;color:var(--muted);line-height:1.5;">
                                Tiempo de respuesta: <strong>&lt; 4 horas laborales</strong>. Seguimiento diario mediante panel de tickets.
                            </div>
                        </div>
                        <div style="background:var(--surface);border:1px solid var(--border);border-left:3px solid var(--accent);border-radius:8px;padding:14px 18px;">
                            <div style="font-weight:700;font-size:0.88rem;color:var(--accent);margin-bottom:4px;">Prioridad BAJA / Consultas</div>
                            <div style="font-size:0.82rem;color:var(--muted);line-height:1.5;">
                                Asesoría técnica en integración de API y cuotas: respuesta en <strong>24 horas hábiles</strong>.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Historial de Tickets -->
            <div class="card-ui">
                <div class="card-ui-header">
                    <div class="card-ui-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
                        Historial de Incidencias Técnicas — <?= htmlspecialchars($user['company_name'] ?? 'Weather Report') ?>
                    </div>
                    <span class="pill-tag pill-tag-info"><?= count($my_tickets) ?> incidencias registradas</span>
                </div>
                <div style="overflow-x:auto;">
                    <table class="table-ui">
                        <thead>
                            <tr>
                                <th style="width:70px;">ID</th>
                                <th>Asunto de la Incidencia</th>
                                <th style="width:120px;">Severidad</th>
                                <th style="width:130px;">Estado</th>
                                <th style="width:140px;">Fecha Registro</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($my_tickets)): ?>
                                <tr>
                                    <td colspan="5" style="text-align:center;padding:32px;color:var(--muted);">
                                        No hay incidencias técnicas abiertas para su organización.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($my_tickets as $t): ?>
                                <tr>
                                    <td class="font-mono" style="color:var(--muted);">#<?= sprintf('%04d', $t['id']) ?></td>
                                    <td>
                                        <div style="font-weight:600;color:var(--text);"><?= htmlspecialchars($t['subject']) ?></div>
                                        <div style="font-size:0.75rem;color:var(--dim);margin-top:2px;max-width:480px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                            <?= htmlspecialchars(strip_tags($t['message'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($t['priority'] === 'ALTA'): ?>
                                            <span class="pill-tag pill-tag-danger">ALTA</span>
                                        <?php elseif ($t['priority'] === 'BAJA'): ?>
                                            <span class="pill-tag pill-tag-info">BAJA</span>
                                        <?php else: ?>
                                            <span class="pill-tag pill-tag-warn">NORMAL</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($t['status'] === 'ABIERTO'): ?>
                                            <span class="pill-tag pill-tag-warn">● EN ESPERA</span>
                                        <?php elseif ($t['status'] === 'EN PROCESO'): ?>
                                            <span class="pill-tag pill-tag-info">⚙ EN CURSO</span>
                                        <?php else: ?>
                                            <span class="pill-tag pill-tag-success">✓ RESUELTO</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="font-mono" style="color:var(--muted);font-size:0.78rem;">
                                        <?= date('d/m/Y H:i', strtotime($t['created_at'])) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- PESTAÑA 2: RESEÑAS Y VALORACIONES DE CLIENTES          -->
        <!-- ══════════════════════════════════════════════════════ -->
        <div id="section-resenas" style="display:<?= $active_tab === 'resenas' ? 'block' : 'none' ?>;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px;">
                <!-- Formulario de Reseña / Testimonio -->
                <div class="card-ui">
                    <div class="card-ui-header">
                        <div class="card-ui-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            Publicar Reseña y Valoración del Servicio
                        </div>
                        <span style="font-size:0.75rem;color:var(--muted);">Opinión corporativa</span>
                    </div>
                    <div style="padding:24px;">
                        <form method="POST" action="/feedback.php">
                            <input type="hidden" name="action" value="review">
                            <div class="form-group-ui">
                                <label class="form-label-ui">Nombre o Cargo del Evaluador</label>
                                <input type="text" name="username" class="form-control-ui" value="<?= htmlspecialchars($user['full_name'] ?? $user['username']) ?>" required>
                            </div>
                            <div class="form-group-ui">
                                <label class="form-label-ui">Puntuación de Calidad del Servicio</label>
                                <select name="rating" class="form-control-ui">
                                    <option value="5" selected>★★★★★ Excelente (5/5) — Máxima fiabilidad telemétrica</option>
                                    <option value="4">★★★★☆ Bueno (4/5) — Cumple expectativas de SLA</option>
                                    <option value="3">★★★☆☆ Regular (3/5) — Precisión aceptable con margen de mejora</option>
                                    <option value="2">★★☆☆☆ Mejorable (2/5) — Incidencias reiteradas en sincronización</option>
                                    <option value="1">★☆☆☆☆ Deficiente (1/5) — Incumplimiento de niveles acordados</option>
                                </select>
                            </div>
                            <div class="form-group-ui">
                                <label class="form-label-ui">Comentario de Evaluación / Experiencia con la Plataforma</label>
                                <textarea name="comment" class="form-control-ui" rows="5" placeholder="Comparta su experiencia con los boletines meteorológicos, precisión de radar y atención técnica..." required></textarea>
                            </div>
                            <button type="submit" class="btn-ui btn-ui-primary" style="width:100%;justify-content:center;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                Publicar Reseña en el Directorio Corporativo
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Feed de Reseñas Públicas de Clientes -->
                <div class="card-ui">
                    <div class="card-ui-header">
                        <div class="card-ui-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            Muro Público de Satisfacción y Reseñas de Clientes
                        </div>
                        <span style="font-size:0.72rem;color:var(--muted);">Últimas <?= count($comments) ?> valoraciones</span>
                    </div>
                    <div style="padding:20px;max-height:480px;overflow-y:auto;display:flex;flex-direction:column;gap:14px;">
                        <?php if (empty($comments)): ?>
                            <div style="text-align:center;padding:40px;color:var(--muted);font-size:0.86rem;">
                                No existen reseñas publicadas aún.
                            </div>
                        <?php else: ?>
                            <?php foreach ($comments as $c): ?>
                            <div style="background:var(--surface);border:1px solid var(--border);border-left:3px solid var(--accent);border-radius:8px;padding:14px 16px;">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <span style="font-weight:600;font-size:0.85rem;color:var(--text);"><?= htmlspecialchars($c['username']) ?></span>
                                        <span style="font-size:0.75rem;color:var(--warn);letter-spacing:1px;">
                                            <?= str_repeat('★', (int)$c['rating']) . str_repeat('☆', 5 - (int)$c['rating']) ?>
                                        </span>
                                    </div>
                                    <span style="font-size:0.72rem;color:var(--dim);font-family:var(--font-mono);">
                                        <?= date('d/m/Y H:i', strtotime($c['created_at'])) ?>
                                    </span>
                                </div>
                                <div style="font-size:0.85rem;color:var(--muted);line-height:1.5;">
                                    <?= $c['comment'] /* WR-RT-04 Stored XSS: salida intencionada sin htmlspecialchars() */ ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <script>
        function switchTab(name) {
            document.querySelectorAll('.btn-tab').forEach(function(b) {
                b.classList.remove('active');
            });
            document.getElementById('section-incidencias').style.display = 'none';
            document.getElementById('section-resenas').style.display = 'none';

            document.getElementById('tab-btn-' + name).classList.add('active');
            document.getElementById('section-' + name).style.display = 'block';

            if (history.replaceState) {
                history.replaceState(null, '', '/feedback.php?tab=' + name);
            }
        }
        </script>

<?php require_once __DIR__ . '/includes/layout_footer.php'; ?>
