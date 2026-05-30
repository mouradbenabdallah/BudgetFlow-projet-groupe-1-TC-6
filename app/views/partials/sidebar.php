<?php
// Sidebar authentifiée : liens principaux et résumé de l'utilisateur connecté.
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isActive = static fn (string $path): string => ($currentPath === $path || ($path !== '/budgets' && str_starts_with($currentPath, $path))) ? 'active' : '';
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$sidebarUser = $user ?? Auth::getUser() ?? [];
$sidebarName = trim((string) ($sidebarUser['name'] ?? 'Utilisateur'));
$sidebarEmail = (string) ($sidebarUser['email'] ?? '');
$nameParts = preg_split('/\s+/', $sidebarName) ?: [];
$initials = '';

foreach (array_slice($nameParts, 0, 2) as $part) {
    if ($part === '') {
        continue;
    }

    $initials .= function_exists('mb_substr')
        ? mb_substr($part, 0, 1, 'UTF-8')
        : substr($part, 0, 1);
}

$initials = $initials !== '' ? strtoupper($initials) : 'U';
$mainNavigation = [
    ['path' => '/dashboard', 'label' => 'Tableau de bord', 'icon' => 'bi-grid'],
    ['path' => '/transactions', 'label' => 'Transactions', 'icon' => 'bi-arrow-left-right'],
    ['path' => '/budgets', 'label' => 'Budgets', 'icon' => 'bi-wallet2'],
    ['path' => '/budgets/shared', 'label' => 'Budgets partagés', 'icon' => 'bi-people'],
    ['path' => '/categories', 'label' => 'Catégories', 'icon' => 'bi-tag'],
];

$accountNavigation = [
    ['path' => '/notifications', 'label' => 'Notifications', 'icon' => 'bi-bell'],
    ['path' => '/profile', 'label' => 'Profil', 'icon' => 'bi-person'],
    ['path' => '/settings', 'label' => 'Paramètres', 'icon' => 'bi-gear'],
];
$isAdmin = ($sidebarUser['role'] ?? 'user') === 'admin';
?>

<aside class="bf-sidebar" aria-label="Navigation principale">
    <a class="bf-sidebar-brand" href="/dashboard" aria-label="BudgetFlow Dashboard">
        <span class="bf-sidebar-brand-icon" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 20 20"><path d="M10 2C6.13 2 3 5.13 3 9c0 2.38 1.19 4.47 3 5.74V17h8v-2.26C15.81 13.47 17 11.38 17 9c0-3.87-3.13-7-7-7z" fill="#00ed64"/><path d="M8 17v1h4v-1H8z" fill="#00ed64" opacity="0.7"/></svg>
        </span>
        <span style="font-size:20px;letter-spacing:-0.5px">Budget<span class="bf-sidebar-brand-accent">Flow</span></span>
    </a>

    <nav class="bf-sidebar-nav" aria-label="Menu principal">
        <span class="bf-sidebar-section-label">MENU PRINCIPAL</span>
        <?php foreach ($mainNavigation as $item): ?>
            <?php $activeClass = $isActive($item['path']); ?>
            <a
                class="bf-sidebar-link <?= $escape($activeClass) ?>"
                href="<?= $escape($item['path']) ?>"
                <?= $activeClass !== '' ? 'aria-current="page"' : '' ?>
            >
                <i class="bi <?= $escape($item['icon']) ?>" aria-hidden="true"></i>
                <span><?= $escape($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <nav class="bf-sidebar-nav bf-sidebar-account" aria-label="Compte">
        <span class="bf-sidebar-section-label">COMPTE</span>
        <?php foreach ($accountNavigation as $item): ?>
            <?php $activeClass = $isActive($item['path']); ?>
            <a
                class="bf-sidebar-link <?= $escape($activeClass) ?>"
                href="<?= $escape($item['path']) ?>"
                <?= $activeClass !== '' ? 'aria-current="page"' : '' ?>
            >
                <i class="bi <?= $escape($item['icon']) ?>" aria-hidden="true"></i>
                <span><?= $escape($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
        <?php if ($isAdmin): ?>
            <?php $activeClass = $isActive('/admin'); ?>
            <a
                class="bf-sidebar-link <?= $escape($activeClass) ?>"
                href="/admin"
                <?= $activeClass !== '' ? 'aria-current="page"' : '' ?>
            >
                <i class="bi bi-shield-check" aria-hidden="true"></i>
                <span>Admin</span>
            </a>
        <?php endif; ?>
        <form method="post" action="/logout" class="bf-form-inline">
            <?= CSRF::getTokenField() ?>
            <button type="submit" class="bf-sidebar-link bf-sidebar-logout-btn">
                <i class="bi bi-box-arrow-left" aria-hidden="true"></i>
                <span>Déconnexion</span>
            </button>
        </form>
    </nav>

    <div class="bf-sidebar-footer">
        <div class="bf-user-panel" style="background:#1c2d38;border:1px solid #3d4f58;border-radius:12px;padding:10px 10px;">
            <div class="bf-avatar" aria-hidden="true"><?= $escape($initials) ?></div>
            <div class="min-w-0">
                <span class="bf-user-name"><?= $escape($sidebarName) ?></span>
                <span class="bf-user-email"><?= $escape(($sidebarUser['role'] ?? 'user') === 'admin' ? 'Admin' : $sidebarEmail) ?></span>
            </div>
        </div>
    </div>
</aside>
