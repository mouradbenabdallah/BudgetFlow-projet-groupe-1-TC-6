<?php
// Vue gestion des utilisateurs — stub pour le prompt 5.
$e = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
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
            Gestion des utilisateurs
        </h2>
        <p style="color: var(--text-secondary); font-size: 14px; margin: 0;">
            Validation des comptes, gestion des rôles, suppressions.
        </p>
    </div>
    <a href="/admin" class="bf-btn-sm">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<div class="bf-card">
    <div class="bf-empty-state">
        <i class="bi bi-people"></i>
        <p>La gestion complète des utilisateurs sera implémentée dans le prompt 5.</p>
        <p style="font-size: 12px; color: var(--text-muted);">
            Fonctionnalités prévues : validation de comptes, changement de rôles,
            traitement des demandes de suppression.
        </p>
    </div>
</div>
