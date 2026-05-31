<?php
// Layout administrateur — fidèle au design Figma (sidebar Forest Black + header blanc).
$adminUser    = Auth::getUser() ?? [];
$pageTitleText = (string) ($pageTitle ?? $title ?? 'Administration');
$safeTitle    = htmlspecialchars($pageTitleText, ENT_QUOTES, 'UTF-8');
$docTitle     = htmlspecialchars($pageTitleText . ' — BudgetFlow Admin', ENT_QUOTES, 'UTF-8');
$currentPath  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$currentQuery = $_SERVER['QUERY_STRING'] ?? '';

$adminName    = trim((string) ($adminUser['name'] ?? 'Admin'));
$adminParts   = preg_split('/\s+/', $adminName) ?: ['A'];
$adminInit    = '';
foreach (array_slice($adminParts, 0, 2) as $p) {
    $adminInit .= $p !== '' ? strtoupper(mb_substr($p, 0, 1, 'UTF-8')) : '';
}
$adminInit = htmlspecialchars($adminInit ?: 'A', ENT_QUOTES, 'UTF-8');
$safeName  = htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8');

// Nombre de comptes en attente pour les badges.
$pendingBadge = (int) ($stats['pending_users'] ?? $pendingCount ?? 0);

$isActive = static fn (string $path): string =>
    ($currentPath === $path || ($path !== '/admin' && str_starts_with($currentPath, $path)))
    ? 'active' : '';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $docTitle ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=DM+Sans:wght@400;500;600;700&family=Source+Code+Pro:wght@400;500;600&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Anti-FOUC : applique le thème avant le rendu pour éviter le flash -->
    <script>(function(){var t=localStorage.getItem('bf-theme')||'light';document.documentElement.dataset.theme=t;}());</script>
    <link href="/style.css?v=10" rel="stylesheet">
    <link rel="icon" href="/img/favicon-budget.png" type="image/png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
    <style>
        /* ── Admin-specific overrides to match Figma exactly ── */
        .adm-sidebar-link {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; border-radius: 8px;
            color: #b8c4c2; text-decoration: none; font-size: 14px; font-weight: 500;
            transition: background .2s, color .2s, border-color .2s;
            border: 1px solid transparent; background: none; width: 100%; cursor: pointer;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .adm-sidebar-link:hover { background: rgba(255,255,255,0.05); color: rgba(255,255,255,.85); }
        .adm-sidebar-link.active {
            background: rgba(0,237,100,0.1); color: #00ed64;
            font-weight: 600; border-color: rgba(0,237,100,0.2);
        }
        .adm-sidebar-link .adm-dot {
            margin-left: auto; width: 6px; height: 6px; border-radius: 50%;
            background: #00ed64; flex-shrink: 0;
        }
        .adm-section-label {
            font-family: 'Source Code Pro','JetBrains Mono',monospace;
            font-size: 10px; font-weight: 600; text-transform: uppercase;
            letter-spacing: 2px; color: #5c6c75; padding: 12px 8px 4px; display: block;
        }
        .adm-pending-pill {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 18px; height: 18px; background: #f59e0b; color: #1A1D27;
            font-size: 10px; font-weight: 700; border-radius: 9px; padding: 0 5px;
            margin-left: auto;
        }
        .adm-avatar {
            width: 36px; height: 36px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700; flex-shrink: 0;
        }
        .adm-badge-access {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 16px; border-radius: 999px; margin-bottom: 20px;
            background: rgba(0,108,250,0.08); border: 1px solid rgba(0,108,250,0.25);
            font-family: 'Source Code Pro',monospace; font-size: 11px;
            text-transform: uppercase; letter-spacing: 1.5px; color: #006cfa;
        }
        .adm-tabs {
            display: flex; gap: 4px; padding: 6px;
            background: #fff; border: 1px solid #b8c4c2; border-radius: 16px;
            margin-bottom: 24px; width: fit-content;
        }
        .adm-tab {
            padding: 8px 20px; border-radius: 12px; font-size: 14px; font-weight: 500;
            cursor: pointer; border: none; transition: all .2s; text-decoration: none;
            display: inline-block;
        }
        .adm-tab.active { background: #001e2b; color: #fff; }
        .adm-tab:not(.active) { background: transparent; color: #5c6c75; }
        .adm-tab:not(.active):hover { background: #f5f7f7; color: #001e2b; }
        .adm-kpi {
            background: #fff; border: 1px solid #b8c4c2; border-radius: 16px;
            padding: 20px; box-shadow: rgba(0,30,43,0.06) 0px 4px 16px;
        }
        .adm-kpi-icon {
            width: 40px; height: 40px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .adm-kpi-value { font-size: 30px; font-weight: 700; color: #001e2b; line-height: 1; }
        .adm-kpi-label {
            font-family: 'Source Code Pro',monospace; font-size: 12px;
            text-transform: uppercase; letter-spacing: 1px; color: #5c6c75; margin-top: 4px;
        }
        .adm-card {
            background: #fff; border: 1px solid #b8c4c2; border-radius: 16px;
            padding: 24px; box-shadow: rgba(0,30,43,0.06) 0px 4px 16px;
        }
        .adm-card-mono {
            font-family: 'Source Code Pro',monospace; font-size: 11px;
            text-transform: uppercase; letter-spacing: 2px; color: #5c6c75;
            display: block; margin-bottom: 4px;
        }
        .adm-card-title {
            font-size: 17px; font-weight: 600; color: #001e2b; margin-bottom: 16px;
        }
        .adm-th {
            font-family: 'Source Code Pro',monospace; font-size: 10px;
            text-transform: uppercase; letter-spacing: 1.5px; color: #5c6c75;
            font-weight: 500; padding: 12px 20px; text-align: left;
        }
        .adm-td { padding: 14px 20px; font-size: 13px; color: #001e2b; }
        .adm-badge-role-admin {
            display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 999px;
            font-size: 11px; font-weight: 600;
            background: rgba(0,108,250,0.1); color: #006cfa;
        }
        .adm-badge-role-user {
            display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 999px;
            font-size: 11px; font-weight: 600;
            background: #f5f7f7; color: #5c6c75;
        }
        .adm-badge-active   { display:inline-flex;align-items:center;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600;background:rgba(0,237,100,0.1);color:#00684a; }
        .adm-badge-pending  { display:inline-flex;align-items:center;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600;background:rgba(245,158,11,0.1);color:#d97706; }
        .adm-badge-inactive { display:inline-flex;align-items:center;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600;background:rgba(225,29,72,0.1);color:#e11d48; }
        .adm-btn-approve {
            display:inline-flex;align-items:center;gap:5px;padding:6px 14px;
            border-radius:100px;border:none;cursor:pointer;
            background:#00684a;color:#fff;font-size:12px;font-weight:600;
            transition:transform .15s;
        }
        .adm-btn-approve:hover { transform:scale(1.05); }
        .adm-btn-reject {
            display:inline-flex;align-items:center;gap:5px;padding:6px 14px;
            border-radius:100px;background:transparent;font-size:12px;cursor:pointer;
            border:1px solid rgba(225,29,72,0.3);color:#e11d48;
            transition:background .15s;
        }
        .adm-btn-reject:hover { background:rgba(225,29,72,0.06); }
        .adm-pending-item {
            display:flex;align-items:center;justify-content:space-between;
            padding:14px 16px;border-radius:12px;
            background:#f9fbfb;border:1px solid rgba(245,158,11,0.2);
            margin-bottom:10px;
        }

        /* Body base weight */
        body { font-weight: 400; }

        /* Header search bar */
        .adm-search {
            display: flex; align-items: center; gap: 8px;
            background: #f5f7f7; border: 1px solid #b8c4c2; border-radius: 8px;
            padding: 7px 14px; min-width: 200px;
        }
        .adm-search input {
            background: none; border: none; outline: none;
            color: #001e2b; font-size: 13px; width: 100%;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .adm-search input::placeholder { color: #b8c4c2; }

        /* Notification bell */
        .adm-bell {
            position: relative; width: 36px; height: 36px; border-radius: 8px;
            background: #f5f7f7; border: 1px solid #b8c4c2;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; color: #3d4f58; font-size: 16px;
        }
        .adm-bell-dot {
            position: absolute; top: 7px; right: 7px; width: 7px; height: 7px;
            background: #00ed64; border-radius: 50%; border: 2px solid #fff;
        }

        /* Page title using DM Serif Display */
        .adm-page-title {
            font-family: 'DM Serif Display', serif;
            font-size: 22px; font-weight: 400; color: #001e2b; margin: 0; line-height: 1.2;
        }
        .adm-page-eyebrow {
            font-family: 'Source Code Pro', monospace;
            font-size: 10px; font-weight: 600; text-transform: uppercase;
            letter-spacing: 2px; color: #5c6c75; display: block; margin-bottom: 2px;
        }

        /* Dark teal button */
        .adm-btn-dark {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 18px; border-radius: 100px;
            background: #1c2d38; border: 1px solid #3d4f58;
            color: #5c6c75; font-size: 13px; font-weight: 500;
            cursor: pointer; transition: all .2s; text-decoration: none;
        }
        .adm-btn-dark:hover { background: #1eaedb; color: #fff; transform: translateX(3px); }

        /* Section heading with green underline */
        .adm-section-heading {
            font-family: 'DM Serif Display', serif;
            font-size: 20px; font-weight: 400; color: #001e2b;
            padding-bottom: 8px; border-bottom: 2px solid #00ed64;
            display: inline-block; margin-bottom: 20px;
        }
    </style>
</head>
<body style="background:#f5f7f7;font-family:'Plus Jakarta Sans',sans-serif;">
<div style="display:flex;min-height:100vh;">

    <!-- ── Sidebar ── -->
    <aside class="adm-sidebar" style="width:240px;min-height:100vh;background:#001e2b;border-right:1px solid #3d4f58;display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100;padding:20px 12px 16px;overflow-y:auto;" aria-label="Navigation administration">

        <!-- Logo -->
        <div style="display:flex;align-items:center;gap:10px;border-bottom:1px solid #3d4f58;padding-bottom:20px;margin-bottom:8px;">
            <div style="width:36px;height:36px;border-radius:8px;background:#00684a;display:flex;align-items:center;justify-content:center;box-shadow:0 0 0 3px rgba(0,104,74,.25);flex-shrink:0;">
                <svg width="18" height="18" viewBox="0 0 20 20"><path d="M10 2C6.13 2 3 5.13 3 9c0 2.38 1.19 4.47 3 5.74V17h8v-2.26C15.81 13.47 17 11.38 17 9c0-3.87-3.13-7-7-7z" fill="#00ed64"/><path d="M8 17v1h4v-1H8z" fill="#00ed64" opacity="0.7"/></svg>
            </div>
            <a href="/admin" style="font-size:19px;font-weight:700;color:#fff;text-decoration:none;letter-spacing:-.4px;">
                Budget<span style="color:#00ed64;">Flow</span>
            </a>
        </div>

        <!-- Nav principale -->
        <nav style="flex:1;" aria-label="Menu admin">
            <span class="adm-section-label">Supervision</span>

            <a href="/admin" class="adm-sidebar-link <?= $isActive('/admin') ?>">
                <i class="bi bi-speedometer2" style="font-size:16px;width:16px;text-align:center;"></i>
                <span>Vue d'ensemble</span>
                <?php if ($isActive('/admin') === 'active'): ?><span class="adm-dot"></span><?php endif; ?>
            </a>

            <a href="/admin/users" class="adm-sidebar-link <?= $isActive('/admin/users') ?>">
                <i class="bi bi-people" style="font-size:16px;width:16px;text-align:center;"></i>
                <span>Utilisateurs</span>
                <?php if ($pendingBadge > 0): ?>
                    <span class="adm-pending-pill"><?= $pendingBadge ?></span>
                <?php elseif ($isActive('/admin/users') === 'active'): ?>
                    <span class="adm-dot"></span>
                <?php endif; ?>
            </a>

            <a href="/admin/budgets" class="adm-sidebar-link <?= $isActive('/admin/budgets') ?>">
                <i class="bi bi-wallet2" style="font-size:16px;width:16px;text-align:center;"></i>
                <span>Budgets partagés</span>
                <?php if ($isActive('/admin/budgets') === 'active'): ?><span class="adm-dot"></span><?php endif; ?>
            </a>

            <span class="adm-section-label">Communication</span>

            <a href="/admin/send-email" class="adm-sidebar-link <?= $isActive('/admin/send-email') ?>">
                <i class="bi bi-envelope-arrow-up" style="font-size:16px;width:16px;text-align:center;"></i>
                <span>Envoyer un email</span>
                <?php if ($isActive('/admin/send-email') === 'active'): ?><span class="adm-dot"></span><?php endif; ?>
            </a>
        </nav>

        <!-- Nav compte -->
        <nav style="border-top:1px solid #3d4f58;padding-top:12px;margin-top:16px;" aria-label="Compte">
            <span class="adm-section-label">Compte</span>

            <a href="/admin/profile" class="adm-sidebar-link <?= $isActive('/admin/profile') ?>">
                <i class="bi bi-person-circle" style="font-size:16px;width:16px;text-align:center;"></i>
                <span>Mon profil</span>
                <?php if ($isActive('/admin/profile') === 'active'): ?><span class="adm-dot"></span><?php endif; ?>
            </a>

            <form method="post" action="/logout">
                <?= CSRF::getTokenField() ?>
                <button type="submit" class="adm-sidebar-link" style="color:#e57373;">
                    <i class="bi bi-box-arrow-left" style="font-size:16px;width:16px;text-align:center;"></i>
                    <span>Déconnexion</span>
                </button>
            </form>
        </nav>

        <!-- Profil bas — cliquable vers /admin/profile -->
        <a href="/admin/profile" style="margin-top:16px;padding:10px 8px;border-radius:12px;background:#1c2d38;border:1px solid #3d4f58;display:flex;align-items:center;gap:10px;text-decoration:none;transition:border-color .2s;"
           onmouseover="this.style.borderColor='#00684a'" onmouseout="this.style.borderColor='#3d4f58'">
            <div class="adm-avatar" style="background:#00684a;">
                <?= $adminInit ?>
            </div>
            <div style="min-width:0;flex:1;">
                <div style="font-size:13px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= $safeName ?></div>
                <div style="font-size:11px;color:#5c6c75;">Administrateur</div>
            </div>
            <i class="bi bi-chevron-right" style="font-size:11px;color:#3d4f58;flex-shrink:0;"></i>
        </a>
    </aside>

    <!-- Voile mobile pour fermer la sidebar -->
    <div class="bf-sidebar-backdrop" id="sidebar-backdrop" aria-hidden="true"></div>

    <!-- ── Main ── -->
    <div class="adm-main" style="flex:1;margin-left:240px;display:flex;flex-direction:column;min-height:100vh;">

        <!-- Header -->
        <header class="adm-main-header" style="height:64px;background:#fff;border-bottom:1px solid #b8c4c2;box-shadow:rgba(0,30,43,0.06) 0px 2px 8px;display:flex;align-items:center;justify-content:space-between;padding:0 28px;position:sticky;top:0;z-index:50;">
            <div class="bf-topbar-lead">
                <button class="bf-menu-toggle" id="sidebar-toggle" type="button" aria-label="Ouvrir le menu" aria-expanded="false">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                <div>
                    <span class="adm-page-eyebrow">Admin Panel</span>
                    <h1 class="adm-page-title"><?= $safeTitle ?></h1>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="adm-search">
                    <i class="bi bi-search" style="color:#b8c4c2;font-size:13px;"></i>
                    <input type="search" placeholder="Rechercher..." autocomplete="off">
                </div>
                <button class="bf-theme-toggle" id="theme-toggle" type="button" title="Mode sombre">
                    <i class="bi bi-moon-fill" id="theme-icon"></i>
                </button>
                <?php if ($pendingBadge > 0): ?>
                <a href="/admin/users?filter=pending" style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:999px;font-size:12px;font-weight:600;text-decoration:none;background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.3);color:#d97706;">
                    <i class="bi bi-clock-history"></i> <?= $pendingBadge ?> en attente
                </a>
                <?php endif; ?>
                <div class="adm-avatar" style="background:#00684a;color:#fff;cursor:default;" aria-label="Admin connecté"><?= $adminInit ?></div>
            </div>
        </header>

        <!-- Contenu -->
        <main class="adm-content-bg" style="flex:1;padding:28px;overflow-y:auto;">
            <?= $content ?? '' ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/script.js?v=3"></script>
<?= $scripts ?? '' ?>
</body>
</html>
