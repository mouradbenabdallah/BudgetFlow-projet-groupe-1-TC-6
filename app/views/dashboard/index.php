<?php
// Vue du tableau de bord utilisateur inspirée de la maquette fournie.
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$formatTnd = static fn (mixed $amount, int $decimals = 0): string => number_format((float) $amount, $decimals, ',', ' ') . ' TND';
$formatDate = static function (mixed $date): string {
    $timestamp = strtotime((string) $date);

    return $timestamp !== false ? date('d M', $timestamp) : '';
};
$safeColor = static function (mixed $color): string {
    $value = (string) $color;

    return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1 ? $value : '#718096';
};
$trend = static function (float $current, float $previous): float {
    if ($previous <= 0) {
        return $current > 0 ? 100.0 : 0.0;
    }

    return (($current - $previous) / $previous) * 100;
};

$income = (float) ($data['total_income'] ?? 0);
$expense = (float) ($data['total_expense'] ?? 0);
$balance = (float) ($data['balance'] ?? 0);
$savingRate = $income > 0 ? max(0, ($balance / $income) * 100) : 0;
$evolution = $data['monthly_evolution'] ?? [];
$previousMonth = $evolution[count($evolution) - 2] ?? ['income' => 0, 'expense' => 0];
$previousBalance = ((float) ($previousMonth['income'] ?? 0)) - ((float) ($previousMonth['expense'] ?? 0));
$previousSavingRate = ((float) ($previousMonth['income'] ?? 0)) > 0
    ? max(0, ($previousBalance / (float) $previousMonth['income']) * 100)
    : 0;

$budgetAlert = null;
foreach ($data['budgets'] ?? [] as $budget) {
    if (in_array($budget['status'] ?? 'ok', ['warning', 'danger'], true)) {
        $budgetAlert = $budget;
        break;
    }
}

$summaryCards = [
    [
        'label' => 'Revenus mensuels',
        'value' => $formatTnd($income),
        'icon' => 'bi-graph-up-arrow',
        'tone' => 'green',
        'trend' => $trend($income, (float) ($previousMonth['income'] ?? 0)),
    ],
    [
        'label' => 'Dépenses totales',
        'value' => $formatTnd($expense),
        'icon' => 'bi-graph-down-arrow',
        'tone' => 'red',
        'trend' => $trend($expense, (float) ($previousMonth['expense'] ?? 0)),
    ],
    [
        'label' => 'Solde net',
        'value' => $formatTnd($balance),
        'icon' => 'bi-currency-dollar',
        'tone' => 'blue',
        'trend' => $trend($balance, $previousBalance),
    ],
    [
        'label' => "Taux d'épargne",
        'value' => number_format($savingRate, 1, ',', ' ') . '%',
        'icon' => 'bi-bullseye',
        'tone' => 'teal',
        'trend' => $trend($savingRate, $previousSavingRate),
    ],
];

$categoryBreakdown = $data['category_breakdown'] ?? [];
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
$categoryJson = json_encode($categoryBreakdown, $jsonFlags) ?: '[]';
$evolutionJson = json_encode($evolution, $jsonFlags) ?: '[]';

$scripts = <<<HTML
<script type="application/json" id="bf-category-chart-data">{$categoryJson}</script>
<script type="application/json" id="bf-evolution-chart-data">{$evolutionJson}</script>
HTML;
?>

<?php if ($budgetAlert !== null): ?>
<section class="bf-alert-budget" aria-label="Alerte budget">
    <span class="bf-alert-icon"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i></span>
    <p>
        <strong>Alerte Budget :</strong>
        Le budget <?= $escape($budgetAlert['name'] ?? '') ?> est à
        <span><?= $escape(number_format((float) ($budgetAlert['percent'] ?? 0), 1, ',', ' ')) ?>%</span>
        (<?= $escape($formatTnd($budgetAlert['spent'] ?? 0, 0)) ?> sur
        <?= $escape($formatTnd($budgetAlert['amount_limit'] ?? 0, 0)) ?>).
        Vous approchez de votre limite !
    </p>
    <button type="button" aria-label="Fermer l'alerte"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
</section>
<?php endif; ?>

<section class="bf-kpi-grid" aria-label="Indicateurs financiers">
    <?php foreach ($summaryCards as $card): ?>
    <?php
        $trendValue = (float) $card['trend'];
        $trendClass = $trendValue >= 0 ? 'positive' : 'negative';
        $trendIcon = $trendValue >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-left';
        ?>
    <article class="bf-card bf-kpi-card">
        <div class="bf-kpi-top">
            <span class="bf-kpi-icon <?= $escape($card['tone']) ?>"><i class="bi <?= $escape($card['icon']) ?>"
                    aria-hidden="true"></i></span>
            <span class="bf-trend <?= $escape($trendClass) ?>">
                <i class="bi <?= $escape($trendIcon) ?>" aria-hidden="true"></i>
                <?= $escape(number_format(abs($trendValue), 1, ',', ' ')) ?>%
            </span>
        </div>
        <p class="bf-kpi-label"><?= $escape($card['label']) ?></p>
        <p class="bf-kpi-value"><?= $escape($card['value']) ?></p>
        <p class="bf-kpi-caption">vs mois dernier</p>
    </article>
    <?php endforeach; ?>
</section>

<section class="bf-dashboard-grid">
    <article class="bf-card bf-panel bf-panel-large" aria-labelledby="monthly-chart-title">
        <div class="bf-panel-heading">
            <div>
                <p class="bf-panel-eyebrow">Vue d'ensemble</p>
                <h2 id="monthly-chart-title">Revenus vs Dépenses Mensuels</h2>
            </div>
            <select class="bf-select" aria-label="Période du graphique">
                <option>6 derniers mois</option>
            </select>
        </div>
        <div class="bf-monthly-chart">
            <canvas id="monthlyChart" aria-label="Revenus et dépenses mensuels"></canvas>
        </div>
    </article>

    <article class="bf-card bf-panel" aria-labelledby="category-chart-title">
        <div class="bf-panel-heading">
            <div>
                <p class="bf-panel-eyebrow">Catégories</p>
                <h2 id="category-chart-title">Répartition des Dépenses</h2>
            </div>
        </div>

        <?php if ($categoryBreakdown === []): ?>
        <div class="bf-empty-light">Aucune dépense ce mois</div>
        <?php else: ?>
        <div class="bf-category-chart">
            <canvas id="categoryChart" aria-label="Répartition des dépenses par catégorie"></canvas>
        </div>
        <div class="bf-category-list">
            <?php foreach (array_slice($categoryBreakdown, 0, 5) as $category): ?>
            <div class="bf-category-row">
                <span class="bf-category-name">
                    <span class="bf-dot" style="--dot-color: <?= $escape($safeColor($category['color'] ?? '')) ?>;"
                        aria-hidden="true"></span>
                    <?= $escape($category['name'] ?? 'Sans catégorie') ?>
                </span>
                <strong><?= $escape($formatTnd($category['amount'] ?? 0, 0)) ?></strong>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </article>
</section>

<section class="bf-lower-grid">
    <article class="bf-card bf-panel" aria-labelledby="budgets-title">
        <div class="bf-panel-heading">
            <div>
                <p class="bf-panel-eyebrow">Budgets</p>
                <h2 id="budgets-title">Suivi des budgets</h2>
            </div>
            <a class="bf-view-all" href="/budgets">Voir tout <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
        </div>

        <?php if (($data['budgets'] ?? []) === []): ?>
        <div class="bf-empty-light">Aucun budget actif pour le moment.</div>
        <?php else: ?>
        <div class="bf-budget-list">
            <?php foreach (array_slice($data['budgets'], 0, 3) as $budget): ?>
            <?php
                    $percent = (int) ($budget['percent'] ?? 0);
                    $barPercent = max(0, min(100, $percent));
                    $status = (string) ($budget['status'] ?? 'ok');
                    ?>
            <div class="bf-budget-item">
                <div class="bf-budget-line">
                    <div>
                        <strong><?= $escape($budget['name'] ?? '') ?></strong>
                        <span><?= $escape($budget['type'] ?? '') ?></span>
                    </div>
                    <span><?= $escape($barPercent) ?>%</span>
                </div>
                <div class="bf-progress-track">
                    <span class="bf-progress-fill <?= $escape($status) ?>"
                        style="width: <?= $escape($barPercent) ?>%;"></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </article>

    <article class="bf-card bf-panel" aria-labelledby="transactions-title">
        <div class="bf-panel-heading">
            <div>
                <p class="bf-panel-eyebrow">Transactions</p>
                <h2 id="transactions-title">Activité récente</h2>
            </div>
            <a class="bf-view-all" href="/transactions">Voir tout <i class="bi bi-chevron-right"
                    aria-hidden="true"></i></a>
        </div>

        <?php if (($data['recent_transactions'] ?? []) === []): ?>
        <div class="bf-empty-light">Aucune transaction récente.</div>
        <?php else: ?>
        <div class="bf-transaction-list">
            <?php foreach (array_slice($data['recent_transactions'], 0, 5) as $transaction): ?>
            <?php
                    $isIncome = ($transaction['type'] ?? '') === 'income';
                    $amountClass = $isIncome ? 'income' : 'expense';
                    $amountPrefix = $isIncome ? '+' : '-';
                    $transactionTitle = trim((string) ($transaction['description'] ?? ''));
                    $transactionTitle = $transactionTitle !== ''
                        ? $transactionTitle
                        : (string) ($transaction['category_name'] ?? 'Transaction');
                    ?>
            <div class="bf-transaction-row">
                <span class="bf-transaction-dot"
                    style="--dot-color: <?= $escape($safeColor($transaction['category_color'] ?? '')) ?>;"
                    aria-hidden="true"></span>
                <div>
                    <strong><?= $escape($transactionTitle) ?></strong>
                    <span><?= $escape($transaction['budget_name'] ?? '') ?> ·
                        <?= $escape($formatDate($transaction['date'] ?? '')) ?></span>
                </div>
                <strong class="bf-transaction-amount <?= $escape($amountClass) ?>">
                    <?= $escape($amountPrefix . $formatTnd($transaction['amount'] ?? 0, 0)) ?>
                </strong>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </article>
</section>