<?php
$e = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$allUsers = $allUsers ?? [];
?>

<!-- Flash messages -->
<?php if (!empty($flashSuccess)): ?>
<div class="alert alert-success d-flex align-items-center gap-2 mb-4" style="border-radius:12px;border:1px solid rgba(0,237,100,0.2);background:rgba(0,237,100,0.08);color:#00684a;">
    <i class="bi bi-check-circle-fill"></i>
    <span><?= $e($flashSuccess) ?></span>
</div>
<?php endif; ?>
<?php if (!empty($flashDanger)): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 mb-4" style="border-radius:12px;border:1px solid rgba(225,29,72,0.2);background:rgba(225,29,72,0.08);color:#e11d48;">
    <i class="bi bi-exclamation-circle-fill"></i>
    <span><?= $e($flashDanger) ?></span>
</div>
<?php endif; ?>
<?php if (!empty($flashInfo)): ?>
<div class="alert alert-info d-flex align-items-center gap-2 mb-4" style="border-radius:12px;border:1px solid rgba(0,108,250,0.2);background:rgba(0,108,250,0.08);color:#006cfa;">
    <i class="bi bi-info-circle-fill"></i>
    <span><?= $flashInfo /* peut contenir du HTML sûr */ ?></span>
</div>
<?php endif; ?>

<form method="post" action="/admin/send-email" id="sendEmailForm">
    <?= CSRF::getTokenField() ?>

    <div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;">

        <!-- Colonne gauche : composition -->
        <div class="adm-card">
            <span class="adm-card-mono">Composition</span>
            <div class="adm-card-title">Rédiger le message</div>

            <!-- Sujet -->
            <div class="mb-4">
                <label class="form-label" style="font-size:12px;font-weight:600;color:#5c6c75;text-transform:uppercase;letter-spacing:1px;">Sujet</label>
                <input type="text" name="subject" class="form-control" required
                    placeholder="Objet de votre message…"
                    value="<?= $e($_POST['subject'] ?? '') ?>"
                    style="border-radius:10px;border:1px solid #b8c4c2;font-size:14px;padding:10px 14px;color:#001e2b;">
            </div>

            <!-- Corps du message -->
            <div class="mb-4">
                <label class="form-label" style="font-size:12px;font-weight:600;color:#5c6c75;text-transform:uppercase;letter-spacing:1px;">Message</label>
                <textarea name="message_body" class="form-control" rows="10" required
                    placeholder="Rédigez votre message ici…"
                    style="border-radius:10px;border:1px solid #b8c4c2;font-size:14px;padding:10px 14px;color:#001e2b;resize:vertical;"><?= $e($_POST['message_body'] ?? '') ?></textarea>
                <div style="font-size:11px;color:#5c6c75;margin-top:6px;">
                    <i class="bi bi-info-circle"></i> Les sauts de ligne seront préservés dans l'email.
                </div>
            </div>

            <!-- Boutons -->
            <div style="display:flex;gap:10px;align-items:center;">
                <button type="submit" class="btn" id="sendBtn"
                    style="background:#001e2b;color:#00ed64;border:1px solid rgba(0,237,100,0.3);border-radius:100px;padding:10px 28px;font-size:14px;font-weight:600;display:inline-flex;align-items:center;gap:8px;">
                    <i class="bi bi-send-fill"></i> Envoyer
                </button>
                <a href="/admin" class="adm-btn-dark">
                    <i class="bi bi-arrow-left"></i> Annuler
                </a>
                <span id="sendingMsg" style="display:none;font-size:13px;color:#5c6c75;font-style:italic;">
                    <i class="bi bi-hourglass-split"></i> Envoi en cours…
                </span>
            </div>
        </div>

        <!-- Colonne droite : sélection des destinataires -->
        <div class="adm-card" style="position:sticky;top:80px;">
            <span class="adm-card-mono">Destinataires</span>
            <div class="adm-card-title">Choisir les destinataires</div>

            <!-- Sélection rapide -->
            <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
                <button type="button" onclick="selectAll()" class="adm-btn-approve" style="font-size:11px;padding:5px 12px;">
                    <i class="bi bi-check-all"></i> Tous
                </button>
                <button type="button" onclick="selectNone()" class="adm-btn-reject" style="font-size:11px;padding:5px 12px;">
                    <i class="bi bi-x-lg"></i> Aucun
                </button>
                <button type="button" onclick="selectActive()" style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:100px;border:1px solid rgba(0,237,100,0.3);background:transparent;color:#00684a;font-size:11px;font-weight:600;cursor:pointer;">
                    <i class="bi bi-person-check"></i> Actifs
                </button>
            </div>

            <!-- Compteur -->
            <div style="font-size:12px;color:#5c6c75;margin-bottom:12px;">
                <span id="selectedCount">0</span> destinataire(s) sélectionné(s)
            </div>

            <!-- Recherche rapide -->
            <div style="display:flex;align-items:center;gap:8px;background:#f5f7f7;border:1px solid #b8c4c2;border-radius:8px;padding:6px 12px;margin-bottom:14px;">
                <i class="bi bi-search" style="color:#b8c4c2;font-size:12px;"></i>
                <input type="text" id="userSearch" placeholder="Filtrer les utilisateurs…"
                    oninput="filterUsers(this.value)"
                    style="background:none;border:none;outline:none;font-size:13px;color:#001e2b;width:100%;">
            </div>

            <!-- Liste des utilisateurs -->
            <div id="userList" style="max-height:360px;overflow-y:auto;display:flex;flex-direction:column;gap:4px;">
                <?php if (empty($allUsers)): ?>
                    <div style="text-align:center;padding:24px;color:#5c6c75;font-size:13px;">
                        <i class="bi bi-people" style="font-size:24px;display:block;margin-bottom:8px;"></i>
                        Aucun utilisateur trouvé.
                    </div>
                <?php else: ?>
                    <?php foreach ($allUsers as $u): ?>
                    <?php
                    $uid    = (int) $u['id'];
                    $uname  = $e($u['name']);
                    $uemail = $e($u['email']);
                    $active = (bool) $u['is_active'];
                    $checked = isset($_POST['user_ids']) && in_array($uid, (array) $_POST['user_ids'], false);
                    ?>
                    <label class="user-row" data-active="<?= $active ? '1' : '0' ?>"
                        style="display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:8px;cursor:pointer;border:1px solid transparent;transition:background .15s;">
                        <input type="checkbox" name="user_ids[]" value="<?= $uid ?>"
                            class="user-cb form-check-input" style="margin:0;flex-shrink:0;"
                            onchange="updateCount()"
                            <?= $checked ? 'checked' : '' ?>>
                        <div style="min-width:28px;width:28px;height:28px;border-radius:8px;background:<?= $active ? 'rgba(0,237,100,0.15)' : 'rgba(245,158,11,0.15)' ?>;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:<?= $active ? '#00684a' : '#d97706' ?>;flex-shrink:0;">
                            <?= strtoupper(mb_substr($u['name'], 0, 1, 'UTF-8')) ?>
                        </div>
                        <div style="min-width:0;flex:1;">
                            <div style="font-size:13px;font-weight:500;color:#001e2b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= $uname ?></div>
                            <div style="font-size:11px;color:#5c6c75;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= $uemail ?></div>
                        </div>
                        <?php if (!$active): ?>
                        <span style="font-size:10px;color:#d97706;flex-shrink:0;">En attente</span>
                        <?php endif; ?>
                    </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</form>

<script>
function updateCount() {
    const n = document.querySelectorAll('.user-cb:checked').length;
    document.getElementById('selectedCount').textContent = n;
}

function selectAll() {
    document.querySelectorAll('.user-cb:not([disabled])').forEach(cb => { cb.checked = true; });
    updateCount();
}

function selectNone() {
    document.querySelectorAll('.user-cb').forEach(cb => { cb.checked = false; });
    updateCount();
}

function selectActive() {
    document.querySelectorAll('.user-row').forEach(row => {
        const cb = row.querySelector('.user-cb');
        if (cb) cb.checked = row.dataset.active === '1';
    });
    updateCount();
}

function filterUsers(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.user-row').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
    });
}

document.getElementById('sendEmailForm').addEventListener('submit', function() {
    const count = document.querySelectorAll('.user-cb:checked').length;
    if (count === 0) {
        alert('Veuillez sélectionner au moins un destinataire.');
        return false;
    }
    document.getElementById('sendBtn').disabled = true;
    document.getElementById('sendingMsg').style.display = 'inline-flex';
});

// Hover effect pour les lignes
document.querySelectorAll('.user-row').forEach(row => {
    row.addEventListener('mouseenter', () => row.style.background = '#f5f7f7');
    row.addEventListener('mouseleave', () => row.style.background = '');
});

updateCount();
</script>
