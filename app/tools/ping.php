<?php
/**
 * Weather Report Solutions S.L. — Diagnóstico de Latencia SLA de Red
 * Unified Web App Layout Architecture
 * VULN-07 (WR-RT-07): OS Command Injection — el parámetro 'host' se concatena en exec() sin sanitizar
 */
require_once __DIR__ . '/../config.php';
session_start();
$user = require_auth();

$output    = '';
$host_val  = '';
$exec_time = null;

if (!empty($_POST['host'])) {
    $host_val = $_POST['host'];
    $t0 = microtime(true);
    // Soporte multiplataforma (Linux en AWS / Windows en local)
    $ping_flag = (DIRECTORY_SEPARATOR === '\\') ? '-n 2 ' : '-c 2 ';
    // WR-RT-07: Inyección directa de comandos del SO en la llamada a ping
    exec('ping ' . $ping_flag . $host_val . ' 2>&1', $lines, $code);
    $exec_time = round((microtime(true) - $t0) * 1000);
    $output = implode("\n", $lines);
}

$page_title = 'Diagnóstico de Red SLA';
$page_heading = 'Herramientas Telemétricas';

require_once __DIR__ . '/../includes/layout_head.php';
require_once __DIR__ . '/../includes/layout_sidebar.php';
?>

<main class="app-main">
    <?php require_once __DIR__ . '/../includes/layout_topbar.php'; ?>

    <div class="app-content-body">
        <!-- Sub-encabezado de infraestructura -->
        <div style="background:var(--surface-card);border:1px solid var(--border);border-radius:12px;padding:20px 24px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 10px 30px rgba(0,0,0,0.2);flex-wrap:wrap;gap:12px;">
            <div>
                <h2 style="font-size:1.25rem;font-weight:700;margin-bottom:4px;">Monitor de Conectividad & Latencia SLA (ICMP)</h2>
                <p style="color:var(--muted);font-size:0.85rem;">
                    Herramienta de diagnóstico de baja latencia para validar la disponibilidad de enlaces troncales de estaciones meteorológicas y nodos AWS.
                </p>
            </div>
            <div style="display:flex;gap:8px;">
                <span class="pill-tag pill-tag-success">PROTOCOLO ICMP ACTIVO</span>
                <span class="pill-tag" style="background:rgba(2,132,199,0.1);color:var(--accent);border:1px solid rgba(56,189,248,0.25);">
                    GATEWAY: 10.0.0.1
                </span>
            </div>
        </div>

        <!-- Grid de estado de nodos -->
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
            <div style="background:var(--surface-card);border:1px solid var(--border);border-radius:10px;padding:16px 20px;cursor:pointer;transition:transform 0.15s, border-color 0.15s;" onclick="setHost('10.0.21.10')">
                <div style="font-size:0.75rem;color:var(--muted);text-transform:uppercase;font-weight:600;letter-spacing:0.5px;">Nodo Berlín (BER-01)</div>
                <div class="font-mono" style="font-size:1.05rem;font-weight:700;color:var(--accent);margin:6px 0;">10.0.21.10</div>
                <div style="display:flex;align-items:center;gap:6px;font-size:0.72rem;color:var(--success);font-weight:600;">
                    <span style="width:6px;height:6px;border-radius:50%;background:var(--success);box-shadow:0 0 6px var(--success);"></span> ONLINE · 18ms
                </div>
            </div>
            <div style="background:var(--surface-card);border:1px solid var(--border);border-radius:10px;padding:16px 20px;cursor:pointer;transition:transform 0.15s, border-color 0.15s;" onclick="setHost('10.0.21.11')">
                <div style="font-size:0.75rem;color:var(--muted);text-transform:uppercase;font-weight:600;letter-spacing:0.5px;">Nodo Madrid (MAD-01)</div>
                <div class="font-mono" style="font-size:1.05rem;font-weight:700;color:var(--accent);margin:6px 0;">10.0.21.11</div>
                <div style="display:flex;align-items:center;gap:6px;font-size:0.72rem;color:var(--success);font-weight:600;">
                    <span style="width:6px;height:6px;border-radius:50%;background:var(--success);box-shadow:0 0 6px var(--success);"></span> ONLINE · 4ms
                </div>
            </div>
            <div style="background:var(--surface-card);border:1px solid var(--border);border-radius:10px;padding:16px 20px;cursor:pointer;transition:transform 0.15s, border-color 0.15s;" onclick="setHost('10.0.21.12')">
                <div style="font-size:0.75rem;color:var(--muted);text-transform:uppercase;font-weight:600;letter-spacing:0.5px;">Nodo Londres (LON-01)</div>
                <div class="font-mono" style="font-size:1.05rem;font-weight:700;color:var(--accent);margin:6px 0;">10.0.21.12</div>
                <div style="display:flex;align-items:center;gap:6px;font-size:0.72rem;color:var(--warn);font-weight:600;">
                    <span style="width:6px;height:6px;border-radius:50%;background:var(--warn);box-shadow:0 0 6px var(--warn);"></span> ALTA LATENCIA · 92ms
                </div>
            </div>
            <div style="background:var(--surface-card);border:1px solid var(--border);border-radius:10px;padding:16px 20px;cursor:pointer;transition:transform 0.15s, border-color 0.15s;" onclick="setHost('10.0.21.15')">
                <div style="font-size:0.75rem;color:var(--muted);text-transform:uppercase;font-weight:600;letter-spacing:0.5px;">Cluster RDS / DB-01</div>
                <div class="font-mono" style="font-size:1.05rem;font-weight:700;color:var(--accent);margin:6px 0;">10.0.21.15</div>
                <div style="display:flex;align-items:center;gap:6px;font-size:0.72rem;color:var(--success);font-weight:600;">
                    <span style="width:6px;height:6px;border-radius:50%;background:var(--success);box-shadow:0 0 6px var(--success);"></span> PRIVADO · 1ms
                </div>
            </div>
        </div>

        <div class="card-ui">
            <div class="card-ui-header">
                <div class="card-ui-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m4.93 4.93 4.24 4.24"/><path d="m14.83 9.17 4.24-4.24"/><path d="m14.83 14.83 4.24 4.24"/><path d="m9.17 14.83-4.24 4.24"/><circle cx="12" cy="12" r="3"/></svg>
                    Consola de Comprobación ICMP
                </div>
                <span style="font-size:0.75rem;color:var(--muted);">Parámetros: 2 paquetes de eco · Timeout: 3000ms</span>
            </div>
            <div style="padding:24px;">
                <p style="font-size:0.86rem;color:var(--muted);margin-bottom:16px;">
                    Seleccione un nodo de la infraestructura o introduzca una dirección IP / FQDN para verificar la resolución de nombres y la fluctuación (jitter):
                </p>

                <!-- Accesos directos a nodos conocidos -->
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
                    <span style="font-size:0.76rem;color:var(--dim);font-weight:600;text-transform:uppercase;">Nodos Rápidos:</span>
                    <button type="button" class="btn-ui btn-ui-ghost" style="padding:4px 10px;font-size:0.78rem;" onclick="setHost('10.0.21.10')">BER-01 (10.0.21.10)</button>
                    <button type="button" class="btn-ui btn-ui-ghost" style="padding:4px 10px;font-size:0.78rem;" onclick="setHost('10.0.21.11')">MAD-01 (10.0.21.11)</button>
                    <button type="button" class="btn-ui btn-ui-ghost" style="padding:4px 10px;font-size:0.78rem;" onclick="setHost('10.0.21.12')">LON-01 (10.0.21.12)</button>
                    <button type="button" class="btn-ui btn-ui-ghost" style="padding:4px 10px;font-size:0.78rem;" onclick="setHost('10.0.21.15')">RDS DB (10.0.21.15)</button>
                    <button type="button" class="btn-ui btn-ui-ghost" style="padding:4px 10px;font-size:0.78rem;" onclick="setHost('127.0.0.1')">Localhost (127.0.0.1)</button>
                </div>

                <form method="POST" action="/tools/ping.php" id="ping-form" style="display:flex;gap:12px;margin-bottom:24px;">
                    <div style="flex:1;">
                        <input type="text" id="host_input" name="host" class="form-control-ui font-mono" placeholder="Introduzca IP o FQDN (ej. 10.0.21.10)" value="<?= htmlspecialchars($host_val) ?>" required>
                    </div>
                    <button type="submit" class="btn-ui btn-ui-primary" style="white-space:nowrap;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        Ejecutar Diagnóstico
                    </button>
                </form>

                <?php if ($output !== ''): ?>
                <div style="background:#03060c;border:1px solid rgba(56,189,248,0.25);border-radius:10px;overflow:hidden;box-shadow:inset 0 2px 10px rgba(0,0,0,0.5), 0 10px 30px rgba(0,0,0,0.4);">
                    <div style="background:#080d1a;padding:10px 16px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid rgba(255,255,255,0.06);">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="width:10px;height:10px;border-radius:50%;background:#ef4444;display:inline-block;"></span>
                            <span style="width:10px;height:10px;border-radius:50%;background:#f59e0b;display:inline-block;"></span>
                            <span style="width:10px;height:10px;border-radius:50%;background:#10b981;display:inline-block;"></span>
                            <span class="font-mono" style="font-size:0.75rem;color:var(--muted);margin-left:8px;">wr-app@kernel: icmp_traceroute</span>
                        </div>
                        <div style="display:flex;gap:10px;align-items:center;">
                            <?php if ($exec_time !== null): ?>
                                <span class="font-mono" style="font-size:0.72rem;color:var(--dim);background:rgba(255,255,255,0.05);padding:2px 8px;border-radius:4px;">
                                    <?= $exec_time ?>ms
                                </span>
                            <?php endif; ?>
                            <button onclick="copyOutput()" class="btn-ui btn-ui-ghost" style="padding:2px 8px;font-size:0.7rem;">Copiar Trazas</button>
                        </div>
                    </div>
                    <div style="padding:18px 20px;">
                        <pre id="output-pre" class="font-mono" style="color:#34d399;font-size:0.84rem;line-height:1.6;white-space:pre-wrap;word-break:break-all;max-height:360px;overflow-y:auto;"><?= htmlspecialchars($output) ?></pre>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <script>
        function setHost(ip) {
            var el = document.getElementById('host_input');
            if (el) {
                el.value = ip;
                el.focus();
            }
        }
        function copyOutput() {
            var pre = document.getElementById('output-pre');
            if (pre) {
                navigator.clipboard.writeText(pre.innerText).then(() => {
                    alert('Trazas de consola copiadas al portapapeles.');
                });
            }
        }
        </script>
<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
