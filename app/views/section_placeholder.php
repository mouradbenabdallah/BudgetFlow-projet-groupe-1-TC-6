<?php
Auth::requireRole('user');
$sectionTitle = $sectionTitle ?? 'Section';
$sectionIcon = $sectionIcon ?? 'bi-construction';
$sectionMessage = $sectionMessage ?? 'Cette section est en cours de développement et sera bientôt disponible.';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? $sectionTitle, ENT_QUOTES, 'UTF-8') ?> - BudgetFlow</title>
    <link href="/style.css" rel="stylesheet">
</head>
<body class="bg-light bf-page-app">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <div class="bf-main">
        <header class="bf-topbar">
            <h1 class="h5 mb-0"><?= htmlspecialchars($sectionTitle, ENT_QUOTES, 'UTF-8') ?></h1>
            <div class="d-flex align-items-center gap-2">
                <form method="post" action="/logout" style="margin:0;">
                    <?= CSRF::getTokenField() ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-box-arrow-right"></i> D&eacute;connexion
                    </button>
                </form>
            </div>
        </header>
        <main class="bf-content">
            <div class="d-flex flex-column align-items-center justify-content-center text-center py-5">
                <i class="bi <?= htmlspecialchars($sectionIcon, ENT_QUOTES, 'UTF-8') ?> text-muted mb-3" style="font-size:3rem;"></i>
                <h2 class="h4 mb-2"><?= htmlspecialchars($sectionTitle, ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="text-muted mb-4"><?= htmlspecialchars($sectionMessage, ENT_QUOTES, 'UTF-8') ?></p>
                <a href="/dashboard" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Retour au tableau de bord
                </a>
            </div>
        </main>
    </div>
</body>
</html>
