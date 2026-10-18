<?php
/** @var \yii\web\View $this */
/** @var string $content */
?>
<!doctype html>
<html lang="uk">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($this->title ?? 'Лист', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { margin:0; padding:0; background:#f6f6f8; color:#222; }
    .wrapper { width:100%; table-layout:fixed; background:#f6f6f8; padding:20px 0; }
    .container { width:100%; max-width:600px; margin:0 auto; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.06) }
    .header { background:#0d6efd; color:#fff; padding:16px 24px; font-weight:600; font-size:18px; }
    .content { padding:24px; font-size:15px; line-height:1.6; }
    .btn { display:inline-block; padding:10px 16px; background:#0d6efd; color:#fff !important; border-radius:6px; text-decoration:none; }
    .muted { color:#666; font-size:12px; }
    .footer { padding:16px 24px; background:#fafafa; color:#666; font-size:12px; }
    a { color:#0d6efd; }
  </style>
</head>
<body>
  <table class="wrapper" role="presentation" cellspacing="0" cellpadding="0" border="0">
    <tr>
      <td align="center">
        <table class="container" role="presentation" cellspacing="0" cellpadding="0" border="0">
          <tr>
            <td class="header">
              Ваша активність — наші пропозиції 🎉
            </td>
          </tr>
          <tr>
            <td class="content">
              <?= $content ?>
            </td>
          </tr>
          <tr>
            <td class="footer">
              Ви отримали цей лист, бо активні на сайті за останні 24 год. Якщо це помилка — просто ігноруйте.<br>
              <span class="muted">© <?= date('Y') ?> Ваша компанія</span>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
