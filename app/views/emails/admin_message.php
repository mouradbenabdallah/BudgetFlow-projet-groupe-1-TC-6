<?php
$emailTitle = htmlspecialchars($subject ?? 'Message de l\'administration', ENT_QUOTES, 'UTF-8');
$e = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
require __DIR__ . '/partials/top.php';
?>

<!-- Icône message admin -->
<div style="text-align:center;margin-bottom:28px;">
  <div style="width:60px;height:60px;border-radius:50%;background:rgba(0,108,250,0.12);border:1.5px solid rgba(0,108,250,0.25);display:inline-block;line-height:60px;text-align:center;">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#006cfa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-top:16px;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
  </div>
</div>

<h1 style="font-size:22px;font-weight:700;color:#ffffff;margin:0 0 8px;text-align:center;letter-spacing:-0.3px;"><?= $e($subject ?? 'Message de l\'administration') ?></h1>
<p style="font-size:14px;color:#b8c4c2;text-align:center;margin:0 0 32px;">Message de l'équipe BudgetFlow</p>

<p style="font-size:15px;color:#c8d8df;margin:0 0 16px;">
  Bonjour <strong style="color:#ffffff;"><?= $e($userName ?? 'Utilisateur') ?></strong>,
</p>

<!-- Corps du message -->
<div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:10px;padding:20px 22px;margin-bottom:28px;">
  <?php
  $lines = explode("\n", $messageBody ?? '');
  foreach ($lines as $line) {
      $line = trim($line);
      if ($line === '') {
          echo '<br>';
      } else {
          echo '<p style="font-size:14px;color:#b8c4c2;margin:0 0 10px;line-height:1.7;">' . $e($line) . '</p>';
      }
  }
  ?>
</div>

<!-- CTA -->
<div style="text-align:center;margin:0 0 28px;">
  <a href="<?= $e($loginUrl ?? '/login') ?>"
     style="display:inline-block;background:#006cfa;color:#ffffff;font-size:14px;font-weight:700;padding:13px 36px;border-radius:100px;text-decoration:none;letter-spacing:0.2px;">
    Accéder à BudgetFlow →
  </a>
</div>

<!-- Note de bas -->
<div style="background:rgba(0,108,250,0.06);border:1px solid rgba(0,108,250,0.15);border-radius:10px;padding:14px 18px;">
  <p style="font-size:12px;color:#5c6c75;margin:0;line-height:1.6;">
    Ce message a été envoyé par l'administrateur de BudgetFlow. Si vous avez des questions, contactez l'administration.
  </p>
</div>

<?php require __DIR__ . '/partials/bottom.php'; ?>
