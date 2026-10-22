<?php
/**
 * Weather Report Solutions S.L. — Perfil Corporativo & Emblema
 * Unified Web App Layout Architecture
 * VULN-04 (WR-RT-09): File Upload sin validación de extensión (WebShell Upload)
 */
require_once __DIR__ . '/../config.php';
session_start();
$user = require_auth();

$upload_dir = __DIR__ . '/../uploads/';
if (!file_exists($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

$message     = '';
$status_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fileToUpload'])) {
    $file     = $_FILES['fileToUpload'];
    $filename = basename($file['name']);
    $target   = $upload_dir . $filename;

    if ($file['size'] > 5_000_000) {
        $message     = 'El archivo supera el límite de 5 MB permitido por la cuota telemétrica.';
        $status_type = 'error';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $message     = 'Error de procesamiento en socket de carga. Código de error: ' . $file['error'];
        $status_type = 'error';
    } else {
        // WR-RT-09: Upload directo sin lista blanca de extensiones ni verificación de payload
        if (move_uploaded_file($file['tmp_name'], $target)) {
            $avatar_path = '/uploads/' . $filename;
            $pdo->prepare('UPDATE users SET avatar_url = ? WHERE id = ?')
                ->execute([$avatar_path, $user['id']]);

            $user['avatar_url'] = $avatar_path;
            $_SESSION['avatar_url'] = $avatar_path;

            $message     = 'Emblema corporativo y credencial visual actualizada satisfactoriamente.';
            $status_type = 'success';
        } else {
            $message     = 'Fallo de persistencia en disco. Verifique los permisos del directorio /uploads/.';
            $status_type = 'error';
        }
    }
}

// Comprobar avatar en disco
$has_avatar = false;
$avatar_src = '';
if (!empty($user['avatar_url'])) {
    $local_path = __DIR__ . '/..' . $user['avatar_url'];
    if (file_exists($local_path)) {
        $has_avatar = true;
        $avatar_src = $user['avatar_url'] . '?t=' . time();
    }
}

// Cargar API Key de la empresa
$comp_stmt = $pdo->prepare('SELECT * FROM companies WHERE id = ?');
$comp_stmt->execute([$user['company_id']]);
$company = $comp_stmt->fetch() ?: ['api_key' => 'wr_live_' . substr(md5($user['id']), 0, 24)];

$page_title = 'Perfil & Emblema Corporativo';
$page_heading = 'Ajustes de Cuenta';

require_once __DIR__ . '/../includes/layout_head.php';
require_once __DIR__ . '/../includes/layout_sidebar.php';
?>

<main class="app-main">
    <?php require_once __DIR__ . '/../includes/layout_topbar.php'; ?>

    <div class="app-content-body">
        <!-- Sub-encabezado informativo -->
        <div style="background:var(--surface-card);border:1px solid var(--border);border-radius:12px;padding:20px 24px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 10px 30px rgba(0,0,0,0.2);flex-wrap:wrap;gap:12px;">
            <div>
                <h2 style="font-size:1.25rem;font-weight:700;margin-bottom:4px;">Perfil e Identidad de Empresa</h2>
                <p style="color:var(--muted);font-size:0.85rem;">
                    Configure el emblema visual de su empresa y gestione sus credenciales telemétricas de producción.
                </p>
            </div>
            <div style="display:flex;gap:8px;">
                <span class="pill-tag pill-tag-success">CUENTA ACTIVA</span>
                <span class="pill-tag pill-tag-info">#ORG-<?= str_pad($user['company_id'] ?? 1, 3, '0', STR_PAD_LEFT) ?></span>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert-ui <?= $status_type === 'success' ? 'alert-ui-success' : 'alert-ui-danger' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <?php if ($status_type === 'success'): ?>
                    <polyline points="20 6 9 17 4 12"/>
                    <?php else: ?>
                    <circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/>
                    <?php endif; ?>
                </svg>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:340px 1fr;gap:24px;margin-bottom:24px;">
            <!-- Ficha de Usuario / Organización -->
            <div class="card-ui" style="margin-bottom:0;">
                <div class="card-ui-header">
                    <div class="card-ui-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/></svg>
                        Ficha de Identidad
                    </div>
                </div>
                <div style="padding:24px;text-align:center;">
                    <div style="width:100px;height:100px;border-radius:16px;background:var(--surface);border:2px solid var(--border);margin:0 auto 16px;display:flex;align-items:center;justify-content:center;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,0.3);">
                        <?php if ($has_avatar): ?>
                            <img src="<?= htmlspecialchars($avatar_src) ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
                        <?php else: ?>
                            <span style="font-family:'Outfit',sans-serif;font-size:2.4rem;font-weight:800;color:var(--accent);">
                                <?= strtoupper(substr($user['username'], 0, 1)) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <h3 style="font-size:1.1rem;font-weight:700;color:var(--text);margin-bottom:4px;">
                        <?= htmlspecialchars($user['full_name'] ?? $user['username']) ?>
                    </h3>
                    <div style="font-size:0.8rem;color:var(--muted);margin-bottom:12px;">
                        <?= htmlspecialchars($user['company_name'] ?? 'Weather Report Solutions') ?>
                    </div>
                    <span class="pill-tag pill-tag-info" style="font-size:0.72rem;letter-spacing:0.5px;">
                        ROL: <?= strtoupper($user['role']) ?>
                    </span>

                    <div style="margin-top:24px;border-top:1px solid var(--border);padding-top:18px;text-align:left;display:flex;flex-direction:column;gap:12px;font-size:0.83rem;">
                        <div style="display:flex;justify-content:space-between;">
                            <span style="color:var(--dim);">Usuario:</span>
                            <span class="font-mono" style="color:var(--text);font-weight:600;"><?= htmlspecialchars($user['username']) ?></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;">
                            <span style="color:var(--dim);">Email:</span>
                            <span style="color:var(--text);"><?= htmlspecialchars($user['email']) ?></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;">
                            <span style="color:var(--dim);">Plan SLA:</span>
                            <span style="color:var(--accent);font-weight:600;"><?= htmlspecialchars($user['plan_type'] ?? 'Enterprise Pro') ?></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;">
                            <span style="color:var(--dim);">ID Organización:</span>
                            <span class="font-mono" style="color:var(--muted);">#WR-ORG-<?= str_pad($user['company_id'] ?? 1, 3, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;">
                            <span style="color:var(--dim);">Miembro desde:</span>
                            <span style="color:var(--muted);"><?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulario de Carga de Logo -->
            <div class="card-ui" style="margin-bottom:0;">
                <div class="card-ui-header">
                    <div class="card-ui-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                        Actualizar Logotipo y Emblema de Empresa
                    </div>
                    <span style="font-size:0.75rem;color:var(--muted);">Incrustado en reportes PDF generados</span>
                </div>
                <div style="padding:24px;">
                    <p style="font-size:0.86rem;color:var(--muted);margin-bottom:20px;line-height:1.6;">
                        El emblema corporativo se incrusta de manera oficial en la cabecera de los boletines de heladas, avisos SIGMET aeronáuticos e informes confidenciales descargados en formato digital.
                    </p>

                    <form action="/profile/upload_avatar.php" method="POST" enctype="multipart/form-data" id="avatarForm">
                        <div style="border:2px dashed var(--border);border-radius:12px;padding:36px 20px;text-align:center;background:rgba(8,12,20,0.5);margin-bottom:20px;cursor:pointer;transition:border-color 0.2s;" onclick="document.getElementById('fileInput').click()" ondragover="event.preventDefault();this.style.borderColor='var(--accent)';" ondragleave="this.style.borderColor='var(--border)';" ondrop="handleDrop(event)">
                            <input type="file" name="fileToUpload" id="fileInput" style="display:none;" onchange="previewFile(this)">
                            
                            <div style="width:50px;height:50px;border-radius:10px;background:rgba(2,132,199,0.12);border:1px solid rgba(56,189,248,0.25);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;color:var(--accent);">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                            </div>
                            <div style="font-size:0.95rem;font-weight:600;color:var(--text);margin-bottom:4px;" id="fileSelectPrompt">
                                Haga clic para examinar o arrastre su logotipo aquí
                            </div>
                            <div style="font-size:0.78rem;color:var(--dim);margin-bottom:14px;">
                                Soporta formatos vectoriales y gráficos rasterizados (PNG con canal alfa recomendado)
                            </div>
                            <div style="display:flex;justify-content:center;gap:6px;flex-wrap:wrap;">
                                <span class="pill-tag" style="background:rgba(255,255,255,0.04);border:1px solid var(--border);color:var(--muted);font-size:0.68rem;">PNG</span>
                                <span class="pill-tag" style="background:rgba(255,255,255,0.04);border:1px solid var(--border);color:var(--muted);font-size:0.68rem;">SVG</span>
                                <span class="pill-tag" style="background:rgba(255,255,255,0.04);border:1px solid var(--border);color:var(--muted);font-size:0.68rem;">WEBP</span>
                                <span class="pill-tag" style="background:rgba(255,255,255,0.04);border:1px solid var(--border);color:var(--muted);font-size:0.68rem;">JPG</span>
                                <span class="pill-tag" style="background:rgba(255,255,255,0.04);border:1px solid var(--border);color:var(--muted);font-size:0.68rem;">MÁX 5 MB</span>
                            </div>
                        </div>

                        <div id="fileSelectedBar" style="display:none;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:12px 16px;margin-bottom:20px;justify-content:space-between;align-items:center;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--accent);"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                <div>
                                    <div id="selectedFileName" class="font-mono" style="font-size:0.85rem;font-weight:600;color:var(--text);"></div>
                                    <div id="selectedFileSize" style="font-size:0.72rem;color:var(--muted);"></div>
                                </div>
                            </div>
                            <span class="pill-tag pill-tag-success" style="font-size:0.68rem;">LISTO PARA CARGAR</span>
                        </div>

                        <button type="submit" class="btn-ui btn-ui-primary" style="width:100%;justify-content:center;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/></svg>
                            Confirmar y Actualizar Emblema Corporativo
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Ajustes de Información de Operador (WR-RT-07 Mass Assignment) -->
        <div class="card-ui">
            <div class="card-ui-header">
                <div class="card-ui-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Ajustes de Perfil y Nombre de Operador
                </div>
                <span class="pill-tag pill-tag-info">API v1 REST</span>
            </div>
            <div style="padding:24px;">
                <form id="profileUpdateForm" onsubmit="handleProfileUpdate(event)">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                        <div class="form-group-ui">
                            <label class="form-label-ui">Nombre Completo de Operador</label>
                            <input type="text" id="full_name_input" class="form-control-ui" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group-ui">
                            <label class="form-label-ui">Identificador de Usuario (No modificable)</label>
                            <input type="text" class="form-control-ui font-mono" value="<?= htmlspecialchars($user['username']) ?>" disabled style="opacity:0.6;">
                        </div>
                    </div>
                    <div id="profile-update-status" style="font-size:0.82rem;min-height:20px;margin-bottom:12px;"></div>
                    <button type="submit" class="btn-ui btn-ui-ghost">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        Guardar Cambios de Perfil
                    </button>
                </form>
            </div>
        </div>

        <!-- API Key & Credenciales Telemétricas de la Empresa -->
        <div class="card-ui">
            <div class="card-ui-header">
                <div class="card-ui-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Credenciales de Acceso a la API REST de Telemetría
                </div>
                <span class="pill-tag pill-tag-success">PRODUCCIÓN</span>
            </div>
            <div style="padding:24px;">
                <p style="font-size:0.86rem;color:var(--muted);margin-bottom:16px;">
                    Utilice esta clave secreta en la cabecera <code>X-API-Key</code> o <code>Authorization: Bearer</code> para conectar sus pipelines de ingestión automática:
                </p>
                <div style="display:flex;gap:12px;align-items:center;margin-bottom:12px;">
                    <input type="password" id="api-key-input" class="form-control-ui font-mono" value="<?= htmlspecialchars($company['api_key'] ?? 'wr_live_default_key') ?>" readonly style="flex:1;">
                    <button type="button" onclick="toggleApiKeyVisibility()" class="btn-ui btn-ui-ghost" id="btn-toggle-key">Mostrar</button>
                    <button type="button" onclick="copyApiKey()" class="btn-ui btn-ui-primary">Copiar Clave</button>
                </div>
                <div style="font-size:0.75rem;color:var(--dim);">
                    Advertencia: Nunca exponga esta clave en clientes públicos JavaScript ni en repositorios abiertos.
                </div>
            </div>
        </div>

        <script>
        function previewFile(input) {
            if (input.files && input.files[0]) {
                var file = input.files[0];
                document.getElementById('selectedFileName').textContent = file.name;
                document.getElementById('selectedFileSize').textContent = (file.size / 1024).toFixed(1) + ' KB';
                document.getElementById('fileSelectedBar').style.display = 'flex';
                document.getElementById('fileSelectPrompt').textContent = 'Archivo seleccionado: ' + file.name;
            }
        }
        function handleDrop(e) {
            e.preventDefault();
            var dt = e.dataTransfer;
            if (dt.files && dt.files[0]) {
                var fileInput = document.getElementById('fileInput');
                fileInput.files = dt.files;
                previewFile(fileInput);
            }
        }
        function toggleApiKeyVisibility() {
            var inp = document.getElementById('api-key-input');
            var btn = document.getElementById('btn-toggle-key');
            if (inp.type === 'password') {
                inp.type = 'text';
                btn.textContent = 'Ocultar';
            } else {
                inp.type = 'password';
                btn.textContent = 'Mostrar';
            }
        }
        function copyApiKey() {
            var inp = document.getElementById('api-key-input');
            navigator.clipboard.writeText(inp.value).then(() => {
                alert('API Key corporativa copiada al portapapeles.');
            });
        }
        async function handleProfileUpdate(e) {
            e.preventDefault();
            const fullName = document.getElementById('full_name_input').value.trim();
            const statusDiv = document.getElementById('profile-update-status');
            statusDiv.innerHTML = '<span style="color:var(--muted)">Guardando cambios de perfil...</span>';
            try {
                const res = await fetch('/api/v1/update_profile.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ full_name: fullName })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    statusDiv.innerHTML = `<span style="color:var(--success)">✓ ${data.message}</span>`;
                } else {
                    statusDiv.innerHTML = `<span style="color:var(--danger)">✕ ${data.message}</span>`;
                }
            } catch (err) {
                statusDiv.innerHTML = '<span style="color:var(--danger)">Error al comunicar con la API de perfil.</span>';
            }
        }
        </script>
<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
