<?php
$pageTitle = htmlspecialchars(($title ?? 'Authentification') . ' - BudgetFlow', ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="/style.css" rel="stylesheet">
</head>
<body class="bf-page-auth">
    <main class="bf-auth-shell">
        <aside class="bf-auth-panel" aria-label="Présentation BudgetFlow">
            <div class="bf-brand" aria-label="BudgetFlow">
                <span class="bf-brand-mark" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" focusable="false">
                        <path d="M10 2C6.13 2 3 5.13 3 9c0 2.38 1.19 4.47 3 5.74V17h8v-2.26C15.81 13.47 17 11.38 17 9c0-3.87-3.13-7-7-7z" fill="currentColor"/>
                    </svg>
                </span>
                <span>Budget<span class="bf-brand-accent">Flow</span></span>
            </div>

            <div class="bf-panel-content">
                <span class="bf-kicker">Finances personnelles</span>
                <h2 class="bf-panel-title">Votre budget avance <span class="bf-brand-accent">avec vous</span></h2>
                <p class="bf-panel-copy">Suivez vos dépenses, préparez vos objectifs et gardez vos budgets partagés lisibles dans une interface sécurisée.</p>
                <div class="bf-stat-grid" aria-label="Statistiques BudgetFlow">
                    <div>
                        <div class="bf-stat-value">2.4M+</div>
                        <div class="bf-stat-label">Utilisateurs</div>
                    </div>
                    <div>
                        <div class="bf-stat-value">3.6M TND</div>
                        <div class="bf-stat-label">Budgets</div>
                    </div>
                    <div>
                        <div class="bf-stat-value">4.9/5</div>
                        <div class="bf-stat-label">Note</div>
                    </div>
                </div>
            </div>

            <div class="bf-quote">
                <p>"BudgetFlow m'a aidé à voir clairement où allait mon argent et à reprendre le contrôle de mes objectifs."</p>
            </div>
        </aside>

        <section class="bf-auth-main">
            <div class="bf-auth-stack">
                <div class="bf-mobile-brand">
                    <div class="bf-brand" aria-label="BudgetFlow">
                        <span class="bf-brand-mark" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 20 20" fill="none" focusable="false">
                                <path d="M10 2C6.13 2 3 5.13 3 9c0 2.38 1.19 4.47 3 5.74V17h8v-2.26C15.81 13.47 17 11.38 17 9c0-3.87-3.13-7-7-7z" fill="currentColor"/>
                            </svg>
                        </span>
                        <span>Budget<span class="bf-brand-accent">Flow</span></span>
                    </div>
                </div>

                <?= $content ?? '' ?>
            </div>
        </section>
    </main>

    <script src="/script.js"></script>
</body>
</html>
