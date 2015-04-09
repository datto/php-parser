<?php

/**
 * Copyright (C) 2015 Datto, Inc.
 *
 * This file is part of PHP JSON-RPC.
 *
 * PHP JSON-RPC is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * PHP JSON-RPC is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with PHP JSON-RPC. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <smortensen@datto.com>
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL-3.0
 * @copyright 2015 Datto, Inc.
 */

namespace Datto;

class Parser
{
    const TYPE_STRING = 1;
    const TYPE_RE = 2;
    const TYPE_AND = 3;
    const TYPE_OR = 4;

    /** @var array */
    private $grammar;

    public function __construct($grammar)
    {
        $this->grammar = $grammar;
    }

    public function parse($pattern, $input)
    {
        if (!is_array($this->grammar) || !is_string($input)) {
            return null;
        }

        $result = $this->evaluate($input, $pattern);

        if ($input !== false) {
            return null;
        }

        return $result;
    }

    private function evaluate(&$text, $name)
    {
        if (!self::isValidName($name)) {
            return null;
        }

        $definition = @$this->grammar[$name];

        if (!is_array($definition)) {
            return null;
        }

        @list($type, $pattern, $format) = $definition;

        switch ($type) {
            case self::TYPE_STRING:
                return $this->evaluateString($text, $pattern, $format);

            case self::TYPE_RE:
                return $this->evaluateRegularExpression($text, $pattern, $format);

            case self::TYPE_AND:
                return $this->evaluateAnd($text, $pattern, $format);

            case self::TYPE_OR:
                return $this->evaluateOr($text, $pattern);
        }

        return null;
    }

    private function evaluateString(&$text, $literal, $format)
    {
        if (!self::isNonEmptyString($literal)) {
            return null;
        }

        $length = strlen($literal);

        if (strncmp($text, $literal, $length) !== 0) {
            return null;
        }

        $text = substr($text, $length);

        return self::format(array($literal), $format);
    }

    private function evaluateRegularExpression(&$text, $body, $format)
    {
        if (!self::isNonEmptyString($body)) {
            return null;
        }

        $pattern = '~^' . $body . '~';

        if (preg_match($pattern, $text, $input) !== 1) {
            return null;
        }

        $length = strlen($input[0]);
        $text = substr($text, $length);

        return self::format($input, $format);
    }

    private function evaluateAnd(&$text, $patterns, $format)
    {
        if (!self::isNonEmptyArray($patterns)) {
            return null;
        }

        $input = array();

        $originalText = $text;

        foreach ($patterns as $pattern) {
            $value = $this->evaluate($text, $pattern);

            if ($value === null) {
                $text = $originalText;
                return null;
            }

            $input[] = $value;
        }

        return self::format($input, $format);
    }

    private function evaluateOr(&$text, $patterns)
    {
        if (!self::isNonEmptyArray($patterns)) {
            return null;
        }

        $originalText = $text;

        foreach ($patterns as $pattern) {
            $value = $this->evaluate($text, $pattern);

            if ($value !== null) {
                return $value;
            }

            $text = $originalText;
        }

        return null;
    }

    private static function format($input, $format)
    {
        if (is_int($format)) {
            return @$input[$format];
        }

        if (is_array($format)) {
            $output = array();

            foreach ($format as $entry) {
                if (is_array($entry)) {
                    $value = self::lookup($input, $entry);
                } else {
                    $value = $entry;
                }

                $output[] = $value;
            }

            return $output;
        }

        return true;
    }

    private static function lookUp($value, $path)
    {
        foreach ($path as $i) {
            $value = @$value[$i];
        }

        return $value;
    }

    private static function isValidName($input)
    {
        return self::isNonEmptyString($input);
    }

    private static function isNonEmptyArray($input)
    {
        return is_array($input) && (0 < count($input));
    }

    private static function isNonEmptyString($input)
    {
        return is_string($input) && (0 < strlen($input));
    }
}
