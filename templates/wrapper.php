<?php
declare(strict_types=1);
/** @var array $collection */
/** @var string $content */
/** @var string $version */
$collectionName = htmlspecialchars($collection['name'] ?? 'Gallery', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html>
  <head>
    <title><?= $collectionName ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="snig.css" rel="stylesheet" type="text/css" />
  </head>
  <body>
    <?= $content ?>
    <footer>
      Created with <a href="https://snig.plix.at">s<sup>n</sup>ig</a> <?= htmlspecialchars($version, ENT_QUOTES, 'UTF-8') ?>.
      <div class="license">
        © 2025 Thomas Klausner, licensed under <a href="https://creativecommons.org/licenses/by-nc-sa/4.0/">CC BY-NC-SA 4.0</a>
      </div>
    </footer>
  </body>
</html>
