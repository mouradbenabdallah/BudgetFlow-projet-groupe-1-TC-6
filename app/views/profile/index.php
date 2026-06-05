<?php
/**
 * Vue Profil utilisateur — design fidèle à img/profile.png.
 *
 * Reçoit de ProfileController::index() :
 *   $user        — données complètes (name, email, role, is_active, phone, preferences, created_at)
 *   $tab         — onglet actif : 'profile' | 'security' | 'notifications' | 'preferences'
 *   $txCount     — nombre total de transactions
 *   $netBalance  — solde net (revenus − dépenses)
 *   $prefs       — tableau décodé des préférences (currency, language, timezone)
 *   $flash*      — messages flash
 */
$e = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$user        = $user        ?? [];
$tab         = $tab         ?? 'profile';
$txCount     = (int)   ($txCount    ?? 0);
$netBalance  = (float) ($netBalance ?? 0.0);
$prefs       = $prefs       ?? [];
$profileBase = $profileBase ?? '/profile';

$nameParts = preg_split('/\s+/', trim((string) ($user['name'] ?? 'U'))) ?: ['U'];
$initials  = '';
foreach (array_slice($nameParts, 0, 2) as $p) {
    $initials .= $p !== '' ? strtoupper(mb_substr($p, 0, 1, 'UTF-8')) : '';
}
$initials    = $e($initials ?: 'U');
$memberSince = !empty($user['created_at'])
    ? date('M Y', strtotime((string) $user['created_at'])) : '—';
$lastLogin   = !empty($user['last_login_at'])
    ? date('d M Y', strtotime((string) $user['last_login_at'])) : '—';

$currency = $e($prefs['currency'] ?? 'TND');
$language = $e($prefs['language'] ?? 'fr');
$timezone = $e($prefs['timezone'] ?? 'Africa/Tunis');

$netFormatted = number_format(abs($netBalance), 0, ',', ' ') . ' ' . ($prefs['currency'] ?? 'TND');
if ($netBalance < 0) {
    $netFormatted = '-' . $netFormatted;
}
?>

<style>
/* ── Profile page — theme-aware classes ── */
.pf-tab-bar {
    background: var(--bg-card);
    border: 1px solid var(--border);
    box-shadow: 0 1px 4px rgba(0,30,43,0.06);
}
.pf-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    box-shadow: 0 1px 8px rgba(0,30,43,0.06);
}
.pf-card-title {
    font-family: 'Source Code Pro', monospace;
    font-size: 10px; text-transform: uppercase;
    letter-spacing: 2.5px; color: var(--text-muted);
    margin: 0 0 24px; font-weight: 600;
}
.pf-section-h3 {
    font-size: 16px; font-weight: 600;
    color: var(--text-primary); margin: 0 0 24px;
    display: flex; align-items: center; gap: 8px;
}
.pf-section-desc {
    font-size: 13px; color: var(--text-secondary);
    margin: 0 0 20px; line-height: 1.65;
}
.pf-field-label {
    display: block;
    font-family: 'Source Code Pro', monospace;
    font-size: 10px; font-weight: 600;
    text-transform: uppercase; letter-spacing: 1.5px;
    color: var(--text-muted); margin-bottom: 8px;
}
.pf-field-input {
    width: 100%; padding: 12px 16px;
    border: 1.5px solid var(--border);
    border-radius: 8px; font-size: 14px;
    color: var(--text-primary);
    font-family: 'Plus Jakarta Sans', sans-serif;
    outline: none; box-sizing: border-box;
    background: var(--bg-elevated);
    transition: border-color .2s, box-shadow .2s;
}
.pf-field-input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(22,163,74,.12);
}
.pf-field-input::placeholder { color: var(--text-muted); }
.pf-stat-cell {
    background: var(--bg-elevated);
    border: 1px solid var(--border);
    border-radius: 10px; padding: 14px 16px;
}
.pf-stat-label {
    font-family: 'Source Code Pro', monospace;
    font-size: 9px; font-weight: 600;
    text-transform: uppercase; letter-spacing: 1.5px;
    color: var(--text-muted); margin: 0 0 6px;
}
.pf-stat-value {
    font-size: 17px; font-weight: 700;
    color: var(--text-primary); margin: 0;
    font-family: 'DM Serif Display', serif;
}
.pf-danger-card { border-color: rgba(239,68,68,.2) !important; }
[data-theme="dark"] .pf-danger-card { border-color: rgba(239,68,68,.25) !important; }
[data-theme="dark"] .pf-card { box-shadow: 0 1px 8px rgba(0,0,0,.3); }
[data-theme="dark"] .pf-field-input:focus {
    border-color: #3B82F6;
    box-shadow: 0 0 0 3px rgba(59,130,246,.12);
}
</style>

<!-- Flash messages -->
<?php if (!empty($flashSuccess)): ?>
<div class="bf-alert bf-alert-success" style="margin-bottom:20px;">
    <i class="bi bi-check-circle-fill"></i> <?= $e($flashSuccess) ?>
</div>
<?php endif; ?>
<?php if (!empty($flashDanger)): ?>
<div class="bf-alert bf-alert-danger" style="margin-bottom:20px;">
    <i class="bi bi-exclamation-circle-fill"></i> <?= $e($flashDanger) ?>
</div>
<?php endif; ?>
<?php if (!empty($flashInfo)): ?>
<div class="bf-alert bf-alert-info" style="margin-bottom:20px;">
    <i class="bi bi-info-circle-fill"></i> <?= $e($flashInfo) ?>
</div>
<?php endif; ?>

<!-- ── Hero card (intentionally dark — part of brand) ── -->
<div style="background:#001e2b;border-radius:16px;padding:28px 32px;display:flex;align-items:center;gap:24px;margin-bottom:24px;position:relative;overflow:hidden;box-shadow:0 4px 24px rgba(0,30,43,0.18);">
    <div style="position:absolute;inset:0;opacity:.04;background-image:radial-gradient(circle at 1px 1px,#3d4f58 1px,transparent 0);background-size:28px 28px;pointer-events:none;"></div>

    <div style="position:relative;flex-shrink:0;z-index:1;">
        <div style="width:80px;height:80px;border-radius:16px;background:#00684a;border:2px solid rgba(0,237,100,0.25);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;color:#fff;font-family:'Plus Jakarta Sans',sans-serif;">
            <?= $initials ?>
        </div>
        <div style="position:absolute;bottom:-5px;right:-5px;width:26px;height:26px;border-radius:50%;background:#1c2d38;border:2px solid #001e2b;display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-camera-fill" style="font-size:10px;color:#5c6c75;"></i>
        </div>
    </div>

    <div style="flex:1;min-width:0;z-index:1;">
        <span style="font-family:'Source Code Pro',monospace;font-size:10px;text-transform:uppercase;letter-spacing:2px;color:#00ed64;display:block;margin-bottom:6px;">Account</span>
        <h2 style="font-family:'DM Serif Display',serif;font-size:28px;color:#fff;margin:0 0 4px;line-height:1.2;"><?= $e($user['name'] ?? 'Utilisateur') ?></h2>
        <p style="font-size:14px;color:#5c6c75;margin:0 0 12px;"><?= $e($user['email'] ?? '') ?></p>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <?php if (($user['role'] ?? '') === 'admin'): ?>
            <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:999px;font-size:12px;font-weight:600;background:rgba(0,108,250,0.15);border:1px solid rgba(0,108,250,0.3);color:#5b9cf6;">
                <i class="bi bi-shield-fill" style="font-size:9px;"></i>Admin
            </span>
            <?php else: ?>
            <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:999px;font-size:12px;font-weight:600;background:rgba(0,237,100,0.1);border:1px solid rgba(0,237,100,0.2);color:#00ed64;">
                <i class="bi bi-person-fill" style="font-size:9px;"></i>Utilisateur
            </span>
            <?php endif; ?>
            <span style="font-size:13px;color:#b8c4c2;display:inline-flex;align-items:center;gap:5px;">
                <span style="width:7px;height:7px;border-radius:50%;background:<?= (bool)($user['is_active'] ?? false) ? '#00ed64' : '#f59e0b' ?>;display:inline-block;"></span>
                <?= (bool)($user['is_active'] ?? false) ? 'Active' : 'En attente' ?>
            </span>
            <span style="font-family:'Source Code Pro',monospace;font-size:12px;color:#5c6c75;">Member since <?= $e($memberSince) ?></span>
        </div>
    </div>

    <form method="post" action="/logout" style="flex-shrink:0;z-index:1;">
        <?= CSRF::getTokenField() ?>
        <button type="submit"
            style="display:inline-flex;align-items:center;gap:8px;padding:10px 22px;border-radius:100px;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.14);color:#b8c4c2;font-size:13px;font-weight:500;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif;transition:all .2s;"
            onmouseover="this.style.background='rgba(225,29,72,0.15)';this.style.borderColor='rgba(225,29,72,0.35)';this.style.color='#e11d48';"
            onmouseout="this.style.background='rgba(255,255,255,0.07)';this.style.borderColor='rgba(255,255,255,0.14)';this.style.color='#b8c4c2';">
            <i class="bi bi-box-arrow-right"></i> Sign Out
        </button>
    </form>
</div>

<!-- ── Onglets ── -->
<?php
$tabs = [
    ['key' => 'profile',  'label' => 'Profile',  'icon' => 'bi-person-fill', 'color' => '#006cfa'],
    ['key' => 'security', 'label' => 'Security', 'icon' => 'bi-lock-fill',   'color' => '#f59e0b'],
];
?>
<div class="pf-tab-bar" style="border-radius:14px;padding:5px;display:flex;gap:4px;margin-bottom:24px;">
    <?php foreach ($tabs as $t): $active = $tab === $t['key']; ?>
    <a href="<?= $e($profileBase) ?>?tab=<?= $e($t['key']) ?>"
       style="flex:1;display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:9px 16px;border-radius:10px;font-size:13px;font-weight:500;text-decoration:none;transition:all .15s;
              <?= $active ? 'background:#001e2b;color:#fff;' : 'color:var(--text-muted);' ?>">
        <i class="bi <?= $e($t['icon']) ?>" style="font-size:13px;color:<?= $active ? '#fff' : $e($t['color']) ?>;"></i>
        <?= $e($t['label']) ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- ══════════════ ONGLET PROFILE ══════════════ -->
<?php if ($tab === 'profile'): ?>

<div style="display:grid;grid-template-columns:3fr 2fr;gap:20px;align-items:start;">

    <!-- Personal Information -->
    <div class="pf-card" style="border-radius:14px;padding:28px 32px;">
        <p class="pf-card-title">Personal Information</p>

        <form method="post" action="/profile/update-info" style="display:flex;flex-direction:column;gap:20px;">
            <?= CSRF::getTokenField() ?>
            <input type="hidden" name="_back" value="<?= $e($profileBase) ?>">

            <div>
                <label class="pf-field-label">Full Name</label>
                <input type="text" name="name" required
                    value="<?= $e($user['name'] ?? '') ?>"
                    class="pf-field-input">
            </div>

            <div>
                <label class="pf-field-label">Email Address</label>
                <input type="email" name="email" required
                    value="<?= $e($user['email'] ?? '') ?>"
                    class="pf-field-input">
            </div>

            <div>
                <label class="pf-field-label">Phone Number</label>
                <input type="tel" name="phone"
                    value="<?= $e($user['phone'] ?? '') ?>"
                    placeholder="+216 20 123 456"
                    class="pf-field-input">
            </div>

            <div>
                <button type="submit" class="bf-btn-primary">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    <!-- Account Overview -->
    <div class="pf-card" style="border-radius:14px;padding:28px 32px;">
        <p class="pf-card-title">Account Overview</p>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <?php
            $statsGrid = [
                ['label' => 'Member Since', 'value' => $memberSince],
                ['label' => 'Last Login',   'value' => $lastLogin],
                ['label' => 'Transactions', 'value' => number_format($txCount, 0, ',', ' ')],
                ['label' => 'Net Balance',  'value' => $netFormatted],
            ];
            foreach ($statsGrid as $s):
            ?>
            <div class="pf-stat-cell">
                <p class="pf-stat-label"><?= $e($s['label']) ?></p>
                <p class="pf-stat-value"><?= $e($s['value']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ══════════════ ONGLET SECURITY ══════════════ -->
<?php elseif ($tab === 'security'): ?>

<div style="display:grid;grid-template-columns:3fr 2fr;gap:20px;align-items:start;">

    <!-- Changer le mot de passe -->
    <div class="pf-card" style="border-radius:14px;padding:28px 32px;">
        <p class="pf-card-title">Security</p>
        <h3 class="pf-section-h3">
            <i class="bi bi-lock-fill" style="color:#f59e0b;"></i> Changer le mot de passe
        </h3>

        <form method="post" action="/profile/update-password" style="display:flex;flex-direction:column;gap:18px;">
            <?= CSRF::getTokenField() ?>
            <input type="hidden" name="_back" value="<?= $e($profileBase) ?>?tab=security">
            <?php foreach ([
                ['name' => 'current_password', 'label' => 'Mot de passe actuel',  'ac' => 'current-password'],
                ['name' => 'new_password',     'label' => 'Nouveau mot de passe', 'ac' => 'new-password'],
                ['name' => 'confirm_password', 'label' => 'Confirmer le nouveau', 'ac' => 'new-password'],
            ] as $f): ?>
            <div>
                <label class="pf-field-label"><?= $e($f['label']) ?></label>
                <div style="position:relative;">
                    <input type="password" name="<?= $e($f['name']) ?>" required autocomplete="<?= $e($f['ac']) ?>" placeholder="••••••••"
                        class="pf-field-input" style="padding-right:42px;">
                    <button type="button" data-password-toggle="<?= $e($f['name']) ?>"
                        style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;padding:4px;line-height:1;">
                        <i class="bi bi-eye" style="font-size:15px;"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            <div>
                <button type="submit" class="bf-btn-primary" style="background:#f59e0b;border-color:#f59e0b;">
                    <i class="bi bi-key-fill"></i> Modifier le mot de passe
                </button>
            </div>
        </form>
    </div>

    <!-- Zone de danger -->
    <div class="pf-card pf-danger-card" style="border-radius:14px;padding:28px 32px;">
        <p class="pf-card-title" style="color:#f87171;">Danger Zone</p>
        <h3 class="pf-section-h3">
            <i class="bi bi-trash3-fill" style="color:#e11d48;"></i> Supprimer le compte
        </h3>
        <p class="pf-section-desc">
            Cette action envoie une demande à l'administrateur. Vos données seront définitivement supprimées après validation.
        </p>
        <form method="post" action="/profile/request-deletion"
            onsubmit="return confirm('Confirmer la demande de suppression de compte ?')">
            <?= CSRF::getTokenField() ?>
            <input type="hidden" name="_back" value="<?= $e($profileBase) ?>?tab=security">
            <button type="submit"
                style="display:inline-flex;align-items:center;gap:6px;padding:10px 22px;background:transparent;color:#e11d48;border:1.5px solid rgba(239,68,68,.35);border-radius:100px;font-size:13px;font-weight:600;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif;transition:all .15s;"
                onmouseover="this.style.background='rgba(239,68,68,.08)'" onmouseout="this.style.background='transparent'">
                <i class="bi bi-exclamation-triangle-fill"></i> Demander la suppression
            </button>
        </form>
    </div>
</div>

<?php endif; ?>
