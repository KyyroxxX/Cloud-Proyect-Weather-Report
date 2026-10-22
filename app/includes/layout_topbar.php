<?php
/**
 * Weather Report Solutions S.L. — Unified Topbar HUD Component
 */
if (!isset($user) && function_exists('current_user')) {
    $user = current_user();
}
?>
<!-- ── UNIFIED TOPBAR ────────────────────────────────────── -->
<header class="app-topbar">
    <div class="topbar-left">
        <h1 class="topbar-title"><?= htmlspecialchars($page_heading ?? 'Dashboard') ?></h1>
        <?php if (!empty($user)): ?>
        <div class="topbar-meta">
            <?= htmlspecialchars($user['company_name'] ?? 'Weather Report Solutions') ?>
            · <span style="color:var(--accent);"><?= htmlspecialchars($user['plan_type'] ?? 'Enterprise SaaS') ?></span>
        </div>
        <?php endif; ?>
    </div>
    <div class="topbar-right">
        <div id="topbar-clock" class="font-mono" style="font-size:0.75rem;color:var(--dim);background:rgba(255,255,255,0.03);padding:4px 10px;border-radius:6px;border:1px solid var(--border);">
            --:--:-- UTC
        </div>
        <div class="telemetry-status-badge">
            <span class="telemetry-pulse"></span>
            <span>TELEMETRÍA EN VIVO · 42ms</span>
        </div>
        <a href="/feedback.php" class="btn-ui btn-ui-ghost" style="padding:6px 12px;font-size:0.78rem;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            Soporte
        </a>
    </div>
</header>
<script>
(function() {
    function updateClock() {
        const el = document.getElementById('topbar-clock');
        if (el) {
            const now = new Date();
            el.innerText = now.toUTCString().slice(17, 25) + ' UTC';
        }
    }
    updateClock();
    setInterval(updateClock, 1000);
})();
</script>
