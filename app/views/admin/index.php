<?php
// Tableau de bord admin — design fidèle au Figma (onglets Overview / Analytiques).
$e   = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$tab = in_array($_GET['tab'] ?? '', ['analytics'], true) ? ($_GET['tab'] ?? 'overview') : 'overview';

// Données graphiques JSON pour Chart.js.
$chartUserLabels  = array_column($stats['monthly_users'] ?? [], 'month');
$chartUserData    = array_column($stats['monthly_users'] ?? [], 'users');
$chartTxLabels    = array_column($stats['monthly_tx'] ?? [], 'month');
$chartExpenses    = array_column($stats['monthly_tx'] ?? [], 'expenses');
$chartIncome      = array_column($stats['monthly_tx'] ?? [], 'income');
?>

<?php if (!empty($flashSuccess)): ?>
<div class="bf-alert bf-alert-success" role="alert"><i class="bi bi-check-circle"></i> <?= $e($flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashDanger)): ?>
<div class="bf-alert bf-alert-danger" role="alert"><i class="bi bi-x-circle"></i> <?= $e($flashDanger) ?></div>
<?php endif; ?>
<?php if (!empty($flashInfo)): ?>
<div class="bf-alert bf-alert-info" role="alert"><i class="bi bi-info-circle"></i> <?= $e($flashInfo) ?></div>
<?php endif; ?>

<!-- Badge accès admin -->
<div class="adm-badge-access">
    <i class="bi bi-shield-check"></i> Admin Access
</div>

<!-- Onglets -->
<div class="adm-tabs">
    <a href="/admin?tab=overview"   class="adm-tab <?= $tab === 'overview'   ? 'active' : '' ?>">Vue d'ensemble</a>
    <a href="/admin/users"          class="adm-tab">Utilisateurs</a>
    <a href="/admin?tab=analytics"  class="adm-tab <?= $tab === 'analytics'  ? 'active' : '' ?>">Analytiques</a>
    <a href="/admin/budgets"        class="adm-tab">Budgets</a>
</div>

<?php if ($tab === 'overview'): ?>
<!-- ═══════════════════ ONGLET OVERVIEW ═══════════════════ -->

<!-- KPI cards -->
<div class="row g-4 mb-5">
    <?php
    $kpiCards = [
        ['label' => 'Total Utilisateurs', 'value' => $stats['total_users'],        'sub' => '+0 cette semaine',     'color' => '#006cfa', 'icon' => 'bi-people'],
        ['label' => 'En attente',         'value' => $stats['pending_users'],       'sub' => 'À valider',            'color' => '#f59e0b', 'icon' => 'bi-clock-history',
         'href' => '/admin/users?filter=pending'],
        ['label' => 'Budgets Partagés',   'value' => $stats['shared_budgets'],      'sub' => $stats['total_budgets'] . ' total', 'color' => '#00684a', 'icon' => 'bi-wallet2'],
        ['label' => 'Transactions',       'value' => $stats['total_transactions'],  'sub' => number_format((float) $stats['total_volume'], 0, ',', ' ') . ' DT dépensé', 'color' => '#a855f7', 'icon' => 'bi-arrow-left-right'],
    ];
    foreach ($kpiCards as $k):
        $tag  = isset($k['href']) ? 'a' : 'div';
        $href = isset($k['href']) ? 'href="' . $e($k['href']) . '"' : '';
    ?>
    <div class="col-6 col-xl-3">
        <<?= $tag ?> <?= $href ?> class="adm-kpi" style="display:block;text-decoration:none;box-shadow:rgba(0,30,43,0.12) 0px 8px 24px;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px;">
                <div class="adm-kpi-icon" style="background:<?= $e($k['color']) ?>12;border:1px solid <?= $e($k['color']) ?>30;">
                    <i class="bi <?= $e($k['icon']) ?>" style="font-size:18px;color:<?= $e($k['color']) ?>;"></i>
                </div>
            </div>
            <div class="adm-kpi-value"><?= $e($k['value']) ?></div>
            <div class="adm-kpi-label"><?= $e($k['label']) ?></div>
            <div style="font-size:11px;color:<?= $e($k['color']) ?>;margin-top:4px;"><?= $e($k['sub']) ?></div>
        </<?= $tag ?>>
    </div>
    <?php endforeach; ?>
</div>

<!-- Section heading: Activité Plateforme -->
<h2 class="adm-section-heading">Activité Plateforme</h2>

<!-- Graphiques -->
<div class="row g-4 mb-5">
    <div class="col-md-6">
        <div class="adm-card">
            <span class="adm-card-mono">Croissance</span>
            <div class="adm-card-title">Inscriptions — 6 derniers mois</div>
            <canvas id="chartUsers" height="160"></canvas>
        </div>
    </div>
    <div class="col-md-6">
        <div class="adm-card">
            <span class="adm-card-mono">Activité</span>
            <div class="adm-card-title">Transactions mensuelle</div>
            <canvas id="chartTx" height="160"></canvas>
        </div>
    </div>
</div>

<!-- Section heading: Validations -->
<h2 class="adm-section-heading">Validations</h2>

<!-- Comptes en attente -->
<div class="adm-card mb-5" style="border-color:<?= $stats['pending_users'] > 0 ? 'rgba(245,158,11,0.3)' : '#b8c4c2' ?>;<?= $stats['pending_users'] > 0 ? 'box-shadow:rgba(0,30,43,0.12) 0px 26px 44px,rgba(0,0,0,0.13) 0px 7px 13px;' : '' ?>">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <i class="bi bi-exclamation-circle" style="font-size:18px;color:#f59e0b;"></i>
        <h3 style="font-size:17px;font-weight:600;color:#001e2b;margin:0;">Validations en attente</h3>
        <?php if ($stats['pending_users'] > 0): ?>
        <span class="adm-badge-pending" style="margin-left:4px;"><?= $e($stats['pending_users']) ?> en attente</span>
        <?php endif; ?>
        <a href="/admin/users?filter=pending" style="margin-left:auto;font-size:12px;color:#5c6c75;text-decoration:none;">Voir tous →</a>
    </div>

    <?php if (empty($stats['pending_list'])): ?>
        <p style="text-align:center;padding:24px 0;font-size:14px;color:#b8c4c2;">
            <i class="bi bi-check2-circle" style="font-size:24px;display:block;margin-bottom:8px;color:#00684a;"></i>
            Aucune validation en attente
        </p>
    <?php else: ?>
        <?php foreach ($stats['pending_list'] as $pending): ?>
        <div class="adm-pending-item">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="adm-avatar" style="background:#00684a;">
                    <?= $e(strtoupper(mb_substr((string) $pending['name'], 0, 2, 'UTF-8'))) ?>
                </div>
                <div>
                    <div style="font-size:14px;font-weight:600;color:#001e2b;"><?= $e($pending['name']) ?></div>
                    <div style="font-size:12px;color:#5c6c75;"><?= $e($pending['email']) ?> · Inscrit le <?= $e(date('d/m/Y', strtotime((string) $pending['created_at']))) ?></div>
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-shrink:0;">
                <form method="post" action="/admin/users/validate">
                    <?= CSRF::getTokenField() ?>
                    <input type="hidden" name="user_id" value="<?= $e($pending['id']) ?>">
                    <button type="submit" class="adm-btn-approve">
                        <i class="bi bi-check2"></i> Valider
                    </button>
                </form>
                <form method="post" action="/admin/users/delete" onsubmit="return confirm('Supprimer ce compte ?')">
                    <?= CSRF::getTokenField() ?>
                    <input type="hidden" name="user_id" value="<?= $e($pending['id']) ?>">
                    <button type="submit" class="adm-btn-reject">
                        <i class="bi bi-x"></i> Refuser
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Actions rapides + Email test -->
<div class="row g-4 mb-5">
    <div class="col-md-6">
        <div class="adm-card" style="padding:20px;">
            <span class="adm-card-mono">Outils</span>
            <div class="adm-card-title" style="margin-bottom:12px;">Actions rapides</div>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <a href="/admin/users?filter=pending" style="display:flex;align-items:center;justify-content:space-between;padding:11px 14px;border-radius:10px;border:1px solid #b8c4c2;text-decoration:none;color:#001e2b;font-size:13px;font-weight:500;transition:border-color .15s;" onmouseover="this.style.borderColor='#00684a'" onmouseout="this.style.borderColor='#b8c4c2'">
                    <span><i class="bi bi-check2-circle" style="color:#f59e0b;margin-right:8px;"></i>Valider des comptes</span>
                    <?php if ($stats['pending_users'] > 0): ?><span class="adm-pending-pill"><?= $e($stats['pending_users']) ?></span><?php endif; ?>
                </a>
                <a href="/admin/users/export?filter=all" style="display:flex;align-items:center;justify-content:space-between;padding:11px 14px;border-radius:10px;border:1px solid #b8c4c2;text-decoration:none;color:#001e2b;font-size:13px;font-weight:500;transition:border-color .15s;" onmouseover="this.style.borderColor='#006cfa'" onmouseout="this.style.borderColor='#b8c4c2'">
                    <span><i class="bi bi-download" style="color:#006cfa;margin-right:8px;"></i>Exporter utilisateurs (CSV)</span>
                    <i class="bi bi-chevron-right" style="color:#b8c4c2;"></i>
                </a>
                <a href="/admin/budgets" style="display:flex;align-items:center;justify-content:space-between;padding:11px 14px;border-radius:10px;border:1px solid #b8c4c2;text-decoration:none;color:#001e2b;font-size:13px;font-weight:500;transition:border-color .15s;" onmouseover="this.style.borderColor='#a855f7'" onmouseout="this.style.borderColor='#b8c4c2'">
                    <span><i class="bi bi-wallet2" style="color:#a855f7;margin-right:8px;"></i>Superviser les budgets partagés</span>
                    <i class="bi bi-chevron-right" style="color:#b8c4c2;"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="adm-card" style="padding:20px;">
            <span class="adm-card-mono">Diagnostic</span>
            <div class="adm-card-title" style="margin-bottom:12px;">Email SMTP Gmail</div>
            <div style="background:#f9fbfb;border:1px solid #f0f2f2;border-radius:10px;padding:12px 14px;margin-bottom:14px;">
                <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:6px;">
                    <span style="color:#5c6c75;font-family:'Source Code Pro',monospace;font-size:10px;text-transform:uppercase;letter-spacing:1px;">Serveur</span>
                    <span style="color:#001e2b;font-family:monospace;">smtp.gmail.com:587</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:12px;">
                    <span style="color:#5c6c75;font-family:'Source Code Pro',monospace;font-size:10px;text-transform:uppercase;letter-spacing:1px;">Expéditeur</span>
                    <span style="color:#001e2b;font-family:monospace;font-size:11px;"><?= $e($mailFrom) ?></span>
                </div>
            </div>
            <form method="post" action="/admin/test-email">
                <?= CSRF::getTokenField() ?>
                <button type="submit" style="width:100%;padding:10px 20px;border-radius:100px;border:none;background:#001e2b;color:#fff;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:transform .15s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                    <i class="bi bi-send"></i> Envoyer un email de test
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Section heading: Flux Récent -->
<h2 class="adm-section-heading">Flux Récent</h2>

<!-- Activité récente -->
<div class="adm-card">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <div>
            <span class="adm-card-mono">Flux</span>
            <div class="adm-card-title" style="margin-bottom:0;">Activité récente</div>
        </div>
    </div>
    <?php if (empty($stats['recent_activity'])): ?>
        <p style="text-align:center;padding:32px;font-size:14px;color:#b8c4c2;">Aucune transaction enregistrée.</p>
    <?php else: ?>
    <div style="overflow-x:auto;margin:0 -24px;padding:0 24px;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:1px solid #b8c4c2;background:#f9fbfb;">
                    <th class="adm-th">Date</th>
                    <th class="adm-th">Utilisateur</th>
                    <th class="adm-th">Budget</th>
                    <th class="adm-th">Catégorie</th>
                    <th class="adm-th" style="text-align:right;">Montant</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats['recent_activity'] as $i => $tx): ?>
                <tr style="border-bottom:<?= $i < count($stats['recent_activity']) - 1 ? '1px solid #f0f2f2' : 'none' ?>;" onmouseover="this.style.background='#f9fbfb'" onmouseout="this.style.background='transparent'">
                    <td class="adm-td" style="color:#5c6c75;font-family:'Source Code Pro',monospace;font-size:12px;"><?= $e(date('d/m/Y', strtotime((string) $tx['date']))) ?></td>
                    <td class="adm-td"><?= $e($tx['user_name']) ?></td>
                    <td class="adm-td" style="color:#3d4f58;"><?= $e($tx['budget_name']) ?></td>
                    <td class="adm-td" style="color:#5c6c75;"><?= $tx['category_name'] !== null ? $e($tx['category_name']) : '—' ?></td>
                    <td class="adm-td" style="text-align:right;font-weight:700;font-variant-numeric:tabular-nums;font-family:'Source Code Pro',monospace;font-size:12px;color:<?= $tx['type'] === 'income' ? '#00684a' : '#e11d48' ?>;">
                        <?= $tx['type'] === 'income' ? '+' : '-' ?><?= number_format((float) $tx['amount'], 3, ',', ' ') ?> DT
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php elseif ($tab === 'analytics'): ?>
<!-- ═══════════════════ ONGLET ANALYTIQUES ═══════════════════ -->

<!-- Section heading: Métriques -->
<h2 class="adm-section-heading">Métriques Globales</h2>

<!-- Métriques globales -->
<div class="row g-4 mb-5">
    <?php
    $aCards = [
        ['label' => 'Volume Total', 'value' => number_format((float) $stats['total_volume'], 0, ',', ' ') . ' DT', 'change' => '', 'icon' => '💰'],
        ['label' => 'Total Transactions', 'value' => number_format($stats['total_transactions'], 0, ',', ' '), 'change' => '', 'icon' => '📊'],
        ['label' => 'Budgets Partagés', 'value' => $stats['shared_budgets'], 'change' => '/ ' . $stats['total_budgets'] . ' total', 'icon' => '🤝'],
        ['label' => 'Utilisateurs', 'value' => $stats['total_users'], 'change' => $stats['pending_users'] . ' en attente', 'icon' => '👥'],
    ];
    foreach ($aCards as $c): ?>
    <div class="col-6 col-xl-3">
        <div class="adm-kpi">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                <span style="font-size:22px;"><?= $c['icon'] ?></span>
                <?php if ($c['change']): ?>
                <span style="font-size:12px;font-weight:600;color:#00684a;"><?= $e($c['change']) ?></span>
                <?php endif; ?>
            </div>
            <div class="adm-kpi-value" style="font-size:24px;"><?= $e($c['value']) ?></div>
            <div class="adm-kpi-label"><?= $e($c['label']) ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Section heading: Tendances -->
<h2 class="adm-section-heading">Tendances</h2>

<!-- Graphiques analytiques -->
<div class="row g-4 mb-5">
    <div class="col-md-6">
        <div class="adm-card">
            <span class="adm-card-mono">Croissance</span>
            <div class="adm-card-title">Inscriptions mensuelles</div>
            <canvas id="chartUsersAnalytics" height="200"></canvas>
        </div>
    </div>
    <div class="col-md-6">
        <div class="adm-card">
            <span class="adm-card-mono">Flux financier</span>
            <div class="adm-card-title">Revenus vs Dépenses (DT)</div>
            <canvas id="chartFinancial" height="200"></canvas>
        </div>
    </div>
</div>

<!-- Section heading: Distribution -->
<h2 class="adm-section-heading">Distribution</h2>

<!-- Distribution utilisateurs -->
<div class="adm-card">
    <span class="adm-card-mono">Distribution</span>
    <div class="adm-card-title">Répartition des utilisateurs</div>
    <div style="max-width:500px;">
        <?php
        $total = max(1, $stats['total_users']);
        $distrib = [
            ['label' => 'Comptes actifs',     'count' => $stats['total_users'] - $stats['pending_users'], 'color' => '#00684a'],
            ['label' => 'En attente',          'count' => $stats['pending_users'],                          'color' => '#f59e0b'],
            ['label' => 'Budgets personnels',  'count' => $stats['total_budgets'] - $stats['shared_budgets'], 'color' => '#006cfa'],
            ['label' => 'Budgets partagés',    'count' => $stats['shared_budgets'],                          'color' => '#a855f7'],
        ];
        foreach ($distrib as $d): ?>
        <div style="margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                <span style="font-size:13px;font-weight:500;color:#001e2b;"><?= $e($d['label']) ?></span>
                <span style="font-size:13px;color:#5c6c75;"><?= $e($d['count']) ?></span>
            </div>
            <div style="height:8px;border-radius:999px;background:#f0f2f2;overflow:hidden;">
                <div style="height:100%;border-radius:999px;background:<?= $e($d['color']) ?>;width:<?= $d['count'] > 0 ? min(100, round($d['count'] / $total * 100)) : 0 ?>%;transition:width .5s;"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php
$chartUsersJson   = json_encode(array_values($chartUserLabels), JSON_UNESCAPED_UNICODE);
$chartUsersData   = json_encode(array_values($chartUserData));
$chartTxLabelsJson = json_encode(array_values($chartTxLabels), JSON_UNESCAPED_UNICODE);
$chartExpJson     = json_encode(array_values($chartExpenses));
$chartIncJson     = json_encode(array_values($chartIncome));

$scripts = <<<JS
<script>
const tooltipStyle = {
    backgroundColor: '#001e2b',
    borderColor: '#3d4f58',
    borderWidth: 1,
    borderRadius: 12,
    titleFont: { family: "'Source Code Pro',monospace", size: 11 },
    bodyFont: { family: "'Plus Jakarta Sans',sans-serif", size: 12 },
    bodyColor: '#e8edeb',
    titleColor: '#5c6c75',
};
const gridStyle = { color: '#f0f2f2', drawBorder: false };
const axisStyle = (color='#5c6c75') => ({
    grid: gridStyle,
    ticks: { color, font: { size: 12, family: "'Plus Jakarta Sans',sans-serif" } },
    border: { display: false },
});

function mkLine(id, labels, data, color, label) {
    const el = document.getElementById(id);
    if (!el) return;
    new Chart(el, {
        type: 'line',
        data: {
            labels,
            datasets: [{ label, data, borderColor: color, backgroundColor: color + '18',
                borderWidth: 2.5, pointBackgroundColor: color, pointRadius: 4, fill: true, tension: 0.4 }]
        },
        options: {
            responsive: true, plugins: { legend: { display: false }, tooltip: { ...tooltipStyle } },
            scales: { x: axisStyle(), y: { ...axisStyle(), beginAtZero: true } }
        }
    });
}

function mkBar(id, labels, datasets) {
    const el = document.getElementById(id);
    if (!el) return;
    new Chart(el, {
        type: 'bar',
        data: { labels, datasets },
        options: {
            responsive: true, plugins: { legend: { display: datasets.length > 1 }, tooltip: { ...tooltipStyle } },
            scales: { x: axisStyle(), y: { ...axisStyle(), beginAtZero: true } }
        }
    });
}

const uLabels = {$chartUsersJson};
const uData   = {$chartUsersData};
const tLabels = {$chartTxLabelsJson};
const tExp    = {$chartExpJson};
const tInc    = {$chartIncJson};

// Overview charts
mkLine('chartUsers', uLabels.length ? uLabels : ['Jan','Fév','Mar','Avr','Mai','Juin'],
       uData.length  ? uData  : [0,0,0,0,0,0], '#006cfa', 'Inscriptions');
mkBar('chartTx', tLabels.length ? tLabels : ['Jan','Fév','Mar','Avr','Mai','Juin'], [
    { label: 'Dépenses (DT)', data: tExp.length ? tExp : [0,0,0,0,0,0], backgroundColor: '#e11d4880', borderRadius: 6 },
    { label: 'Revenus (DT)',  data: tInc.length ? tInc : [0,0,0,0,0,0], backgroundColor: '#00684a80', borderRadius: 6 },
]);

// Analytics charts (same data, different canvas IDs)
mkLine('chartUsersAnalytics', uLabels.length ? uLabels : ['Jan','Fév','Mar','Avr','Mai','Juin'],
       uData.length ? uData : [0,0,0,0,0,0], '#006cfa', 'Inscriptions');
mkBar('chartFinancial', tLabels.length ? tLabels : ['Jan','Fév','Mar','Avr','Mai','Juin'], [
    { label: 'Dépenses (DT)', data: tExp.length ? tExp : [0,0,0,0,0,0], backgroundColor: '#e11d4880', borderRadius: 6 },
    { label: 'Revenus (DT)',  data: tInc.length ? tInc : [0,0,0,0,0,0], backgroundColor: '#00684a80', borderRadius: 6 },
]);
</script>
JS;
?>
