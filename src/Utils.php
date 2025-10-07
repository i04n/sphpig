<?php
declare(strict_types=1);

namespace Snig;

final class Utils
{
    private function __construct()
    {
    }

    public static function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (!mkdir($path, 0777, true) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('Unable to create directory %s', $path));
        }
    }

    public static function formatBytes(int $bytes): string
    {
        $bytes = max($bytes, 0);
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB'];
        $power = (int) floor(log((float) $bytes, 1000));
        $power = max(0, min($power, count($units) - 1));
        $value = $bytes / (1000 ** $power);
        $precision = $power === 0 || $value >= 10 ? 0 : 1;

        return number_format($value, $precision) . ' ' . $units[$power];
    }
}
