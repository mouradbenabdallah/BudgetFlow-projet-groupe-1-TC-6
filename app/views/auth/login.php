<?php
$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$email = $h($old['email'] ?? '');
?>
<div class="bf-card">
    <div>
        <span class="bf-kicker">Bon retour</span>
        <h1 class="bf-card-title">Connectez-vous à BudgetFlow</h1>
        <p class="bf-card-subtitle">
            Vous n'avez pas de compte ?
            <a class="bf-muted-link" href="/register">Créez-en un</a>
        </p>
    </div>

    <?php if (!empty($flashInfo)): ?>
        <div class="bf-alert bf-alert-info mt-4" role="status">
            <?= $h($flashInfo) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors['form'])): ?>
        <div class="bf-alert bf-alert-danger mt-4" role="alert">
            <?= $h($errors['form']) ?>
        </div>
    <?php endif; ?>

    <form class="bf-form" action="/login" method="post" novalidate>
        <?= CSRF::getTokenField() ?>

        <div class="bf-field">
            <label class="bf-label" for="email">Adresse email</label>
            <input
                class="bf-input <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                type="email"
                id="email"
                name="email"
                value="<?= $email ?>"
                placeholder="vous@exemple.com"
                autocomplete="email"
                required
            >
            <?php if (!empty($errors['email'])): ?>
                <div class="bf-alert bf-alert-danger bf-field-error" role="alert">
                    <?= $h($errors['email']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="bf-field">
            <label class="bf-label" for="password">Mot de passe</label>
            <div class="bf-password-wrap">
                <input
                    class="bf-input <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                    type="password"
                    id="password"
                    name="password"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    required
                >
                <button class="bf-password-toggle" type="button" data-password-toggle="password" aria-label="Afficher le mot de passe">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </button>
            </div>
            <?php if (!empty($errors['password'])): ?>
                <div class="bf-alert bf-alert-danger bf-field-error" role="alert">
                    <?= $h($errors['password']) ?>
                </div>
            <?php endif; ?>
        </div>

        <button class="bf-btn-primary" type="submit">
            Se connecter
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M5 12h14M13 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </form>

    <p class="bf-auth-footnote">Les sessions sont sécurisées et séparées selon le rôle utilisateur ou administrateur.</p>
</div>
