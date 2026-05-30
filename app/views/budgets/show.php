<?php
/**
 * Vue : Détail d'un budget partagé (Shared Budget Detail)
 *
 * Layout inspiré de l'image budget_partager.png :
 * - Sidebar gauche : liste des groupes/budgets partagés
 * - Panneau sombre droit : détails du budget, progression, membres
 * - Panneau blanc : transactions avec formulaire inline
 *
 * Design system : MongoDB-inspired (design.md)
 * - Forest black (#001e2b) pour les panneaux sombres
 * - MongoDB Green (#00ed64) comme accent
 * - Source Code Pro uppercase pour les labels techniques
 * - Ombres teintées teal : rgba(0,30,43,0.12)
 */

$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$formatDT = static fn (mixed $amount): string => number_format((float) $amount, 2, ',', ' ') . ' DT';

/** Données injectées par le contrôleur */
$budget = $budget ?? [];
$isOwner = $isOwner ?? false;
$members = $members ?? [];
$memberContributions = $memberContributions ?? [];
$transactions = $transactions ?? [];
$categories = $categories ?? [];
$percent = $percent ?? 0;
$status = $status ?? 'ok';
$userId = (int) ($user['id'] ?? 0);

/** Calculs dérivés */
$spent = (float) ($budget['spent'] ?? 0);
$income = (float) ($budget['income'] ?? 0);
$balance = (float) ($budget['balance'] ?? 0);
$limit = $budget['amount_limit'] !== null ? (float) $budget['amount_limit'] : null;
$remaining = $limit !== null ? $limit - $spent : null;

/** Couleurs de progression selon le statut (design.md : danger/warning/ok) */
$progressColor = match ($status) {
    'danger' => '#FF4D4D',
    'warning' => '#FFB547',
    default => '#22D3A5',
};

/** Liste complète des membres (propriétaire + membres invités) */
$ownerData = ['id' => $userId, 'name' => $user['name'] ?? 'Propriétaire', 'email' => $user['email'] ?? ''];
$allMembers = array_merge([$ownerData], $members);
$memberCount = count($allMembers);

/** Budgets partagés pour la sidebar gauche */
$sharedBudgets = $sharedBudgets ?? [];
$currentBudgetId = (int) ($budget['id'] ?? 0);

/** Génère les initiales d'un nom (ex: "Alex Johnson" → "AJ") */
function bfShowGetInitials(string $name): string {
    $parts = explode(' ', trim($name), 2);
    $i = '';
    if (!empty($parts[0])) $i .= strtoupper(substr($parts[0], 0, 1));
    if (!empty($parts[1])) $i .= strtoupper(substr($parts[1], 0, 1));
    elseif (!empty($parts[0]) && strlen($parts[0]) > 1) $i = strtoupper(substr($parts[0], 0, 2));
    return $i ?: '?';
}
?>

<div class="bf-page">

    <!-- ============================================================
         SIDEBAR GAUCHE — Liste des groupes/budgets partagés
         ============================================================ -->
    <aside class="bf-shared-sidebar">
        <p class="bf-shared-sidebar-title">
            Mes groupes <span class="count">(<?= count($sharedBudgets) ?>)</span>
            <a href="/budgets/create" class="bf-btn-new"><i class="bi bi-plus"></i> Nouveau</a>
        </p>

        <?php foreach ($sharedBudgets as $sb): ?>
        <?php
            $sbSpent = (float) ($sb['spent'] ?? 0);
            $sbLimit = $sb['amount_limit'] !== null ? (float) $sb['amount_limit'] : 0;
            $sbPct = $sbLimit > 0 ? min(100, ($sbSpent / $sbLimit) * 100) : 0;
            $sbFillClass = $sbPct >= 100 ? 'danger' : ($sbPct >= 80 ? 'warning' : '');
            $sbMembers = $sb['members'] ?? [];
            $sbActive = (int) ($sb['id'] ?? 0) === $currentBudgetId;
            $sbMemberCount = count($sbMembers) + 1;
        ?>
        <a href="/budgets/show?id=<?= $e((int) ($sb['id'] ?? 0)) ?>" class="bf-group-card <?= $sbActive ? 'active' : '' ?>">
            <div class="bf-group-card-header">
                <div>
                    <p class="bf-group-card-name"><?= $e($sb['name'] ?? 'Budget') ?></p>
                    <p class="bf-group-card-period"><?= $sbMemberCount ?> membre<?= $sbMemberCount > 1 ? 's' : '' ?></p>
                </div>
                <div class="bf-group-card-members">
                    <?php foreach (array_slice($sbMembers, 0, 3) as $m): ?>
                    <span class="bf-group-avatar" title="<?= $e($m['name'] ?? '') ?>"><?= bfShowGetInitials($m['name'] ?? '') ?></span>
                    <?php endforeach; ?>
                    <?php if (count($sbMembers) > 3): ?><span class="bf-group-avatar" style="background:#5c6c75">+<?= count($sbMembers) - 3 ?></span><?php endif; ?>
                </div>
            </div>
            <?php if ($sbLimit > 0): ?>
            <div class="bf-group-progress" style="background:<?= $sbActive ? '#3d4f58' : '#f0f2f2' ?>">
                <span class="bf-group-progress-fill <?= $sbFillClass ?>" style="width:<?= $sbPct ?>%"></span>
            </div>
            <?php else: ?>
            <div style="height:4px;background:<?= $sbActive ? '#3d4f58' : '#f0f2f2' ?>;border-radius:2px;margin-bottom:6px"></div>
            <?php endif; ?>
            <div class="bf-group-card-footer">
                <span class="bf-group-spent"><?= $formatDT($sbSpent) ?> dépensé</span>
                <span class="bf-group-pct" style="color:<?= $sbFillClass === 'danger' ? '#FF4D4D' : ($sbFillClass === 'warning' ? '#FFB547' : '#00ed64') ?>"><?= number_format($sbPct, 0) ?>%</span>
            </div>
        </a>
        <?php endforeach; ?>

        <?php if (empty($sharedBudgets)): ?>
        <div class="bf-empty-state-inline">
            <i class="bi bi-people bf-icon-xl bf-text-muted bf-icon-block bf-mb-12"></i>
            Aucun budget partagé. Créez-en un !
        </div>
        <?php endif; ?>
    </aside>

    <!-- ============================================================
         CONTENU PRINCIPAL — Panneaux droit
         ============================================================ -->
    <main class="bf-shared-main">

        <!-- Alertes budget (warning ≥80%, danger ≥100%) -->
        <?php if ($status === 'warning'): ?>
        <div class="bf-alert bf-alert-warning">
            <i class="bi bi-exclamation-triangle"></i>
            Attention : vous avez consommé <?= number_format($percent, 0) ?>% de votre budget
        </div>
        <?php endif; ?>
        <?php if ($status === 'danger'): ?>
        <div class="bf-alert bf-alert-danger">
            <i class="bi bi-x-circle"></i>
            Budget dépassé de <?= number_format($overAmount ?? 0, 2, ',', ' ') ?> DT
        </div>
        <?php endif; ?>

        <!-- ============================================================
             PANNEAU SOMBRE — Détails du budget + Membres
             Design : #001e2b forest-black (design.md §1)
             ============================================================ -->
        <div class="bf-budget-detail-panel">
            <div class="bf-budget-detail-header">
                <div>
                    <p class="bf-budget-detail-label">Shared Budget</p>
                    <h2 class="bf-budget-detail-name"><?= $e($budget['name'] ?? 'Budget') ?></h2>
                    <p class="bf-budget-detail-date">Créé le <?= date('d/m/Y', strtotime($budget['start_date'] ?? date('Y-m-d'))) ?></p>
                </div>
                <div class="bf-budget-detail-amount">
                    <p class="bf-budget-detail-spent"><?= $formatDT($spent) ?></p>
                    <p class="bf-budget-detail-limit">sur <?= $limit !== null ? $formatDT($limit) : 'Illimité' ?> budget</p>
                </div>
            </div>

            <?php if ($limit !== null && $limit > 0): ?>
            <div class="bf-budget-progress-track">
                <span class="bf-budget-progress-fill" style="width:<?= min(100, $percent) ?>%;background:<?= $progressColor ?>"></span>
            </div>
            <div class="bf-budget-progress-info">
                <span class="bf-budget-progress-pct" style="color:<?= $progressColor ?>"><?= number_format($percent, 0) ?>% utilisé</span>
                <span class="bf-budget-progress-remaining"><?= $remaining >= 0 ? '<span style="color:#00ed64;font-weight:700">' . $formatDT($remaining) . '</span> restant' : '<span style="color:#FF4D4D;font-weight:700">' . $formatDT(abs($remaining)) . '</span> dépassé' ?></span>
            </div>
            <?php endif; ?>

            <!-- ============================================================
                 SECTION MEMBRES — Collaboration
                 ============================================================ -->
            <div class="bf-members-section">
                <div class="bf-members-header">
                    <p class="bf-members-title">Membres (<?= $memberCount ?>)</p>
                    <?php if ($isOwner): ?>
                    <button type="button" class="bf-btn-add-member" id="btnAddMember">
                        <i class="bi bi-plus"></i> Ajouter membre
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Formulaire d'invitation (caché par défaut, toggle via JS) -->
                <?php if ($isOwner): ?>
                <form method="post" action="/budgets/invite" class="bf-invite-form" id="inviteForm">
                    <?= CSRF::getTokenField() ?>
                    <input type="hidden" name="budget_id" value="<?= $e((int) ($budget['id'] ?? 0)) ?>">
                    <input type="email" name="email" class="bf-invite-input" placeholder="membre@email.com" required>
                    <button type="submit" class="bf-invite-btn"><i class="bi bi-check-lg"></i></button>
                    <button type="button" class="bf-invite-cancel" id="btnCancelInvite"><i class="bi bi-x-lg"></i></button>
                </form>
                <?php endif; ?>

                <!-- Liste des membres -->
                <?php foreach ($allMembers as $member): ?>
                <?php
                    $contribData = null;
                    foreach ($memberContributions as $mc) {
                        if ((int) ($mc['id'] ?? 0) === (int) ($member['id'] ?? 0)) { $contribData = $mc; break; }
                    }
                    $contribAmount = $contribData !== null ? (float) ($contribData['contributed'] ?? 0) : 0;
                ?>
                <div class="bf-member-row">
                    <div class="bf-member-avatar <?= ($member['id'] ?? 0) === $userId ? 'owner' : '' ?>">
                        <?= bfShowGetInitials($member['name'] ?? '') ?>
                        <?php if (($member['id'] ?? 0) === (int) ($budget['owner_id'] ?? 0)): ?>
                        <div class="owner-badge">★</div>
                        <?php endif; ?>
                    </div>
                    <div class="bf-member-info">
                        <p class="bf-member-name"><?= $e($member['name'] ?? '') ?></p>
                        <p class="bf-member-email"><?= $e($member['email'] ?? '') ?></p>
                    </div>
                    <div class="bf-member-contrib">
                        <p class="bf-member-contrib-amount">+<?= $formatDT($contribAmount) ?></p>
                        <p class="bf-member-contrib-label">contribué</p>
                    </div>
                    <?php if ($isOwner && ($member['id'] ?? 0) !== $userId): ?>
                    <form method="post" action="/budgets/remove-member" class="bf-form-display-inline">
                        <?= CSRF::getTokenField() ?>
                        <input type="hidden" name="budget_id" value="<?= $e((int) ($budget['id'] ?? 0)) ?>">
                        <input type="hidden" name="user_id" value="<?= $e((int) ($member['id'] ?? 0)) ?>">
                        <button type="submit" class="bf-member-remove" title="Retirer">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ============================================================
             PANNEAU TRANSACTIONS — Fond blanc (dual-mode design.md)
             ============================================================ -->
        <div class="bf-tx-panel">
            <div class="bf-tx-header">
                <div>
                    <p class="bf-tx-label">Group Transactions</p>
                    <h3 class="bf-tx-title">Dépenses partagées</h3>
                </div>
                <button type="button" class="bf-btn-add-tx" id="btnAddTx">
                    <i class="bi bi-plus"></i> Ajouter dépense
                </button>
            </div>

            <!-- Formulaire ajouter dépense (caché par défaut, toggle via JS) -->
            <div class="bf-add-tx-form" id="addTxForm">
                <div class="bf-add-tx-form-inner">
                    <form method="post" action="/transactions/create">
                        <?= CSRF::getTokenField() ?>
                        <input type="hidden" name="budget_id" value="<?= $e((int) ($budget['id'] ?? 0)) ?>">
                        <input type="hidden" name="type" value="expense">
                        <div class="bf-add-tx-row">
                            <div class="bf-add-tx-field">
                                <label>Description</label>
                                <input type="text" name="description" class="bf-add-tx-input" placeholder="ex: Courses" required>
                            </div>
                            <div class="bf-add-tx-field">
                                <label>Montant (DT)</label>
                                <input type="number" name="amount" class="bf-add-tx-input" placeholder="0.00" step="0.01" min="0" required>
                            </div>
                        </div>
                        <input type="hidden" name="user_id" value="<?= $e($userId) ?>">
                        <div class="bf-add-tx-field">
                            <label>Catégorie</label>
                            <select name="category_id" class="bf-add-tx-select">
                                <option value="">Sans catégorie</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $e((int) ($cat['id'] ?? 0)) ?>"><?= $e($cat['name'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="bf-add-tx-actions">
                            <button type="submit" class="bf-add-tx-submit"><i class="bi bi-check-lg bf-mr-4"></i> Ajouter dépense</button>
                            <button type="button" class="bf-add-tx-cancel" id="btnCancelTx">Annuler</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Liste des transactions -->
            <?php if (empty($transactions)): ?>
            <div class="bf-tx-empty">
                <i class="bi bi-receipt bf-icon-2xl bf-muted-text-light bf-icon-block bf-mb-12"></i>
                Aucune transaction pour ce budget.
            </div>
            <?php else: ?>
            <?php foreach ($transactions as $tx): ?>
            <?php
                $categoryName = $tx['category_name'] ?? 'Sans catégorie';
                $description = $tx['description'] ?? '';
                $amount = (float) ($tx['amount'] ?? 0);
                $dateFormatted = date('d/m/Y', strtotime($tx['date'] ?? ''));
                $perPerson = $memberCount > 1 ? $amount / $memberCount : $amount;
            ?>
            <div class="bf-tx-row">
                <div class="bf-tx-icon"><i class="bi bi-arrow-up-right"></i></div>
                <div class="bf-tx-info">
                    <p class="bf-tx-desc"><?= $e($description ?: $categoryName) ?></p>
                    <p class="bf-tx-meta">Payé par <strong><?= $e($tx['user_name'] ?? '') ?></strong> · <?= $dateFormatted ?></p>
                    <?php if ($memberCount > 1): ?>
                    <div class="bf-tx-split">
                        <span class="bf-tx-split-label">Partagé entre :</span>
                        <?php foreach ($allMembers as $m): ?>
                        <span class="bf-tx-split-member"><?= $e($m['name'] ?? '') ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="bf-tx-amount">
                    <p class="bf-tx-amount-value"><?= $formatDT($amount) ?></p>
                    <?php if ($memberCount > 1): ?>
                    <p class="bf-tx-amount-per"><?= $formatDT($perPerson) ?>/personne</p>
                    <?php endif; ?>
                    <span class="bf-tx-category"><?= $e($categoryName) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>
</div>

<script>
/**
 * Toggle du formulaire d'invitation membre
 * Utilise classList.toggle('show') au lieu de style.display
 * pour respecter la cascade CSS
 */
(function() {
    var btnAddMember = document.getElementById('btnAddMember');
    var inviteForm = document.getElementById('inviteForm');
    var btnCancelInvite = document.getElementById('btnCancelInvite');

    if (btnAddMember && inviteForm) {
        btnAddMember.addEventListener('click', function() {
            inviteForm.classList.toggle('show');
        });
    }
    if (btnCancelInvite && inviteForm) {
        btnCancelInvite.addEventListener('click', function() {
            inviteForm.classList.remove('show');
        });
    }

    /**
     * Toggle du formulaire d'ajout de dépense
     */
    var btnAddTx = document.getElementById('btnAddTx');
    var addTxForm = document.getElementById('addTxForm');
    var btnCancelTx = document.getElementById('btnCancelTx');

    if (btnAddTx && addTxForm) {
        btnAddTx.addEventListener('click', function() {
            addTxForm.classList.toggle('show');
        });
    }
    if (btnCancelTx && addTxForm) {
        btnCancelTx.addEventListener('click', function() {
            addTxForm.classList.remove('show');
        });
    }
})();
</script>
