<?php

/**
 * Copyright (C) 2015 Datto, Inc.
 *
 * This file is part of PHP Parser.
 *
 * PHP Parser is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * PHP Parser is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with PHP Parser. If not, see <http://www.gnu.org/licenses/>.
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
        if (self::isValidGrammar($grammar)) {
            $this->grammar = $grammar;
        }
    }

    public function parse($rule, $input)
    {
        if (!isset($this->grammar)) {
            return null;
        }

        if (!self::isValidRuleName($this->grammar, $rule) || !self::isValidInput($input)) {
            return null;
        }

        $this->evaluate($rule, $input, $output);

        if ($input !== false) {
            return null;
        }

        return $output;
    }

    private function evaluate($name, &$input, &$output)
    {
        $rule = @$this->grammar[$name];
        $type = array_shift($rule);

        switch ($type) {
            default: # self::TYPE_STRING
                return $this->evaluateString($rule, $input, $output);

            case self::TYPE_RE:
                return $this->evaluateExpression($rule, $input, $output);

            case self::TYPE_AND:
                return $this->evaluateAnd($rule, $input, $output);

            case self::TYPE_OR:
                return $this->evaluateOr($rule, $input, $output);
        }
    }

    private function evaluateString($rule, &$input, &$output)
    {
        $pattern = array_shift($rule);
        $length = strlen($pattern);

        if (strncmp($input, $pattern, $length) !== 0) {
            return false;
        }

        $input = substr($input, $length);

        if (count($rule) === 1) {
            $value = array_shift($rule);
            $output = $value;
        } else {
            $output = $pattern;
        }

        return true;
    }

    private function evaluateExpression($rule, &$input, &$output)
    {
        $innerPattern = array_shift($rule);

        $delimiter = "\x03";
        $outerPattern = "{$delimiter}^{$innerPattern}{$delimiter}";

        if (preg_match($outerPattern, $input, $matches) !== 1) {
            return false;
        }

        $length = strlen($matches[0]);
        $input = substr($input, $length);

        if (count($rule) === 1) {
            $callable = array_shift($rule);
            $output = @call_user_func_array($callable, $matches);
        } else {
            $output = $matches[0];
        }

        return true;
    }

    private function evaluateAnd($rule, &$input, &$output)
    {
        $values = array();

        $originalText = $input;

        $patterns = array_shift($rule);

        foreach ($patterns as $pattern) {
            if (!$this->evaluate($pattern, $input, $value)) {
                $input = $originalText;
                return false;
            }

            $values[] = $value;
        }

        if (count($rule) === 1) {
            $callable = array_shift($rule);
            $output = @call_user_func_array($callable, $values);
        } else {
            $output = $values;
        }

        return true;
    }

    private function evaluateOr($rule, &$input, &$output)
    {
        $originalText = $input;

        $patterns = array_shift($rule);

        foreach ($patterns as $pattern) {
            if ($this->evaluate($pattern, $input, $value)) {
                if (count($rule) === 1) {
                    $callable = array_shift($rule);
                    $output = @call_user_func($callable, $pattern, $value);
                } else {
                    $output = $value;
                }

                return true;
            }

            $input = $originalText;
        }

        return false;
    }

    private static function isValidGrammar($input)
    {
        if (!is_array($input) || (count($input) === 0)) {
            return false;
        }

        foreach ($input as $definition)
        {
            if (!self::isValidRuleDefinition($input, $definition)) {
                return false;
            }
        }

        return true;
    }

    private static function isValidRuleDefinition($grammar, $input)
    {
        if (!is_array($input)) {
            return false;
        }

        $type = array_shift($input);

        switch ($type)
        {
            case self::TYPE_STRING:
                return self::isValidStringDefinition($input);

            case self::TYPE_RE:
                return self::isValidExpressionDefinition($input);

            case self::TYPE_AND:
                return self::isValidAndDefinition($grammar, $input);

            case self::TYPE_OR:
                return self::isValidOrDefinition($grammar, $input);
        }

        return false;
    }

    private static function isValidStringDefinition($input)
    {
        $pattern = array_shift($input);

        return is_string($pattern) && (0 < strlen($pattern)) && (count($input) < 2);
    }

    private static function isValidExpressionDefinition($input)
    {
        $pattern = array_shift($input);

        if (!is_string($pattern) || (strlen($pattern) === 0)) {
            return false;
        }

        if (count($input) === 0) {
            return true;
        }

        $callable = array_shift($input);

        if (!is_callable($callable)) {
            return false;
        }

        if (count($input) !== 0) {
            return false;
        }

        return true;
    }

    private static function isValidAndDefinition($grammar, $input)
    {
        $rules = array_shift($input);

        if (!self::isValidRules($grammar, $rules) || (count($rules) < 2)) {
            return false;
        }

        if (count($input) === 0) {
            return true;
        }

        $callable = array_shift($input);

        if (!is_callable($callable)) {
            return false;
        }

        if (count($input) !== 0) {
            return false;
        }

        return true;
    }

    private static function isValidOrDefinition($grammar, $input)
    {
        $rules = array_shift($input);

        if (!self::isValidRules($grammar, $rules) || (count($rules) < 2)) {
            return false;
        }

        if (count($input) === 0) {
            return true;
        }

        $callable = array_shift($input);

        if (!is_callable($callable)) {
            return false;
        }

        if (count($input) !== 0) {
            return false;
        }

        return true;
    }

    private static function isValidRules($grammar, $input)
    {
        if (!is_array($input)) {
            return false;
        }

        foreach ($input as $rule) {
            if (!self::isValidRuleName($grammar, $rule)) {
                return false;
            }
        }

        return true;
    }

    private static function isValidRuleName($grammar, $input)
    {
        return @isset($grammar[$input]);
    }

    private static function isValidInput($input)
    {
        return is_string($input);
    }
}
