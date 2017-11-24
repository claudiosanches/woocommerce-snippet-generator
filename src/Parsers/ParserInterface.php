<?php
/**
 * Parser interface
 */

namespace ClaudioSanches\WooCommerce\Snippets\Parsers;

/**
 * Parser interface class.
 */
interface ParserInterface
{
    /**
     * Get schema.
     *
     * @return array
     */
    public function getSchema();
}
