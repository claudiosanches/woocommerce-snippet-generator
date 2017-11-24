<?php
/**
 * Constants parser
 */

namespace ClaudioSanches\WooCommerce\Snippets\Parsers;

/**
 * Constants parser
 */
class Constants extends Parser
{
    /**
     * Get constant name.
     *
     * @param  string $constant Hook.
     * @return string
     */
    public function getConstantName(string $constant): string
    {
        return rtrim(trim(current(explode(',', $constant))), '\'\"');
    }

    /**
     * Get data for schema.
     *
     * @return array
     */
    public function getData(): array
    {
        $results = [];

        foreach ($this->files as $file) {
            if (! is_file($file)) {
                continue;
            }

            $content = file_get_contents($file);

            preg_match_all(
                '/(define|wc_maybe_define_constant)\((\s+)?[\'|"](WOOCOMMERCE_(.*)|WC_(.*))[\'|"]/',
                $content,
                $constants
            );

            foreach ($constants[3] as $constant) {
                $constantName = $this->getConstantName($constant);

                $results[$constantName] = [
                    'trigger'     => $constantName,
                    'content'     => $constantName,
                    'description' => 'Constant: ' . $constantName,
                ];
            }
        }

        return $results;
    }
}
