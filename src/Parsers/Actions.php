<?php
/**
 * Actions parser
 */

namespace ClaudioSanches\WooCommerce\Snippets\Parsers;

/**
 * Actions parser
 */
class Actions extends Parser
{

    /**
     * Get hook name.
     *
     * @param  string $hook Hook.
     * @return string
     */
    public function getHookName(string $hook): string
    {
        // Get hook name.
        $hook = trim(current(explode(',', $hook)));

        // Handle [], this-> and ->.
        $hook = str_replace(['this->', '\']', '"]'], '', $hook);
        $hook = str_replace(['->', '[\'', '["'], '_', $hook);

        // Replace variables from $variable to <variable>.
        $hook = preg_replace('/[{]?\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)[\}]?/', '<$1>', $hook);

        // Clean up.
        $hook = preg_replace('/[\s+|\'|\.|\"]/s', '', $hook);

        return $hook;
    }

    /**
     * Get hook output.
     *
     * @param  string $hook Hook name.
     * @param  string $type Type of hook.
     * @return string
     */
    protected function getHookOutput(string $hook, string $type = ''): string
    {
        $index  = 1;
        $args   = explode('<', $hook);
        $output = '';

        foreach ($args as $n => $arg) {
            if ('>' === substr($arg, -1)) {
                $output .= '${' . ($index++) . ':' . rtrim(trim($arg), '>') . '}';
            } else {
                $output .= trim($arg);
            }
        }

        if ('' !== $type) {
            $callback = '${' . ($index++) . ':callback}';

            return 'add_' . $type . '( \'' . $output . '\', ${' . ($index++) . ':\''
            . $callback . '\'}${' . ($index++) . '} );';
        }

        return $output;
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

            preg_match_all('/do_action\((\s+)?[\'|"](woocommerce_(.*)|wc_(.*))[\'|"]/', $content, $actions);
            preg_match_all('/apply_filters\((\s+)?[\'|"](woocommerce_(.*)|wc_(.*))[\'|"]/', $content, $filters);

            foreach ($actions[2] as $action) {
                $actionName = $this->getHookName($action);

                $results[$actionName] = [
                    'content'     => $this->getHookOutput($actionName),
                    'description' => 'Action: ' . $actionName,
                ];
                $results['aa_' . $actionName] = [
                    'content'     => $this->getHookOutput($actionName, 'action'),
                    'description' => 'Action snippet: ' . $actionName,
                ];
            }
        }

        return $results;
    }
}
