<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Invitation BudgetFlow</title></head>
<body style="margin:0;padding:0;background:#0F1117;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0F1117;padding:40px 0;">
  <tr><td align="center">
  <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#1A1D27;border:1px solid #2A2F45;border-radius:16px;overflow:hidden;">

    <!-- Logo -->
    <tr><td style="padding:28px 32px 20px;text-align:center;border-bottom:1px solid #2A2F45;">
      <span style="font-size:24px;font-weight:bold;color:#6C63FF;">BudgetFlow</span>
    </td></tr>

    <!-- Body -->
    <tr><td style="padding:32px;">
      <p style="font-size:15px;color:#C8D0E7;margin:0 0 16px;">Bonjour <strong style="color:#fff;"><?= htmlspecialchars((string)($invitee['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>,</p>

      <p style="font-size:14px;color:#8B92A5;line-height:1.7;margin:0 0 24px;">
        <strong style="color:#C8D0E7;"><?= htmlspecialchars((string)($inviter['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
        vous invite à rejoindre le budget
        <strong style="color:#6C63FF;">« <?= htmlspecialchars((string)($budget['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?> »</strong>.
      </p>

      <!-- Détails budget -->
      <table width="100%" cellpadding="0" cellspacing="0" style="background:#12151F;border:1px solid #2A2F45;border-radius:10px;margin-bottom:28px;">
        <tr>
          <td style="padding:14px 20px;border-bottom:1px solid #2A2F45;">
            <span style="font-size:11px;color:#555B75;text-transform:uppercase;letter-spacing:1px;">Période</span><br>
            <span style="font-size:14px;color:#C8D0E7;font-weight:600;"><?= htmlspecialchars(ucfirst((string)($budget['period'] ?? '—')), ENT_QUOTES, 'UTF-8') ?></span>
          </td>
          <td style="padding:14px 20px;border-bottom:1px solid #2A2F45;border-left:1px solid #2A2F45;">
            <span style="font-size:11px;color:#555B75;text-transform:uppercase;letter-spacing:1px;">Démarré le</span><br>
            <span style="font-size:14px;color:#C8D0E7;font-weight:600;"><?= !empty($budget['start_date']) ? date('d/m/Y', strtotime((string)$budget['start_date'])) : '—' ?></span>
          </td>
        </tr>
        <?php if (!empty($budget['amount_limit'])): ?>
        <tr>
          <td colspan="2" style="padding:14px 20px;">
            <span style="font-size:11px;color:#555B75;text-transform:uppercase;letter-spacing:1px;">Plafond</span><br>
            <span style="font-size:14px;color:#6C63FF;font-weight:700;"><?= number_format((float)$budget['amount_limit'], 2, ',', ' ') ?> DT</span>
          </td>
        </tr>
        <?php endif; ?>
      </table>

      <!-- CTA -->
      <div style="text-align:center;margin:0 0 28px;">
        <a href="<?= htmlspecialchars((string)($budgetUrl ?? '/budgets'), ENT_QUOTES, 'UTF-8') ?>"
           style="display:inline-block;background:#6C63FF;color:#fff;font-size:14px;font-weight:700;padding:12px 28px;border-radius:10px;text-decoration:none;">
          Voir le budget &rarr;
        </a>
      </div>

      <p style="font-size:12px;color:#555B75;margin:0;line-height:1.6;">
        Si vous n'avez pas de compte, vous pouvez en créer un sur BudgetFlow pour rejoindre ce budget.
      </p>
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
