<?php
$pageTitle = htmlspecialchars(($title ?? 'Authentification') . ' - BudgetFlow', ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Source+Code+Pro:wght@400;500;600&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/style.css?v=5" rel="stylesheet">
    <link rel="icon" href="/img/favicon-budget.png" type="image/png">
    <style>
        body { background:#001e2b!important; font-family:'Plus Jakarta Sans',system-ui,sans-serif; }
        .bf-guest-shell { display:flex; min-height:100vh; }

        /* LEFT PANEL */
        .bf-guest-left {
            width:50%; background:#001e2b;
            border-right:1px solid #3d4f58;
            display:flex; flex-direction:column;
            justify-content:space-between;
            padding:48px; position:relative; overflow:hidden;
        }
        .bf-guest-left::before {
            content:''; position:absolute; inset:0; opacity:.05;
            background-image:radial-gradient(circle at 1px 1px,#3d4f58 1px,transparent 0);
            background-size:32px 32px; pointer-events:none;
        }
        .bf-guest-left::after {
            content:''; position:absolute; inset:0; pointer-events:none;
            background:radial-gradient(ellipse 70% 60% at 30% 80%,rgba(0,237,100,0.08) 0%,transparent 70%);
        }
        .bf-guest-logo {
            position:relative; z-index:1;
            display:flex; align-items:center; gap:12px; text-decoration:none;
        }
        .bf-guest-logo-icon {
            width:36px; height:36px; background:#00684a; border-radius:10px;
            display:flex; align-items:center; justify-content:center; flex-shrink:0;
        }
        .bf-guest-logo-text {
            font-family:'DM Serif Display',serif; font-size:24px; color:#fff;
        }
        .bf-guest-hero { position:relative; z-index:1; max-width:420px; }
        .bf-guest-eyebrow {
            font-family:'Source Code Pro',monospace; font-size:11px;
            text-transform:uppercase; letter-spacing:2px; color:#00ed64;
            display:block; margin-bottom:20px;
        }
        .bf-guest-title {
            font-family:'DM Serif Display',serif; font-size:44px; color:#fff;
            line-height:1.15; margin-bottom:20px;
        }
        .bf-guest-title span { color:#00ed64; }
        .bf-guest-copy {
            font-size:16px; color:#5c6c75; line-height:1.7;
            font-weight:300; margin:0;
        }
        .bf-guest-stats { display:flex; gap:32px; margin-top:32px; }
        .bf-guest-stat-val {
            font-family:'DM Serif Display',serif; font-size:24px; color:#fff;
        }
        .bf-guest-stat-lbl { font-size:12px; color:#5c6c75; margin-top:2px; }
        .bf-guest-testimonial {
            position:relative; z-index:1;
            background:#1c2d38; border:1px solid #3d4f58;
            border-radius:16px; padding:24px;
        }
        .bf-guest-testimonial p {
            font-size:14px; color:#b8c4c2; line-height:1.6;
            font-weight:300; font-style:italic; margin:0 0 16px;
        }
        .bf-guest-author { display:flex; align-items:center; gap:12px; }
        .bf-guest-author-avatar {
            width:32px; height:32px; background:#00684a; border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            font-size:11px; font-weight:700; color:#fff; flex-shrink:0;
        }
        .bf-guest-author-name { font-size:13px; font-weight:600; color:#fff; }
        .bf-guest-author-role { font-size:11px; color:#5c6c75; }

        /* RIGHT PANEL */
        .bf-guest-right {
            flex:1; background:#001e2b;
            display:flex; align-items:center; justify-content:center;
            padding:48px;
        }
        .bf-guest-form-wrap { width:100%; max-width:440px; }
        .bf-guest-card {
            background:#1c2d38; border:1px solid #3d4f58; border-radius:16px;
            padding:36px 40px;
            box-shadow:rgba(0,30,43,0.4) 0px 20px 60px;
        }
        .bf-guest-card-eyebrow {
            font-family:'Source Code Pro',monospace; font-size:11px;
            text-transform:uppercase; letter-spacing:2px; color:#00ed64;
            display:block; margin-bottom:8px;
        }
        .bf-guest-card-title {
            font-family:'DM Serif Display',serif; font-size:28px;
            color:#fff; line-height:1.15; margin:0 0 8px;
        }
        .bf-guest-card-sub { font-size:14px; color:#5c6c75; font-weight:300; margin:0 0 28px; }
        .bf-guest-card-sub a { color:#006cfa; font-weight:500; }
        .bf-guest-label {
            display:block; font-family:'Source Code Pro',monospace;
            font-size:11px; text-transform:uppercase; letter-spacing:1.5px;
            color:#5c6c75; margin-bottom:8px;
        }
        .bf-guest-input {
            width:100%; background:#001e2b; border:1px solid #3d4f58;
            border-radius:8px; padding:12px 16px; color:#e8edeb;
            font-size:15px; outline:none; transition:border-color .2s;
            font-family:'Plus Jakarta Sans',sans-serif;
        }
        .bf-guest-input::placeholder { color:#3d4f58; }
        .bf-guest-input:focus { border-color:#00684a; }
        .bf-guest-input.is-invalid { border-color:#e11d48!important; }
        .bf-guest-pw-wrap { position:relative; }
        .bf-guest-pw-toggle {
            position:absolute; right:12px; top:50%; transform:translateY(-50%);
            background:none; border:none; color:#5c6c75; cursor:pointer; padding:4px;
        }
        .bf-guest-submit {
            width:100%; background:#00684a; color:#fff; border:none;
            border-radius:100px; padding:14px; font-size:15px; font-weight:600;
            cursor:pointer; display:flex; align-items:center; justify-content:center;
            gap:8px; margin-top:4px; transition:opacity .2s,transform .15s;
            font-family:'Plus Jakarta Sans',sans-serif;
        }
        .bf-guest-submit:hover { opacity:.92; transform:scale(1.01); }
        .bf-guest-submit:disabled { opacity:.6; cursor:not-allowed; transform:none; }
        .bf-guest-divider {
            display:flex; align-items:center; gap:16px; margin:20px 0;
        }
        .bf-guest-divider hr { flex:1; border-color:#3d4f58; margin:0; }
        .bf-guest-divider span { font-size:12px; color:#5c6c75; white-space:nowrap; }
        .bf-guest-social { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .bf-guest-social-btn {
            background:#001e2b; border:1px solid #3d4f58; border-radius:100px;
            padding:10px; color:#b8c4c2; font-size:14px; cursor:pointer;
            display:flex; align-items:center; justify-content:center;
            transition:border-color .15s;
        }
        .bf-guest-social-btn:hover { border-color:#5c6c75; }
        .bf-guest-demo {
            margin-top:16px; background:rgba(0,237,100,0.06);
            border:1px solid rgba(0,237,100,0.2); border-radius:12px;
            padding:14px 16px; text-align:center;
            font-size:13px; color:#5c6c75;
        }
        .bf-guest-demo a { color:#00ed64; font-weight:500; }
        .bf-guest-error {
            display:flex; align-items:center; gap:8px; padding:12px;
            background:rgba(225,29,72,0.1); border:1px solid rgba(225,29,72,0.3);
            border-radius:8px; margin-bottom:20px;
            font-size:13px; color:#e11d48;
        }
        .bf-guest-footnote { font-size:12px; color:#5c6c75; margin-top:16px; text-align:center; }
        .bf-guest-field { margin-bottom:18px; }
        @media(max-width:1024px) {
            .bf-guest-left { display:none; }
            .bf-guest-right { padding:24px; background:#001e2b; }
        }
    </style>
</head>
<body>
    <div class="bf-guest-shell">
        <!-- LEFT PANEL -->
        <div class="bf-guest-left">
            <a class="bf-guest-logo" href="/">
                <div class="bf-guest-logo-icon">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M10 2C6.13 2 3 5.13 3 9c0 2.38 1.19 4.47 3 5.74V17h8v-2.26C15.81 13.47 17 11.38 17 9c0-3.87-3.13-7-7-7z" fill="#00ed64"/>
                        <path d="M8 17v1h4v-1H8z" fill="#00ed64" opacity="0.7"/>
                    </svg>
                </div>
                <span class="bf-guest-logo-text">Budget<span style="color:#00ed64">Flow</span></span>
            </a>

            <div class="bf-guest-hero">
                <span class="bf-guest-eyebrow">Finances Personnelles</span>
                <h2 class="bf-guest-title">
                    Votre histoire d'argent<br>commence <span>ici</span>
                </h2>
                <p class="bf-guest-copy">
                    Rejoignez 2,4 millions d'utilisateurs qui gèrent leurs finances intelligemment avec la plateforme collaborative BudgetFlow.
                </p>
                <div class="bf-guest-stats">
                    <div>
                        <div class="bf-guest-stat-val">2.4M+</div>
                        <div class="bf-guest-stat-lbl">Utilisateurs</div>
                    </div>
                    <div>
                        <div class="bf-guest-stat-val">3.6M TND</div>
                        <div class="bf-guest-stat-lbl">Budgets</div>
                    </div>
                    <div>
                        <div class="bf-guest-stat-val">4.9★</div>
                        <div class="bf-guest-stat-lbl">Note</div>
                    </div>
                </div>
            </div>

            <div class="bf-guest-testimonial">
                <p>"BudgetFlow m'a aidé à économiser 2 400 TND lors de mon premier mois simplement en visualisant où allait mon argent."</p>
                <div class="bf-guest-author">
                    <div class="bf-guest-author-avatar">SC</div>
                    <div>
                        <div class="bf-guest-author-name">Sarah Chen</div>
                        <div class="bf-guest-author-role">Designer Produit, Stripe</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="bf-guest-right">
            <div class="bf-guest-form-wrap">
                <?= $content ?? '' ?>
            </div>
        </div>
    </div>
    <script src="/script.js"></script>
</body>
</html>
