<?php
$isLoggedIn = Auth::isLoggedIn();
$user = Auth::getUser();
$dashboardUrl = ($user['role'] ?? 'user') === 'admin' ? '/admin' : '/dashboard';
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BudgetFlow - Vos finances sous contrôle</title>
    <meta name="description"
        content="BudgetFlow aide les utilisateurs à suivre leurs dépenses, gérer des budgets partagés et atteindre leurs objectifs financiers.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="/style.css" rel="stylesheet">
</head>

<body class="bf-page-home">
    <header class="bf-header">
        <nav class="bf-container bf-nav" aria-label="Navigation principale">
            <a href="/" class="bf-brand" aria-label="BudgetFlow">
                <span class="bf-brand-mark" aria-hidden="true"></span>
                <span>Budget<span>Flow</span></span>
            </a>

            <div class="bf-nav-links">
                <a href="#fonctionnalites">Fonctionnalités</a>
                <a href="#securite">Sécurité</a>
                <a href="#blog">Blog</a>
            </div>

            <div class="bf-actions">
                <a class="bf-btn"
                    href="<?= $isLoggedIn ? $dashboardUrl : '/login' ?>"><?= $isLoggedIn ? 'Tableau de bord' : 'Connexion' ?></a>
                <a class="bf-btn bf-btn-primary" href="/register">Commencer Gratuitement</a>
            </div>
        </nav>
    </header>

    <main>
        <section class="bf-hero">
            <div class="bf-container bf-hero-inner">
                <span class="bf-pill">Maintenant en bêta publique</span>
                <h1>Vos finances, <span class="text-green">enfin sous contrôle</span></h1>
                <p>BudgetFlow est la plateforme collaborative de finances personnelles qui vous aide à suivre vos
                    dépenses, gérer des budgets partagés et atteindre vos objectifs financiers — ensemble.</p>

                <div class="bf-hero-actions">
                    <a class="bf-btn bf-btn-primary" href="/register">Commencer Gratuitement →</a>
                </div>

                <div class="bf-dashboard" aria-label="Aperçu du tableau de bord BudgetFlow">
                    <img src="/img/dashboard.png" alt="Tableau de bord BudgetFlow" class="bf-dashboard-img">
                </div>

                <div class="bf-dashboard" aria-label="Aperçu du tableau de bord BudgetFlow">
                    <div class="bf-window-top">
                        <span class="bf-dot bf-dot-danger"></span>
                        <span class="bf-dot bf-dot-warning"></span>
                        <span class="bf-dot bf-dot-success"></span>
                        <div class="bf-address">app.budgetflow.io/dashboard</div>
                    </div>
                    <div class="bf-dashboard-body">
                        <aside class="bf-sidebar-mini" aria-hidden="true">
                            <span class="bf-side-icon active"></span>
                            <span class="bf-side-icon"></span>
                            <span class="bf-side-icon"></span>
                            <span class="bf-side-icon"></span>
                        </aside>

                        <div class="bf-mock-grid">
                            <div class="bf-metric">
                                <small>Revenus Mensuels</small>
                                <strong>17,700 TND</strong>
                                <span>+12%</span>
                            </div>
                            <div class="bf-metric">
                                <small>Dépenses Totales</small>
                                <strong>9,645 TND</strong>
                                <span>-8%</span>
                            </div>
                            <div class="bf-metric">
                                <small>Solde Net</small>
                                <strong>37,350 TND</strong>
                                <span>+24%</span>
                            </div>

                            <div class="bf-chart-row">
                                <div class="bf-chart-card">
                                    <div class="bf-bars">
                                        <span class="bf-bar bf-bar-36"></span>
                                        <span class="bf-bar bf-bar-48"></span>
                                        <span class="bf-bar bf-bar-38"></span>
                                        <span class="bf-bar bf-bar-58"></span>
                                        <span class="bf-bar active bf-bar-72"></span>
                                    </div>
                                </div>
                                <div class="bf-chart-card bf-category-mini">
                                    <div class="bf-donut"></div>
                                    <div class="bf-legend">
                                        <span>● Alimentation</span>
                                        <span>● Logement</span>
                                        <span>● Transport</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bf-stats-band">
            <div class="bf-container bf-stats">
                <div class="bf-stat"><strong>2k+</strong><span>Utilisateurs Actifs</span></div>
                <div class="bf-stat"><strong>3.6M TND</strong><span>Budgets Gérés</span></div>
                <div class="bf-stat"><strong>99.9%</strong><span>Disponibilité SLA</span></div>
                <div class="bf-stat"><strong>4.9★</strong><span>Note Moyenne</span></div>
            </div>
        </section>

        <section id="fonctionnalites" class="bf-section">
            <div class="bf-container">
                <div class="section-head">
                    <span class="section-label">Fonctionnalités</span>
                    <h2>Tout ce dont vous avez besoin pour <span class="underline-green">maîtriser votre argent</span>
                    </h2>
                    <p>Du suivi des dépenses quotidiennes à la gestion des budgets familiaux partagés, BudgetFlow vous
                        donne les outils pour un contrôle financier complet.</p>
                </div>

                <div class="features-grid">
                    <article class="feature-card">
                        <small>Insights</small>
                        <div class="feature-icon">C</div>
                        <h3>Analyses Intelligentes</h3>
                        <p>Visualisez vos habitudes de dépenses avec des graphiques interactifs et des informations
                            financières en temps réel.</p>
                    </article>
                    <article class="feature-card">
                        <small>Collaboration</small>
                        <div class="feature-icon">A</div>
                        <h3>Budgets Partagés</h3>
                        <p>Gérez les dépenses de groupe sans effort avec colocataires, partenaires ou équipes.</p>
                    </article>
                    <article class="feature-card">
                        <small>Notifications</small>
                        <div class="feature-icon">B</div>
                        <h3>Alertes Budgétaires</h3>
                        <p>Recevez des alertes intelligentes lorsque vous approchez des limites budgétaires.</p>
                    </article>
                    <article id="securite" class="feature-card">
                        <small>Sécurité</small>
                        <div class="feature-icon">O</div>
                        <h3>Sécurité Bancaire</h3>
                        <p>Vos données financières sont protégées par des sessions sécurisées et des requêtes préparées.
                        </p>
                    </article>
                    <article class="feature-card">
                        <small>Objectifs</small>
                        <div class="feature-icon">↗</div>
                        <h3>Suivi des Objectifs</h3>
                        <p>Définissez des objectifs d'épargne et suivez vos progrès en temps réel.</p>
                    </article>
                    <article class="feature-card">
                        <small>Sync</small>
                        <div class="feature-icon">↺</div>
                        <h3>Synchronisation Instantanée</h3>
                        <p>Connectez tous vos comptes en quelques secondes et gardez une vision à jour.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="bf-section dark">
            <div class="bf-container">
                <div class="section-head">
                    <span class="section-label">Comment ça marche</span>
                    <h2>Opérationnel en quelques minutes</h2>
                </div>

                <div class="steps-grid">
                    <article class="step-card">
                        <strong>01</strong>
                        <h3>Créez votre compte</h3>
                        <p>Inscrivez-vous en quelques secondes. Aucune carte bancaire requise pour le plan gratuit.</p>
                    </article>
                    <article class="step-card">
                        <strong>02</strong>
                        <h3>Configurez vos budgets</h3>
                        <p>Définissez les catégories et limites qui correspondent à votre style de vie.</p>
                    </article>
                    <article class="step-card">
                        <strong>03</strong>
                        <h3>Suivez et progressez</h3>
                        <p>Ajoutez des transactions et surveillez les informations exploitables.</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="blog" class="bf-section soft">
            <div class="bf-container">
                <div class="section-head">
                    <span class="section-label">Témoignages</span>
                    <h2>Aimé par des milliers d'utilisateurs</h2>
                </div>

                <div class="testimonials-grid">
                    <article class="testimonial-card">
                        <div class="stars">★★★★★</div>
                        <p>"BudgetFlow a complètement changé ma façon de gérer mon argent. La fonction de budgets
                            partagés est révolutionnaire."</p>
                        <h4>Sarah Chen</h4>
                        <span>Designer Produit chez Stripe</span>
                    </article>
                    <article class="testimonial-card">
                        <div class="stars">★★★★★</div>
                        <p>"Les analyses sont incroyablement détaillées. Je comprends enfin où va mon argent chaque
                            mois."</p>
                        <h4>Marcus Rivera</h4>
                        <span>Ingénieur Logiciel chez Google</span>
                    </article>
                    <article class="testimonial-card">
                        <div class="stars">★★★★★</div>
                        <p>"Gérer des revenus irréguliers était stressant. BudgetFlow le rend simple avec des
                            ajustements intelligents."</p>
                        <h4>Emma Thompson</h4>
                        <span>Consultante Indépendante</span>
                    </article>
                </div>
            </div>
        </section>

        <section id="tarifs" class="bf-cta">
            <div class="bf-container">
                <span class="section-label">Commencez aujourd'hui</span>
                <h2>Prenez le contrôle de votre <span class="text-green">avenir financier</span></h2>
                <p>Rejoignez 2,4 millions d'utilisateurs qui font confiance à BudgetFlow pour gérer leurs finances.
                    Commencez gratuitement, mettez à niveau quand vous êtes prêt.</p>
                <a class="bf-btn bf-btn-primary" href="/register">Créer un Compte Gratuit →</a>
            </div>
        </section>
    </main>

    <footer class="bf-footer">
        <div class="bf-container">
            <div class="footer-grid">
                <div>
                    <a href="/" class="bf-brand">
                        <span class="bf-brand-mark" aria-hidden="true"></span>
                        <span>Budget<span>Flow</span></span>
                    </a>
                    <p class="footer-copy">La plateforme moderne pour la gestion budgétaire personnelle et
                        collaborative.</p>
                </div>
                <div class="footer-col">
                    <h3>Produit</h3>
                    <a href="#fonctionnalites">Fonctionnalités</a>
                    <a href="#tarifs">Tarifs</a>
                    <a href="#securite">Sécurité</a>
                    <a href="/">Feuille de route</a>
                </div>
                <div class="footer-col">
                    <h3>Entreprise</h3>
                    <a href="/">À propos</a>
                    <a href="#blog">Blog</a>
                    <a href="/">Carrières</a>
                    <a href="/">Presse</a>
                </div>
                <div class="footer-col">
                    <h3>Support</h3>
                    <a href="/">Centre d'aide</a>
                    <a href="/">Docs API</a>
                    <a href="/">Contact</a>
                    <a href="/">Statut</a>
                </div>
            </div>
            <div class="footer-bottom">
                <span>© 2026 BudgetFlow, Inc. Tous droits réservés.</span>
                <span>Politique de confidentialité · Conditions d'utilisation · Paramètres de cookies</span>
            </div>
        </div>
    </footer>
</body>

</html>