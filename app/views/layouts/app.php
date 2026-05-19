<?php
// Layout principal pour toutes les pages accessibles après connexion.
$layoutUser = $user ?? Auth::getUser() ?? [];
$pageTitleText = (string) ($pageTitle ?? $title ?? 'BudgetFlow');
$documentTitle = htmlspecialchars($pageTitleText . ' - BudgetFlow', ENT_QUOTES, 'UTF-8');
$safePageTitle = htmlspecialchars($pageTitleText, ENT_QUOTES, 'UTF-8');
$nameParts = preg_split('/\s+/', trim((string) ($layoutUser['name'] ?? 'Utilisateur'))) ?: [];
$displayName = $nameParts[0] ?? 'Utilisateur';
$safeUserName = htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8');
$initials = '';

foreach (array_slice($nameParts, 0, 2) as $part) {
    if ($part === '') {
        continue;
    }

    $initials .= function_exists('mb_substr')
        ? mb_substr($part, 0, 1, 'UTF-8')
        : substr($part, 0, 1);
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
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/style.css?v=3" rel="stylesheet">
    <link rel="icon" href="/img/logo_fac.webp" type="image/webp">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
    <script src="/script.js"></script>
</head>
<body class="bf-page-app">
    <div class="bf-app-shell">
        <?php require __DIR__ . '/../partials/sidebar.php'; ?>

        <main class="bf-main">
            <header class="bf-topbar">
                <div>
                    <h1 class="bf-page-title"><?= $safePageTitle ?></h1>
                    <p class="bf-page-greeting">Bon retour, <?= $safeUserName ?> <span aria-hidden="true">👋</span></p>
                </div>

                <div class="bf-topbar-actions">
                    <form class="bf-search" role="search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <label class="visually-hidden" for="bf-search-input">Rechercher</label>
                        <input id="bf-search-input" type="search" name="q" placeholder="Search..." autocomplete="off">
                    </form>

                    <button class="bf-icon-button" type="button" aria-label="Notifications">
                        <i class="bi bi-bell" aria-hidden="true"></i>
                        <span class="bf-notification-dot" aria-hidden="true"></span>
                    </button>

                    <span class="bf-top-avatar" aria-label="Utilisateur connecté"><?= $safeInitials ?></span>

                    <form method="post" action="/logout" style="margin:0;">
                        <?= CSRF::getTokenField() ?>
                        <button type="submit" class="bf-btn bf-btn-logout" aria-label="Déconnexion">
                            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                        </button>
                    </form>
                </div>
            </header>

            <div class="bf-content">
                <?= $content ?? '' ?>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/script.js"></script>
    <?= $scripts ?? '' ?>
</body>
</html>
