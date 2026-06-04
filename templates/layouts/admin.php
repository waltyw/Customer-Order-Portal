<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= \App\Core\Security::e($title ?? 'Admin') ?> — Admin — <?= \App\Core\Security::e(\App\Models\Setting::get('app_name')) ?></title>
    <?php $favExt = \App\Models\Setting::get('favicon_ext') ?: 'png'; ?>
    <link rel="icon" href="/assets/img/favicon.<?= $favExt ?>" type="<?= $favExt === 'svg' ? 'image/svg+xml' : ($favExt === 'ico' ? 'image/x-icon' : 'image/png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <?php $activeFont = \App\Models\Setting::get('font_family') ?: 'Inter'; ?>
    <link href="https://fonts.googleapis.com/css2?family=<?= urlencode($activeFont) ?>:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
    <?= \App\Models\Setting::cssVars() ?>
</head>
<body>
<?php
$currentPath = '/' . trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$user = \App\Auth\Auth::user();
?>
<div class="layout">
    <aside class="sidebar sidebar-admin">
        <div class="sidebar-brand">
            <?php
            $logoExt     = \App\Models\Setting::get('logo_ext') ?: 'png';
            $logoFile    = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/logo.' . $logoExt;
            $appName     = \App\Models\Setting::get('app_name') ?: 'Admin';
            $logoLink    = \App\Models\Setting::get('logo_link_url') ?: '/admin';
            $logoTarget  = str_starts_with($logoLink, 'http') ? ' target="_blank" rel="noopener"' : '';
            ?>
            <?php if (file_exists($logoFile)): ?>
            <a href="<?= \App\Core\Security::e($logoLink) ?>"<?= $logoTarget ?>><img src="/assets/img/logo.<?= $logoExt ?>?v=<?= filemtime($logoFile) ?>" alt="<?= \App\Core\Security::e($appName) ?>" class="sidebar-logo-img"></a>
            <?php else: ?>
            <a href="<?= \App\Core\Security::e($logoLink) ?>"<?= $logoTarget ?> style="color:#fff;font-size:16px;font-weight:700;text-decoration:none;padding:8px 0;"><?= \App\Core\Security::e($appName) ?></a>
            <?php endif; ?>
        </div>
        <nav class="sidebar-nav">
            <a href="/admin" class="nav-item <?= $currentPath === '/admin' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <a href="/admin/customers" class="nav-item <?= str_starts_with($currentPath, '/admin/customers') ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Customers
            </a>
            <a href="/admin/tickets" class="nav-item <?= str_starts_with($currentPath, '/admin/tickets') ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Support Tickets
            </a>
            <a href="/admin/invoices" class="nav-item <?= str_starts_with($currentPath, '/admin/invoices') ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                Invoices
            </a>

            <div style="height:1px;background:rgba(255,255,255,.06);margin:8px 0;"></div>
            <div style="padding:6px 12px 2px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:rgba(255,255,255,.3);">Shop</div>

            <a href="/admin/shop/orders" class="nav-item <?= str_starts_with($currentPath, '/admin/shop/orders') ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                Orders
            </a>
            <a href="/admin/shop/products" class="nav-item <?= str_starts_with($currentPath, '/admin/shop/products') || str_starts_with($currentPath, '/admin/shop/stock') ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                Products
            </a>
            <a href="/admin/shop/categories" class="nav-item <?= str_starts_with($currentPath, '/admin/shop/categories') ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                Categories
            </a>
            <a href="/admin/shop/tiers" class="nav-item <?= str_starts_with($currentPath, '/admin/shop/tiers') ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                Pricing Tiers
            </a>
            <a href="/admin/shop/delivery" class="nav-item <?= str_starts_with($currentPath, '/admin/shop/delivery') ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                Delivery
            </a>
        </nav>

        <!-- Settings pinned above footer -->
        <div style="padding:8px 12px;border-top:1px solid rgba(255,255,255,.06);">
            <a href="/admin/settings" class="nav-item <?= str_starts_with($currentPath, '/admin/settings') ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                Settings
            </a>
        </div>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar" style="background:#7c3aed;"><?= strtoupper(substr($user['name'] ?? 'A', 0, 1)) ?></div>
                <div>
                    <div class="user-name"><?= \App\Core\Security::e($user['name'] ?? '') ?></div>
                    <div class="user-company" style="color:#f59e0b;">Administrator</div>
                </div>
            </div>
            <a href="/logout" class="logout-btn" title="Sign out">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            </a>
        </div>
    </aside>

    <main class="main-content">
        <div class="content-inner">
            <?php if (isset($flash)): ?>
            <div class="alert alert-<?= \App\Core\Security::e($flash['type']) ?>">
                <?= \App\Core\Security::e($flash['message']) ?>
                <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
            </div>
            <?php endif; ?>
            <?= $content ?>
        </div>
    </main>
</div>

<script src="/assets/js/app.js"></script>
</body>
</html>
