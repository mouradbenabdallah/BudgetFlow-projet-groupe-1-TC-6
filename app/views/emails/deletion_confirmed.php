<?php $e = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Compte supprimé — BudgetFlow</title></head>
<body style="margin:0;padding:0;background:#0F1117;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0F1117;padding:40px 0;">
  <tr><td align="center">
  <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#1A1D27;border:1px solid #2A2F45;border-radius:16px;overflow:hidden;">

    <!-- Logo -->
    <tr><td style="padding:28px 32px 20px;text-align:center;border-bottom:1px solid #2A2F45;">
      <span style="font-size:24px;font-weight:bold;color:#6C63FF;">BudgetFlow</span>
    </td></tr>

    <!-- Body -->
    <tr><td style="padding:40px 32px;">
      <p style="font-size:15px;color:#C8D0E7;margin:0 0 16px;">Bonjour <strong style="color:#fff;"><?= $e($user['name'] ?? '') ?></strong>,</p>

      <p style="font-size:14px;color:#8B92A5;line-height:1.7;margin:0 0 16px;">
        Votre compte BudgetFlow a été <strong style="color:#FF4D4D;">supprimé</strong> suite à votre demande.
      </p>
      <p style="font-size:14px;color:#8B92A5;line-height:1.7;margin:0 0 28px;">
        Vos données personnelles ont été définitivement effacées de nos systèmes.
      </p>

      <div style="background:#12151F;border:1px solid #2A2F45;border-radius:10px;padding:20px 24px;margin-bottom:28px;text-align:center;">
        <p style="font-size:14px;color:#8B92A5;margin:0;line-height:1.7;">
          Merci d'avoir utilisé BudgetFlow.<br>
          Nous espérons vous revoir bientôt.
        </p>
      </div>
    </td></tr>

    <!-- Footer -->
    <tr><td style="padding:16px 32px;text-align:center;border-top:1px solid #2A2F45;">
      <p style="font-size:12px;color:#555B75;margin:0;">&copy; 2025 BudgetFlow &mdash; ITEAM University</p>
    </td></tr>

  </table>
  </td></tr>
</table>
</body>
</html>
