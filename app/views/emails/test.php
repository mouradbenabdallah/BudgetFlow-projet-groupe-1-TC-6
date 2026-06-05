<?php
$emailTitle = 'Email de test — CASHtoCASH';
$e = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
require __DIR__ . '/partials/top.php';
?>

<!-- Icône test -->
<div style="text-align:center;margin-bottom:28px;">
  <div style="width:60px;height:60px;border-radius:50%;background:rgba(59,130,246,0.12);border:1.5px solid rgba(59,130,246,0.25);display:inline-block;line-height:60px;text-align:center;">
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-top:17px;">
      <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
    </svg>
  </div>
</div>

<h1 style="font-size:22px;font-weight:700;color:#ffffff;margin:0 0 8px;text-align:center;letter-spacing:-0.3px;">Email de test reçu !</h1>
<p style="font-size:14px;color:#b8c4c2;text-align:center;margin:0 0 32px;">La configuration SMTP Gmail fonctionne correctement.</p>

<p style="font-size:15px;color:#c8d8df;margin:0 0 16px;">
  Bonjour <strong style="color:#ffffff;"><?= $e($adminName ?? 'Administrateur') ?></strong>,
</p>
<p style="font-size:14px;color:#b8c4c2;margin:0 0 20px;line-height:1.7;">
  Cet email confirme que le système d'envoi de CASHtoCASH est correctement configuré avec Gmail SMTP.
</p>

<!-- Config summary -->
<div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:10px;padding:18px 20px;margin-bottom:20px;">
  <p style="font-size:11px;color:#5c6c75;margin:0 0 12px;text-transform:uppercase;letter-spacing:1.5px;font-weight:600;">Configuration active</p>
  <table cellpadding="0" cellspacing="0" style="width:100%;">
    <tr>
      <td style="font-size:12px;color:#5c6c75;padding:4px 0;width:110px;">Serveur SMTP</td>
      <td style="font-size:12px;color:#c8d8df;padding:4px 0;font-family:monospace;">smtp.gmail.com:587</td>
    </tr>
    <tr>
      <td style="font-size:12px;color:#5c6c75;padding:4px 0;">Chiffrement</td>
      <td style="font-size:12px;color:#c8d8df;padding:4px 0;font-family:monospace;">STARTTLS</td>
    </tr>
    <tr>
      <td style="font-size:12px;color:#5c6c75;padding:4px 0;">Expéditeur</td>
      <td style="font-size:12px;color:#c8d8df;padding:4px 0;font-family:monospace;"><?= $e($fromEmail ?? '') ?></td>
    </tr>
    <tr>
      <td style="font-size:12px;color:#5c6c75;padding:4px 0;">Envoyé le</td>
      <td style="font-size:12px;color:#c8d8df;padding:4px 0;font-family:monospace;"><?= $e($sentAt ?? date('d/m/Y H:i:s')) ?></td>
    </tr>
  </table>
</div>

<div style="background:rgba(0,237,100,0.06);border:1px solid rgba(0,237,100,0.15);border-radius:10px;padding:14px 18px;">
  <p style="font-size:12px;color:#b8c4c2;margin:0;line-height:1.6;">
    Tous les emails transactionnels (validation de compte, changements de rôle) seront envoyés depuis cette adresse.
  </p>
</div>

<?php require __DIR__ . '/partials/bottom.php'; ?>
