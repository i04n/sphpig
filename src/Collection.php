<?php
declare(strict_types=1);

namespace Snig;

use FilesystemIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

final class Collection
{
    /** @var Image[] */
    private array $images = [];

    /** @var Image[] */
    private array $sorted = [];

    private string $inputDir;
    private string $name;
    private string $sortBy;

    public function __construct(string $inputDir, string $name, string $sortBy = 'created')
    {
        if (!is_dir($inputDir)) {
            throw new InvalidArgumentException(sprintf('Input directory %s not found', $inputDir));
        }

        $this->inputDir = $inputDir;
        $this->name = $name;
        $this->sortBy = $sortBy;

        $iterator = new FilesystemIterator($inputDir, FilesystemIterator::SKIP_DOTS);
        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }

            $filename = $fileInfo->getFilename();
            if (!preg_match('/\.jpe?g$/i', $filename)) {
                continue;
            }

            $image = new Image($fileInfo->getPathname());
            $this->images[$image->getBasename()] = $image;
        }

        $this->sorted = array_values($this->images);
        $this->sortImages();
        $this->linkImages();
    }

    public function resizeImages(string $outputDir, int $thumbnailSize, int $detailSize, bool $forceResize): void
    {
        Utils::ensureDirectory($outputDir);

        $count = count($this->sorted);
        if ($count === 0) {
            return;
        }

        printf("Resizing %d images\n", $count);
        foreach ($this->sorted as $image) {
            $image->resize($outputDir, $thumbnailSize, $detailSize, $forceResize);
        }
        echo PHP_EOL;
    }

    public function writeHtmlPages(string $outputDir, TemplateRenderer $renderer, string $version): void
    {
        $count = count($this->sorted);
        if ($count === 0) {
            return;
        }

        printf("Writing %d html pages\n", $count);
        foreach ($this->sorted as $image) {
            $image->writeHtmlPage($renderer, $outputDir, $this, $version);
        }
    }

    public function writeIndex(string $outputDir, TemplateRenderer $renderer, string $version, string $assetDir, bool $forceZip): void
    {
        Utils::ensureDirectory($outputDir);

        $cssSource = rtrim($assetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'snig.css';
        $cssTarget = rtrim($outputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'snig.css';
        if (is_file($cssSource) && (!is_file($cssTarget) || filemtime($cssSource) > filemtime($cssTarget))) {
            if (!copy($cssSource, $cssTarget)) {
                throw new RuntimeException(sprintf('Unable to copy stylesheet from %s to %s', $cssSource, $cssTarget));
            }
        }

        $zipFile = $this->getZipFilePath($outputDir);
        if ($forceZip || !is_file($zipFile)) {
            printf("Creating zip archive %s\n", basename($zipFile));
            $this->createZipArchive($zipFile);
        }

        $zipSize = is_file($zipFile) ? Utils::formatBytes((int) filesize($zipFile)) : '0 B';

        $renderer->renderToFile('index', [
            'collection' => [
                'name' => $this->name,
                'images' => $this->sorted,
                'zip_file' => basename($zipFile),
                'zip_size' => $zipSize,
            ],
            'version' => $version,
        ], rtrim($outputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.html');
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Image[]
     */
    public function getImages(): array
    {
        return $this->sorted;
    }

    public function getSize(): int
    {
        return count($this->sorted);
    }

    public function getZipFilePath(string $outputDir): string
    {
        $basename = strtolower(basename($outputDir));
        if ($basename === '') {
            $basename = 'snig';
        }

        return rtrim($outputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $basename . '.zip';
    }

    private function sortImages(): void
    {
        $sortKey = strtolower($this->sortBy);
        usort($this->sorted, function (Image $a, Image $b) use ($sortKey): int {
            switch ($sortKey) {
                case 'mtime':
                    $comparison = $a->getMtime() <=> $b->getMtime();
                    break;
                default:
                    $comparison = $a->getCreatedTimestamp() <=> $b->getCreatedTimestamp();
                    break;
            }

            if ($comparison !== 0) {
                return $comparison;
            }

            return strcasecmp($a->getBasename(), $b->getBasename());
        });
    }

    private function linkImages(): void
    {
        $count = count($this->sorted);
        if ($count === 0) {
            return;
        }

        for ($index = 0; $index < $count; $index++) {
            $image = $this->sorted[$index];
            $prevIndex = $index === 0 ? $count - 1 : $index - 1;
            $nextIndex = $index === $count - 1 ? 0 : $index + 1;
            $image->setChain($index + 1, $this->sorted[$prevIndex], $this->sorted[$nextIndex]);
        }
    }

    private function createZipArchive(string $zipFilePath): void
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('The ZipArchive extension is required to create zip files.');
        }

        Utils::ensureDirectory(dirname($zipFilePath));

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException(sprintf('Unable to open zip archive %s for writing', $zipFilePath));
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->inputDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }

            $absolutePath = $fileInfo->getPathname();
            $relativePath = substr($absolutePath, strlen($this->inputDir) + 1) ?: $fileInfo->getFilename();
            $zip->addFile($absolutePath, $relativePath);
        }

        $zip->close();
    }
}
