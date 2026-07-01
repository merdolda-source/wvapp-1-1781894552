<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$isActive = static fn(string $prefix): bool => $prefix === '/' ? $currentPath === '/' : str_starts_with($currentPath, $prefix);
$navUser = Auth::user();
?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<aside class="sidebar" id="sidebar">
    <a class="sidebar-brand" href="/dashboard">
        <span class="sidebar-brand-mark">C</span> CreatorApp24
    </a>

    <nav class="sidebar-nav">
        <a class="sidebar-link <?= $isActive('/dashboard') ? 'active' : '' ?>" href="/dashboard">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>
            Uygulamalarım
        </a>
        <a class="sidebar-link <?= $isActive('/stats/downloads') ? 'active' : '' ?>" href="/stats/downloads">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            İndirmeler
        </a>
        <a class="sidebar-link <?= $isActive('/stats/usage') ? 'active' : '' ?>" href="/stats/usage">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            Kullanım
        </a>
        <a class="sidebar-link <?= $isActive('/account') ? 'active' : '' ?>" href="/account">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Hesabım
        </a>
        <?php if (Auth::isAdmin()): ?>
            <a class="sidebar-link <?= $isActive('/admin') ? 'active' : '' ?>" href="/admin">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Admin
            </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-cta">
        <a href="/apps/new" class="btn" style="width:100%">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;margin-right:6px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Yeni Uygulama
        </a>
    </div>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar"><?= View::e(mb_substr($navUser['name'] ?? '?', 0, 1)) ?></div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= View::e($navUser['name'] ?? '') ?></div>
                <div class="sidebar-user-email"><?= View::e($navUser['email'] ?? '') ?></div>
            </div>
        </div>
        <form action="/logout" method="post">
            <?= Csrf::field() ?>
            <button type="submit" class="sidebar-link sidebar-logout">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Çıkış Yap
            </button>
        </form>
    </div>
</aside>
