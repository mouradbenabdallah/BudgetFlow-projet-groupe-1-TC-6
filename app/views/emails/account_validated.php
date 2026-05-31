<?php
$emailTitle = 'Votre compte BudgetFlow a été activé';
$e = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
require __DIR__ . '/partials/top.php';
?>

<!-- Icône succès -->
<div style="text-align:center;margin-bottom:28px;">
  <div style="width:60px;height:60px;border-radius:50%;background:rgba(0,237,100,0.12);border:1.5px solid rgba(0,237,100,0.25);display:inline-block;line-height:60px;text-align:center;">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#00ed64" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-top:16px;"><polyline points="20 6 9 17 4 12"/></svg>
  </div>
</div>

<h1 style="font-size:22px;font-weight:700;color:#ffffff;margin:0 0 8px;text-align:center;letter-spacing:-0.3px;">Compte activé !</h1>
<p style="font-size:14px;color:#b8c4c2;text-align:center;margin:0 0 32px;">Votre accès à BudgetFlow est maintenant disponible.</p>

<p style="font-size:15px;color:#c8d8df;margin:0 0 12px;">
  Bonjour <strong style="color:#ffffff;"><?= $e($userName ?? 'Utilisateur') ?></strong>,
</p>
<p style="font-size:14px;color:#b8c4c2;margin:0 0 8px;line-height:1.7;">
  Votre compte a été <strong style="color:#00ed64;">validé par un administrateur</strong> BudgetFlow. Vous pouvez désormais vous connecter et commencer à gérer vos budgets personnels et collaboratifs.
</p>

<!-- CTA -->
<div style="text-align:center;margin:32px 0 28px;">
  <a href="<?= $e($loginUrl ?? '/login') ?>"
     style="display:inline-block;background:#00ed64;color:#001e2b;font-size:14px;font-weight:700;padding:13px 36px;border-radius:100px;text-decoration:none;letter-spacing:0.2px;">
    Se connecter →
  </a>
</div>

<!-- Note -->
<div style="background:rgba(0,237,100,0.06);border:1px solid rgba(0,237,100,0.15);border-radius:10px;padding:14px 18px;">
  <p style="font-size:12px;color:#5c6c75;margin:0;line-height:1.6;">
    Si vous n'avez pas créé de compte BudgetFlow, ignorez cet email en toute sécurité.
  </p>
</div>

<?php require __DIR__ . '/partials/bottom.php'; ?>
