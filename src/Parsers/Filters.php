<?php
/**
 * Filters parser
 */

namespace ClaudioSanches\WooCommerce\Snippets\Parsers;

/**
 * Filters parser
 */
class Filters extends Actions
{

    /**
     * Get Hooks.
     *
     * @return array
     */
    protected function getHooks(): array
    {
        $results = [];

        foreach ($this->files as $file) {
            if (! is_file($file)) {
                continue;
            }

            $content = file_get_contents($file);

            preg_match_all('/apply_filters\((\s+)?[\'|"](woocommerce_(.*)|wc_(.*))[\'|"]/', $content, $filters);

            foreach ($filters[2] as $filter) {
                $filterName = $this->getHookName($filter);

                $results[] = [
                    'trigger'     => $filterName,
                    'content'     => $this->getHookOutput($filterName),
                    'description' => 'Filter: ' . $filterName,
                ];
                $results[] = [
                    'trigger'     => 'af_' . $filterName,
                    'content'     => $this->getHookOutput($filterName, 'filter'),
                    'description' => 'Filter: ' . $filterName,
                ];
            }
        }

        return $results;
    }
}