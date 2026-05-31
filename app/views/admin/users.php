<?php
/**
 * Vue Admin — Gestion des utilisateurs.
 *
 * Reçoit de AdminController::users() :
 *   $users        — page courante d'utilisateurs (avec budget_count, transaction_count)
 *   $filter       — filtre actif : 'all' | 'pending' | 'active' | 'admin'
 *   $page         — numéro de page courante
 *   $totalPages   — nombre total de pages
 *   $totalCount   — nombre total d'utilisateurs (filtre appliqué)
 *   $pendingCount — nombre de comptes en attente (pour le badge)
 *   $currentUser  — données de l'admin connecté
 *   $flash*       — messages flash success / danger / info
 */
$e          = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$currentUid = (int) ($currentUser['id'] ?? 0);
$filters = [
    ['key' => 'all',     'label' => 'Tous'],
    ['key' => 'pending', 'label' => 'En attente', 'count' => $pendingCount],
    ['key' => 'active',  'label' => 'Actifs'],
    ['key' => 'admin',   'label' => 'Admins'],
];
?>

<?php if (!empty($flashSuccess)): ?>
<div class="bf-alert bf-alert-success" role="alert"><i class="bi bi-check-circle"></i> <?= $flashSuccess /* intentional — may contain <strong> for temp password */ ?></div>
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

<!-- Tabs nav (pour rester cohérent avec le dashboard) -->
<div class="adm-tabs">
    <a href="/admin?tab=overview" class="adm-tab">Vue d'ensemble</a>
    <a href="/admin/users"        class="adm-tab active">Utilisateurs</a>
    <a href="/admin?tab=analytics" class="adm-tab">Analytiques</a>
    <a href="/admin/budgets"      class="adm-tab">Budgets</a>
</div>

<!-- Section heading -->
<h2 class="adm-section-heading">Gestion des Utilisateurs</h2>

<!-- Barre de contrôles -->
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <!-- Filtres -->
    <div style="display:flex;gap:6px;flex-wrap:wrap;">
        <?php foreach ($filters as $f): ?>
        <a href="/admin/users?filter=<?= $e($f['key']) ?>"
           style="display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:999px;font-size:13px;font-weight:500;text-decoration:none;border:1px solid;transition:all .15s;
                  <?= $filter === $f['key'] ? 'background:#001e2b;color:#fff;border-color:#001e2b;' : 'background:transparent;color:#5c6c75;border-color:#b8c4c2;' ?>">
            <?= $e($f['label']) ?>
            <?php if (!empty($f['count']) && $f['count'] > 0): ?>
                <span style="font-size:10px;font-weight:700;padding:0 6px;border-radius:99px;min-width:18px;text-align:center;
                             background:<?= $filter === $f['key'] ? 'rgba(255,255,255,0.2)' : '#f59e0b' ?>;
                             color:<?= $filter === $f['key'] ? '#fff' : '#1A1D27' ?>;"><?= $f['count'] ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Recherche + Export -->
    <div style="display:flex;align-items:center;gap:8px;">
        <div class="adm-search" style="min-width:220px;">
            <i class="bi bi-search" style="color:#b8c4c2;font-size:13px;"></i>
            <input id="userSearch" type="search" placeholder="Rechercher nom ou email…"
                   autocomplete="off" oninput="filterUsers(this.value)">
        </div>
        <a href="/admin/users/export?filter=<?= $e($filter) ?>"
           style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:10px;border:1px solid #b8c4c2;background:#fff;color:#3d4f58;font-size:13px;text-decoration:none;font-weight:500;transition:border-color .15s;"
           onmouseover="this.style.borderColor='#001e2b'" onmouseout="this.style.borderColor='#b8c4c2'">
            <i class="bi bi-download"></i> CSV
        </a>
    </div>
</div>

<!-- Tableau -->
<div style="background:#fff;border:1px solid #b8c4c2;border-radius:16px;overflow:hidden;box-shadow:rgba(0,30,43,0.06) 0px 4px 16px;">
    <?php if (empty($users)): ?>
        <div style="text-align:center;padding:64px 16px;color:#b8c4c2;">
            <i class="bi bi-people" style="font-size:40px;display:block;margin-bottom:12px;"></i>
            Aucun utilisateur dans cette catégorie.
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;" id="usersTable">
                <thead>
                    <tr style="border-bottom:1px solid #b8c4c2;background:#f9fbfb;">
                        <th class="adm-th">Utilisateur</th>
                        <th class="adm-th">Rôle</th>
                        <th class="adm-th">Statut</th>
                        <th class="adm-th" style="text-align:center;">Budgets</th>
                        <th class="adm-th" style="text-align:center;">Transactions</th>
                        <th class="adm-th">Inscrit le</th>
                        <th class="adm-th" style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <?php foreach ($users as $i => $u): ?>
                    <?php
                        $uid       = (int) $u['id'];
                        $isMe      = $uid === $currentUid;
                        $isPending = !(bool) $u['is_active'];
                        $isAdmin   = $u['role'] === 'admin';
                        $uParts    = preg_split('/\s+/', trim((string) $u['name'])) ?: ['?'];
                        $uInit     = '';
                        foreach (array_slice($uParts, 0, 2) as $p) {
                            $uInit .= $p !== '' ? strtoupper(mb_substr($p, 0, 1, 'UTF-8')) : '';
                        }
                        $uInit = $uInit ?: '?';
                    ?>
                    <tr class="user-row"
                        data-search="<?= $e(strtolower($u['name'] . ' ' . $u['email'])) ?>"
                        style="border-bottom:<?= $i < count($users) - 1 ? '1px solid #f0f2f2' : 'none' ?>;"
                        onmouseover="this.style.background='#f9fbfb'" onmouseout="this.style.background='transparent'">

                        <!-- Utilisateur -->
                        <td class="adm-td">
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div class="adm-avatar" style="background:<?= $isAdmin ? '#00684a' : '#1c2d38' ?>;color:#fff;">
                                    <?= $e($uInit) ?>
                                </div>
                                <div>
                                    <div style="font-size:13px;font-weight:600;color:#001e2b;">
                                        <?= $e($u['name']) ?>
                                        <?php if ($isMe): ?><span style="font-size:10px;background:rgba(0,108,250,.1);color:#006cfa;padding:1px 6px;border-radius:4px;margin-left:4px;">Moi</span><?php endif; ?>
                                    </div>
                                    <div style="font-size:11px;color:#5c6c75;"><?= $e($u['email']) ?></div>
                                </div>
                            </div>
                        </td>

                        <!-- Rôle -->
                        <td class="adm-td">
                            <?php if ($isAdmin): ?>
                                <span class="adm-badge-role-admin"><i class="bi bi-shield-fill" style="margin-right:4px;"></i>Admin</span>
                            <?php else: ?>
                                <span class="adm-badge-role-user">User</span>
                            <?php endif; ?>
                        </td>

                        <!-- Statut -->
                        <td class="adm-td">
                            <?php if ($isPending): ?>
                                <span class="adm-badge-pending">Pending</span>
                            <?php elseif ((bool) $u['is_active']): ?>
                                <span class="adm-badge-active">Active</span>
                            <?php else: ?>
                                <span class="adm-badge-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>

                        <!-- Budgets -->
                        <td class="adm-td" style="text-align:center;font-size:13px;font-weight:600;color:#001e2b;"><?= $e($u['budget_count']) ?></td>

                        <!-- Transactions -->
                        <td class="adm-td" style="text-align:center;font-size:13px;font-weight:600;color:#001e2b;"><?= $e($u['transaction_count']) ?></td>

                        <!-- Date -->
                        <td class="adm-td" style="font-family:'Source Code Pro',monospace;font-size:12px;color:#5c6c75;">
                            <?= $e(date('d/m/Y', strtotime((string) $u['created_at']))) ?>
                        </td>

                        <!-- Actions -->
                        <td class="adm-td" style="text-align:right;">
                            <?php if ($isMe): ?>
                                <span style="font-size:11px;color:#b8c4c2;">—</span>
                            <?php elseif ($isPending): ?>
                                <div style="display:flex;gap:6px;justify-content:flex-end;">
                                    <form method="post" action="/admin/users/validate">
                                        <?= CSRF::getTokenField() ?>
                                        <input type="hidden" name="user_id" value="<?= $e($uid) ?>">
                                        <button type="submit" class="adm-btn-approve" style="font-size:12px;">
                                            <i class="bi bi-check2"></i> Valider
                                        </button>
                                    </form>
                                    <form method="post" action="/admin/users/delete" onsubmit="return confirm('Supprimer <?= addslashes($e($u['name'])) ?> ?')">
                                        <?= CSRF::getTokenField() ?>
                                        <input type="hidden" name="user_id" value="<?= $e($uid) ?>">
                                        <button type="submit" class="adm-btn-reject" style="font-size:12px;">
                                            <i class="bi bi-x"></i> Refuser
                                        </button>
                                    </form>
                                </div>
                            <?php elseif (!$isAdmin): ?>
                                <div style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap;">
                                    <form method="post" action="/admin/users/reset-password" onsubmit="return confirm('Réinitialiser le mot de passe de <?= addslashes($e($u['name'])) ?> ?')">
                                        <?= CSRF::getTokenField() ?>
                                        <input type="hidden" name="user_id" value="<?= $e($uid) ?>">
                                        <button type="submit" class="adm-btn-approve" style="background:#006cfa;border:none;font-size:12px;" title="Réinitialiser le mot de passe">
                                            <i class="bi bi-key"></i>
                                        </button>
                                    </form>
                                    <form method="post" action="/admin/users/role">
                                        <?= CSRF::getTokenField() ?>
                                        <input type="hidden" name="user_id" value="<?= $e($uid) ?>">
                                        <input type="hidden" name="new_role" value="admin">
                                        <button type="submit" class="adm-btn-dark" style="font-size:12px;padding:6px 14px;">
                                            <i class="bi bi-shield-plus"></i> → Admin
                                        </button>
                                    </form>
                                    <form method="post" action="/admin/users/delete" onsubmit="return confirm('Supprimer <?= addslashes($e($u['name'])) ?> ?')">
                                        <?= CSRF::getTokenField() ?>
                                        <input type="hidden" name="user_id" value="<?= $e($uid) ?>">
                                        <button type="submit" class="adm-btn-reject" style="font-size:12px;"><i class="bi bi-trash3"></i></button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <form method="post" action="/admin/users/role">
                                    <?= CSRF::getTokenField() ?>
                                    <input type="hidden" name="user_id" value="<?= $e($uid) ?>">
                                    <input type="hidden" name="new_role" value="user">
                                    <button type="submit" class="adm-btn-approve" style="background:#f5f7f7;color:#5c6c75;border:1px solid #b8c4c2;font-size:12px;">
                                        <i class="bi bi-shield-minus"></i> → User
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- No search results -->
        <div id="noSearchResults" style="display:none;text-align:center;padding:40px;color:#b8c4c2;">
            <i class="bi bi-search" style="font-size:32px;display:block;margin-bottom:10px;"></i>
            Aucun utilisateur ne correspond à la recherche.
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-top:1px solid #f0f2f2;">
            <span style="font-size:12px;color:#5c6c75;font-family:'Source Code Pro',monospace;">
                Page <?= $e($page) ?> / <?= $e($totalPages) ?> — <?= $e($totalCount) ?> utilisateurs
            </span>
            <div style="display:flex;gap:6px;">
                <?php if ($page > 1): ?>
                <a href="/admin/users?filter=<?= $e($filter) ?>&page=<?= $page - 1 ?>"
                   style="padding:6px 14px;border-radius:8px;border:1px solid #b8c4c2;font-size:13px;color:#3d4f58;text-decoration:none;">← Préc.</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                <a href="/admin/users?filter=<?= $e($filter) ?>&page=<?= $page + 1 ?>"
                   style="padding:6px 14px;border-radius:8px;border:1px solid #b8c4c2;font-size:13px;color:#3d4f58;text-decoration:none;">Suiv. →</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php $scripts = <<<'JS'
<script>
function filterUsers(q) {
    q = q.toLowerCase().trim();
    let visible = 0;
    document.querySelectorAll('#usersTableBody .user-row').forEach(row => {
        const match = !q || row.dataset.search.includes(q);
        row.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    const el = document.getElementById('noSearchResults');
    if (el) el.style.display = visible === 0 && q ? 'block' : 'none';
}
document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        const s = document.getElementById('userSearch');
        if (s) { e.preventDefault(); s.focus(); }
    }
});
</script>
JS;
?>
