<?php
// Vue profil utilisateur — stub pour le prompt 6.
$e = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$user = $user ?? [];
?>

<?php if (!empty($flashSuccess)): ?>
<div class="bf-alert bf-alert-success" role="alert">
    <i class="bi bi-check-circle"></i>
    <?= $e($flashSuccess) ?>
</div>
<?php endif; ?>
<?php if (!empty($flashDanger)): ?>
<div class="bf-alert bf-alert-danger" role="alert">
    <i class="bi bi-x-circle"></i>
    <?= $e($flashDanger) ?>
</div>
<?php endif; ?>
<?php if (!empty($flashInfo)): ?>
<div class="bf-alert bf-alert-info" role="alert">
    <i class="bi bi-info-circle"></i>
    <?= $e($flashInfo) ?>
</div>
<?php endif; ?>

<div class="bf-page-header">
    <div>
        <h2 class="h4 mb-1" style="color: var(--text-primary); font-family: 'DM Sans', sans-serif;">
            Mon profil
        </h2>
        <p style="color: var(--text-secondary); font-size: 14px; margin: 0;">
            Gérez vos informations personnelles et la sécurité de votre compte.
        </p>
    </div>
</div>

<div class="row g-3">
    <!-- Informations du compte -->
    <div class="col-lg-6">
        <div class="bf-card">
            <h3 class="mb-3" style="font-family: 'DM Sans', sans-serif; font-size: 16px; font-weight: 600; color: var(--text-primary);">
                <i class="bi bi-person me-2" style="color: var(--accent);"></i>Informations
            </h3>

            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="bf-avatar" style="width: 56px; height: 56px; font-size: 20px;">
                    <?= $e(mb_strtoupper(mb_substr((string) ($user['name'] ?? 'U'), 0, 1, 'UTF-8'), 'UTF-8')) ?>
                </div>
                <div>
                    <p class="mb-0 fw-600" style="color: var(--text-primary); font-weight: 600;"><?= $e($user['name'] ?? '') ?></p>
                    <p class="mb-0" style="color: var(--text-secondary); font-size: 13px;"><?= $e($user['email'] ?? '') ?></p>
                    <span class="bf-badge bf-badge-user mt-1"><?= $e($user['role'] ?? 'user') ?></span>
                </div>
            </div>

            <div class="bf-empty-state" style="padding: 24px;">
                <i class="bi bi-pencil-square" style="font-size: 28px;"></i>
                <p style="font-size: 13px;">La modification du profil sera disponible dans le prompt 6.</p>
            </div>
        </div>
    </div>

    <!-- Sécurité -->
    <div class="col-lg-6">
        <div class="bf-card">
            <h3 class="mb-3" style="font-family: 'DM Sans', sans-serif; font-size: 16px; font-weight: 600; color: var(--text-primary);">
                <i class="bi bi-shield-lock me-2" style="color: var(--color-warning);"></i>Sécurité
            </h3>
            <div class="bf-empty-state" style="padding: 24px;">
                <i class="bi bi-lock" style="font-size: 28px;"></i>
                <p style="font-size: 13px;">Le changement de mot de passe et la suppression de compte seront disponibles dans le prompt 6.</p>
            </div>
        </div>
    </div>
</div>
