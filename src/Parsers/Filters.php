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

            preg_match_all('/apply_filters\((\s+)?[\'|"](woocommerce_(.*)|wc_(.*))[\'|"]/', $content, $filters);

            foreach ($filters[2] as $filter) {
                $filterName = $this->getHookName($filter);

                $results[$filterName] = [
                    'trigger'     => $filterName,
                    'content'     => $this->getHookOutput($filterName),
                    'description' => 'Filter: ' . $filterName,
                ];
                $results['af_' . $filterName] = [
                    'trigger'     => 'af_' . $filterName,
                    'content'     => $this->getHookOutput($filterName, 'filter'),
                    'description' => 'Filter: ' . $filterName,
                ];
            }
        }

        return $results;
    }
}
