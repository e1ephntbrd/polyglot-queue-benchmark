<?php
/** @var \yii\web\View $this */
/** @var array $data */
$offers = $data['offers'] ?? [];
$windowHours = (int)($data['windowHours'] ?? 24);
$minViews = (int)($data['minViews'] ?? 3);
$fallbackUrl = $data['fallbackUrl'] ?? 'https://example.local';
?>
Дякуємо за вашу активність!

Ми помітили, що ви переглядали товари (мінімум <?= (int)$minViews ?> рази) і взаємодіяли з промо за останні <?= $windowHours ?> годин.

<?php if ($offers): ?>
Пропозиції:
<?php foreach ($offers as $o): ?>
- <?= ($o['title'] ?? 'Пропозиція') ?> — знижка <?= isset($o['discount']) ? (int)$o['discount'] : 0 ?>%
  <?= ($o['url'] ?? $fallbackUrl) ?>
<?php endforeach; ?>
<?php else: ?>
Зараз діють сезонні пропозиції — перегляньте новинки:
<?= $fallbackUrl ?>
<?php endif; ?>

Якщо не хочете отримувати такі листи — змініть налаштування у профілі.
