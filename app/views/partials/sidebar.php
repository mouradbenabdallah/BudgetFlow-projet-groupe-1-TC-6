<?php
// Sidebar authentifiée : liens principaux et résumé de l'utilisateur connecté.
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isActive = static fn (string $path): string => $currentPath === $path ? 'active' : '';
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
    ['path' => '/analytics', 'label' => 'Analyses', 'icon' => 'bi-graph-up-arrow'],
];

$accountNavigation = [
    ['path' => '/notifications', 'label' => 'Notifications', 'icon' => 'bi-bell'],
    ['path' => '/profile', 'label' => 'Profil', 'icon' => 'bi-person'],
    ['path' => '/admin', 'label' => 'Admin', 'icon' => 'bi-shield-check'],
    ['path' => '/settings', 'label' => 'Paramètres', 'icon' => 'bi-gear'],
    ['path' => '/logout', 'label' => 'Déconnexion', 'icon' => 'bi-box-arrow-left'],
];
?>

<aside class="bf-sidebar" aria-label="Navigation principale">
    <a class="bf-sidebar-brand" href="/dashboard" aria-label="BudgetFlow Dashboard">
        <span class="bf-sidebar-brand-icon" aria-hidden="true"></span>
        <span>Budget<span class="bf-sidebar-brand-accent">Flow</span></span>
    </a>

    <nav class="bf-sidebar-nav" aria-label="Menu principal">
        <span class="bf-sidebar-section-label">Menu principal</span>
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
        <span class="bf-sidebar-section-label">Compte</span>
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
    </nav>

    <div class="bf-sidebar-footer">
        <div class="bf-user-panel">
            <div class="bf-avatar" aria-hidden="true"><?= $escape($initials) ?></div>
            <div class="min-w-0">
                <span class="bf-user-name"><?= $escape($sidebarName) ?></span>
                <span class="bf-user-email"><?= $escape(($sidebarUser['role'] ?? 'user') === 'admin' ? 'Admin' : $sidebarEmail) ?></span>
            </div>
        </div>
    </div>
</aside>
