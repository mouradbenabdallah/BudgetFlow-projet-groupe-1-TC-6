<?php
/**
 * Vue Admin — Supervision des budgets partagés.
 *
 * Reçoit de AdminController::budgets() :
 *   $budgets       — tous les budgets de type "shared" avec agrégats (total_spent, member_count…)
 *   $flashSuccess  — message de succès éventuel
 *   $flashDanger   — message d'erreur éventuel
 */
$e = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>

<?php if (!empty($flashSuccess)): ?>
<div class="bf-alert bf-alert-success" role="alert"><i class="bi bi-check-circle"></i> <?= $e($flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashDanger)): ?>
<div class="bf-alert bf-alert-danger" role="alert"><i class="bi bi-x-circle"></i> <?= $e($flashDanger) ?></div>
<?php endif; ?>

<!-- Badge accès admin -->
<div class="adm-badge-access">
    <i class="bi bi-shield-check"></i> Admin Access
</div>

<!-- Tabs nav -->
<div class="adm-tabs">
    <a href="/admin?tab=overview"   class="adm-tab">Vue d'ensemble</a>
    <a href="/admin/users"          class="adm-tab">Utilisateurs</a>
    <a href="/admin?tab=analytics"  class="adm-tab">Analytiques</a>
    <a href="/admin/budgets"        class="adm-tab active">Budgets</a>
</div>

<!-- En-tête -->
<h2 class="adm-section-heading">Budgets Partagés</h2>
<p style="font-size:14px;font-weight:300;color:#5c6c75;margin-bottom:24px;">Supervision de tous les budgets collaboratifs de la plateforme.</p>

<!-- Tableau -->
<div style="background:#fff;border:1px solid #b8c4c2;border-radius:16px;overflow:hidden;box-shadow:rgba(0,30,43,0.06) 0px 4px 16px;">
    <?php if (empty($budgets)): ?>
        <div style="text-align:center;padding:64px 16px;color:#b8c4c2;">
            <i class="bi bi-wallet2" style="font-size:40px;display:block;margin-bottom:12px;"></i>
            Aucun budget partagé sur la plateforme.
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:1px solid #b8c4c2;background:#f9fbfb;">
                        <th class="adm-th">Budget</th>
                        <th class="adm-th">Propriétaire</th>
                        <th class="adm-th" style="text-align:center;">Membres</th>
                        <th class="adm-th" style="text-align:center;">Transactions</th>
                        <th class="adm-th" style="text-align:right;">Dépenses</th>
                        <th class="adm-th" style="text-align:right;">Plafond</th>
                        <th class="adm-th">Statut</th>
                        <th class="adm-th">Créé le</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($budgets as $i => $budget): ?>
                    <?php
                        $spent  = (float) $budget['total_spent'];
                        $limit  = $budget['amount_limit'] !== null ? (float) $budget['amount_limit'] : null;
                        $pct    = ($limit !== null && $limit > 0) ? ($spent / $limit * 100) : null;
                        $isLast = $i === count($budgets) - 1;

                        if ($limit === null) {
                            $statusLabel = 'Sans limite';
                            $statusCls   = 'adm-badge-role-user';
                        } elseif ($pct >= 100) {
                            $statusLabel = '<i class="bi bi-exclamation-octagon-fill" style="margin-right:4px;"></i>Dépassé';
                            $statusCls   = 'adm-badge-inactive';
                        } elseif ($pct >= 80) {
                            $statusLabel = '<i class="bi bi-exclamation-triangle-fill" style="margin-right:4px;"></i>Proche';
                            $statusCls   = 'adm-badge-pending';
                        } else {
                            $statusLabel = '<i class="bi bi-check2" style="margin-right:4px;"></i>Maîtrisé';
                            $statusCls   = 'adm-badge-active';
                        }
                    ?>
                    <tr style="border-bottom:<?= $isLast ? 'none' : '1px solid #f0f2f2' ?>;"
                        onmouseover="this.style.background='#f9fbfb'" onmouseout="this.style.background='transparent'">

                        <td class="adm-td">
                            <a href="/budgets/show?id=<?= $e($budget['id']) ?>" style="font-weight:600;color:#001e2b;text-decoration:none;" onmouseover="this.style.color='#00684a'" onmouseout="this.style.color='#001e2b'">
                                <?= $e($budget['name']) ?>
                            </a>
                            <div style="font-size:10px;color:#b8c4c2;text-transform:uppercase;letter-spacing:.5px;font-family:'Source Code Pro',monospace;margin-top:2px;"><?= $e($budget['period']) ?></div>
                        </td>

                        <td class="adm-td">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div style="width:30px;height:30px;border-radius:8px;background:#1c2d38;color:#b8c4c2;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;">
                                    <?= $e(strtoupper(mb_substr((string) $budget['owner_name'], 0, 2, 'UTF-8'))) ?>
                                </div>
                                <div>
                                    <div style="font-size:13px;font-weight:500;color:#001e2b;"><?= $e($budget['owner_name']) ?></div>
                                    <div style="font-size:11px;color:#5c6c75;"><?= $e($budget['owner_email']) ?></div>
                                </div>
                            </div>
                        </td>

                        <td class="adm-td" style="text-align:center;font-weight:600;color:#001e2b;"><?= $e($budget['member_count']) ?></td>
                        <td class="adm-td" style="text-align:center;font-weight:600;color:#001e2b;"><?= $e($budget['transaction_count']) ?></td>

                        <td class="adm-td" style="text-align:right;font-weight:700;color:#e11d48;font-variant-numeric:tabular-nums;font-family:'Source Code Pro',monospace;font-size:12px;">
                            <?= number_format($spent, 3, ',', ' ') ?> DT
                        </td>
                        <td class="adm-td" style="text-align:right;color:#5c6c75;font-variant-numeric:tabular-nums;font-family:'Source Code Pro',monospace;font-size:12px;">
                            <?= $limit !== null ? number_format($limit, 3, ',', ' ') . ' DT' : '—' ?>
                        </td>

                        <td class="adm-td"><span class="<?= $statusCls ?>"><?= $statusLabel ?></span></td>

                        <td class="adm-td" style="font-family:'Source Code Pro',monospace;font-size:12px;color:#5c6c75;">
                            <?= $e(date('d/m/Y', strtotime((string) $budget['created_at']))) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="padding:12px 20px;border-top:1px solid #f0f2f2;">
            <span style="font-size:11px;color:#b8c4c2;font-family:'Source Code Pro',monospace;text-transform:uppercase;letter-spacing:1px;">
                <?= count($budgets) ?> budget<?= count($budgets) > 1 ? 's' : '' ?> partagé<?= count($budgets) > 1 ? 's' : '' ?>
            </span>
        </div>
    <?php endif; ?>
</div>
