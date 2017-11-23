<?php
/**
 * Snippet generator for WooCommerce
 */

namespace ClaudioSanches\WooCommerce\Snippets;

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
    protected function getStyleFileNames(string $type): string
    {
        // Sublime by default.
        $names = [
            'functions' => 'functions.sublime-completions',
        ];

        if ('vscode' === $this->style) {
            $names = [
                'functions' => 'functions.json',
            ];
        }

        return $names[$type];
    }

    /**
     * Get functions JSON schema.
     *
     * @param  array  $functions List of functions.
     * @return array
     */
    protected function getFunctionsSchema(array $functions): array
    {
        if ('vscode' === $this->style) {
            $schema = [];

            foreach ($functions as $id => $value) {
                $schema[$id] = [
                    'prefix'      => $value['trigger'],
                    'body'        => $value['content'],
                    // 'description' => $value['contents'],
                ];
            }

            return $schema;
        }

        $schema = [
            'scope' => 'source.php - comment - constant.other.class - entity - meta.catch - ' .
                'meta.class - meta.function.arguments - meta.use - string - support.class - ' .
                'variable.other, source.php meta.class.php meta.block.php meta.function.php ' .
                'meta.block.php - comment - constant.other.class - entity - meta.catch - ' .
                'meta.function.arguments - meta.use - string - support.class - variable.other',
            'completions' => [],
        ];

        foreach ($functions as $value) {
            $schema['completions'][] = [
                'trigger'  => $value['trigger'],
                'contents' => $value['content'],
            ];
        }

        return $schema;
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
                        $current_file = "{$currentDir}{$this->ds}{$items[$i]}";

                        if (is_file($current_file)
                            && strpos($items[$i], '.php')
                            && ! in_array($items[$i], $this->getExcludedFiles(), true)) {
                            $path[] = "{$currentDir}{$this->ds}{$items[$i]}";
                        } elseif (is_dir($current_file)) {
                            $stack[] = $current_file;
                        }
                    }
                    $i++;
                }
            }
        }

        return $path;
    }

    /**
     * Get functions.
     *
     * @param  array $files List of files.
     * @return array
     */
    protected function getFunctions($files): array
    {
        $results   = [];
        $functions = [];

        foreach ($files as $file) {
            if (! is_file($file)) {
                continue;
            }

            $content          = file_get_contents($file);
            $tokens           = token_get_all($content);
            $inFunction       = false;
            $inClass          = false;
            $inFunctionParams = false;
            $parenthesisDepth = 0;
            $bracesDepth      = 0;
            $currentFunction  = '';
            $functionParams   = '';

            foreach ($tokens as $token) {
                if (is_array($token)) {
                    switch (token_name($token[0])) {
                        case 'T_CURLY_OPEN':
                            $bracesDepth++;
                            break;
                        case 'T_CLASS':
                        case 'T_ABSTRACT':
                        case 'T_INTERFACE':
                            $inClass = true;
                            $bracesDepth = 2;
                            break;
                        case 'T_FUNCTION':
                            $inFunction = true;
                            $got_function_name = false;
                            break;
                        case 'T_STRING':
                            if ($inFunction && ! $got_function_name) {
                                $currentFunction = $token[1];
                                $got_function_name = true;
                                $inFunctionParams = true;
                                continue 2;
                            }
                    }

                    if ($inFunctionParams && ! $inClass) {
                        $functionParams .= $token[1];
                    }
                } else {
                    if ($inFunctionParams) {
                        switch ($token) {
                            case '(':
                                $parenthesisDepth++;
                                continue 2;
                                break;
                            case ')':
                                if ($parenthesisDepth) {
                                    $parenthesisDepth--;
                                }
                                if ($parenthesisDepth) {
                                    continue 2;
                                }
                                $functions[]      = [$currentFunction, trim($functionParams), $inClass, $file];
                                $currentFunction  = '';
                                $functionParams   = '';
                                $inFunctionParams = false;
                                continue 2;
                                break;
                        }
                        $functionParams .= $token;
                    } else {
                        switch ($token) {
                            case '{':
                                $bracesDepth++;
                                continue 2;
                                break;
                            case '}':
                                $bracesDepth--;
                                if (! $bracesDepth) {
                                    $inClass = false;
                                }
                                continue 2;
                                break;
                        }
                    }
                }
            }

            $tokens = null;
        }

        foreach ($functions as $key => $function) {
            if ($function[2]) {
                continue;
            }
            $args = '';

            if (! empty($function[1])) {
                $index       = 1;
                $extraBraces = [];
                $funcArgs    = explode(',', trim(preg_replace('/(\'\,*.?\')|(\$)/', '', $function[1])));
                $countFuncs  = count($funcArgs);

                foreach ($funcArgs as $arg) {
                    $arg = explode('=', $arg);

                    if (1 < count($arg) && 1 < $countFuncs) {
                        $args .= '${' . ($index++) . ':';
                        if (2 < $index) {
                            $args .= ', ${' . ($index++) . ':' . trim($arg[0]) . '}';
                        } else {
                            $args .= '${' . ($index++) . ':' . trim($arg[0]) . '}';
                        }
                        $extraBraces[] = '}';
                    } else {
                        if (1 < $index) {
                            $args .= ', ';
                        }
                        $args .= '${' . ($index++) . ':' . trim($arg[0]) . '}';
                    }
                }

                foreach ($extraBraces as $brace) {
                    $args .= $brace;
                }
            }

            $results[$function[0]] = [
                'trigger' => $function[0],
                'content' => $args ? $function[0] . '( ' . $args . ' )' : $function[0] . '()',
            ];
        }

        return $results;
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
            mkdir($buildPath, 0755);
        }

        if ($functions = $this->getFunctions($files)) {
            $results['functions'] = count($functions);

            file_put_contents(
                $buildPath . $this->ds . $this->getStyleFileNames('functions'),
                json_encode($this->getFunctionsSchema($functions), JSON_PRETTY_PRINT)
            );
        }

        return $results;
    }
}
