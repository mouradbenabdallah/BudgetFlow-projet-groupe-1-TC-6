<?php
$layoutUser = $user ?? Auth::getUser() ?? [];
$pageTitleText = (string) ($pageTitle ?? $title ?? 'Budgets partagés');
$documentTitle = htmlspecialchars($pageTitleText . ' - BudgetFlow', ENT_QUOTES, 'UTF-8');
$safePageTitle = htmlspecialchars($pageTitleText, ENT_QUOTES, 'UTF-8');
$nameParts = preg_split('/\s+/', trim((string) ($layoutUser['name'] ?? 'Utilisateur'))) ?: [];
$displayName = $nameParts[0] ?? 'Utilisateur';
$initials = '';
foreach (array_slice($nameParts, 0, 2) as $part) {
    if ($part === '') continue;
    $initials .= function_exists('mb_substr') ? mb_substr($part, 0, 1, 'UTF-8') : substr($part, 0, 1);
}
$safeInitials = htmlspecialchars(strtoupper($initials !== '' ? $initials : 'U'), ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $documentTitle ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=DM+Sans:wght@400;500;600;700&family=Source+Code+Pro:wght@400;500;600&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Anti-FOUC : applique le thème avant le rendu pour éviter le flash -->
    <script>(function(){var t=localStorage.getItem('bf-theme')||'light';document.documentElement.dataset.theme=t;}());</script>
    <link href="/style.css?v=10" rel="stylesheet">
    <link rel="icon" href="/img/favicon-budget.png" type="image/png">
    <link rel="icon" href="/img/favicon-budget.ico" type="image/x-icon">
    <script src="/script.js?v=3"></script>
</head>
<body class="bf-page-app">
    <div class="bf-app-shell">
        <?php require __DIR__ . '/../partials/sidebar.php'; ?>
        <div class="bf-sidebar-backdrop" id="sidebar-backdrop" aria-hidden="true"></div>

        <main class="bf-main">
            <header class="bf-topbar">
                <div class="bf-topbar-lead">
                    <button class="bf-menu-toggle" id="sidebar-toggle" type="button" aria-label="Ouvrir le menu" aria-expanded="false">
                        <i class="bi bi-list" aria-hidden="true"></i>
                    </button>
                    <div>
                        <h1 class="bf-page-title"><?= $safePageTitle ?></h1>
                        <p class="bf-page-subtitle">Collaborez sur les dépenses de groupe et finances partagées</p>
                    </div>
                </div>
                <div class="bf-topbar-actions">
                    <form class="bf-search" role="search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <label class="visually-hidden" for="bf-search-shared">Rechercher</label>
                        <input id="bf-search-shared" type="search" name="q" placeholder="Search..." autocomplete="off">
                    </form>
                    <button class="bf-theme-toggle" id="theme-toggle" type="button" title="Mode sombre">
                        <i class="bi bi-moon-fill" id="theme-icon"></i>
                    </button>
                    <span class="bf-top-avatar" aria-label="Utilisateur connecté"><?= $safeInitials ?></span>
                    <form method="post" action="/logout" class="bf-form-inline">
                        <?= CSRF::getTokenField() ?>
                        <button type="submit" class="bf-btn bf-btn-logout" aria-label="Déconnexion">
                            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                        </button>
                    </form>
                </div>
            </header>

            <div class="bf-content bf-content-no-padding">
                <?= $content ?? '' ?>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?= $scripts ?? '' ?>
</body>
</html>
