<?php
// Layout administrateur — sidebar dédiée aux routes /admin.
$pageTitleText  = htmlspecialchars((string) ($pageTitle ?? $title ?? 'Administration'), ENT_QUOTES, 'UTF-8');
$documentTitle  = $pageTitleText . ' - BudgetFlow Admin';
$currentPath    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $documentTitle ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Inter:wght@400;500&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/style.css" rel="stylesheet">
    <link rel="icon" href="/img/favicon-budget.png" type="image/png">
</head>
<body class="bf-page-app">
<div class="bf-app-shell">

    <!-- Sidebar admin -->
    <nav class="bf-sidebar" aria-label="Navigation administration">
        <div class="bf-sidebar-brand">
            <span class="bf-sidebar-brand-icon" aria-hidden="true">
                <i class="bi bi-shield-check"></i>
            </span>
            Budget<span class="bf-brand-accent">Flow</span>
        </div>

        <ul class="bf-sidebar-nav list-unstyled">
            <li>
                <span class="bf-sidebar-section-label">Administration</span>
            </li>
            <li>
                <a href="/admin" class="bf-sidebar-link <?= $currentPath === '/admin' ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2" aria-hidden="true"></i>
                    Tableau de bord
                </a>
            </li>
            <li>
                <a href="/admin/users" class="bf-sidebar-link <?= str_starts_with($currentPath, '/admin/users') ? 'active' : '' ?>">
                    <i class="bi bi-people" aria-hidden="true"></i>
                    Utilisateurs
                </a>
            </li>
            <li>
                <a href="/admin/budgets" class="bf-sidebar-link <?= str_starts_with($currentPath, '/admin/budgets') ? 'active' : '' ?>">
                    <i class="bi bi-wallet2" aria-hidden="true"></i>
                    Budgets partagés
                </a>
            </li>
        </ul>

        <div class="bf-sidebar-footer">
            <form method="post" action="/logout" class="bf-form-inline">
                <?= CSRF::getTokenField() ?>
                <button type="submit" class="bf-sidebar-link bf-sidebar-logout-btn w-100">
                    <i class="bi bi-box-arrow-left" aria-hidden="true"></i>
                    Déconnexion
                </button>
            </form>
        </div>
    </nav>

    <!-- Contenu principal -->
    <main class="bf-main">
        <header class="bf-topbar">
            <div>
                <h1 class="bf-page-title"><?= $pageTitleText ?></h1>
                <p class="bf-page-greeting">Panneau d'administration</p>
            </div>
            <div class="bf-topbar-actions">
                <span class="bf-badge bf-badge-admin">
                    <i class="bi bi-shield-check me-1"></i> Admin
                </span>
            </div>
        </header>

        <div class="bf-content">
            <?= $content ?? '' ?>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
