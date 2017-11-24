<?php
/**
 * Functions parser
 */

namespace ClaudioSanches\WooCommerce\Snippets\Parsers;

/**
 * Functions parser
 */
class Functions extends Parser
{

    /**
     * Get data for schema.
     *
     * @return array
     */
    public function getData(): array
    {
        $results   = [];
        $functions = [];

        foreach ($this->files as $file) {
            if (! is_file($file)) {
                continue;
            }

            $tokens           = token_get_all(file_get_contents($file));
            $inFunction       = false;
            $inClass          = false;
            $inFunctionParams = false;
            $functionDoc      = '';
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
                            $inClass     = true;
                            $bracesDepth = 2;
                            break;
                        case 'T_DOC_COMMENT':
                            $functionDoc = $token[1];
                            break;
                        case 'T_FUNCTION':
                            $inFunction = true;
                            $gotFunctionName = false;
                            break;
                        case 'T_STRING':
                            if ($inFunction && ! $gotFunctionName) {
                                $currentFunction   = $token[1];
                                $gotFunctionName = true;
                                $inFunctionParams  = true;
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
                                $functions[]      = [
                                    $currentFunction,
                                    trim($functionParams),
                                    $inClass,
                                    $file,
                                    $functionDoc
                                ];
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
            $args        = '';
            $description = [];

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

            preg_match('/\/\*\*\n[\s\r\t]+\*\s(.*)\n/', $function[4], $description);

            $results[$function[0]] = [
                'content'     => $args ? $function[0] . '( ' . $args . ' )' : $function[0] . '()',
                'description' => ! empty($description[1]) ? $description[1] : 'Function: ' . $function[0] . '()',
            ];
        }

        return $results;
    }
}
