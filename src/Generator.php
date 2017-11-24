<?php
/**
 * Snippet generator for WooCommerce
 */

namespace ClaudioSanches\WooCommerce\Snippets;

use ClaudioSanches\WooCommerce\Snippets\Parsers\Functions;
use ClaudioSanches\WooCommerce\Snippets\Parsers\Actions;
use ClaudioSanches\WooCommerce\Snippets\Parsers\Filters;

/**
 * Generator class
 */
class Generator
{
    /**
     * Directory separator.
     *
     * @var string
     */
    protected $ds = DIRECTORY_SEPARATOR;

    /**
     * Path to scan.
     *
     * @var string
     */
    protected $path = '';

    /**
     * List of excluded paths.
     *
     * @var array
     */
    protected $excludedPaths = [
        '.',
        '..',
        'apigen',
        'assets',
        'dummy-data',
        'i18n',
        'legacy',
        'legacy-flat-rate',
        'legacy-free-shipping',
        'legacy-international-delivery',
        'legacy-local-delivery',
        'legacy-local-pickup',
        'libraries',
        'node_modules',
        'Simplify',
        'tests',
        'vendor',
    ];

    /**
     * List of excluded files.
     *
     * @var array
     */
    protected $excludedFiles = [
        'wc-deprecated-functions.php',
        'wc-update-functions.php',
    ];

    /**
     * Generator style.
     * Available 'sublime' and 'vscode'.
     *
     * @var string
     */
    protected $style = 'sublime';

    /**
     * Set path
     *
     * @param string $path Path.
     */
    public function setPath(string $path)
    {
        $this->path = rtrim($path, '/');
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set excluded paths.
     *
     * @param array $paths Paths
     */
    public function setExcludedPaths(array $paths)
    {
        $this->excludedPaths = $paths;
    }

    /**
     * Get excluded paths.
     *
     * @return array
     */
    public function getExcludedPaths(): array
    {
        return $this->excludedPaths;
    }

    /**
     * Set excluded files.
     *
     * @param array $files Files
     */
    public function setExcludedFiles(array $files)
    {
        $this->excludedFiles = $files;
    }

    /**
     * Get excluded files.
     *
     * @return array
     */
    public function getExcludedFiles(): array
    {
        return $this->excludedFiles;
    }

    /**
     * Set style.
     *
     * @param string $style Style.
     */
    public function setStyle(string $style)
    {
        $this->style = $style;
    }

    /**
     * Get style.
     *
     * @return string
     */
    public function getStyle(): string
    {
        return $this->style;
    }

    /**
     * Get file name.
     *
     * @param  string $type File name type.
     * @return string
     */
    protected function getSnippetsFilename(string $type): string
    {
        // Sublime by default.
        $suffix = 'sublime-completions';

        switch ($this->style) {
            case 'vscode':
                $suffix = '.json';
                break;
            case 'atom':
                $suffix = '.json';
                break;
        }

        return $type . $suffix;
    }

    /**
     * Get file to scan.
     *
     * @return array
     */
    public function getFiles(): array
    {
        $stack[] = $this->getPath();

        while ($stack) {
            $currentDir = array_pop($stack);
            if ($items = scandir($currentDir)) {
                $i = 0;

                while (isset($items[$i])) {
                    if (! in_array($items[$i], $this->getExcludedPaths(), true)) {
                        $currentFile = "{$currentDir}{$this->ds}{$items[$i]}";

                        if (is_file($currentFile)
                            && strpos($items[$i], '.php')
                            && ! in_array($items[$i], $this->getExcludedFiles(), true)) {
                            $path[] = "{$currentDir}{$this->ds}{$items[$i]}";
                        } elseif (is_dir($currentFile)) {
                            $stack[] = $currentFile;
                        }
                    }
                    $i++;
                }
            }
        }

        return $path;
    }

    /**
     * Generate snippets.
     *
     * @return array
     */
    public function generate(): array
    {
        $files     = $this->getFiles();
        $results   = [];
        $buildPath = __DIR__ . "{$this->ds}..{$this->ds}build{$this->ds}{$this->style}";

        if (!file_exists($buildPath)) {
            mkdir($buildPath, 0755, true);
        }

        // Functions.
        $functions = new Functions($files, $this->style);
        if ($functionsSchema = $functions->getSchema()) {
            $results['functions'] = count($functionsSchema);

            file_put_contents(
                $buildPath . $this->ds . $this->getSnippetsFilename('functions'),
                json_encode($functionsSchema, JSON_PRETTY_PRINT)
            );
        }

        // Actions.
        $actions = new Actions($files, $this->style);
        if ($actionsSchema = $actions->getSchema()) {
            $results['actions'] = count($actionsSchema) / 2;

            file_put_contents(
                $buildPath . $this->ds . $this->getSnippetsFilename('actions'),
                json_encode($actionsSchema, JSON_PRETTY_PRINT)
            );
        }

        // Filters.
        $filters = new Filters($files, $this->style);
        if ($filtersSchema = $filters->getSchema()) {
            $results['filters'] = count($filtersSchema) / 2;

            file_put_contents(
                $buildPath . $this->ds . $this->getSnippetsFilename('filters'),
                json_encode($filtersSchema, JSON_PRETTY_PRINT)
            );
        }

        return $results;
    }
}
