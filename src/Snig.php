<?php
declare(strict_types=1);

namespace Snig;

use InvalidArgumentException;

final class Snig
{
    public const VERSION = '1.001-php';

    private string $name;
    private string $input;
    private string $output;
    private string $templateDir;
    private string $assetDir;
    private int $thumbnailSize;
    private int $detailSize;
    private string $sortBy;
    private array $forceMap = [];

    /**
     * @param array<int, string> $force
     */
    public function __construct(
        string $name,
        string $input,
        string $output,
        string $templateDir,
        string $assetDir,
        int $thumbnailSize = 200,
        int $detailSize = 1000,
        string $sortBy = 'created',
        array $force = []
    ) {
        $this->name = $name;
        $this->input = $input;
        $this->output = $output;
        $this->templateDir = $templateDir;
        $this->assetDir = $assetDir;
        $this->thumbnailSize = $thumbnailSize;
        $this->detailSize = $detailSize;
        $this->sortBy = $sortBy;
        $this->forceMap = array_fill_keys(array_map('strtolower', $force), true);
    }

    public function run(): void
    {
        $inputDir = realpath($this->input);
        if ($inputDir === false || !is_dir($inputDir)) {
            throw new InvalidArgumentException(sprintf('Input directory %s not found', $this->input));
        }

        if (!is_dir($this->templateDir)) {
            throw new InvalidArgumentException(sprintf('Template directory %s not found', $this->templateDir));
        }

        if (!is_dir($this->assetDir)) {
            throw new InvalidArgumentException(sprintf('Asset directory %s not found', $this->assetDir));
        }

        Utils::ensureDirectory($this->output);
        $outputDir = realpath($this->output) ?: $this->output;

        $collection = new Collection($inputDir, $this->name, $this->sortBy);
        $renderer = new TemplateRenderer($this->templateDir);

        $collection->resizeImages($outputDir, $this->thumbnailSize, $this->detailSize, $this->forceMap['resize'] ?? false);
        $collection->writeHtmlPages($outputDir, $renderer, self::VERSION);
        $collection->writeIndex($outputDir, $renderer, self::VERSION, $this->assetDir, $this->forceMap['zip'] ?? false);
    }
}
