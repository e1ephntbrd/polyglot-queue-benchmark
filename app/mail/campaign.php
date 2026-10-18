<?php
/** @var \yii\web\View $this */
/** @var array $data */
$offers = $data['offers'] ?? [];
$windowHours = (int)($data['windowHours'] ?? 24);
$minViews = (int)($data['minViews'] ?? 3);
$fallbackUrl = $data['fallbackUrl'] ?? 'https://example.local';
?>
<h2>Дякуємо за вашу активність!</h2>
<p>
  Ми помітили, що ви переглядали товари (мінімум <?= (int)$minViews ?> рази) і взаємодіяли з промо за останні <?= $windowHours ?> годин.
</p>

<?php if ($offers): ?>
  <p>Ось кілька гарячих пропозицій для вас:</p>
  <ul>
    <?php foreach ($offers as $o): ?>
      <li>
        <strong><?= htmlspecialchars($o['title'] ?? 'Пропозиція', ENT_QUOTES, 'UTF-8') ?></strong>
        — знижка <?= isset($o['discount']) ? (int)$o['discount'] : 0 ?>%
        <br>
        <a class="btn" href="<?= htmlspecialchars($o['url'] ?? $fallbackUrl, ENT_QUOTES, 'UTF-8') ?>">Перейти до пропозиції</a>
      </li>
    <?php endforeach; ?>
  </ul>
<?php else: ?>
  <p>Зараз діють сезонні пропозиції — загляньте до нас і оберіть найкраще для себе.</p>
  <p><a class="btn" href="<?= htmlspecialchars($fallbackUrl, ENT_QUOTES, 'UTF-8') ?>">Переглянути новинки</a></p>
<?php endif; ?>

<p class="muted">Якщо не хочете отримувати такі листи — змініть налаштування у своєму профілі.</p>
