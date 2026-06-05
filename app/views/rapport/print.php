<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CashToCash — Rapport <?= htmlspecialchars($periode, ENT_QUOTES, 'UTF-8') ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 13px;
    color: #1a1a2e;
    background: #eef0f5;
}

/* ── Toolbar ── */
.toolbar {
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 100;
    background: #0d1117;
    color: #fff;
    padding: 0 32px;
    height: 58px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    border-bottom: 1px solid rgba(34,211,165,.25);
}
.toolbar-brand { display: flex; align-items: center; gap: 10px; }
.toolbar-logo  {
    width: 30px; height: 30px;
    background: #22D3A5;
    border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    color: #0d1117; font-size: 14px;
}
.toolbar-title { font-weight: 700; font-size: 15px; color: #fff; }
.toolbar-periode { font-size: 12px; color: #22D3A5; font-weight: 600; margin-left: 6px; }
.toolbar-btns  { display: flex; gap: 10px; align-items: center; }
.btn-print {
    background: #22D3A5;
    color: #0d1117;
    border: none;
    border-radius: 8px;
    padding: 9px 20px;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 7px;
    transition: background .2s, transform .15s;
}
.btn-print:hover { background: #1db88e; transform: scale(1.03); }
.btn-back {
    background: transparent;
    color: #8B90A7;
    border: 1px solid #2A2F45;
    border-radius: 8px;
    padding: 9px 16px;
    font-size: 12px;
    cursor: pointer;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all .15s;
}
.btn-back:hover { color: #fff; border-color: #555; }

/* ── Page wrapper ── */
.page-wrap {
    max-width: 820px;
    margin: 74px auto 40px;
    display: flex;
    flex-direction: column;
    gap: 0;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 8px 40px rgba(0,0,0,.14);
    overflow: hidden;
}

/* ── Header ── */
.rh {
    background: linear-gradient(135deg, #0d1117 60%, #112820);
    padding: 30px 36px 26px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    position: relative;
    overflow: hidden;
}
.rh::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 180px; height: 180px;
    background: rgba(34,211,165,.07);
    border-radius: 50%;
}
.rh::after {
    content: '';
    position: absolute;
    bottom: -30px; left: 30%;
    width: 120px; height: 120px;
    background: rgba(34,211,165,.04);
    border-radius: 50%;
}
.rh-brand { display: flex; align-items: center; gap: 14px; position: relative; z-index: 1; }
.rh-logo {
    width: 48px; height: 48px;
    background: #22D3A5;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    color: #0d1117; font-size: 22px; flex-shrink: 0;
    box-shadow: 0 4px 16px rgba(34,211,165,.4);
}
.rh-name    { font-size: 24px; font-weight: 800; color: #fff; letter-spacing: -.4px; }
.rh-sub     { font-size: 12px; color: #22D3A5; font-weight: 600; margin-top: 3px; letter-spacing: .04em; text-transform: uppercase; }
.rh-meta    { text-align: right; position: relative; z-index: 1; }
.rh-periode { font-size: 20px; font-weight: 800; color: #22D3A5; letter-spacing: -.3px; }
.rh-line    { font-size: 11px; color: rgba(255,255,255,.45); margin-top: 4px; }

/* ── Divider strip ── */
.rh-strip {
    height: 4px;
    background: linear-gradient(90deg, #22D3A5, #0ea5e9, #22D3A5);
    background-size: 200% 100%;
}

/* ── Body ── */
.rb { padding: 28px 36px; }

/* ── Section ── */
.section { margin-bottom: 28px; }
.section-title {
    font-size: 11px;
    font-weight: 800;
    color: #1a1a2e;
    text-transform: uppercase;
    letter-spacing: .1em;
    padding-bottom: 8px;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    border-bottom: 2px solid #22D3A5;
}
.section-title i { color: #22D3A5; font-size: 14px; }

/* ── Stat cards ── */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 10px;
}
.stat-card {
    border-radius: 10px;
    padding: 16px 14px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.stat-card::after {
    content: '';
    position: absolute;
    top: -16px; right: -16px;
    width: 56px; height: 56px;
    border-radius: 50%;
    opacity: .08;
}
.stat-card.income  { background: #f0fdf8; border: 1.5px solid #22D3A5; }
.stat-card.income::after  { background: #22D3A5; }
.stat-card.expense { background: #fff5f5; border: 1.5px solid #FF6B6B; }
.stat-card.expense::after { background: #FF6B6B; }
.stat-card.balance { background: #f0f9ff; border: 1.5px solid #38bdf8; }
.stat-card.balance::after { background: #38bdf8; }
.stat-icon { font-size: 18px; margin-bottom: 6px; display: block; }
.stat-icon.income  { color: #22D3A5; }
.stat-icon.expense { color: #FF6B6B; }
.stat-icon.balance { color: #38bdf8; }
.stat-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #6b7280; }
.stat-value { font-size: 19px; font-weight: 800; margin-top: 5px; font-variant-numeric: tabular-nums; line-height: 1.1; }
.stat-value.income  { color: #16a34a; }
.stat-value.expense { color: #dc2626; }
.stat-value.balance { color: #0369a1; }
.stat-nb { font-size: 11px; color: #9ca3af; margin-top: 8px; text-align: center; }

/* ── Table ── */
.table-wrap { border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb; }
table { width: 100%; border-collapse: collapse; font-size: 11.5px; }
thead tr { background: #1a1a2e; }
th { padding: 10px 12px; text-align: left; font-size: 10px; font-weight: 700;
     text-transform: uppercase; letter-spacing: .07em; color: #22D3A5; }
th.right { text-align: right; }
td { padding: 9px 12px; border-bottom: 1px solid #f3f4f6; color: #374151; vertical-align: middle; }
td.right { text-align: right; }
tbody tr:last-child td { border-bottom: none; }
tbody tr:nth-child(even) td { background: #f9fafb; }
.amount { font-weight: 700; font-variant-numeric: tabular-nums; }
.income-amt  { color: #16a34a; }
.expense-amt { color: #dc2626; }

/* ── Badges ── */
.badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 9.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.badge-personal { background: #eff6ff; color: #2563eb; }
.badge-shared   { background: #f0fdf8; color: #16a34a; }
.badge-ok       { background: #f0fdf8; color: #16a34a; }
.badge-warn     { background: #fffbeb; color: #d97706; }
.badge-over     { background: #fef2f2; color: #dc2626; }

/* ── Budgets ── */
.budget-grid { display: flex; flex-direction: column; gap: 10px; }
.budget-item {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 12px 14px;
    background: #fafafa;
}
.budget-row   { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
.budget-name  { font-weight: 600; font-size: 13px; color: #1a1a2e; }
.budget-badges { display: flex; gap: 5px; }
.progress-bg  { background: #e5e7eb; border-radius: 999px; height: 7px; overflow: hidden; }
.progress-fill { height: 7px; border-radius: 999px; }
.prog-ok      { background: linear-gradient(90deg, #22D3A5, #16a34a); }
.prog-warn    { background: linear-gradient(90deg, #fbbf24, #f59e0b); }
.prog-danger  { background: linear-gradient(90deg, #f87171, #ef4444); }
.budget-info  { font-size: 11px; color: #6b7280; margin-top: 6px; }

/* ── Catégories ── */
.cat-bar-row  { display: flex; align-items: center; gap: 10px; margin-bottom: 7px; }
.cat-dot      { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.cat-name     { flex: 0 0 140px; font-size: 12px; color: #374151; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cat-bar-bg   { flex: 1; background: #e5e7eb; border-radius: 999px; height: 6px; overflow: hidden; }
.cat-bar-fill { height: 6px; border-radius: 999px; }
.cat-amount   { flex: 0 0 100px; text-align: right; font-size: 12px; font-weight: 700; color: #dc2626; font-variant-numeric: tabular-nums; }
.cat-pct      { flex: 0 0 34px; text-align: right; font-size: 11px; color: #9ca3af; font-weight: 600; }

/* ── Footer ── */
.rf {
    background: #0d1117;
    padding: 14px 36px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 10.5px;
    color: rgba(255,255,255,.35);
    border-top: 1px solid rgba(34,211,165,.2);
}
.rf strong { color: #22D3A5; }

/* ── Print ── */
@media print {
    body { background: #fff !important; }
    .toolbar { display: none !important; }
    .page-wrap { margin: 0; box-shadow: none; border-radius: 0; max-width: 100%; }
    .rh, .rh-strip, thead tr, .rf {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .stat-card, .progress-fill, .badge, .cat-bar-fill {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .section    { page-break-inside: avoid; }
    .budget-item { page-break-inside: avoid; }
}
</style>
</head>
<body>

<?php
$h        = fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$money    = fn(mixed $v): string => number_format((float) $v, 2, ',', ' ') . ' DT';
$dateGen  = date('d/m/Y à H:i');
$userName = $h($data['user']['name'] ?? 'Utilisateur');
$typeLabel = $type === 'mensuel' ? 'Mensuel' : 'Annuel';
?>

<!-- Toolbar -->
<div class="toolbar">
    <div class="toolbar-brand">
        <div class="toolbar-logo"><i class="bi bi-bar-chart-fill"></i></div>
        <span class="toolbar-title">CashToCash</span>
        <span class="toolbar-periode">— Rapport <?= $h($typeLabel) ?> · <?= $h($periode) ?></span>
    </div>
    <div class="toolbar-btns">
        <a href="/rapport" class="btn-back">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
        <button class="btn-print" onclick="window.print()">
            <i class="bi bi-printer-fill"></i> Imprimer / Enregistrer PDF
        </button>
    </div>
</div>

<div class="page-wrap">

    <!-- En-tête -->
    <div class="rh">
        <div class="rh-brand">
            <div class="rh-logo"><i class="bi bi-bar-chart-fill"></i></div>
            <div>
                <div class="rh-name">CashToCash</div>
                <div class="rh-sub">Rapport <?= $h($typeLabel) ?></div>
            </div>
        </div>
        <div class="rh-meta">
            <div class="rh-periode"><?= $h($periode) ?></div>
            <div class="rh-line">Généré le <?= $dateGen ?></div>
            <div class="rh-line"><?= $userName ?></div>
        </div>
    </div>
    <div class="rh-strip"></div>

    <div class="rb">

        <?php if (in_array('stats', $sections, true) && isset($data['stats'])): ?>
        <?php $s = $data['stats']; $balSign = ((float) $s['balance']) >= 0 ? '+' : ''; ?>
        <div class="section">
            <div class="section-title">
                <i class="bi bi-bar-chart-fill"></i> Statistiques générales
            </div>
            <div class="stats-grid">
                <div class="stat-card income">
                    <i class="bi bi-arrow-up-circle-fill stat-icon income"></i>
                    <div class="stat-label">Revenus totaux</div>
                    <div class="stat-value income">+<?= $money($s['total_income']) ?></div>
                </div>
                <div class="stat-card expense">
                    <i class="bi bi-arrow-down-circle-fill stat-icon expense"></i>
                    <div class="stat-label">Dépenses totales</div>
                    <div class="stat-value expense">&minus;<?= $money($s['total_expense']) ?></div>
                </div>
                <div class="stat-card balance">
                    <i class="bi bi-wallet2 stat-icon balance"></i>
                    <div class="stat-label">Solde net</div>
                    <div class="stat-value balance"><?= $balSign . $money($s['balance']) ?></div>
                </div>
            </div>
            <p class="stat-nb"><?= (int) $s['nb_transactions'] ?> transaction(s) enregistrée(s) sur la période.</p>
        </div>
        <?php endif; ?>

        <?php if (in_array('transactions', $sections, true) && !empty($data['transactions'])): ?>
        <div class="section">
            <div class="section-title">
                <i class="bi bi-credit-card"></i> Transactions détaillées
            </div>
            <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Catégorie</th>
                        <th>Budget</th>
                        <th class="right">Montant</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($data['transactions'] as $tx): ?>
                    <?php
                    $cls  = $tx['type'] === 'income' ? 'income-amt' : 'expense-amt';
                    $sign = $tx['type'] === 'income' ? '+' : '&minus;';
                    ?>
                    <tr>
                        <td><?= $h(date('d/m/Y', strtotime((string) $tx['date']))) ?></td>
                        <td><?= $h($tx['description'] ?? '—') ?></td>
                        <td><?= $h($tx['category_name'] ?? 'Non catégorisé') ?></td>
                        <td><?= $h($tx['budget_name']) ?></td>
                        <td class="right amount <?= $cls ?>"><?= $sign . $money($tx['amount']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if (in_array('budgets', $sections, true) && !empty($data['budgets'])): ?>
        <div class="section">
            <div class="section-title">
                <i class="bi bi-wallet2"></i> État des budgets
            </div>
            <div class="budget-grid">
            <?php foreach ($data['budgets'] as $b): ?>
                <?php
                $typeBadge  = $b['type'] === 'shared' ? 'badge-shared' : 'badge-personal';
                $typeLabel2 = $b['type'] === 'shared' ? 'Partagé' : 'Personnel';
                $hasLimit   = (float) $b['amount_limit'] > 0;
                $pct        = $hasLimit ? min((int) round(((float) $b['spent'] / (float) $b['amount_limit']) * 100), 100) : 0;
                $progCls    = $pct >= 100 ? 'prog-danger' : ($pct >= 80 ? 'prog-warn' : 'prog-ok');
                $sBadge     = $pct >= 100 ? 'badge-over'  : ($pct >= 80 ? 'badge-warn' : 'badge-ok');
                $sLabel     = $pct >= 100 ? 'Dépassé'     : ($pct >= 80 ? 'Proche'     : 'OK');
                ?>
                <div class="budget-item">
                    <div class="budget-row">
                        <div class="budget-name"><?= $h($b['name']) ?></div>
                        <div class="budget-badges">
                            <span class="badge <?= $typeBadge ?>"><?= $h($typeLabel2) ?></span>
                            <?php if ($hasLimit): ?>
                            <span class="badge <?= $sBadge ?>"><?= $h($sLabel) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($hasLimit): ?>
                    <div class="progress-bg">
                        <div class="progress-fill <?= $progCls ?>" style="width:<?= $pct ?>%"></div>
                    </div>
                    <div class="budget-info">
                        <?= $money($b['spent']) ?> / <?= $money($b['amount_limit']) ?> &nbsp;&mdash;&nbsp; <?= $pct ?>%
                    </div>
                    <?php else: ?>
                    <div class="budget-info"><?= $money($b['spent']) ?> dépensés (sans plafond)</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (in_array('categories', $sections, true) && !empty($data['categories'])): ?>
        <?php $totalCat = array_sum(array_column($data['categories'], 'total')); ?>
        <div class="section">
            <div class="section-title">
                <i class="bi bi-tag"></i> Répartition par catégorie
            </div>
            <?php foreach ($data['categories'] as $cat): ?>
            <?php $pct = $totalCat > 0 ? round(((float) $cat['total'] / (float) $totalCat) * 100) : 0; ?>
            <div class="cat-bar-row">
                <div class="cat-dot" style="background:<?= $h($cat['color'] ?? '#22D3A5') ?>"></div>
                <div class="cat-name"><?= $h($cat['name']) ?></div>
                <div class="cat-bar-bg">
                    <div class="cat-bar-fill"
                         style="width:<?= $pct ?>%;background:<?= $h($cat['color'] ?? '#22D3A5') ?>"></div>
                </div>
                <div class="cat-amount">&minus;<?= $money($cat['total']) ?></div>
                <div class="cat-pct"><?= $pct ?>%</div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>

    <div class="rf">
        <span>CashToCash &mdash; <strong>ITEAM University</strong></span>
        <span>Rapport généré le <?= $dateGen ?> &bull; <?= $userName ?></span>
    </div>

</div>

<script>
window.addEventListener('load', function() {
    setTimeout(function() { window.print(); }, 600);
});
</script>
</body>
</html>
