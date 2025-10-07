<?php
declare(strict_types=1);

namespace Snig;

final class TemplateRenderer
{
    private string $templateDir;

    public function __construct(string $templateDir)
    {
        $this->templateDir = $templateDir;
    }

    public function renderToFile(string $template, array $data, string $outputPath): void
    {
        $content = $this->renderTemplate($template, $data);
        $wrapperData = $data;
        $wrapperData['content'] = $content;
        $html = $this->renderTemplate('wrapper', $wrapperData);

        $directory = \dirname($outputPath);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Unable to create directory %s', $directory));
            }
        }

        if (file_put_contents($outputPath, $html) === false) {
            throw new \RuntimeException(sprintf('Unable to write template output to %s', $outputPath));
        }
    }

    private function renderTemplate(string $template, array $data): string
    {
        $path = $this->templateDir . DIRECTORY_SEPARATOR . $template . '.php';
        if (!is_file($path)) {
            throw new \RuntimeException(sprintf('Template %s not found in %s', $template, $this->templateDir));
        }

        extract($data, EXTR_OVERWRITE);
        ob_start();
        try {
            include $path;
        } finally {
            $output = ob_get_clean();
        }

        return $output;
    }
}
