<?php
$emailTitle = 'Modification de votre rôle CASHtoCASH';
$e = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$isAdmin = ($newRole ?? 'user') === 'admin';
require __DIR__ . '/partials/top.php';
?>

<!-- Icône rôle -->
<div style="text-align:center;margin-bottom:28px;">
  <div style="width:60px;height:60px;border-radius:50%;background:<?= $isAdmin ? 'rgba(255,181,71,0.12)' : 'rgba(0,237,100,0.10)' ?>;border:1.5px solid <?= $isAdmin ? 'rgba(255,181,71,0.25)' : 'rgba(0,237,100,0.2)' ?>;display:inline-block;line-height:60px;text-align:center;">
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="<?= $isAdmin ? '#FFB547' : '#00ed64' ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-top:17px;">
      <?php if ($isAdmin): ?>
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
      <?php else: ?>
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
      <?php endif; ?>
    </svg>
  </div>
</div>

<h1 style="font-size:22px;font-weight:700;color:#ffffff;margin:0 0 8px;text-align:center;letter-spacing:-0.3px;">
  Rôle mis à jour
</h1>
<p style="font-size:14px;color:#b8c4c2;text-align:center;margin:0 0 32px;">
  Votre rôle sur la plateforme a été modifié par un administrateur.
</p>

<p style="font-size:15px;color:#c8d8df;margin:0 0 16px;">
  Bonjour <strong style="color:#ffffff;"><?= $e($userName ?? 'Utilisateur') ?></strong>,
</p>

<div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:10px;padding:18px 20px;margin-bottom:24px;">
  <p style="font-size:13px;color:#5c6c75;margin:0 0 8px;text-transform:uppercase;letter-spacing:1px;">Nouveau rôle</p>
  <p style="font-size:20px;font-weight:700;color:<?= $isAdmin ? '#FFB547' : '#00ed64' ?>;margin:0;">
    <?= $isAdmin ? 'Administrateur' : 'Utilisateur' ?>
  </p>
</div>

<p style="font-size:14px;color:#b8c4c2;margin:0 0 8px;line-height:1.7;">
  <?php if ($isAdmin): ?>
    Vous disposez maintenant des droits d'administration. Vous pouvez accéder au panneau d'administration via votre tableau de bord.
  <?php else: ?>
    Vos droits ont été mis à jour. Vous conservez un accès complet à vos budgets et transactions.
  <?php endif; ?>
</p>

<!-- CTA -->
<div style="text-align:center;margin:28px 0 0;">
  <a href="<?= $e($loginUrl ?? '/login') ?>"
     style="display:inline-block;background:#00ed64;color:#001e2b;font-size:14px;font-weight:700;padding:13px 36px;border-radius:100px;text-decoration:none;">
    Accéder à mon compte →
  </a>
</div>

<?php require __DIR__ . '/partials/bottom.php'; ?>
