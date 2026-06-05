<?php
$e       = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$pct     = (float) ($percent ?? 0);
$spentV  = (float) ($spent   ?? 0);
$limitV  = (float) ($limit   ?? 0);
$ecart   = $limitV - $spentV;
$bannerColor = $pct >= 100 ? '#FF4D4D' : '#FFB547';
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Alerte Budget CASHtoCASH</title></head>
<body style="margin:0;padding:0;background:#0F1117;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0F1117;padding:40px 0;">
  <tr><td align="center">
  <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#1A1D27;border:1px solid #2A2F45;border-radius:16px;overflow:hidden;">

    <!-- Bandeau couleur dynamique -->
    <tr><td style="background:<?= $e($bannerColor) ?>;padding:12px 32px;text-align:center;">
      <span style="font-size:13px;font-weight:700;color:#fff;">
        <?= $pct >= 100 ? 'Budget DEPASSÉ' : 'Alerte : ' . round($pct) . '% du plafond atteint' ?>
      </span>
    </td></tr>

    <!-- Logo -->
    <tr><td style="padding:24px 32px 16px;text-align:center;border-bottom:1px solid #2A2F45;">
      <span style="font-size:24px;font-weight:bold;color:#6C63FF;">CASHtoCASH</span>
    </td></tr>

    <!-- Body -->
    <tr><td style="padding:32px;">
      <p style="font-size:15px;color:#C8D0E7;margin:0 0 12px;">Bonjour <strong style="color:#fff;"><?= $e($user['name'] ?? '') ?></strong>,</p>
      <p style="font-size:14px;color:#8B92A5;line-height:1.7;margin:0 0 24px;">
        Le budget <strong style="color:#6C63FF;">« <?= $e($budget['name'] ?? '') ?> »</strong>
        a atteint <strong style="color:<?= $e($bannerColor) ?>;"><?= round($pct) ?>%</strong> de son plafond.
      </p>

      <!-- Tableau chiffres -->
      <table width="100%" cellpadding="0" cellspacing="0" style="background:#12151F;border:1px solid #2A2F45;border-radius:10px;margin-bottom:28px;">
        <tr>
          <td style="padding:14px 20px;border-bottom:1px solid #2A2F45;">
            <span style="font-size:11px;color:#555B75;text-transform:uppercase;letter-spacing:1px;">Dépenses</span><br>
            <span style="font-size:16px;color:#FF4D4D;font-weight:700;"><?= number_format($spentV, 2, ',', ' ') ?> DT</span>
          </td>
          <td style="padding:14px 20px;border-bottom:1px solid #2A2F45;border-left:1px solid #2A2F45;">
            <span style="font-size:11px;color:#555B75;text-transform:uppercase;letter-spacing:1px;">Plafond</span><br>
            <span style="font-size:16px;color:#C8D0E7;font-weight:700;"><?= number_format($limitV, 2, ',', ' ') ?> DT</span>
          </td>
        </tr>
        <tr>
          <td colspan="2" style="padding:14px 20px;">
            <span style="font-size:11px;color:#555B75;text-transform:uppercase;letter-spacing:1px;">
              <?= $ecart >= 0 ? 'Restant' : 'Dépassement' ?>
            </span><br>
            <span style="font-size:16px;color:<?= $ecart >= 0 ? '#6C63FF' : '#FF4D4D' ?>;font-weight:700;">
              <?= $ecart >= 0
                  ? number_format($ecart, 2, ',', ' ') . ' DT restants'
                  : 'Dépassé de ' . number_format(abs($ecart), 2, ',', ' ') . ' DT' ?>
            </span>
          </td>
        </tr>
      </table>

      <!-- CTA -->
      <div style="text-align:center;margin:0 0 28px;">
        <a href="<?= $e($budgetUrl ?? '/budgets') ?>"
           style="display:inline-block;background:#6C63FF;color:#fff;font-size:14px;font-weight:700;padding:12px 28px;border-radius:10px;text-decoration:none;">
          Voir le budget &rarr;
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
