<?php
/**
 * Weather Report Solutions S.L. — Panel de Control y Administración
 * Unified Web App Layout Architecture
 * VULN-09: Exposición de variables de entorno y arquitectura de infraestructura
 */
require_once __DIR__ . '/../config.php';
session_start();
$user = require_auth();

if ($user['role'] !== 'admin') {
    http_response_code(403);
    // VULN-09: El mensaje de error expone la ruta del servidor
    die('<div style="font-family:monospace;background:#070a12;color:#f87171;padding:40px;min-height:100vh;">'
      . '<h2 style="font-size:1.5rem;margin-bottom:12px;">403 Forbidden — Acceso Restringido</h2>'
      . '<p style="color:#94a3b8;margin-bottom:16px;">Se requiere credencial con rol de Super Administrador para acceder a este entorno.</p>'
      . '<div style="background:#0e1626;border:1px solid rgba(255,255,255,0.08);padding:16px;border-radius:8px;margin-bottom:20px;font-size:0.85rem;">'
      . '<p>Server Path: ' . $_SERVER['DOCUMENT_ROOT'] . $_SERVER['PHP_SELF'] . '</p>'
      . '<p>User-Agent: ' . htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? '') . '</p>'
      . '<p>Client IP: ' . htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '') . '</p>'
      . '</div>'
      . '<a href="/dashboard.php" style="color:#38bdf8;text-decoration:none;font-weight:600;">← Volver al Dashboard</a></div>');
}

// Cargar datos de administración
$all_users    = $pdo->query('SELECT u.*, c.company_name FROM users u LEFT JOIN companies c ON u.company_id = c.id ORDER BY u.created_at DESC')->fetchAll();
$all_companies= $pdo->query('SELECT * FROM companies ORDER BY id')->fetchAll();
$all_reports  = $pdo->query('SELECT r.*, c.company_name FROM reports r JOIN companies c ON r.company_id = c.id ORDER BY r.created_at DESC')->fetchAll();
$all_tickets  = $pdo->query('SELECT t.*, u.username, c.company_name FROM tickets t LEFT JOIN users u ON t.user_id = u.id LEFT JOIN companies c ON t.company_id = c.id ORDER BY t.created_at DESC')->fetchAll();

// Acciones admin
$action_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_user'])) {
        $uid = intval($_POST['delete_user']);
        if ($uid !== $user['id']) {
            $pdo->prepare('UPDATE users SET is_active = 0 WHERE id = ?')->execute([$uid]);
            $action_msg = 'Usuario desactivado satisfactoriamente.';
        }
    }
}

// Info de sistema (VULN-09: exposición de información sensible)
$php_version  = PHP_VERSION;
$server_soft  = $_SERVER['SERVER_SOFTWARE'] ?? 'N/A';
$doc_root     = $_SERVER['DOCUMENT_ROOT'] ?? 'N/A';
$server_ip    = $_SERVER['SERVER_ADDR']    ?? 'N/A';

$page_title = 'Panel de Administración';
$page_heading = 'Administración del Sistema';

require_once __DIR__ . '/../includes/layout_head.php';
require_once __DIR__ . '/../includes/layout_sidebar.php';
?>

<main class="app-main">
    <?php require_once __DIR__ . '/../includes/layout_topbar.php'; ?>

    <div class="app-content-body">
        <!-- Sub-encabezado informativo -->
        <div style="background:var(--surface-card);border:1px solid var(--border);border-radius:12px;padding:20px 24px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 10px 30px rgba(0,0,0,0.2);flex-wrap:wrap;gap:12px;">
            <div>
                <h2 style="font-size:1.25rem;font-weight:700;margin-bottom:4px;">Centro de Control Maestro</h2>
                <p style="color:var(--muted);font-size:0.85rem;">
                    Administración de entidades multitenancy, cuentas corporativas, informes clasificados y topología de servidores.
                </p>
            </div>
            <div style="display:flex;gap:8px;">
                <span class="pill-tag pill-tag-danger">ROOT PRIVILEGES</span>
                <span class="pill-tag pill-tag-info"><?= count($all_users) ?> USUARIOS REGISTRADOS</span>
            </div>
        </div>

        <?php if ($action_msg): ?>
            <div class="alert-ui alert-ui-success">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                <span><?= htmlspecialchars($action_msg) ?></span>
            </div>
        <?php endif; ?>

        <!-- KPI Grid de Administración -->
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
            <div class="card-ui" style="margin-bottom:0;padding:18px 20px;">
                <div style="font-size:0.75rem;color:var(--muted);text-transform:uppercase;font-weight:600;">Cuentas de Usuario</div>
                <div class="font-mono" style="font-size:1.8rem;font-weight:700;color:var(--accent);margin:4px 0;"><?= count($all_users) ?></div>
                <div style="font-size:0.72rem;color:var(--dim);">Operadores y Administradores</div>
            </div>
            <div class="card-ui" style="margin-bottom:0;padding:18px 20px;">
                <div style="font-size:0.75rem;color:var(--muted);text-transform:uppercase;font-weight:600;">Empresas Tenant</div>
                <div class="font-mono" style="font-size:1.8rem;font-weight:700;color:var(--success);margin:4px 0;"><?= count($all_companies) ?></div>
                <div style="font-size:0.72rem;color:var(--dim);">Organizaciones con API Keys</div>
            </div>
            <div class="card-ui" style="margin-bottom:0;padding:18px 20px;">
                <div style="font-size:0.75rem;color:var(--muted);text-transform:uppercase;font-weight:600;">Dossiers Generados</div>
                <div class="font-mono" style="font-size:1.8rem;font-weight:700;color:#a5b4fc;margin:4px 0;"><?= count($all_reports) ?></div>
                <div style="font-size:0.72rem;color:var(--dim);">Archivos cifrados con SHA-256</div>
            </div>
            <div class="card-ui" style="margin-bottom:0;padding:18px 20px;">
                <div style="font-size:0.75rem;color:var(--muted);text-transform:uppercase;font-weight:600;">Tickets Soporte</div>
                <div class="font-mono" style="font-size:1.8rem;font-weight:700;color:var(--warn);margin:4px 0;"><?= count($all_tickets) ?></div>
                <div style="font-size:0.72rem;color:var(--dim);">Incidencias registradas</div>
            </div>
        </div>

        <!-- TABS DE NAVEGACIÓN -->
        <div style="display:flex;gap:6px;background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:24px;max-width:680px;">
            <button type="button" class="tab-btn active" onclick="switchTab('users', this)" style="flex:1;padding:8px 16px;border-radius:7px;border:none;background:rgba(2,132,199,0.2);color:var(--accent);font-weight:600;font-size:0.84rem;cursor:pointer;transition:all 0.15s;">
                Usuarios (<?= count($all_users) ?>)
            </button>
            <button type="button" class="tab-btn" onclick="switchTab('companies', this)" style="flex:1;padding:8px 16px;border-radius:7px;border:none;background:transparent;color:var(--muted);font-weight:600;font-size:0.84rem;cursor:pointer;transition:all 0.15s;">
                Empresas (<?= count($all_companies) ?>)
            </button>
            <button type="button" class="tab-btn" onclick="switchTab('reports', this)" style="flex:1;padding:8px 16px;border-radius:7px;border:none;background:transparent;color:var(--muted);font-weight:600;font-size:0.84rem;cursor:pointer;transition:all 0.15s;">
                Dossiers (<?= count($all_reports) ?>)
            </button>
            <button type="button" class="tab-btn" onclick="switchTab('system', this)" style="flex:1;padding:8px 16px;border-radius:7px;border:none;background:transparent;color:var(--muted);font-weight:600;font-size:0.84rem;cursor:pointer;transition:all 0.15s;">
                Infraestructura
            </button>
        </div>

        <!-- TAB: USUARIOS -->
        <div id="tab-users" class="tab-content" style="display:block;">
            <div class="card-ui">
                <div class="card-ui-header">
                    <div class="card-ui-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Directorio de Cuentas de Usuario
                    </div>
                    <input type="text" placeholder="Filtrar usuario..." onkeyup="filterTable('users-table', this.value)" style="background:var(--surface);border:1px solid var(--border);color:var(--text);padding:4px 10px;border-radius:6px;font-size:0.75rem;outline:none;">
                </div>
                <div style="overflow-x:auto;">
                    <table class="table-ui" id="users-table">
                        <thead>
                            <tr>
                                <th style="width:70px;">ID</th>
                                <th>Usuario / Nombre</th>
                                <th>Email</th>
                                <th>Organización</th>
                                <th>Rol</th>
                                <th>Último Acceso</th>
                                <th style="text-align:right;">Operación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_users as $u): ?>
                            <tr>
                                <td class="font-mono" style="color:var(--muted);">#<?= sprintf('%03d', $u['id']) ?></td>
                                <td>
                                    <div style="font-weight:600;color:var(--text);"><?= htmlspecialchars($u['username']) ?></div>
                                    <div style="font-size:0.75rem;color:var(--dim);"><?= htmlspecialchars($u['full_name'] ?? '') ?></div>
                                </td>
                                <td class="font-mono" style="font-size:0.8rem;"><?= htmlspecialchars($u['email']) ?></td>
                                <td><?= htmlspecialchars($u['company_name'] ?? '—') ?></td>
                                <td>
                                    <span class="pill-tag <?= $u['role'] === 'admin' ? 'pill-tag-danger' : 'pill-tag-info' ?>" style="font-size:0.68rem;">
                                        <?= strtoupper($u['role']) ?>
                                    </span>
                                </td>
                                <td class="font-mono" style="font-size:0.78rem;color:var(--muted);">
                                    <?= $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : '—' ?>
                                </td>
                                <td style="text-align:right;">
                                    <?php if ($u['id'] !== $user['id']): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="delete_user" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn-ui" style="padding:4px 8px;font-size:0.74rem;background:rgba(239,68,68,0.1);color:#fca5a5;border:1px solid rgba(239,68,68,0.2);" onclick="return confirm('¿Desactivar este usuario?')">
                                            Desactivar
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <span style="color:var(--dim);font-size:0.75rem;">(Sesión actual)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: EMPRESAS -->
        <div id="tab-companies" class="tab-content" style="display:none;">
            <div class="card-ui">
                <div class="card-ui-header">
                    <div class="card-ui-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M9 8h1"/><path d="M9 12h1"/><path d="M9 16h1"/><path d="M14 8h1"/><path d="M14 12h1"/><path d="M14 16h1"/><path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"/></svg>
                        Empresas y Entidades Conectadas
                    </div>
                    <input type="text" placeholder="Filtrar empresa..." onkeyup="filterTable('companies-table', this.value)" style="background:var(--surface);border:1px solid var(--border);color:var(--text);padding:4px 10px;border-radius:6px;font-size:0.75rem;outline:none;">
                </div>
                <div style="overflow-x:auto;">
                    <table class="table-ui" id="companies-table">
                        <thead>
                            <tr>
                                <th style="width:70px;">ID</th>
                                <th>Razón Social</th>
                                <th>Plan Contratado</th>
                                <th>API Key Corporativa</th>
                                <th>Contacto Técnico</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_companies as $c): ?>
                            <tr>
                                <td class="font-mono" style="color:var(--muted);">#<?= sprintf('%03d', $c['id']) ?></td>
                                <td style="font-weight:600;color:var(--text);"><?= htmlspecialchars($c['company_name']) ?></td>
                                <td>
                                    <span class="pill-tag pill-tag-success" style="font-size:0.7rem;"><?= htmlspecialchars($c['plan_type']) ?></span>
                                </td>
                                <td class="font-mono" style="font-size:0.76rem;color:var(--accent);"><?= htmlspecialchars($c['api_key']) ?></td>
                                <td style="font-size:0.82rem;color:var(--muted);"><?= htmlspecialchars($c['contact_email'] ?? '—') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: INFORMES -->
        <div id="tab-reports" class="tab-content" style="display:none;">
            <div class="card-ui">
                <div class="card-ui-header">
                    <div class="card-ui-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        Catálogo Global de Dossiers Clasificados
                    </div>
                    <input type="text" placeholder="Filtrar informe..." onkeyup="filterTable('reports-admin-table', this.value)" style="background:var(--surface);border:1px solid var(--border);color:var(--text);padding:4px 10px;border-radius:6px;font-size:0.75rem;outline:none;">
                </div>
                <div style="overflow-x:auto;">
                    <table class="table-ui" id="reports-admin-table">
                        <thead>
                            <tr>
                                <th style="width:70px;">ID</th>
                                <th>Título del Dossier</th>
                                <th>Organización Asignada</th>
                                <th>Clasificación</th>
                                <th>Emisión</th>
                                <th style="text-align:right;">Acceso</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_reports as $r): ?>
                            <tr>
                                <td class="font-mono" style="color:var(--muted);">#<?= sprintf('%04d', $r['id']) ?></td>
                                <td style="font-weight:600;color:var(--text);"><?= htmlspecialchars(substr($r['title'], 0, 50)) ?>...</td>
                                <td><?= htmlspecialchars($r['company_name']) ?></td>
                                <td>
                                    <?php if ($r['classification'] === 'STRICTLY CONFIDENTIAL'): ?>
                                        <span class="pill-tag pill-tag-danger">STRICTLY CONFIDENTIAL</span>
                                    <?php elseif ($r['classification'] === 'CONFIDENTIAL'): ?>
                                        <span class="pill-tag pill-tag-warn">CONFIDENTIAL</span>
                                    <?php else: ?>
                                        <span class="pill-tag pill-tag-info">INTERNAL USE</span>
                                    <?php endif; ?>
                                </td>
                                <td class="font-mono" style="font-size:0.78rem;color:var(--muted);"><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
                                <td style="text-align:right;">
                                    <a href="/api/v1/download_report.php?report_id=<?= $r['id'] ?>" class="btn-ui btn-ui-ghost" style="padding:4px 8px;font-size:0.74rem;">
                                        Abrir Visor →
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: SISTEMA & ARQUITECTURA -->
        <div id="tab-system" class="tab-content" style="display:none;">
            <div class="card-ui">
                <div class="card-ui-header">
                    <div class="card-ui-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="8" x="2" y="2" rx="2" ry="2"/><rect width="20" height="8" x="2" y="14" rx="2" ry="2"/><line x1="6" x2="6.01" y1="6" y2="6"/><line x1="6" x2="6.01" y1="18" y2="18"/></svg>
                        Telemetría y Parámetros del Entorno de Ejecución
                    </div>
                    <span class="pill-tag pill-tag-warn">TOPOLOGÍA INTERNA EXPUESTA</span>
                </div>
                <div style="padding:24px;display:grid;grid-template-columns:repeat(2,1fr);gap:16px;">
                    <div style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:14px 18px;">
                        <div style="font-size:0.72rem;color:var(--dim);text-transform:uppercase;font-weight:600;letter-spacing:0.5px;">Motor PHP Runtime</div>
                        <div class="font-mono" style="font-size:0.95rem;color:var(--accent);margin-top:4px;"><?= $php_version ?></div>
                    </div>
                    <div style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:14px 18px;">
                        <div style="font-size:0.72rem;color:var(--dim);text-transform:uppercase;font-weight:600;letter-spacing:0.5px;">Servidor Web HTTP</div>
                        <div class="font-mono" style="font-size:0.95rem;color:var(--accent);margin-top:4px;"><?= htmlspecialchars($server_soft) ?></div>
                    </div>
                    <div style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:14px 18px;">
                        <div style="font-size:0.72rem;color:var(--dim);text-transform:uppercase;font-weight:600;letter-spacing:0.5px;">Document Root en Host</div>
                        <div class="font-mono" style="font-size:0.95rem;color:var(--accent);margin-top:4px;"><?= htmlspecialchars($doc_root) ?></div>
                    </div>
                    <div style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:14px 18px;">
                        <div style="font-size:0.72rem;color:var(--dim);text-transform:uppercase;font-weight:600;letter-spacing:0.5px;">Dirección IP del Servidor</div>
                        <div class="font-mono" style="font-size:0.95rem;color:var(--accent);margin-top:4px;"><?= htmlspecialchars($server_ip) ?></div>
                    </div>
                    <div style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:14px 18px;">
                        <div style="font-size:0.72rem;color:var(--dim);text-transform:uppercase;font-weight:600;letter-spacing:0.5px;">Cluster RDS / PostgreSQL</div>
                        <div class="font-mono" style="font-size:0.95rem;color:var(--accent);margin-top:4px;"><?= DB_HOST ?>:<?= DB_PORT ?> (<?= DB_NAME ?>)</div>
                    </div>
                    <div style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:14px 18px;">
                        <div style="font-size:0.72rem;color:var(--dim);text-transform:uppercase;font-weight:600;letter-spacing:0.5px;">Región AWS Cloud</div>
                        <div class="font-mono" style="font-size:0.95rem;color:var(--accent);margin-top:4px;"><?= AWS_REGION ?></div>
                    </div>
                    <div style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:14px 18px;grid-column:span 2;">
                        <div style="font-size:0.72rem;color:var(--dim);text-transform:uppercase;font-weight:600;letter-spacing:0.5px;">AWS IAM Access Key</div>
                        <div class="font-mono" style="font-size:0.95rem;color:#fca5a5;margin-top:4px;"><?= AWS_ACCESS_KEY ?></div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        function switchTab(name, btn) {
            document.querySelectorAll('.tab-btn').forEach(function(b) {
                b.style.background = 'transparent';
                b.style.color = 'var(--muted)';
            });
            document.querySelectorAll('.tab-content').forEach(function(c) {
                c.style.display = 'none';
            });
            btn.style.background = 'rgba(2,132,199,0.2)';
            btn.style.color = 'var(--accent)';
            var target = document.getElementById('tab-' + name);
            if (target) {
                target.style.display = 'block';
            }
        }

        function filterTable(tableId, query) {
            var q = query.toLowerCase();
            document.querySelectorAll('#' + tableId + ' tbody tr').forEach(function(row) {
                var text = row.innerText.toLowerCase();
                row.style.display = text.indexOf(q) > -1 ? '' : 'none';
            });
        }
        </script>
<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
