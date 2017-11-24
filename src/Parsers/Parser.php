<?php
/**
 * Abstract parser
 */

namespace ClaudioSanches\WooCommerce\Snippets\Parsers;

/**
 * Parser abstract class.
 */
abstract class Parser implements ParserInterface
{
    /**
     * List of files.
     *
     * @var array
     */
    protected $files = [];

    /**
     * Generator style.
     *
     * @var string
     */
    protected $style = '';

    /**
     * Initialize parser.
     *
     * @param array $files List of files.
     */
    public function __construct(array $files, string $style)
    {
        $this->files = $files;
        $this->style = $style;
    }

    /**
     * Get schema.
     *
     * @return array
     */
    public function getSchema(): array
    {
        $data = $this->getData();

        if ('vscode' === $this->style) {
            $schema = [];

            foreach ($data as $key => $value) {
                $schema[$key] = [
                    'prefix'      => $key,
                    'body'        => $value['content'],
                    'description' => $value['description'],
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

        foreach ($data as $key => $value) {
            $schema['completions'][] = [
                'trigger'  => $key,
                'contents' => $value['content'],
            ];
        }

        return $schema;
    }
}
