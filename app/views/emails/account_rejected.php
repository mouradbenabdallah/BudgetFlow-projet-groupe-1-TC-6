<?php
$emailTitle = 'Votre demande de compte BudgetFlow';
$e = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
require __DIR__ . '/partials/top.php';
?>

<!-- Icône info -->
<div style="text-align:center;margin-bottom:28px;">
  <div style="width:60px;height:60px;border-radius:50%;background:rgba(245,158,11,0.1);border:1.5px solid rgba(245,158,11,0.2);display:inline-block;line-height:60px;text-align:center;">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-top:16px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
  </div>
</div>

<h1 style="font-size:22px;font-weight:700;color:#ffffff;margin:0 0 8px;text-align:center;letter-spacing:-0.3px;">Demande non retenue</h1>
<p style="font-size:14px;color:#b8c4c2;text-align:center;margin:0 0 32px;">Votre demande d'accès à BudgetFlow n'a pas pu être validée.</p>

<p style="font-size:15px;color:#c8d8df;margin:0 0 12px;">
  Bonjour <strong style="color:#ffffff;"><?= $e($userName ?? 'Utilisateur') ?></strong>,
</p>
<p style="font-size:14px;color:#b8c4c2;margin:0 0 8px;line-height:1.7;">
  Après examen de votre demande, votre compte n'a pas pu être activé. Si vous pensez qu'il s'agit d'une erreur ou souhaitez plus d'informations, n'hésitez pas à nous contacter.
</p>

<!-- Note -->
<div style="background:rgba(245,158,11,0.06);border:1px solid rgba(245,158,11,0.15);border-radius:10px;padding:14px 18px;margin-top:24px;">
  <p style="font-size:12px;color:#b8c4c2;margin:0;line-height:1.6;">
    Vous pouvez créer un nouveau compte à tout moment sur la plateforme. Votre adresse email reste disponible pour une future inscription.
  </p>
</div>

<?php require __DIR__ . '/partials/bottom.php'; ?>
