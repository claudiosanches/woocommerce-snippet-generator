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
     * Get schema.
     *
     * @return array
     */
    public function getSchema(): array
    {
        $hooks = $this->getHooks();

        if ('vscode' === $this->style) {
            $schema = [];

            foreach ($hooks as $value) {
                $schema[$value['trigger']] = [
                    'prefix'      => $value['trigger'],
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

        foreach ($hooks as $value) {
            $schema['completions'][] = [
                'trigger'  => $value['trigger'],
                'contents' => $value['content'],
            ];
        }

        return $schema;
    }

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

            preg_match_all('/do_action\((\s+)?[\'|"](woocommerce_(.*)|wc_(.*))[\'|"]/', $content, $actions);
            preg_match_all('/apply_filters\((\s+)?[\'|"](woocommerce_(.*)|wc_(.*))[\'|"]/', $content, $filters);

            foreach ($actions[2] as $action) {
                $actionName = $this->getHookName($action);

                $results[] = [
                    'trigger'     => $actionName,
                    'content'     => $this->getHookOutput($actionName),
                    'description' => 'Action: ' . $actionName,
                ];
                $results[] = [
                    'trigger'     => 'aa_' . $actionName,
                    'content'     => $this->getHookOutput($actionName, 'action'),
                    'description' => 'Action: ' . $actionName,
                ];
            }
        }

        return $results;
    }
}
