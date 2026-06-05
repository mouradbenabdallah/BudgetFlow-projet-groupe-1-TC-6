<?php
/**
 * Vue — Formulaire d'inscription.
 *
 * Utilise les classes .bf-guest-* définies dans layouts/guest.php.
 * Reçoit de AuthController::showRegister() / AuthController::register() :
 *   $errors  — tableau de messages d'erreur par champ + clé 'form'
 *   $old     — anciennes valeurs (name, email) pour ré-affichage après erreur
 *   $flashInfo — message flash (compte en attente de validation)
 */
$h    = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$name  = $h($old['name']  ?? '');
$email = $h($old['email'] ?? '');
?>
<div class="bf-guest-card">

    <span class="bf-guest-card-eyebrow">Créer un compte</span>
    <h1 class="bf-guest-card-title">Rejoignez CASHtoCASH</h1>
    <p class="bf-guest-card-sub">
        Vous avez déjà un compte ?
        <a href="/login">Connectez-vous</a>
    </p>

    <?php if (!empty($flashInfo)): ?>
    <div class="bf-guest-error" role="status" style="background:rgba(0,108,250,0.1);border-color:rgba(0,108,250,0.3);color:#5b9cf6;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= $h($flashInfo) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors['form'])): ?>
    <div class="bf-guest-error" role="alert">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= $h($errors['form']) ?>
    </div>
    <?php endif; ?>

    <form action="/register" method="post" novalidate>
        <?= CSRF::getTokenField() ?>

        <!-- Nom complet -->
        <div class="bf-guest-field">
            <label class="bf-guest-label" for="name">Nom complet</label>
            <input
                class="bf-guest-input <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                type="text" id="name" name="name"
                value="<?= $name ?>"
                placeholder="Alex Johnson"
                autocomplete="name"
                required
            >
            <?php if (!empty($errors['name'])): ?>
            <p style="font-size:12px;color:#e11d48;margin-top:4px"><?= $h($errors['name']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Email -->
        <div class="bf-guest-field">
            <label class="bf-guest-label" for="email">Adresse email</label>
            <input
                class="bf-guest-input <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                type="email" id="email" name="email"
                value="<?= $email ?>"
                placeholder="vous@exemple.com"
                autocomplete="email"
                required
            >
            <?php if (!empty($errors['email'])): ?>
            <p style="font-size:12px;color:#e11d48;margin-top:4px"><?= $h($errors['email']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Mot de passe -->
        <div class="bf-guest-field">
            <label class="bf-guest-label" for="password">Mot de passe</label>
            <div class="bf-guest-pw-wrap">
                <input
                    class="bf-guest-input <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                    type="password" id="password" name="password"
                    placeholder="••••••••"
                    autocomplete="new-password"
                    style="padding-right:44px"
                    required
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

        <!-- Confirmer le mot de passe -->
        <div class="bf-guest-field">
            <label class="bf-guest-label" for="password_confirmation">Confirmer le mot de passe</label>
            <div class="bf-guest-pw-wrap">
                <input
                    class="bf-guest-input <?= isset($errors['password_confirmation']) ? 'is-invalid' : '' ?>"
                    type="password" id="password_confirmation" name="password_confirmation"
                    placeholder="••••••••"
                    autocomplete="new-password"
                    style="padding-right:44px"
                    required
                >
                <button class="bf-guest-pw-toggle" type="button" data-password-toggle="password_confirmation" aria-label="Afficher le mot de passe">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </button>
            </div>
            <?php if (!empty($errors['password_confirmation'])): ?>
            <p style="font-size:12px;color:#e11d48;margin-top:4px"><?= $h($errors['password_confirmation']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Bouton d'envoi -->
        <button class="bf-guest-submit" type="submit">
            Créer le compte
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M5 12h14M13 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </form>

    <p class="bf-guest-footnote">
        Après inscription, un administrateur doit valider votre compte avant la première connexion.
    </p>
</div>
