<?php
$e    = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$ru   = $requestingUser ?? [];
$date = !empty($ru['created_at']) ? date('d/m/Y', strtotime((string) $ru['created_at'])) : '—';
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Demande de suppression — CASHtoCASH Admin</title></head>
<body style="margin:0;padding:0;background:#0F1117;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0F1117;padding:40px 0;">
  <tr><td align="center">
  <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#1A1D27;border:1px solid #2A2F45;border-radius:16px;overflow:hidden;">

    <!-- Logo -->
    <tr><td style="padding:28px 32px 20px;text-align:center;border-bottom:1px solid #2A2F45;">
      <span style="font-size:24px;font-weight:bold;color:#6C63FF;">CASHtoCASH</span>
      <span style="display:block;font-size:11px;color:#555B75;margin-top:4px;text-transform:uppercase;letter-spacing:2px;">Administration</span>
    </td></tr>

    <!-- Bandeau alerte -->
    <tr><td style="background:#FF4D4D;padding:10px 32px;text-align:center;">
      <span style="font-size:13px;font-weight:700;color:#fff;">Demande de suppression de compte</span>
    </td></tr>

    <!-- Body -->
    <tr><td style="padding:32px;">
      <p style="font-size:15px;color:#C8D0E7;margin:0 0 16px;">Bonjour Administrateur,</p>
      <p style="font-size:14px;color:#8B92A5;line-height:1.7;margin:0 0 24px;">
        L'utilisateur ci-dessous a demandé la suppression de son compte CASHtoCASH.
      </p>

      <!-- Fiche utilisateur -->
      <table width="100%" cellpadding="0" cellspacing="0" style="background:#12151F;border:1px solid #2A2F45;border-radius:10px;margin-bottom:28px;">
        <tr>
          <td style="padding:14px 20px;border-bottom:1px solid #2A2F45;">
            <span style="font-size:11px;color:#555B75;text-transform:uppercase;letter-spacing:1px;">Nom</span><br>
            <span style="font-size:14px;color:#fff;font-weight:600;"><?= $e($ru['name'] ?? '—') ?></span>
          </td>
          <td style="padding:14px 20px;border-bottom:1px solid #2A2F45;border-left:1px solid #2A2F45;">
            <span style="font-size:11px;color:#555B75;text-transform:uppercase;letter-spacing:1px;">Email</span><br>
            <span style="font-size:14px;color:#6C63FF;"><?= $e($ru['email'] ?? '—') ?></span>
          </td>
        </tr>
        <tr>
          <td colspan="2" style="padding:14px 20px;">
            <span style="font-size:11px;color:#555B75;text-transform:uppercase;letter-spacing:1px;">Inscrit le</span><br>
            <span style="font-size:14px;color:#C8D0E7;"><?= $e($date) ?></span>
          </td>
        </tr>
      </table>

      <!-- CTA -->
      <div style="text-align:center;margin:0 0 20px;">
        <a href="<?= $e($adminUrl ?? '/admin/users') ?>"
           style="display:inline-block;background:#6C63FF;color:#fff;font-size:14px;font-weight:700;padding:12px 28px;border-radius:10px;text-decoration:none;">
          Gérer les utilisateurs &rarr;
        </a>
      </div>
    </td></tr>

    <!-- Footer -->
    <tr><td style="padding:16px 32px;text-align:center;border-top:1px solid #2A2F45;">
      <p style="font-size:12px;color:#555B75;margin:0;">&copy; 2025 CASHtoCASH &mdash; ITEAM University</p>
    </td></tr>

  </table>
  </td></tr>
</table>
</body>
</html>
