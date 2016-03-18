<?php

namespace Jade\Lexer;

/**
 * Class Jade\Lexer\Attributes.
 */
class Attributes extends InputHandler
{
    protected $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * @return object
     */
    public function parseWith($str)
    {
        $token = $this->token;

        $key = '';
        $val = '';
        $quote = '';
        $states = array('key');
        $escapedAttribute = '';
        $previousChar = '';
        $previousNonBlankChar = '';

        $state = function () use (&$states) {
            return $states[count($states) - 1];
        };

        $interpolate = function ($attr) use (&$quote) {
            return str_replace('\\#{', '#{', preg_replace('/(?<!\\\\)#{([^}]+)}/', $quote . ' . $1 . ' . $quote, $attr));
        };

        $parse = function ($char, $nextChar = '') use (&$key, &$val, &$quote, &$states, &$token, &$escapedAttribute, &$previousChar, &$previousNonBlankChar, $state, $interpolate) {
            switch ($char) {
                case ',':
                case "\n":
                case "\t":
                case ' ':
                    switch ($state()) {
                        case 'expr':
                        case 'array':
                        case 'string':
                        case 'object':
                            $val .= $char;
                            break;

                        default:
                            if (
                                ($char === ' ' || $char === "\t") &&
                                (
                                    !preg_match('/^[a-zA-Z0-9_\\x7f-\\xff"\'\\]\\)\\}]$/', $previousNonBlankChar) ||
                                    !preg_match('/^[a-zA-Z0-9_]$/', $nextChar)
                                )
                            ) {
                                $val .= $char;
                                break;
                            }
                            array_push($states, 'key');
                            $val = trim($val);
                            $key = trim($key);

                            if (empty($key)) {
                                return;
                            }

                            $key = preg_replace(
                                array('/^[\'\"]|[\'\"]$/', '/\!/'), '', $key
                            );
                            $token->escaped[$key] = $escapedAttribute;
                            $token->attributes[$key] = ('' == $val) ? true : $interpolate($val);

                            $key = '';
                            $val = '';
                    }
                    break;

                case '=':
                    switch ($state()) {
                        case 'key char':
                            $key .= $char;
                            break;

                        case 'val':
                        case 'expr':
                        case 'array':
                        case 'string':
                        case 'object':
                            $val .= $char;
                            break;

                        default:
                            $escapedAttribute = '!' != $previousChar;
                            array_push($states, 'val');
                    }
                    break;

                case '(':
                    if ($state() == 'val' || $state() == 'expr') {
                        array_push($states, 'expr');
                    }
                    $val .= $char;
                    break;

                case ')':
                    if ($state() == 'val' || $state() == 'expr') {
                        array_pop($states);
                    }
                    $val .= $char;
                    break;

                case '{':
                    if ($state() == 'val') {
                        array_push($states, 'object');
                    }
                    $val .= $char;
                    break;

                case '}':
                    if ($state() == 'object') {
                        array_pop($states);
                    }
                    $val .= $char;
                    break;

                case '[':
                    if ($state() == 'val') {
                        array_push($states, 'array');
                    }
                    $val .= $char;
                    break;

                case ']':
                    if ($state() == 'array') {
                        array_pop($states);
                    }
                    $val .= $char;
                    break;

                case '"':
                case "'":
                    switch ($state()) {
                        case 'key':
                            array_push($states, 'key char');
                            break;

                        case 'key char':
                            array_pop($states);
                            break;

                        case 'string':
                            if ($char == $quote) {
                                array_pop($states);
                            }
                            $val .= $char;
                            break;

                        default:
                            array_push($states, 'string');
                            $val .= $char;
                            $quote = $char;
                            break;
                    }
                    break;

                case '':
                    break;

                default:
                    switch ($state()) {
                        case 'key':
                        case 'key char':
                            $key .= $char;
                            break;

                        default:
                            $val .= $char;
                            break;
                    }
            }
            $previousChar = $char;
            if (trim($char) !== '') {
                $previousNonBlankChar = $char;
            }
        };

        for ($i = 0; $i < mb_strlen($str); $i++) {
            $parse(mb_substr($str, $i, 1), mb_substr($str, $i + 1, 1));
        }

        $parse(',');
    }
}