<?php
declare(strict_types=1);

namespace Snig;

use InvalidArgumentException;
use RuntimeException;

final class Image
{
    private ?Image $previous = null;
    private ?Image $next = null;
    private int $position = 0;
    private array $exif = [];
    private ?string $created = null;
    private ?string $model = null;
    private ?int $orientation = null;
    private ?int $createdTimestamp = null;
    private int $mtime;
    private string $filePath;

    public function __construct(string $filePath)
    {
        $realPath = realpath($filePath);
        if ($realPath === false || !is_file($realPath)) {
            throw new InvalidArgumentException(sprintf('Image file %s not found', $filePath));
        }

        $this->filePath = $realPath;
        $this->mtime = (int) (filemtime($realPath) ?: time());

        if (function_exists('exif_read_data')) {
            $rawExif = @exif_read_data($realPath, 'ANY_TAG', true);
            if (is_array($rawExif)) {
                foreach ($rawExif as $section) {
                    if (!is_array($section)) {
                        continue;
                    }
                    foreach ($section as $key => $value) {
                        if (!isset($this->exif[$key])) {
                            $this->exif[$key] = $value;
                        }
                    }
                }
            }
        }

        $createdValue = $this->exif['DateTimeOriginal'] ?? $this->exif['CreateDate'] ?? $this->exif['DateTime'] ?? null;
        if (is_string($createdValue) && trim($createdValue) !== '' && strncmp($createdValue, '0000', 4) !== 0) {
            $normalized = self::normalizeExifDate($createdValue);
            $timestamp = strtotime($normalized);
            if ($timestamp !== false) {
                $this->createdTimestamp = $timestamp;
            }
            $this->created = $normalized;
        }

        if ($this->createdTimestamp === null) {
            $this->createdTimestamp = $this->mtime;
        }

        $this->model = isset($this->exif['Model']) && $this->exif['Model'] !== '' ? (string) $this->exif['Model'] : null;

        if (isset($this->exif['Orientation'])) {
            $this->orientation = $this->parseOrientation($this->exif['Orientation']);
        }
    }

    public function setChain(int $position, ?Image $previous, ?Image $next): void
    {
        $this->position = $position;
        $this->previous = $previous;
        $this->next = $next;
    }

    public function resize(string $outputDir, int $thumbnailSize, int $detailSize, bool $force): void
    {
        Utils::ensureDirectory($outputDir);

        $basename = $this->getBasename();
        $originalCopy = rtrim($outputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'orig_' . $basename;
        if (!is_file($originalCopy)) {
            if (!copy($this->filePath, $originalCopy)) {
                throw new RuntimeException(sprintf('Unable to copy original image to %s', $originalCopy));
            }
        }

        $variants = [
            ['format' => 'thumbnail', 'size' => $thumbnailSize],
            ['format' => 'preview', 'size' => $detailSize],
        ];

        $resource = null;
        foreach ($variants as $variant) {
            $size = (int) $variant['size'];
            if ($size <= 0) {
                continue;
            }

            $targetPath = rtrim($outputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $variant['format'] . '_' . $basename;
            if (!$force && is_file($targetPath)) {
                continue;
            }

            if ($resource === null) {
                $resource = $this->loadImageResource();
            }

            $scaled = $this->createScaledImage($resource, $size);
            if (imagejpeg($scaled, $targetPath, 100) === false) {
                $this->destroyImage($scaled);
                throw new RuntimeException(sprintf('Failed to write resized image %s', $targetPath));
            }
            $this->destroyImage($scaled);

            echo '.';
            flush();
        }

        if ($resource !== null) {
            $this->destroyImage($resource);
        }
    }

    public function writeHtmlPage(TemplateRenderer $renderer, string $outputDir, Collection $collection, string $version): void
    {
        $renderer->renderToFile('page', [
            'image' => $this,
            'collection' => [
                'name' => $collection->getName(),
                'size' => $collection->getSize(),
            ],
            'version' => $version,
        ], rtrim($outputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->getHtmlFile());
    }

    public function getUrl(string $format): string
    {
        return $format . '_' . $this->getBasename();
    }

    public function getHtmlFile(): string
    {
        $html = strtolower($this->getBasename());
        return preg_replace('/\.[^.]+$/', '.html', $html) ?? $html . '.html';
    }

    public function getBasename(): string
    {
        return basename($this->filePath);
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getCreated(): ?string
    {
        return $this->created;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function getMtime(): int
    {
        return $this->mtime;
    }

    public function getPos(): int
    {
        return $this->position;
    }

    public function getPrev(): ?Image
    {
        return $this->previous;
    }

    public function getNext(): ?Image
    {
        return $this->next;
    }

    public function getCreatedTimestamp(): int
    {
        return $this->createdTimestamp ?? $this->mtime;
    }

    private static function normalizeExifDate(string $value): string
    {
        $trimmed = trim($value);
        if (preg_match('/^(\d{4}):(\d{2}):(\d{2}) (\d{2}):(\d{2}):(\d{2})$/', $trimmed, $matches)) {
            return sprintf('%s-%s-%s %s:%s:%s', $matches[1], $matches[2], $matches[3], $matches[4], $matches[5], $matches[6]);
        }

        $timestamp = strtotime($trimmed);
        if ($timestamp !== false) {
            return date('Y-m-d H:i:s', $timestamp);
        }

        return $trimmed;
    }

    /**
     * @return resource|object
     */
    private function loadImageResource()
    {
        $resource = @imagecreatefromjpeg($this->filePath);
        if ($resource === false) {
            throw new RuntimeException(sprintf('Unable to read image file %s', $this->filePath));
        }

        if (function_exists('imagesetinterpolation')) {
            @imagesetinterpolation($resource, IMG_BICUBIC);
        }

        return $this->applyOrientation($resource);
    }

    /**
     * @param resource|object $source
     */
    private function createScaledImage($source, int $maxSize)
    {
        $width = imagesx($source);
        $height = imagesy($source);

        if ($width === 0 || $height === 0) {
            throw new RuntimeException(sprintf('Image %s has invalid dimensions', $this->filePath));
        }

        if ($width >= $height) {
            $targetWidth = $maxSize;
            $targetHeight = (int) round($height * ($maxSize / $width));
        } else {
            $targetHeight = $maxSize;
            $targetWidth = (int) round($width * ($maxSize / $height));
        }

        $scaled = imagecreatetruecolor($targetWidth, $targetHeight);
        if (!imagecopyresampled($scaled, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height)) {
            $this->destroyImage($scaled);
            throw new RuntimeException(sprintf('Unable to scale image %s', $this->filePath));
        }

        return $scaled;
    }

    private function parseOrientation($value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        if (is_string($value)) {
            if (preg_match('/([1-8])/', $value, $matches)) {
                return (int) $matches[1];
            }

            $lower = strtolower($value);
            if (strpos($lower, '90 cw') !== false) {
                return 6;
            }
            if (strpos($lower, '90 ccw') !== false) {
                return 8;
            }
            if (strpos($lower, '180') !== false) {
                return 3;
            }
        }

        return null;
    }

    /**
     * @param resource|object $image
     * @return resource|object
     */
    private function applyOrientation($image)
    {
        $orientation = $this->orientation;
        if ($orientation === null || $orientation === 1) {
            return $image;
        }

        switch ($orientation) {
            case 2:
                if (function_exists('imageflip')) {
                    imageflip($image, IMG_FLIP_HORIZONTAL);
                }
                return $image;
            case 3:
                $rotated = imagerotate($image, 180, 0);
                $this->destroyImage($image);
                return $rotated ?: $image;
            case 4:
                if (function_exists('imageflip')) {
                    imageflip($image, IMG_FLIP_VERTICAL);
                }
                return $image;
            case 5:
                if (function_exists('imageflip')) {
                    imageflip($image, IMG_FLIP_HORIZONTAL);
                }
                $rotated = imagerotate($image, 90, 0);
                $this->destroyImage($image);
                return $rotated ?: $image;
            case 6:
                $rotated = imagerotate($image, -90, 0);
                $this->destroyImage($image);
                return $rotated ?: $image;
            case 7:
                if (function_exists('imageflip')) {
                    imageflip($image, IMG_FLIP_HORIZONTAL);
                }
                $rotated = imagerotate($image, -90, 0);
                $this->destroyImage($image);
                return $rotated ?: $image;
            case 8:
                $rotated = imagerotate($image, 90, 0);
                $this->destroyImage($image);
                return $rotated ?: $image;
            default:
                return $image;
        }
    }

    /**
     * @param resource|object $image
     */
    private function destroyImage($image): void
    {
        if (is_resource($image)) {
            imagedestroy($image);
            return;
        }

        if (is_object($image) && strtolower(get_class($image)) === 'gdimage') {
            imagedestroy($image);
        }
    }
}
