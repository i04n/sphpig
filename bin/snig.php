#!/usr/bin/env php
<?php
declare(strict_types=1);

use Snig\Snig;

$splAutoloader = static function (string $class): void {
    $prefix = 'Snig\\';
    $prefixLength = strlen($prefix);
    if (strncmp($class, $prefix, $prefixLength) !== 0) {
        return;
    }

    $relative = substr($class, $prefixLength);
    $relativePath = __DIR__ . '/../src/' . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
    if (is_file($relativePath)) {
        require $relativePath;
    }
};

spl_autoload_register($splAutoloader);

$options = getopt('', [
    'name::',
    'th_size::',
    'detail_size::',
    'sort_by::',
    'input:',
    'output:',
    'force::',
]);

if ($options === false) {
    fwrite(STDERR, "Unable to parse CLI arguments\n");
    exit(1);
}

$input = $options['input'] ?? null;
$output = $options['output'] ?? null;

if ($input === null || $output === null) {
    showUsage();
    exit(1);
}

$thumbnailSize = isset($options['th_size']) ? (int) $options['th_size'] : 200;
$detailSize = isset($options['detail_size']) ? (int) $options['detail_size'] : 1000;
$sortBy = isset($options['sort_by']) ? strtolower((string) $options['sort_by']) : 'created';

if (!in_array($sortBy, ['created', 'mtime'], true)) {
    fwrite(STDERR, "Invalid value for --sort_by. Use 'created' or 'mtime'.\n");
    exit(1);
}

if ($thumbnailSize <= 0 || $detailSize <= 0) {
    fwrite(STDERR, "Thumbnail and detail sizes must be positive integers\n");
    exit(1);
}

$forces = [];
if (array_key_exists('force', $options)) {
    $rawForce = $options['force'];
    $rawValues = is_array($rawForce) ? $rawForce : [$rawForce];
    foreach ($rawValues as $value) {
        if ($value === false || $value === null) {
            continue;
        }
        $parts = preg_split('/[,\s]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);
        if ($parts !== false) {
            $forces = array_merge($forces, $parts);
        }
    }
}

$name = $options['name'] ?? basename(rtrim((string) $input, DIRECTORY_SEPARATOR));
if (!is_string($name) || $name === '') {
    $name = basename(rtrim((string) $input, DIRECTORY_SEPARATOR));
}

$baseDir = realpath(__DIR__ . '/..') ?: __DIR__ . '/..';
$templateDir = $baseDir . '/templates';
$assetDir = $baseDir . '/assets';

if (!extension_loaded('gd')) {
    fwrite(STDERR, "The GD extension is required to resize images.\n");
    exit(1);
}

try {
    $snig = new Snig(
        (string) $name,
        (string) $input,
        (string) $output,
        $templateDir,
        $assetDir,
        $thumbnailSize,
        $detailSize,
        $sortBy,
        $forces
    );

    $snig->run();
} catch (\Throwable $exception) {
    fwrite(STDERR, 'Error: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

exit(0);

function showUsage(): void
{
    $script = basename(__FILE__);
    $lines = [
        "Usage: {$script} --input <dir> --output <dir> [options]",
        '',
        'Options:',
        '  --name <name>          Human-readable gallery name',
        '  --th_size <pixels>     Thumbnail width (default: 200)',
        '  --detail_size <pixels> Preview width (default: 1000)',
        "  --sort_by <created|mtime>  Sorting strategy (default: created)",
        '  --force <resize|zip>   Repeatable option to force work',
    ];
    fwrite(STDERR, implode(PHP_EOL, $lines) . PHP_EOL);
}
