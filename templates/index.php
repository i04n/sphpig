<?php
declare(strict_types=1);
/** @var array $collection */
$images = $collection['images'] ?? [];
$collectionName = htmlspecialchars($collection['name'] ?? 'Gallery', ENT_QUOTES, 'UTF-8');
$zipFile = htmlspecialchars($collection['zip_file'] ?? '', ENT_QUOTES, 'UTF-8');
$zipSize = htmlspecialchars($collection['zip_size'] ?? '', ENT_QUOTES, 'UTF-8');
$totalImages = count($images);
$counter = str_pad((string) $totalImages, 3, '0', STR_PAD_LEFT);
?>
<main class="index">
  <nav>
    <ul id="menu">
      <li class="collection-name"><?= $collectionName ?></li>
      <li class="counter"><?= $counter ?></li>
      <?php if ($zipFile !== ''): ?>
      <li class="zip-file"><a href="<?= $zipFile ?>" download>⇓ <?= $zipFile ?> (<?= $zipSize ?>)</a></li>
      <?php endif; ?>
    </ul>
  </nav>
  <ul class="images">
    <?php foreach ($images as $image): ?>
      <li>
        <a href="<?= htmlspecialchars($image->getHtmlFile(), ENT_QUOTES, 'UTF-8') ?>">
          <img src="<?= htmlspecialchars($image->getUrl('thumbnail'), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($image->getBasename(), ENT_QUOTES, 'UTF-8') ?>" />
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</main>
