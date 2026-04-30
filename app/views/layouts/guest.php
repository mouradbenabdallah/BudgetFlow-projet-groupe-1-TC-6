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

    <script>
        document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                var target = document.getElementById(button.getAttribute('data-password-toggle'));
                if (!target) {
                    return;
                }

                var isPassword = target.getAttribute('type') === 'password';
                target.setAttribute('type', isPassword ? 'text' : 'password');
                button.setAttribute('aria-label', isPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
                button.innerHTML = isPassword
                    ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 3l18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10.58 10.58A2 2 0 0012 14a2 2 0 001.42-.58M9.88 5.09A9.77 9.77 0 0112 4c5 0 9 5 9 8a10.88 10.88 0 01-2.2 3.43M6.1 6.1C4.22 7.43 3 9.78 3 12c0 3 4 8 9 8a9.65 9.65 0 004.08-.93" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
                    : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>';
            });
        });
    </script>
</body>
</html>
