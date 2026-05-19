<?php
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$formatDT = static fn (mixed $amount): string => number_format((float) $amount, 0, ',', ' ') . ' DT';
$formatDTPrecise = static fn (mixed $amount): string => number_format((float) $amount, 0, ',', ' ');

$budget = $budget ?? [];
$isOwner = $isOwner ?? false;
$members = $members ?? [];
$transactions = $transactions ?? [];
$categoryBreakdown = $categoryBreakdown ?? [];
$percent = $percent ?? 0;
$status = $status ?? 'ok';
$typeFilter = $typeFilter ?? null;
$userId = (int) ($user['id'] ?? 0);

$periodLabels = ['weekly' => 'Hebdomadaire', 'monthly' => 'Mensuel', 'custom' => 'Personnalisé'];
$spent = (float) ($budget['spent'] ?? 0);
$income = (float) ($budget['income'] ?? 0);
$balance = (float) ($budget['balance'] ?? 0);
$limit = $budget['amount_limit'] !== null ? (float) $budget['amount_limit'] : null;
$remaining = $limit !== null ? $limit - $spent : null;

function bfShowGetInitials(string $name): string {
    $parts = explode(' ', trim($name), 2);
    $i = '';
    if (!empty($parts[0])) $i .= strtoupper(substr($parts[0], 0, 1));
    if (!empty($parts[1])) $i .= strtoupper(substr($parts[1], 0, 1));
    elseif (!empty($parts[0]) && strlen($parts[0]) > 1) $i = strtoupper(substr($parts[0], 0, 2));
    return $i ?: '?';
}

$progressColor = match ($status) {
    'danger' => '#e11d48',
    'warning' => '#f59e0b',
    default => '#00ed64',
};

$chartColors = [];
$chartLabels = [];
$chartValues = [];
foreach ($categoryBreakdown as $c) {
    $chartColors[] = preg_match('/^#[0-9A-Fa-f]{6}$/', ($c['color'] ?? '')) ? $c['color'] : '#007f5f';
    $chartLabels[] = $c['name'] ?? '';
    $chartValues[] = (float) ($c['total'] ?? 0);
}

$ownerData = ['id' => $userId, 'name' => $user['name'] ?? 'Propriétaire', 'email' => $user['email'] ?? ''];
$allMembers = array_merge([$ownerData], $members);
$memberCount = count($allMembers);
?>

<style>
.bf-show-page { background: #f5f7fa; min-height: 100vh; }
.bf-show-page .bf-topbar { background: #ffffff; border-bottom: 1px solid #e2e8f0; }

.bf-show-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.bf-show-title { font-size: 22px; font-weight: 700; color: #0f172a; margin: 0 0 4px; }
.bf-show-subtitle { font-size: 13px; color: #64748b; margin: 0; display: flex; align-items: center; gap: 8px; }
.bf-show-badge {
    display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px;
    border-radius: 999px; font-size: 11px; font-weight: 600;
}
.bf-show-badge.shared { background: rgba(99,102,241,0.1); color: #6366f1; }
.bf-show-badge.personal { background: rgba(0,127,95,0.1); color: #007f5f; }

.bf-show-actions { display: flex; gap: 8px; }
.bf-show-btn {
    display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px;
    border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer;
    text-decoration: none; border: 1px solid #e2e8f0; background: #fff; color: #64748b;
    transition: all 0.2s;
}
.bf-show-btn:hover { border-color: #cbd5e1; color: #0f172a; }
.bf-show-btn.danger { border-color: #fca5a5; color: #dc2626; }
.bf-show-btn.danger:hover { background: #fef2f2; }

.bf-show-alert {
    padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; font-weight: 600;
    display: flex; align-items: center; gap: 10px;
}
.bf-show-alert.warning { background: rgba(255,181,71,0.1); border: 1px solid rgba(255,181,71,0.3); color: #FFB547; }
.bf-show-alert.danger { background: rgba(255,77,77,0.1); border: 1px solid rgba(255,77,77,0.3); color: #FF4D4D; }

.bf-show-kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
.bf-show-kpi-card {
    background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.bf-show-kpi-card .kpi-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 8px; }
.bf-show-kpi-card .kpi-value { font-size: 24px; font-weight: 800; color: #0f172a; margin: 0; font-family: var(--bf-font-mono); }
.bf-show-kpi-card .kpi-icon { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; font-size: 16px; margin-bottom: 12px; }

.bf-show-budget-panel {
    background: var(--bf-sidebar); color: #fff; border-radius: 16px; padding: 28px;
    margin-bottom: 20px; box-shadow: 0 4px 20px rgba(0,30,43,0.3);
}
.bf-show-budget-panel-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.bf-show-budget-panel-name { font-size: 22px; font-weight: 800; margin: 0 0 4px; }
.bf-show-budget-panel-meta { font-size: 13px; color: rgba(216,231,228,0.6); margin: 0; }
.bf-show-budget-panel-amount { text-align: right; }
.bf-show-budget-panel-spent { font-size: 32px; font-weight: 800; font-family: var(--bf-font-mono); margin: 0; }
.bf-show-budget-panel-limit { font-size: 13px; color: rgba(216,231,228,0.5); margin: 0; }

.bf-show-progress-track { height: 12px; border-radius: 999px; background: rgba(255,255,255,0.1); overflow: hidden; margin-bottom: 12px; }
.bf-show-progress-fill { height: 100%; border-radius: 999px; transition: width 0.3s; }
.bf-show-progress-info { display: flex; justify-content: space-between; font-size: 14px; }
.bf-show-progress-pct { font-weight: 700; }
.bf-show-progress-remaining { color: rgba(216,231,228,0.6); }

.bf-show-members-panel {
    background: var(--bf-sidebar); color: #fff; border-radius: 16px; padding: 28px;
    margin-bottom: 20px; box-shadow: 0 4px 20px rgba(0,30,43,0.3);
}
.bf-show-panel-title { font-size: 13px; font-weight: 600; color: rgba(216,231,228,0.4); text-transform: uppercase; letter-spacing: 1px; margin: 0 0 4px; }
.bf-show-panel-heading { font-size: 18px; font-weight: 700; margin: 0 0 20px; display: flex; align-items: center; gap: 8px; }
.bf-show-panel-heading i { color: var(--bf-green); }

.bf-show-member-row {
    display: flex; align-items: center; gap: 14px; padding: 14px 16px;
    background: rgba(255,255,255,0.06); border-radius: 12px; margin-bottom: 8px;
}
.bf-show-member-avatar {
    width: 40px; height: 40px; border-radius: 50%; display: grid; place-items: center;
    font-size: 13px; font-weight: 700; flex-shrink: 0; position: relative;
}
.bf-show-member-avatar.owner { background: #007f5f; color: #fff; }
.bf-show-member-avatar.member { background: #334155; color: #E2E8F0; }
.bf-show-member-avatar .owner-badge {
    position: absolute; top: -2px; right: -2px; width: 16px; height: 16px;
    background: #f59e0b; border-radius: 50%; border: 2px solid var(--bf-sidebar);
    display: grid; place-items: center; font-size: 9px; color: #fff;
}
.bf-show-member-info { flex: 1; min-width: 0; }
.bf-show-member-name { font-size: 14px; font-weight: 600; margin: 0; }
.bf-show-member-email { font-size: 12px; color: rgba(216,231,228,0.5); margin: 0; }
.bf-show-member-remove {
    background: none; border: none; color: rgba(216,231,228,0.4); cursor: pointer;
    padding: 6px; border-radius: 8px; font-size: 16px; transition: all 0.2s;
}
.bf-show-member-remove:hover { color: #e11d48; background: rgba(225,29,72,0.1); }

.bf-show-invite-form { display: flex; gap: 8px; margin-top: 16px; }
.bf-show-invite-input {
    flex: 1; padding: 10px 14px; border: 1px solid rgba(255,255,255,0.15);
    border-radius: 10px; background: rgba(255,255,255,0.06); color: #fff;
    font-size: 14px; outline: none;
}
.bf-show-invite-input::placeholder { color: rgba(216,231,228,0.3); }
.bf-show-invite-input:focus { border-color: var(--bf-green); }
.bf-show-invite-btn {
    padding: 10px 20px; background: var(--bf-green-dark); color: #fff; border: none;
    border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer;
    white-space: nowrap; transition: all 0.2s;
}
.bf-show-invite-btn:hover { background: #009a6e; }

.bf-show-transactions-panel {
    background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.bf-show-tx-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.bf-show-tx-title { font-size: 18px; font-weight: 700; color: #0f172a; margin: 0; }

.bf-show-tx-tabs { display: flex; gap: 4px; margin-bottom: 16px; }
.bf-show-tx-tab {
    padding: 8px 18px; border-radius: 8px; font-size: 13px; font-weight: 500;
    text-decoration: none; transition: all 0.2s;
}
.bf-show-tx-tab.active { background: var(--bf-sidebar); color: #fff; }
.bf-show-tx-tab:not(.active) { background: transparent; border: 1px solid #e2e8f0; color: #64748b; }
.bf-show-tx-tab:not(.active):hover { border-color: #cbd5e1; color: #0f172a; }
.bf-show-tx-tab.income.active { background: rgba(0,237,100,0.15); color: #007f5f; border-color: rgba(0,237,100,0.2); }
.bf-show-tx-tab.expense.active { background: rgba(225,29,72,0.1); color: #dc2626; border-color: rgba(225,29,72,0.15); }

.bf-show-tx-row {
    display: flex; align-items: center; gap: 14px; padding: 16px 0;
    border-bottom: 1px solid #f1f5f9;
}
.bf-show-tx-row:last-child { border-bottom: none; }
.bf-show-tx-avatar {
    width: 40px; height: 40px; border-radius: 50%; display: grid; place-items: center;
    font-size: 12px; font-weight: 700; background: rgba(99,102,241,0.1); color: #6366f1; flex-shrink: 0;
}
.bf-show-tx-info { flex: 1; min-width: 0; }
.bf-show-tx-desc { font-size: 14px; font-weight: 600; color: #0f172a; margin: 0; }
.bf-show-tx-meta { font-size: 12px; color: #94a3b8; margin: 4px 0 0; }
.bf-show-tx-meta strong { color: #007f5f; }
.bf-show-tx-amount { text-align: right; flex-shrink: 0; }
.bf-show-tx-amount-value { font-size: 16px; font-weight: 800; font-family: var(--bf-font-mono); margin: 0; }
.bf-show-tx-amount-value.income { color: #007f5f; }
.bf-show-tx-amount-value.expense { color: #dc2626; }
.bf-show-tx-amount-per { font-size: 12px; color: #6366f1; margin: 2px 0 0; }
.bf-show-tx-category {
    display: inline-block; font-size: 11px; padding: 3px 10px;
    background: #f1f5f9; border-radius: 999px; color: #64748b; margin-top: 6px;
}

.bf-show-empty-tx {
    text-align: center; padding: 40px 20px; color: #94a3b8; font-size: 14px;
    border: 1px dashed #e2e8f0; border-radius: 12px;
}

.bf-show-chart-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
.bf-show-chart-panel {
    background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

@media (max-width: 1200px) { .bf-show-kpi-row { grid-template-columns: repeat(2, 1fr); } .bf-show-chart-row { grid-template-columns: 1fr; } }
@media (max-width: 768px) { .bf-show-kpi-row { grid-template-columns: 1fr; } }
</style>

<div class="bf-show-page">

    <!-- Header -->
    <div class="bf-show-header">
        <div>
            <h2 class="bf-show-title"><?= $e($budget['name'] ?? 'Budget') ?></h2>
            <p class="bf-show-subtitle">
                <?= $e($periodLabels[$budget['period'] ?? 'monthly'] ?? 'Mensuel') ?>
                <?php if (($budget['type'] ?? '') === 'shared'): ?>
                <span class="bf-show-badge shared"><i class="bi bi-people"></i> Partagé</span>
                <?php else: ?>
                <span class="bf-show-badge personal"><i class="bi bi-person"></i> Personnel</span>
                <?php endif; ?>
            </p>
        </div>
        <?php if ($isOwner): ?>
        <div class="bf-show-actions">
            <a href="/budgets/edit?id=<?= $e((int) ($budget['id'] ?? 0)) ?>" class="bf-show-btn">
                <i class="bi bi-pencil"></i> Modifier
            </a>
            <form method="post" action="/budgets/delete" style="display:inline;">
                <?= CSRF::getTokenField() ?>
                <input type="hidden" name="id" value="<?= $e((int) ($budget['id'] ?? 0)) ?>">
                <button type="submit" class="bf-show-btn danger" data-confirm="Supprimer ce budget ?">
                    <i class="bi bi-trash"></i> Supprimer
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- Alertes budget -->
    <?php if ($status === 'warning'): ?>
    <div class="bf-show-alert warning">
        <i class="bi bi-exclamation-triangle"></i>
        Attention : vous avez consommé <?= number_format($percent, 0) ?>% de votre budget
    </div>
    <?php endif; ?>
    <?php if ($status === 'danger'): ?>
    <div class="bf-show-alert danger">
        <i class="bi bi-x-circle"></i>
        Budget dépassé de <?= number_format($overAmount, 0, ',', ' ') ?> DT
    </div>
    <?php endif; ?>

    <!-- KPI Cards -->
    <div class="bf-show-kpi-row">
        <div class="bf-show-kpi-card">
            <div class="kpi-icon" style="background:#ecfdf5;color:#059669"><i class="bi bi-graph-up-arrow"></i></div>
            <p class="kpi-label">Revenus</p>
            <p class="kpi-value" style="color:#059669">+<?= $formatDT($income) ?></p>
        </div>
        <div class="bf-show-kpi-card">
            <div class="kpi-icon" style="background:#eff6ff;color:#2563eb"><i class="bi bi-receipt"></i></div>
            <p class="kpi-label">Dépenses</p>
            <p class="kpi-value" style="color:#2563eb">-<?= $formatDT($spent) ?></p>
        </div>
        <div class="bf-show-kpi-card">
            <div class="kpi-icon" style="background:<?= $balance >= 0 ? '#ecfdf5' : '#fef2f2' ?>;color:<?= $balance >= 0 ? '#059669' : '#dc2626' ?>"><i class="bi bi-<?= $balance >= 0 ? 'wallet2' : 'exclamation-triangle' ?>"></i></div>
            <p class="kpi-label">Solde</p>
            <p class="kpi-value" style="color:<?= $balance >= 0 ? '#059669' : '#dc2626' ?>"><?= $balance >= 0 ? '+' : '' ?><?= $formatDT($balance) ?></p>
        </div>
        <div class="bf-show-kpi-card">
            <div class="kpi-icon" style="background:#fef3c7;color:#d97706"><i class="bi bi-wallet2"></i></div>
            <p class="kpi-label">Restant</p>
            <p class="kpi-value" style="color:<?= $remaining >= 0 ? '#059669' : '#dc2626' ?>"><?= $limit !== null ? $formatDT(abs($remaining)) : '∞' ?></p>
        </div>
    </div>

    <!-- Budget Panel (dark) -->
    <?php if ($limit !== null && $limit > 0): ?>
    <div class="bf-show-budget-panel">
        <div class="bf-show-budget-panel-header">
            <div>
                <p class="bf-show-budget-panel-name"><?= $e($budget['name'] ?? '') ?></p>
                <p class="bf-show-budget-panel-meta"><?= $e($periodLabels[$budget['period'] ?? 'monthly'] ?? 'Mensuel') ?> • <?= date('d/m/Y', strtotime($budget['start_date'] ?? date('Y-m-d'))) ?></p>
            </div>
            <div class="bf-show-budget-panel-amount">
                <p class="bf-show-budget-panel-spent"><?= $formatDT($spent) ?></p>
                <p class="bf-show-budget-panel-limit">sur <?= $formatDT($limit) ?></p>
            </div>
        </div>
        <div class="bf-show-progress-track">
            <span class="bf-show-progress-fill" style="width:<?= min(100, $percent) ?>%;background:<?= $progressColor ?>"></span>
        </div>
        <div class="bf-show-progress-info">
            <span class="bf-show-progress-pct" style="color:<?= $progressColor ?>"><?= number_format($percent, 0) ?>% utilisé</span>
            <span class="bf-show-progress-remaining"><?= $remaining >= 0 ? '<span style="color:var(--bf-green);font-weight:700">' . $formatDT($remaining) . '</span> restant' : '<span style="color:#e11d48;font-weight:700">' . $formatDT(abs($remaining)) . '</span> dépassé' ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Charts Row -->
    <?php if (!empty($categoryBreakdown)): ?>
    <div class="bf-show-chart-row">
        <div class="bf-show-chart-panel">
            <p class="bf-show-panel-title">Répartition</p>
            <h3 style="font-size:16px;font-weight:700;color:#0f172a;margin:0 0 16px">Dépenses par catégorie</h3>
            <div style="display:flex;gap:24px;align-items:center;flex-wrap:wrap">
                <div style="width:160px;height:160px;flex-shrink:0">
                    <canvas id="categoryChart"
                        data-colors="<?= htmlspecialchars(json_encode($chartColors), ENT_QUOTES, 'UTF-8') ?>"
                        data-labels="<?= htmlspecialchars(json_encode($chartLabels), ENT_QUOTES, 'UTF-8') ?>"
                        data-values="<?= htmlspecialchars(json_encode($chartValues), ENT_QUOTES, 'UTF-8') ?>">
                    </canvas>
                </div>
                <div style="flex:1;min-width:180px">
                    <?php
                    $totalCat = array_sum(array_column($categoryBreakdown, 'total'));
                    foreach ($categoryBreakdown as $cat):
                        $color = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) ($cat['color'] ?? '')) === 1 ? (string) $cat['color'] : '#007f5f';
                        $catPct = $totalCat > 0 ? ((float) $cat['total'] / $totalCat) * 100 : 0;
                    ?>
                    <div style="display:flex;align-items:center;gap:10px;padding:6px 0;border-bottom:1px solid #f1f5f9">
                        <div style="width:10px;height:10px;border-radius:3px;flex-shrink:0;background:<?= $color ?>"></div>
                        <span style="flex:1;font-size:13px;color:#0f172a"><?= $e($cat['name'] ?? '') ?></span>
                        <span style="font-size:13px;font-weight:700;font-family:var(--bf-font-mono);color:#0f172a"><?= $formatDT($cat['total'] ?? 0) ?></span>
                        <span style="font-size:12px;color:#94a3b8;min-width:40px;text-align:right"><?= number_format($catPct, 0) ?>%</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="bf-show-chart-panel">
            <p class="bf-show-panel-title">Vue d'ensemble</p>
            <h3 style="font-size:16px;font-weight:700;color:#0f172a;margin:0 0 16px">Revenus vs Dépenses</h3>
            <div style="height:200px;display:grid;place-items:center;color:#94a3b8;font-size:14px">
                <i class="bi bi-bar-chart-line" style="font-size:48px;color:#e2e8f0;display:block;margin-bottom:8px"></i>
                Données mensuelles disponibles dans le tableau de bord
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Membres (budget partagé) -->
    <?php if (($budget['type'] ?? '') === 'shared'): ?>
    <div class="bf-show-members-panel">
        <p class="bf-show-panel-title">Collaboration</p>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
            <h3 class="bf-show-panel-heading"><i class="bi bi-people"></i> Membres</h3>
            <span style="color:rgba(216,231,228,0.5);font-size:13px"><?= $memberCount ?> membre<?= $memberCount > 1 ? 's' : '' ?></span>
        </div>

        <?php foreach ($allMembers as $member): ?>
        <div class="bf-show-member-row">
            <div class="bf-show-member-avatar <?= ($member['id'] ?? 0) === $userId ? 'owner' : 'member' ?>">
                <?= bfShowGetInitials($member['name'] ?? '') ?>
                <?php if (($member['id'] ?? 0) === (int) ($budget['owner_id'] ?? 0)): ?>
                <div class="owner-badge">★</div>
                <?php endif; ?>
            </div>
            <div class="bf-show-member-info">
                <p class="bf-show-member-name"><?= $e($member['name'] ?? '') ?></p>
                <p class="bf-show-member-email"><?= $e($member['email'] ?? '') ?></p>
            </div>
            <?php if ($isOwner && ($member['id'] ?? 0) !== $userId): ?>
            <form method="post" action="/budgets/remove-member" style="display:inline;">
                <?= CSRF::getTokenField() ?>
                <input type="hidden" name="budget_id" value="<?= $e((int) ($budget['id'] ?? 0)) ?>">
                <input type="hidden" name="user_id" value="<?= $e((int) ($member['id'] ?? 0)) ?>">
                <button type="submit" class="bf-show-member-remove" title="Retirer">
                    <i class="bi bi-x-lg"></i>
                </button>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <?php if ($isOwner): ?>
        <form method="post" action="/budgets/invite" class="bf-show-invite-form">
            <?= CSRF::getTokenField() ?>
            <input type="hidden" name="budget_id" value="<?= $e((int) ($budget['id'] ?? 0)) ?>">
            <input type="email" name="email" class="bf-show-invite-input" placeholder="membre@email.com" required>
            <button type="submit" class="bf-show-invite-btn">
                <i class="bi bi-envelope" style="margin-right:6px"></i> Inviter
            </button>
        </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Transactions -->
    <div class="bf-show-transactions-panel">
        <div class="bf-show-tx-header">
            <div>
                <p class="bf-show-panel-title">Historique</p>
                <h3 class="bf-show-tx-title"><?= ($budget['type'] ?? '') === 'shared' ? 'Dépenses partagées' : 'Transactions' ?></h3>
            </div>
            <a href="/transactions/create?budget_id=<?= $e((int) ($budget['id'] ?? 0)) ?>" class="bf-btn-create" style="font-size:13px;padding:8px 18px">
                <i class="bi bi-plus-lg"></i> Ajouter
            </a>
        </div>

        <!-- Onglets filtre -->
        <div class="bf-show-tx-tabs">
            <a href="/budgets/show?id=<?= $e((int) ($budget['id'] ?? 0)) ?>" class="bf-show-tx-tab <?= $typeFilter === null ? 'active' : '' ?>">Toutes</a>
            <a href="/budgets/show?id=<?= $e((int) ($budget['id'] ?? 0)) ?>&type=income" class="bf-show-tx-tab income <?= $typeFilter === 'income' ? 'active' : '' ?>">Revenus</a>
            <a href="/budgets/show?id=<?= $e((int) ($budget['id'] ?? 0)) ?>&type=expense" class="bf-show-tx-tab expense <?= $typeFilter === 'expense' ? 'active' : '' ?>">Dépenses</a>
        </div>

        <?php if (empty($transactions)): ?>
        <div class="bf-show-empty-tx">
            <i class="bi bi-receipt" style="font-size:36px;color:#e2e8f0;display:block;margin-bottom:12px"></i>
            Aucune transaction pour ce budget.
        </div>
        <?php else: ?>
        <?php foreach ($transactions as $tx): ?>
        <?php
            $categoryName = $tx['category_name'] ?? 'Sans catégorie';
            $description = $tx['description'] ?? '';
            $amount = (float) ($tx['amount'] ?? 0);
            $isIncome = ($tx['type'] ?? 'expense') === 'income';
            $dateFormatted = date('d/m/Y', strtotime($tx['date'] ?? ''));
            $perPerson = $memberCount > 1 ? $amount / $memberCount : $amount;
        ?>
        <div class="bf-show-tx-row">
            <div class="bf-show-tx-avatar"><?= bfShowGetInitials($tx['user_name'] ?? '') ?></div>
            <div class="bf-show-tx-info">
                <p class="bf-show-tx-desc"><?= $e($description ?: $categoryName) ?></p>
                <p class="bf-show-tx-meta">Payé par <strong><?= $e($tx['user_name'] ?? '') ?></strong> · <?= $dateFormatted ?></p>
                <?php if (($budget['type'] ?? '') === 'shared' && $memberCount > 1): ?>
                <div style="display:flex;gap:4px;margin-top:6px;flex-wrap:wrap">
                    <span style="font-size:11px;color:#94a3b8;margin-right:4px">Partagé entre :</span>
                    <?php foreach ($allMembers as $m): ?>
                    <span style="font-size:10px;padding:2px 8px;background:#f1f5f9;border-radius:999px;color:#64748b"><?= $e($m['name'] ?? '') ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="bf-show-tx-amount">
                <p class="bf-show-tx-amount-value <?= $isIncome ? 'income' : 'expense' ?>">
                    <?= $isIncome ? '+' : '-' ?><?= $formatDT($amount) ?>
                </p>
                <?php if (($budget['type'] ?? '') === 'shared' && $memberCount > 1): ?>
                <p class="bf-show-tx-amount-per"><?= $formatDT($perPerson) ?>/pers.</p>
                <?php endif; ?>
                <span class="bf-show-tx-category"><?= $e($categoryName) ?></span>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>
