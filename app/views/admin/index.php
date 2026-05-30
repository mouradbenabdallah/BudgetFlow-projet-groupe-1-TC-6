<?php
// Vue tableau de bord admin — stub pour le prompt 5.
?>
<div class="bf-page-header">
    <div>
        <h2 class="h4 mb-1" style="color: var(--text-primary); font-family: 'DM Sans', sans-serif;">
            Bienvenue dans le panneau d'administration
        </h2>
        <p style="color: var(--text-secondary); font-size: 14px; margin: 0;">
            Supervisez les utilisateurs, les rôles et les budgets partagés.
        </p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="bf-card text-center">
            <i class="bi bi-people fs-2 mb-2" style="color: var(--accent);"></i>
            <h3 class="h6 mb-1" style="color: var(--text-secondary); font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Utilisateurs</h3>
            <a href="/admin/users" class="bf-btn-primary mt-3">Gérer</a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="bf-card text-center">
            <i class="bi bi-wallet2 fs-2 mb-2" style="color: var(--color-income);"></i>
            <h3 class="h6 mb-1" style="color: var(--text-secondary); font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Budgets partagés</h3>
            <a href="/admin/budgets" class="bf-btn-primary mt-3">Superviser</a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="bf-card text-center">
            <i class="bi bi-clock-history fs-2 mb-2" style="color: var(--color-warning);"></i>
            <h3 class="h6 mb-1" style="color: var(--text-secondary); font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Comptes en attente</h3>
            <p style="color: var(--text-muted); font-size: 13px; margin-top: 8px;">Disponible dans le prompt 5</p>
        </div>
    </div>
</div>

<div class="bf-card">
    <div class="bf-empty-state">
        <i class="bi bi-tools"></i>
        <p>Le panneau d'administration complet sera implémenté dans le prompt 5.</p>
    </div>
</div>
