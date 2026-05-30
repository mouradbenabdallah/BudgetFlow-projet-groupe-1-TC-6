<?php
$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$email = $h($old['email'] ?? '');
?>
<div class="bf-guest-card">

    <span class="bf-guest-card-eyebrow">Bon Retour</span>
    <h1 class="bf-guest-card-title">Connectez-vous à BudgetFlow</h1>
    <p class="bf-guest-card-sub">
        Vous n'avez pas de compte ?
        <a href="/register">Créez-en un</a>
    </p>

    <?php if (!empty($flashInfo)): ?>
    <div class="bf-guest-error" role="status">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= $h($flashInfo) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors['form'])): ?>
    <div class="bf-guest-error" role="alert">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= $h($errors['form']) ?>
    </div>
    <?php endif; ?>

    <form action="/login" method="post" novalidate>
        <?= CSRF::getTokenField() ?>

        <!-- Email -->
        <div class="bf-guest-field">
            <label class="bf-guest-label" for="email">Adresse Email</label>
            <input
                class="bf-guest-input <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                type="email" id="email" name="email"
                value="<?= $email ?>"
                placeholder="vous@exemple.com"
                autocomplete="email" required
            >
            <?php if (!empty($errors['email'])): ?>
            <p style="font-size:12px;color:#e11d48;margin-top:4px"><?= $h($errors['email']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Password -->
        <div class="bf-guest-field">
            <label class="bf-guest-label" for="password">Mot de Passe</label>
            <div class="bf-guest-pw-wrap">
                <input
                    class="bf-guest-input <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                    type="password" id="password" name="password"
                    placeholder="••••••••"
                    autocomplete="current-password" required
                    style="padding-right:44px"
                >
                <button class="bf-guest-pw-toggle" type="button" data-password-toggle="password" aria-label="Afficher le mot de passe">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </button>
            </div>
            <?php if (!empty($errors['password'])): ?>
            <p style="font-size:12px;color:#e11d48;margin-top:4px"><?= $h($errors['password']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Submit -->
        <button class="bf-guest-submit" type="submit">
            Se Connecter
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M5 12h14M13 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>

        <!-- Divider -->
        <div class="bf-guest-divider">
            <hr><span>ou continuer avec</span><hr>
        </div>

        <!-- Social -->
        <div class="bf-guest-social">
            <button type="button" class="bf-guest-social-btn">Google</button>
            <button type="button" class="bf-guest-social-btn">GitHub</button>
        </div>
    </form>
</div>

<!-- Demo hint -->
<div class="bf-guest-demo">
    Démo : <strong style="color:#b8c4c2">admin@budgetflow.local</strong> / <strong style="color:#b8c4c2">password</strong>
</div>
