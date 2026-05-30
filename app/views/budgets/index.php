<?php
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$formatDT = static fn (mixed $amount): string => number_format((float) $amount, 2, ',', ' ') . ' DT';
$formatDTPrecise = static fn (mixed $amount): string => number_format((float) $amount, 2, ',', ' ');
$userId = (int) ($user['id'] ?? 0);
$personalBudgets = $personalBudgets ?? [];
$sharedBudgets = $sharedBudgets ?? [];
$periodLabels = ['weekly' => 'Hebdomadaire', 'monthly' => 'Mensuel', 'custom' => 'Personnalisé'];

$totalBudget = 0; $totalSpent = 0; $alertCount = 0; $overBudgetCount = 0;
foreach (array_merge($personalBudgets, $sharedBudgets) as $b) {
    $l = ($b['amount_limit'] ?? null) !== null ? (float) $b['amount_limit'] : 0;
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

<div class="bf-budgets-page">

    <?php if (!empty($flashSuccess)): ?>
    <div class="bf-alert bf-alert-success" role="alert">
        <i class="bi bi-check-circle"></i>
        <p class="bf-flex-1"><?= $e($flashSuccess) ?></p>
        <button class="bf-alert-close-btn" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
    </div>
    <?php endif; ?>
    <?php if (!empty($flashWarning)): ?>
    <div class="bf-alert bf-alert-warning" role="alert">
        <i class="bi bi-exclamation-triangle"></i>
        <p class="bf-flex-1"><?= $e($flashWarning) ?></p>
        <button class="bf-alert-close-btn" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
    </div>
    <?php endif; ?>
    <?php if (!empty($flashDanger)): ?>
    <div class="bf-alert bf-alert-danger" role="alert">
        <i class="bi bi-x-circle bf-alert-icon-danger"></i>
        <p class="bf-flex-1"><?= $e($flashDanger) ?></p>
        <button class="bf-alert-close-btn" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
    </div>
    <?php endif; ?>

    <!-- Alertes budgets proches de la limite -->
    <?php foreach ($alertBudgets as $ab): ?>
    <div class="bf-alert bf-alert-warning">
        <i class="bi bi-exclamation-triangle"></i>
        <p style="margin:0;flex:1"><strong><?= $e($ab['name']) ?></strong> est à <?= number_format($ab['pct'], 0) ?>% — <?= $formatDTPrecise($ab['remaining']) ?> DT restant</p>
    </div>
    <?php endforeach; ?>

    <!-- KPI Cards -->
    <div class="bf-kpi-row">
        <div class="bf-card">
            <div class="kpi-header">
                <p class="kpi-label">Budget total</p>
                <div class="kpi-icon bf-kpi-icon-success"><i class="bi bi-bar-chart-line"></i></div>
            </div>
            <p class="kpi-value"><?= $formatDT($totalBudget) ?></p>
            <p class="kpi-sub">par mois</p>
        </div>
        <div class="bf-card">
            <div class="kpi-header">
                <p class="kpi-label">Total dépensé</p>
                <div class="kpi-icon bf-kpi-icon-info"><i class="bi bi-cash-stack"></i></div>
            </div>
            <p class="kpi-value" style="color:#2563eb"><?= $formatDT($totalSpent) ?></p>
            <p class="kpi-sub"><?= number_format($usagePct, 0) ?>% utilisé</p>
        </div>
        <div class="bf-card">
            <div class="kpi-header">
                <p class="kpi-label">Restant</p>
                <div class="kpi-icon" style="background:<?= $remaining >= 0 ? '#ecfdf5' : '#fef2f2' ?>;color:<?= $remaining >= 0 ? '#059669' : '#dc2626' ?>"><i class="bi bi-<?= $remaining >= 0 ? 'wallet2' : 'exclamation-triangle' ?>"></i></div>
            </div>
            <p class="kpi-value" style="color:<?= $remaining >= 0 ? '#059669' : '#dc2626' ?>"><?= $formatDT(abs($remaining)) ?></p>
            <p class="kpi-sub"><?= $remaining >= 0 ? 'disponible' : 'dépassé' ?></p>
        </div>
        <div class="bf-card">
            <div class="kpi-header">
                <p class="kpi-label">Alertes</p>
                <div class="kpi-icon bf-kpi-icon-warning"><i class="bi bi-bell"></i></div>
            </div>
            <p class="kpi-value" style="color:#d97706"><?= $alertCount ?></p>
            <p class="kpi-sub"><?= $overBudgetCount ?> dépassé<?= $overBudgetCount > 1 ? 's' : '' ?></p>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="bf-budgets-toolbar">
        <p class="section-label"><?= count($allBudgets) ?> budget<?= count($allBudgets) > 1 ? 's' : '' ?></p>
        <a href="/budgets/create" class="bf-btn-primary">
            <i class="bi bi-plus-lg"></i> Créer un budget
        </a>
    </div>

    <?php if (empty($allBudgets)): ?>
    <div class="bf-empty-budgets">
        <i class="bi bi-wallet2"></i>
        <h3>Aucun budget pour le moment</h3>
        <p>Créez votre premier budget pour commencer à suivre vos dépenses.</p>
        <a href="/budgets/create" class="bf-btn-primary"><i class="bi bi-plus-lg"></i> Créer un budget</a>
    </div>
    <?php else: ?>
    <!-- Budget Cards Grid -->
    <div class="bf-budget-grid">
        <?php foreach ($allBudgets as $b): ?>
        <?php
            $spent = (float) ($b['spent'] ?? 0);
            $limit = ($b['amount_limit'] ?? null) !== null ? (float) $b['amount_limit'] : 0;
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
        <a href="/budgets/show?id=<?= $e((int) ($b['id'] ?? 0)) ?>" class="bf-card <?= $cardClass ?>">
            <div class="bf-card-header">
                <div class="bf-card-icon" style="background:<?= $catColor ?>15;color:<?= $catColor ?>">
                    <i class="bi <?= $catIcon ?>"></i>
                </div>
                <div>
                    <p class="bf-card-name"><?= $e($name) ?></p>
                    <p class="bf-card-period"><?= $e($periodLabel) ?>
                        <?php if (($b['type'] ?? '') === 'shared'): ?><span style="margin-left:4px;color:#6366f1"><i class="bi bi-people" style="font-size:10px"></i></span><?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="bf-card-amounts">
                <span class="bf-card-spent"><?= $formatDT($spent) ?></span>
                <span class="bf-card-limit"> / <?= $limit > 0 ? $formatDT($limit) : 'Illimité' ?></span>
            </div>

            <?php if ($limit > 0): ?>
            <div class="bf-card-progress">
                <div class="bf-card-progress-bar">
                    <span class="bf-card-progress-fill" style="width:<?= $pct ?>%;background:<?= $fillColor ?>"></span>
                </div>
                <div class="bf-card-progress-info">
                    <span class="bf-card-pct" style="color:<?= $fillColor ?>"><?= number_format($pct, 0) ?>% utilisé</span>
                    <span class="bf-card-left"><?= $formatDT($left) ?> restant</span>
                </div>
            </div>
            <?php endif; ?>

            <div class="bf-card-footer">
                <span class="bf-card-badge <?= $statusClass ?>">
                    <?php if ($statusClass === 'ok'): ?><i class="bi bi-check-circle"></i><?php elseif ($statusClass === 'warning'): ?><i class="bi bi-exclamation-triangle"></i><?php else: ?><i class="bi bi-x-circle"></i><?php endif; ?>
                    <?= $statusLabel ?>
                </span>
                <?php if (!empty($members)): ?>
                <div class="bf-card-members">
                    <?php foreach (array_slice($members, 0, 3) as $m): ?>
                    <span class="bf-card-member-avatar" title="<?= $e($m['name'] ?? '') ?>"><?= bfGetInitials($m['name'] ?? '') ?></span>
                    <?php endforeach; ?>
                    <?php if (count($members) > 3): ?><span class="bf-card-member-avatar" style="background:#94a3b8">+<?= count($members) - 3 ?></span><?php endif; ?>
                </div>
                <?php elseif ($limit > 0): ?>
                <span class="bf-card-togo"><?= $formatDT($left) ?> restant</span>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
