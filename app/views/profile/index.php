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

// Initiales de l'avatar (max 2 lettres).
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

<!-- Flash messages -->
<?php if (!empty($flashSuccess)): ?>
<div style="display:flex;align-items:center;gap:8px;padding:12px 16px;border-radius:10px;background:rgba(0,237,100,0.08);border:1px solid rgba(0,237,100,0.25);color:#00684a;font-size:13px;margin-bottom:20px;">
    <i class="bi bi-check-circle-fill"></i> <?= $e($flashSuccess) ?>
</div>
<?php endif; ?>
<?php if (!empty($flashDanger)): ?>
<div style="display:flex;align-items:center;gap:8px;padding:12px 16px;border-radius:10px;background:rgba(225,29,72,0.08);border:1px solid rgba(225,29,72,0.25);color:#e11d48;font-size:13px;margin-bottom:20px;">
    <i class="bi bi-exclamation-circle-fill"></i> <?= $e($flashDanger) ?>
</div>
<?php endif; ?>
<?php if (!empty($flashInfo)): ?>
<div style="display:flex;align-items:center;gap:8px;padding:12px 16px;border-radius:10px;background:rgba(0,108,250,0.08);border:1px solid rgba(0,108,250,0.25);color:#006cfa;font-size:13px;margin-bottom:20px;">
    <i class="bi bi-info-circle-fill"></i> <?= $e($flashInfo) ?>
</div>
<?php endif; ?>

<!-- ── Hero card ── -->
<div style="background:#001e2b;border-radius:16px;padding:28px 32px;display:flex;align-items:center;gap:24px;margin-bottom:24px;position:relative;overflow:hidden;box-shadow:0 4px 24px rgba(0,30,43,0.18);">
    <div style="position:absolute;inset:0;opacity:.04;background-image:radial-gradient(circle at 1px 1px,#3d4f58 1px,transparent 0);background-size:28px 28px;pointer-events:none;"></div>

    <!-- Avatar + icône caméra -->
    <div style="position:relative;flex-shrink:0;z-index:1;">
        <div style="width:80px;height:80px;border-radius:16px;background:#00684a;border:2px solid rgba(0,237,100,0.25);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;color:#fff;font-family:'Plus Jakarta Sans',sans-serif;">
            <?= $initials ?>
        </div>
        <div style="position:absolute;bottom:-5px;right:-5px;width:26px;height:26px;border-radius:50%;background:#1c2d38;border:2px solid #001e2b;display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-camera-fill" style="font-size:10px;color:#5c6c75;"></i>
        </div>
    </div>

    <!-- Infos -->
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

    <!-- Bouton Sign Out -->
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
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:5px;display:flex;gap:4px;margin-bottom:24px;box-shadow:0 1px 4px rgba(0,30,43,0.06);">
    <?php foreach ($tabs as $t): $active = $tab === $t['key']; ?>
    <a href="<?= $e($profileBase) ?>?tab=<?= $e($t['key']) ?>"
       style="flex:1;display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:9px 16px;border-radius:10px;font-size:13px;font-weight:500;text-decoration:none;transition:all .15s;
              <?= $active ? 'background:#001e2b;color:#fff;' : 'color:#64748b;' ?>">
        <i class="bi <?= $e($t['icon']) ?>" style="font-size:13px;color:<?= $active ? '#fff' : $e($t['color']) ?>;"></i>
        <?= $e($t['label']) ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- ══════════════ ONGLET PROFILE ══════════════ -->
<?php if ($tab === 'profile'): ?>

<div style="display:grid;grid-template-columns:3fr 2fr;gap:20px;align-items:start;">

    <!-- ── Colonne gauche : Personal Information ── -->
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:28px 32px;box-shadow:0 1px 8px rgba(0,30,43,0.06);">

        <p style="font-family:'Source Code Pro',monospace;font-size:10px;text-transform:uppercase;letter-spacing:2.5px;color:#94a3b8;margin:0 0 24px;font-weight:600;">Personal Information</p>

        <form method="post" action="/profile/update-info" style="display:flex;flex-direction:column;gap:20px;">
            <?= CSRF::getTokenField() ?>
            <input type="hidden" name="_back" value="<?= $e($profileBase) ?>">

            <!-- Full Name -->
            <div>
                <label style="display:block;font-family:'Source Code Pro',monospace;font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:#94a3b8;margin-bottom:8px;">Full Name</label>
                <input type="text" name="name" required
                    value="<?= $e($user['name'] ?? '') ?>"
                    style="width:100%;padding:12px 16px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;color:#0f172a;font-family:'Plus Jakarta Sans',sans-serif;outline:none;box-sizing:border-box;background:#fff;transition:border-color .2s;"
                    onfocus="this.style.borderColor='#00684a'" onblur="this.style.borderColor='#e2e8f0'">
            </div>

            <!-- Email Address -->
            <div>
                <label style="display:block;font-family:'Source Code Pro',monospace;font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:#94a3b8;margin-bottom:8px;">Email Address</label>
                <input type="email" name="email" required
                    value="<?= $e($user['email'] ?? '') ?>"
                    style="width:100%;padding:12px 16px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;color:#0f172a;font-family:'Plus Jakarta Sans',sans-serif;outline:none;box-sizing:border-box;background:#fff;transition:border-color .2s;"
                    onfocus="this.style.borderColor='#00684a'" onblur="this.style.borderColor='#e2e8f0'">
            </div>

            <!-- Phone Number -->
            <div>
                <label style="display:block;font-family:'Source Code Pro',monospace;font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:#94a3b8;margin-bottom:8px;">Phone Number</label>
                <input type="tel" name="phone"
                    value="<?= $e($user['phone'] ?? '') ?>"
                    placeholder="+216 20 123 456"
                    style="width:100%;padding:12px 16px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;color:#0f172a;font-family:'Plus Jakarta Sans',sans-serif;outline:none;box-sizing:border-box;background:#fff;transition:border-color .2s;"
                    onfocus="this.style.borderColor='#00684a'" onblur="this.style.borderColor='#e2e8f0'">
            </div>

            <div>
                <button type="submit"
                    style="padding:11px 28px;background:#0f172a;color:#fff;border:none;border-radius:100px;font-size:13px;font-weight:600;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif;letter-spacing:.2px;transition:opacity .2s;"
                    onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    <!-- ── Colonne droite : Statistiques du compte ── -->
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:28px 32px;box-shadow:0 1px 8px rgba(0,30,43,0.06);">

        <p style="font-family:'Source Code Pro',monospace;font-size:10px;text-transform:uppercase;letter-spacing:2.5px;color:#94a3b8;margin:0 0 24px;font-weight:600;">Account Overview</p>

        <!-- Grille stats 2×2 -->
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
            <div style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px;padding:14px 16px;">
                <p style="font-family:'Source Code Pro',monospace;font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:#94a3b8;margin:0 0 6px;"><?= $e($s['label']) ?></p>
                <p style="font-size:17px;font-weight:700;color:#0f172a;margin:0;font-family:'DM Serif Display',serif;"><?= $e($s['value']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ══════════════ ONGLET SECURITY ══════════════ -->
<?php elseif ($tab === 'security'): ?>

<div style="display:grid;grid-template-columns:3fr 2fr;gap:20px;align-items:start;">

    <!-- Changer le mot de passe -->
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:28px 32px;box-shadow:0 1px 8px rgba(0,30,43,0.06);">
        <p style="font-family:'Source Code Pro',monospace;font-size:10px;text-transform:uppercase;letter-spacing:2.5px;color:#94a3b8;margin:0 0 6px;font-weight:600;">Security</p>
        <h3 style="font-size:16px;font-weight:600;color:#0f172a;margin:0 0 24px;display:flex;align-items:center;gap:8px;">
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
                <label style="display:block;font-family:'Source Code Pro',monospace;font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:#94a3b8;margin-bottom:8px;"><?= $e($f['label']) ?></label>
                <div style="position:relative;">
                    <input type="password" name="<?= $e($f['name']) ?>" required autocomplete="<?= $e($f['ac']) ?>" placeholder="••••••••"
                        style="width:100%;padding:12px 42px 12px 16px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;color:#0f172a;font-family:'Plus Jakarta Sans',sans-serif;outline:none;box-sizing:border-box;background:#fff;transition:border-color .2s;"
                        onfocus="this.style.borderColor='#f59e0b'" onblur="this.style.borderColor='#e2e8f0'">
                    <button type="button" data-password-toggle="<?= $e($f['name']) ?>"
                        style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;padding:4px;line-height:1;">
                        <i class="bi bi-eye" style="font-size:15px;"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            <div>
                <button type="submit"
                    style="padding:11px 28px;background:#f59e0b;color:#fff;border:none;border-radius:100px;font-size:13px;font-weight:600;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif;transition:opacity .2s;"
                    onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                    <i class="bi bi-key-fill" style="margin-right:6px;"></i>Modifier le mot de passe
                </button>
            </div>
        </form>
    </div>

    <!-- Zone de danger -->
    <div style="background:#fff;border:1px solid #fee2e2;border-radius:14px;padding:28px 32px;box-shadow:0 1px 8px rgba(0,30,43,0.06);">
        <p style="font-family:'Source Code Pro',monospace;font-size:10px;text-transform:uppercase;letter-spacing:2.5px;color:#f87171;margin:0 0 6px;font-weight:600;">Danger Zone</p>
        <h3 style="font-size:16px;font-weight:600;color:#0f172a;margin:0 0 12px;display:flex;align-items:center;gap:8px;">
            <i class="bi bi-trash3-fill" style="color:#e11d48;"></i> Supprimer le compte
        </h3>
        <p style="font-size:13px;color:#64748b;margin:0 0 20px;line-height:1.65;">
            Cette action envoie une demande à l'administrateur. Vos données seront définitivement supprimées après validation.
        </p>
        <form method="post" action="/profile/request-deletion"
            onsubmit="return confirm('Confirmer la demande de suppression de compte ?')">
            <?= CSRF::getTokenField() ?>
            <input type="hidden" name="_back" value="<?= $e($profileBase) ?>?tab=security">
            <button type="submit"
                style="display:inline-flex;align-items:center;gap:6px;padding:10px 22px;background:transparent;color:#e11d48;border:1.5px solid #fecaca;border-radius:100px;font-size:13px;font-weight:600;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif;transition:all .15s;"
                onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='transparent'">
                <i class="bi bi-exclamation-triangle-fill"></i> Demander la suppression
            </button>
        </form>
    </div>
</div>

<?php endif; ?>

<!-- Bouton d'aide flottant -->
<div style="position:fixed;bottom:32px;right:32px;z-index:200;">
    <button title="Aide"
        style="width:44px;height:44px;border-radius:50%;background:#0f172a;border:1px solid #334155;color:#94a3b8;font-size:16px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(0,30,43,0.25);transition:all .2s;"
        onmouseover="this.style.background='#00684a';this.style.color='#fff';"
        onmouseout="this.style.background='#0f172a';this.style.color='#94a3b8';">?</button>
</div>
