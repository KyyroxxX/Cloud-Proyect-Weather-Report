<?php
/**
 * Weather Report Solutions S.L. — Unified Persistent Sidebar HUD
 * Injected across all internal SaaS pages to maintain 100% layout and HUD consistency.
 */
if (!isset($user) && function_exists('current_user')) {
    $user = current_user();
}
$current_page = $_SERVER['PHP_SELF'] ?? '';
?>
<!-- ── UNIFIED PERSISTENT SIDEBAR ────────────────────────── -->
<aside class="app-sidebar">
    <a href="/index.php" class="sidebar-brand-link" title="Volver a la portada principal">
        <img src="/favicon.svg" alt="Weather Report Logo" style="width:30px;height:30px;border-radius:7px;display:block;flex-shrink:0;">
        <span class="sidebar-brand-name">Weather Report</span>
        <span class="sidebar-brand-badge">PROD</span>
    </a>

    <nav class="sidebar-nav">
        <div class="nav-category">Centro de Operaciones</div>
        <a href="/dashboard.php" class="nav-link <?= str_contains($current_page, 'dashboard.php') ? 'active' : '' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
            Dashboard Principal
        </a>
        <a href="/api/v1/download_report.php?report_id=1" class="nav-link <?= str_contains($current_page, 'download_report.php') ? 'active' : '' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
            Informes Clasificados
        </a>
        <a href="/feedback.php" class="nav-link <?= str_contains($current_page, 'feedback.php') ? 'active' : '' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            Soporte & Incidencias
        </a>

        <div class="nav-category">Herramientas Telemétricas</div>
        <a href="/tools/ping.php" class="nav-link <?= str_contains($current_page, 'ping.php') ? 'active' : '' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
            Diagnóstico de Red SLA
        </a>
        <a href="/tools/webhook.php" class="nav-link <?= str_contains($current_page, 'webhook.php') ? 'active' : '' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            Integración de Webhooks
        </a>
        <a href="/api/v1/raw_telemetry_export.php?file=sensor_audit_2026_q3.csv" class="nav-link <?= str_contains($current_page, 'raw_telemetry_export.php') ? 'active' : '' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
            Exportación de Sensores (Raw)
        </a>

        <div class="nav-category">Cuenta & Seguridad</div>
        <a href="/profile/upload_avatar.php" class="nav-link <?= str_contains($current_page, 'upload_avatar.php') ? 'active' : '' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
            Perfil & Emblema
        </a>
        <a href="/forgot_password.php" class="nav-link <?= str_contains($current_page, 'forgot_password.php') ? 'active' : '' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/></svg>
            Restablecer Clave
        </a>
        <?php if (!empty($user) && ($user['role'] === 'admin')): ?>
        <a href="/admin/panel.php" class="nav-link <?= str_contains($current_page, 'panel.php') ? 'active' : '' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Panel de Administración
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-user-footer">
        <?php if (!empty($user)): ?>
        <div class="user-profile-badge">
            <div class="user-avatar-circle">
                <?php if (!empty($user['avatar_url']) && file_exists(__DIR__ . '/..' . $user['avatar_url'])): ?>
                    <img src="<?= htmlspecialchars($user['avatar_url']) ?>?t=<?= time() ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
                <?php else: ?>
                    <?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div class="user-meta">
                <div class="user-display-name"><?= htmlspecialchars($user['full_name'] ?? $user['username'] ?? 'Usuario') ?></div>
                <div class="user-role-pill">
                    <span class="role-tag"><?= htmlspecialchars($user['role'] ?? 'user') ?></span>
                    <span><?= htmlspecialchars($user['company_name'] ?? '') ?></span>
                </div>
            </div>
        </div>
        <a href="/logout.php" class="btn-sidebar-logout">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
            Cerrar Sesión
        </a>
        <?php else: ?>
        <a href="/login.php" class="btn-sidebar-logout" style="background:var(--primary);color:#fff;border:none;">
            Iniciar Sesión →
        </a>
        <?php endif; ?>
    </div>
</aside>
