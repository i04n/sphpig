<?php
declare(strict_types=1);
/** @var Snig\Image $image */
/** @var array $collection */
$collectionName = htmlspecialchars($collection['name'] ?? 'Gallery', ENT_QUOTES, 'UTF-8');
$totalImages = (int) ($collection['size'] ?? 0);
$currentPos = $image->getPos();
$counterCurrent = str_pad((string) $currentPos, 3, '0', STR_PAD_LEFT);
$counterTotal = str_pad((string) $totalImages, 3, '0', STR_PAD_LEFT);
$previous = $image->getPrev();
$next = $image->getNext();
?>
<main class="page">
  <nav>
    <ul id="menu">
      <li class="collection-name"><a href="index.html" title="<?= $collectionName ?>"><?= $collectionName ?></a></li>
      <li class="counter"><?= $counterCurrent ?> / <?= $counterTotal ?></li>
      <li><a href="index.html" title="<?= $collectionName ?>">⇧</a></li>
      <li><a href="<?= htmlspecialchars($image->getUrl('orig'), ENT_QUOTES, 'UTF-8') ?>" download>⇓</a></li>
      <?php if ($previous !== null): ?>
      <li><a href="<?= htmlspecialchars($previous->getHtmlFile(), ENT_QUOTES, 'UTF-8') ?>" title="previous">⇦</a></li>
      <?php endif; ?>
      <?php if ($next !== null): ?>
      <li><a href="<?= htmlspecialchars($next->getHtmlFile(), ENT_QUOTES, 'UTF-8') ?>" title="next">⇨</a></li>
      <?php endif; ?>
      <li class="help">(or click image for next)</li>
    </ul>
  </nav>
  <?php $nextHref = $next ? $next->getHtmlFile() : 'index.html'; ?>
  <a href="<?= htmlspecialchars($nextHref, ENT_QUOTES, 'UTF-8') ?>">
    <img src="<?= htmlspecialchars($image->getUrl('preview'), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($image->getBasename(), ENT_QUOTES, 'UTF-8') ?>" />
  </a>
  <aside class="info">
    <ul>
      <li><?= htmlspecialchars($image->getBasename(), ENT_QUOTES, 'UTF-8') ?></li>
      <?php if ($image->getModel() !== null): ?>
      <li><?= htmlspecialchars($image->getModel() ?? '', ENT_QUOTES, 'UTF-8') ?></li>
      <?php endif; ?>
      <?php if ($image->getCreated() !== null): ?>
      <li><?= htmlspecialchars($image->getCreated() ?? '', ENT_QUOTES, 'UTF-8') ?></li>
      <?php endif; ?>
    </ul>
  </aside>
</main>
