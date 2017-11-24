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
}
