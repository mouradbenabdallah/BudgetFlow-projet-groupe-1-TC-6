<?php
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$formatDT = static fn (mixed $amount): string => number_format((float) $amount, 0, ',', ' ') . ' DT';
$formatDTPrecise = static fn (mixed $amount): string => number_format((float) $amount, 0, ',', ' ');
$userId = (int) ($user['id'] ?? 0);
$personalBudgets = $personalBudgets ?? [];
$sharedBudgets = $sharedBudgets ?? [];
$periodLabels = ['weekly' => 'Hebdomadaire', 'monthly' => 'Mensuel', 'custom' => 'Personnalisé'];

$totalBudget = 0; $totalSpent = 0; $alertCount = 0; $overBudgetCount = 0;
foreach (array_merge($personalBudgets, $sharedBudgets) as $b) {
    $l = $b['amount_limit'] !== null ? (float) $b['amount_limit'] : 0;
    $s = (float) ($b['spent'] ?? 0);
    $totalBudget += $l; $totalSpent += $s;
    if ($l > 0) { $pct = ($s / $l) * 100; if ($pct >= 80) $alertCount++; if ($pct >= 100) $overBudgetCount++; }
}
$remaining = $totalBudget - $totalSpent;
$usagePct = $totalBudget > 0 ? ($totalSpent / $totalBudget) * 100 : 0;
$allBudgets = array_merge($personalBudgets, $sharedBudgets);

function bfGetProgressColor(float $p): string { return match(true) { $p >= 100 => '#e11d48', $p >= 80 => '#f59e0b', default => '#007f5f' }; }
function bfGetStatusBadge(float $p): string { return match(true) { $p >= 100 => 'Dépassé', $p >= 80 => 'Proche limite', default => 'On Track' }; }
function bfGetStatusClass(float $p): string { return match(true) { $p >= 100 => 'danger', $p >= 80 => 'warning', default => 'ok' }; }
function bfGetInitials(string $name): string {
    $parts = explode(' ', trim($name), 2);
    $i = '';
    if (!empty($parts[0])) $i .= strtoupper(substr($parts[0], 0, 1));
    if (!empty($parts[1])) $i .= strtoupper(substr($parts[1], 0, 1));
    elseif (!empty($parts[0]) && strlen($parts[0]) > 1) $i = strtoupper(substr($parts[0], 0, 2));
    return $i ?: '?';
}
function bfGetCategoryIcon(string $name): string {
    $n = strtolower($name);
    if (str_contains($n, 'aliment') || str_contains($n, 'nourriture') || str_contains($n, 'repas')) return 'bi-bag';
    if (str_contains($n, 'transport') || str_contains($n, 'voiture') || str_contains($n, 'essence')) return 'bi-car-front';
    if (str_contains($n, 'loisir') || str_contains($n, 'divert') || str_contains($n, 'cinéma')) return 'bi-film';
    if (str_contains($n, 'logement') || str_contains($n, 'loyer') || str_contains($n, 'maison')) return 'bi-house-door';
    if (str_contains($n, 'santé') || str_contains($n, 'sante') || str_contains($n, 'médical')) return 'bi-heart-pulse';
    if (str_contains($n, 'éducation') || str_contains($n, 'education') || str_contains($n, 'école')) return 'bi-book';
    if (str_contains($n, 'shopping') || str_contains($n, 'achat') || str_contains($n, 'vêtement')) return 'bi-bag';
    if (str_contains($n, 'service') || str_contains($n, 'facture') || str_contains($n, 'électricité')) return 'bi-lightning';
    if (str_contains($n, 'salaire') || str_contains($n, 'revenu') || str_contains($n, 'income')) return 'bi-cash-stack';
    return 'bi-wallet2';
}
function bfGetCategoryColor(string $name): string {
    $colors = ['#007f5f', '#0d6efd', '#e11d48', '#f59e0b', '#0f766e', '#6366f1', '#ec4899', '#8b5cf6', '#14b8a6', '#f97316'];
    $idx = hexdec(substr(md5(strtolower($name)), 0, 8)) % count($colors);
    return $colors[$idx];
}

$alertBudgets = [];
foreach ($allBudgets as $b) {
    $limit = $b['amount_limit'] !== null ? (float) $b['amount_limit'] : 0;
    $spent = (float) ($b['spent'] ?? 0);
    if ($limit > 0) {
        $pct = ($spent / $limit) * 100;
        if ($pct >= 80 && $pct < 100) {
            $alertBudgets[] = ['name' => $b['name'], 'pct' => $pct, 'remaining' => $limit - $spent];
        }
    }
}
?>

<style>
.bf-budgets-page { background: #f5f7fa; min-height: 100vh; }
.bf-budgets-page .bf-topbar { background: #ffffff; border-bottom: 1px solid #e2e8f0; }
.bf-budgets-page .bf-page-title { font-size: 22px; font-weight: 700; color: #0f172a; }
.bf-budgets-page .bf-page-greeting { font-size: 13px; color: #64748b; }

.bf-kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
.bf-kpi-card-v2 {
    background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.bf-kpi-card-v2 .kpi-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
.bf-kpi-card-v2 .kpi-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin: 0; }
.bf-kpi-card-v2 .kpi-icon {
    width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; font-size: 16px;
}
.bf-kpi-card-v2 .kpi-value { font-size: 26px; font-weight: 800; color: #0f172a; margin: 0 0 4px; font-family: var(--bf-font-mono); }
.bf-kpi-card-v2 .kpi-sub { font-size: 12px; color: #94a3b8; margin: 0; }

.bf-alert-strip {
    display: flex; align-items: center; gap: 10px; padding: 12px 16px;
    border-radius: 10px; margin-bottom: 8px; font-size: 13px;
    background: #fef9ed; border: 1px solid #f5d89a; color: #92400e;
}
.bf-alert-strip i { color: #f59e0b; font-size: 16px; }
.bf-alert-strip strong { color: #0f172a; }

.bf-budgets-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.bf-budgets-toolbar .section-label { font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin: 0; }
.bf-btn-create {
    display: inline-flex; align-items: center; gap: 8px; padding: 10px 22px;
    background: var(--bf-sidebar); color: #fff; border: none; border-radius: 999px;
    font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none;
    transition: all 0.2s;
}
.bf-btn-create:hover { background: #003d4d; color: #fff; transform: translateY(-1px); }

.bf-budget-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }

.bf-budget-card {
    background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 22px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: all 0.2s; cursor: pointer;
    text-decoration: none; color: inherit; display: block;
}
.bf-budget-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); transform: translateY(-2px); border-color: #cbd5e1; }
.bf-budget-card.warning { border-color: #f5d89a; }
.bf-budget-card.danger { border-color: #fca5a5; }

.bf-budget-card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.bf-budget-card-icon {
    width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center;
    font-size: 20px; flex-shrink: 0;
}
.bf-budget-card-name { font-size: 15px; font-weight: 700; color: #0f172a; margin: 0; }
.bf-budget-card-period { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin: 2px 0 0; }

.bf-budget-card-amounts { margin-bottom: 14px; }
.bf-budget-card-spent { font-size: 24px; font-weight: 800; color: #0f172a; font-family: var(--bf-font-mono); }
.bf-budget-card-limit { font-size: 14px; color: #94a3b8; font-weight: 500; }

.bf-budget-card-progress { margin-bottom: 12px; }
.bf-budget-card-progress-bar {
    height: 8px; border-radius: 999px; background: #f1f5f9; overflow: hidden;
}
.bf-budget-card-progress-fill { height: 100%; border-radius: 999px; transition: width 0.3s; }
.bf-budget-card-progress-info { display: flex; justify-content: space-between; align-items: center; margin-top: 8px; }
.bf-budget-card-pct { font-size: 12px; font-weight: 600; }
.bf-budget-card-left { font-size: 12px; color: #94a3b8; }

.bf-budget-card-footer { display: flex; justify-content: space-between; align-items: center; }
.bf-budget-card-badge {
    display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px;
    border-radius: 999px; font-size: 11px; font-weight: 600;
}
.bf-budget-card-badge.ok { background: #ecfdf5; color: #059669; }
.bf-budget-card-badge.warning { background: #fef3c7; color: #d97706; }
.bf-budget-card-badge.danger { background: #fef2f2; color: #dc2626; }
.bf-budget-card-members { display: flex; align-items: center; }
.bf-budget-card-member-avatar {
    width: 24px; height: 24px; border-radius: 50%; display: grid; place-items: center;
    font-size: 9px; font-weight: 700; background: var(--bf-sidebar); color: #fff;
    border: 2px solid #fff; margin-left: -6px;
}
.bf-budget-card-member-avatar:first-child { margin-left: 0; }

.bf-empty-budgets {
    text-align: center; padding: 60px 20px; background: #ffffff;
    border: 1px solid #e2e8f0; border-radius: 14px;
}
.bf-empty-budgets i { font-size: 48px; color: #cbd5e1; margin-bottom: 16px; display: block; }
.bf-empty-budgets h3 { font-size: 18px; font-weight: 700; color: #0f172a; margin: 0 0 8px; }
.bf-empty-budgets p { font-size: 14px; color: #94a3b8; margin: 0 0 24px; }

@media (max-width: 1200px) { .bf-budget-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) { .bf-budget-grid { grid-template-columns: 1fr; } .bf-kpi-row { grid-template-columns: repeat(2, 1fr); } }
</style>

<div class="bf-budgets-page">

    <?php if (!empty($flashSuccess)): ?>
    <div class="bf-alert-strip" role="alert" style="background:#ecfdf5;border-color:#a7f3d0;color:#065f46;margin-bottom:16px">
        <i class="bi bi-check-circle"></i>
        <p style="margin:0;flex:1"><?= $e($flashSuccess) ?></p>
        <button style="background:none;border:none;color:#94a3b8;cursor:pointer" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
    </div>
    <?php endif; ?>
    <?php if (!empty($flashWarning)): ?>
    <div class="bf-alert-strip" role="alert">
        <i class="bi bi-exclamation-triangle"></i>
        <p style="margin:0;flex:1"><?= $e($flashWarning) ?></p>
        <button style="background:none;border:none;color:#94a3b8;cursor:pointer" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
    </div>
    <?php endif; ?>
    <?php if (!empty($flashDanger)): ?>
    <div class="bf-alert-strip" role="alert" style="background:#fef2f2;border-color:#fca5a5;color:#991b1b">
        <i class="bi bi-x-circle" style="color:#dc2626"></i>
        <p style="margin:0;flex:1"><?= $e($flashDanger) ?></p>
        <button style="background:none;border:none;color:#94a3b8;cursor:pointer" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
    </div>
    <?php endif; ?>

    <!-- Alertes budgets proches de la limite -->
    <?php foreach ($alertBudgets as $ab): ?>
    <div class="bf-alert-strip">
        <i class="bi bi-exclamation-triangle"></i>
        <p style="margin:0;flex:1"><strong><?= $e($ab['name']) ?></strong> est à <?= number_format($ab['pct'], 0) ?>% — <?= $formatDTPrecise($ab['remaining']) ?> DT restant</p>
    </div>
    <?php endforeach; ?>

    <!-- KPI Cards -->
    <div class="bf-kpi-row">
        <div class="bf-kpi-card-v2">
            <div class="kpi-header">
                <p class="kpi-label">Budget total</p>
                <div class="kpi-icon" style="background:#ecfdf5;color:#059669"><i class="bi bi-bar-chart-line"></i></div>
            </div>
            <p class="kpi-value"><?= $formatDT($totalBudget) ?></p>
            <p class="kpi-sub">par mois</p>
        </div>
        <div class="bf-kpi-card-v2">
            <div class="kpi-header">
                <p class="kpi-label">Total dépensé</p>
                <div class="kpi-icon" style="background:#eff6ff;color:#2563eb"><i class="bi bi-cash-stack"></i></div>
            </div>
            <p class="kpi-value" style="color:#2563eb"><?= $formatDT($totalSpent) ?></p>
            <p class="kpi-sub"><?= number_format($usagePct, 0) ?>% utilisé</p>
        </div>
        <div class="bf-kpi-card-v2">
            <div class="kpi-header">
                <p class="kpi-label">Restant</p>
                <div class="kpi-icon" style="background:<?= $remaining >= 0 ? '#ecfdf5' : '#fef2f2' ?>;color:<?= $remaining >= 0 ? '#059669' : '#dc2626' ?>"><i class="bi bi-<?= $remaining >= 0 ? 'wallet2' : 'exclamation-triangle' ?>"></i></div>
            </div>
            <p class="kpi-value" style="color:<?= $remaining >= 0 ? '#059669' : '#dc2626' ?>"><?= $formatDT(abs($remaining)) ?></p>
            <p class="kpi-sub"><?= $remaining >= 0 ? 'disponible' : 'dépassé' ?></p>
        </div>
        <div class="bf-kpi-card-v2">
            <div class="kpi-header">
                <p class="kpi-label">Alertes</p>
                <div class="kpi-icon" style="background:#fef3c7;color:#d97706"><i class="bi bi-bell"></i></div>
            </div>
            <p class="kpi-value" style="color:#d97706"><?= $alertCount ?></p>
            <p class="kpi-sub"><?= $overBudgetCount ?> dépassé<?= $overBudgetCount > 1 ? 's' : '' ?></p>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="bf-budgets-toolbar">
        <p class="section-label"><?= count($allBudgets) ?> budget<?= count($allBudgets) > 1 ? 's' : '' ?></p>
        <a href="/budgets/create" class="bf-btn-create">
            <i class="bi bi-plus-lg"></i> Créer un budget
        </a>
    </div>

    <?php if (empty($allBudgets)): ?>
    <div class="bf-empty-budgets">
        <i class="bi bi-wallet2"></i>
        <h3>Aucun budget pour le moment</h3>
        <p>Créez votre premier budget pour commencer à suivre vos dépenses.</p>
        <a href="/budgets/create" class="bf-btn-create"><i class="bi bi-plus-lg"></i> Créer un budget</a>
    </div>
    <?php else: ?>
    <!-- Budget Cards Grid -->
    <div class="bf-budget-grid">
        <?php foreach ($allBudgets as $b): ?>
        <?php
            $spent = (float) ($b['spent'] ?? 0);
            $limit = $b['amount_limit'] !== null ? (float) $b['amount_limit'] : 0;
            $pct = $limit > 0 ? min(100, ($spent / $limit) * 100) : 0;
            $left = max(0, $limit - $spent);
            $fillColor = bfGetProgressColor($pct);
            $statusLabel = bfGetStatusBadge($pct);
            $statusClass = bfGetStatusClass($pct);
            $isOwner = (int) ($b['owner_id'] ?? 0) === $userId;
            $members = $b['members'] ?? [];
            $name = $b['name'] ?? 'Budget';
            $periodLabel = $periodLabels[$b['period'] ?? 'monthly'] ?? 'Mensuel';
            $catIcon = bfGetCategoryIcon($name);
            $catColor = bfGetCategoryColor($name);
            $cardClass = $statusClass === 'danger' ? 'danger' : ($statusClass === 'warning' ? 'warning' : '');
        ?>
        <a href="/budgets/show?id=<?= $e((int) ($b['id'] ?? 0)) ?>" class="bf-budget-card <?= $cardClass ?>">
            <div class="bf-budget-card-header">
                <div class="bf-budget-card-icon" style="background:<?= $catColor ?>15;color:<?= $catColor ?>">
                    <i class="bi <?= $catIcon ?>"></i>
                </div>
                <div>
                    <p class="bf-budget-card-name"><?= $e($name) ?></p>
                    <p class="bf-budget-card-period"><?= $e($periodLabel) ?>
                        <?php if (($b['type'] ?? '') === 'shared'): ?><span style="margin-left:4px;color:#6366f1"><i class="bi bi-people" style="font-size:10px"></i></span><?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="bf-budget-card-amounts">
                <span class="bf-budget-card-spent"><?= $formatDT($spent) ?></span>
                <span class="bf-budget-card-limit"> / <?= $limit > 0 ? $formatDT($limit) : 'Illimité' ?></span>
            </div>

            <?php if ($limit > 0): ?>
            <div class="bf-budget-card-progress">
                <div class="bf-budget-card-progress-bar">
                    <span class="bf-budget-card-progress-fill" style="width:<?= $pct ?>%;background:<?= $fillColor ?>"></span>
                </div>
                <div class="bf-budget-card-progress-info">
                    <span class="bf-budget-card-pct" style="color:<?= $fillColor ?>"><?= number_format($pct, 0) ?>% utilisé</span>
                    <span class="bf-budget-card-left"><?= $formatDT($left) ?> restant</span>
                </div>
            </div>
            <?php endif; ?>

            <div class="bf-budget-card-footer">
                <span class="bf-budget-card-badge <?= $statusClass ?>">
                    <?php if ($statusClass === 'ok'): ?><i class="bi bi-check-circle"></i><?php elseif ($statusClass === 'warning'): ?><i class="bi bi-exclamation-triangle"></i><?php else: ?><i class="bi bi-x-circle"></i><?php endif; ?>
                    <?= $statusLabel ?>
                </span>
                <?php if (!empty($members)): ?>
                <div class="bf-budget-card-members">
                    <?php foreach (array_slice($members, 0, 3) as $m): ?>
                    <span class="bf-budget-card-member-avatar" title="<?= $e($m['name'] ?? '') ?>"><?= bfGetInitials($m['name'] ?? '') ?></span>
                    <?php endforeach; ?>
                    <?php if (count($members) > 3): ?><span class="bf-budget-card-member-avatar" style="background:#94a3b8">+<?= count($members) - 3 ?></span><?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
