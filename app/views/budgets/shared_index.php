<?php
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$formatDT = static fn (mixed $amount): string => number_format((float) $amount, 2, ',', ' ') . ' DT';
$sharedBudgets = $sharedBudgets ?? [];

function bfSharedGetInitials(string $name): string {
    $parts = explode(' ', trim($name), 2);
    $i = '';
    if (!empty($parts[0])) $i .= strtoupper(substr($parts[0], 0, 1));
    if (!empty($parts[1])) $i .= strtoupper(substr($parts[1], 0, 1));
    elseif (!empty($parts[0]) && strlen($parts[0]) > 1) $i = strtoupper(substr($parts[0], 0, 2));
    return $i ?: '?';
}
?>

<div class="bf-shared-index-page">

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
        <i class="bi bi-x-circle"></i>
        <p class="bf-flex-1"><?= $e($flashDanger) ?></p>
        <button class="bf-alert-close-btn" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
    </div>
    <?php endif; ?>

    <div class="bf-shared-index-header">
        <p class="section-label"><?= count($sharedBudgets) ?> budget<?= count($sharedBudgets) > 1 ? 's' : '' ?> partagé<?= count($sharedBudgets) > 1 ? 's' : '' ?></p>
        <a href="/budgets/create" class="bf-btn-primary">
            <i class="bi bi-plus-lg"></i> Nouveau budget partagé
        </a>
    </div>

    <?php if (empty($sharedBudgets)): ?>
    <div class="bf-shared-empty">
        <i class="bi bi-people"></i>
        <h3>Aucun budget partagé</h3>
        <p>Créez un budget partagé pour collaborer avec d'autres membres sur vos dépenses.</p>
        <a href="/budgets/create" class="bf-btn-primary"><i class="bi bi-plus-lg"></i> Créer un budget partagé</a>
    </div>
    <?php else: ?>
    <div class="bf-shared-grid">
        <?php foreach ($sharedBudgets as $b): ?>
        <?php
            $spent = (float) ($b['spent'] ?? 0);
            $limit = $b['amount_limit'] !== null ? (float) $b['amount_limit'] : 0;
            $pct = $limit > 0 ? min(100, ($spent / $limit) * 100) : 0;
            $left = max(0, $limit - $spent);
            $fillClass = $pct >= 100 ? 'danger' : ($pct >= 80 ? 'warning' : '');
            $statusLabel = $pct >= 100 ? 'Dépassé' : ($pct >= 80 ? 'Proche limite' : 'On Track');
            $statusClass = $pct >= 100 ? 'danger' : ($pct >= 80 ? 'warning' : 'ok');
            $members = $b['members'] ?? [];
            $periodLabels = ['weekly' => 'Hebdomadaire', 'monthly' => 'Mensuel', 'custom' => 'Personnalisé'];
            $periodLabel = $periodLabels[$b['period'] ?? 'monthly'] ?? 'Mensuel';
        ?>
        <a href="/budgets/show?id=<?= $e((int) ($b['id'] ?? 0)) ?>" class="bf-shared-card">
            <div class="bf-shared-card-header">
                <div>
                    <p class="bf-shared-card-name"><?= $e($b['name'] ?? 'Budget') ?></p>
                    <p class="bf-shared-card-period"><?= $e($periodLabel) ?> · <?= count($members) + 1 ?> membres</p>
                </div>
                <div class="bf-shared-card-members">
                    <?php foreach (array_slice($members, 0, 3) as $m): ?>
                    <span class="bf-shared-avatar" title="<?= $e($m['name'] ?? '') ?>"><?= bfSharedGetInitials($m['name'] ?? '') ?></span>
                    <?php endforeach; ?>
                    <?php if (count($members) > 3): ?><span class="bf-shared-avatar" style="background:#94a3b8">+<?= count($members) - 3 ?></span><?php endif; ?>
                </div>
            </div>

            <div class="bf-shared-card-amounts">
                <span class="bf-shared-card-spent"><?= $formatDT($spent) ?></span>
                <span class="bf-shared-card-limit"> / <?= $limit > 0 ? $formatDT($limit) : 'Illimité' ?></span>
            </div>

            <?php if ($limit > 0): ?>
            <div class="bf-shared-card-progress">
                <div class="bf-shared-progress-bar">
                    <span class="bf-shared-progress-fill <?= $fillClass ?>" style="width:<?= $pct ?>%"></span>
                </div>
                <div class="bf-shared-progress-info">
                    <span class="bf-shared-pct" style="color:<?= $fillClass === 'danger' ? '#FF4D4D' : ($fillClass === 'warning' ? '#FFB547' : '#22D3A5') ?>"><?= number_format($pct, 0) ?>% utilisé</span>
                    <span class="bf-shared-left"><?= $formatDT($left) ?> restant</span>
                </div>
            </div>
            <?php endif; ?>

            <div class="bf-shared-card-footer">
                <span class="bf-shared-badge <?= $statusClass ?>">
                    <?php if ($statusClass === 'ok'): ?><i class="bi bi-check-circle"></i><?php elseif ($statusClass === 'warning'): ?><i class="bi bi-exclamation-triangle"></i><?php else: ?><i class="bi bi-x-circle"></i><?php endif; ?>
                    <?= $statusLabel ?>
                </span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
