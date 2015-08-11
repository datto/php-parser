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
    const TYPE_METHOD = 3;
    const TYPE_MANY = 4;
    const TYPE_OR = 5;
    const TYPE_AND = 6;

    /** @var array */
    private $rules;

    /** @var string */
    protected $input;

    /** @var int */
    protected $i;

    /**
     * @param array $rules
     */
    public function __construct($rules)
    {
        if ($this->isValidRules($rules)) {
            $this->rules = $rules;
        }
    }

    /**
     * @param string $rule
     * @param string $input
     * @return mixed
     */
    public function evaluate($rule, $input)
    {
        // Invalid rules
        if (is_null($this->rules)) {
            return null;
        }

        // Invalid arguments
        if (!self::isValidRuleName($rule) || !self::isValidInput($input)) {
            return null;
        }

        // Unknown rule
        if (!isset($this->rules[$rule])) {
            return null;
        }

        $this->input = $input;
        $this->i = 0;

        // Not a match
        if (!$this->applyRule($rule, $output)) {
            return null;
        }

        // Incomplete match
        if ($this->i !== strlen($input)) {
            return null;
        }

        return $output;
    }

    private function applyRule($rule, &$output)
    {
        $definition = $this->rules[$rule];

        $type = array_shift($definition);

        switch ($type) {
            default: # self::STRING:
                return $this->getString($definition, $output);

            case self::TYPE_RE:
                @list($pattern, $method) = $definition;
                return $this->getExpression($pattern, $method, $output);

            case self::TYPE_METHOD:
                @list($method) = $definition;
                return $this->getMethod($method, $output);

            case self::TYPE_AND:
                @list($rules, $method) = $definition;
                return $this->getAnd($rules, $method, $output);

            case self::TYPE_OR:
                @list($rules, $method) = $definition;
                return $this->getOr($rules, $method, $output);

            case self::TYPE_MANY:
                @list($rule, $min, $max, $method) = $definition;
                return $this->getMany($rule, $min, $max, $method, $output);
        }
    }

    private function getString($definition, &$output)
    {
        @list($needle, $value) = $definition;

        $length = strlen($needle);
        $i = &$this->i;

        if (@substr_compare($this->input, $needle, $i, $length) !== 0) {
            return false;
        }

        if (array_key_exists(1, $definition)) {
            $output = $value;
        } else {
            $output = $needle;
        }

        $this->i += $length;
        return true;
    }

    private function getExpression($pattern, $method, &$output)
    {
        $subject = substr($this->input, $this->i);

        $pregPattern = self::getPregPattern($pattern);

        if (preg_match($pregPattern, $subject, $matches) !== 1) {
            return false;
        }

        if (is_int($method)) {
            $output = @$matches[$method];

            if (!is_string($output)) {
                return false;
            }
        } elseif (is_string($method)) {
            $output = $this->callUserMethod($method, $matches);
        } else {
            $output = $matches[0];
        }

        $this->i += strlen($matches[0]);
        return true;
    }

    private static function getPregPattern($pattern)
    {
        $delimiter = "\x03";

        return "{$delimiter}^{$pattern}{$delimiter}";
    }

    private function getMethod($method, &$output)
    {
        return $this->callUserMethod($method, array(&$output)) === true;
    }

    private function getAnd($rules, $method, &$output)
    {
        $i = $this->i;

        $output = array();

        foreach ($rules as $rule) {
            if (!$this->applyRule($rule, $output[])) {
                $this->i = $i;
                return false;
            }
        }

        if (is_string($method)) {
            $output = $this->callUserMethod($method, $output);
        } elseif (is_int($method)) {
            $output = $output[$method];
        }

        return true;
    }

    private function getOr($rules, $method, &$output)
    {
        foreach ($rules as $rule) {
            if (!$this->applyRule($rule, $output)) {
                continue;
            }

            if (is_string($method)) {
                $output = $this->callUserMethod($method, array($output, $rule));
            }

            return true;
        }

        return false;
    }

    private function getMany($rule, $min, $max, $method, &$output)
    {
        $i = $this->i;

        $output = array();

        while ($this->applyRule($rule, $output[]));

        array_pop($output);
        $n = count($output);

        if (($min !== null) && ($n < $min)) {
            $this->i = $i;
            return false;
        }

        if (($max !== null) && ($max < $n)) {
            $this->i = $i;
            return false;
        }

        if (is_string($method)) {
            $output = $this->callUserMethod($method, array($output));
        }

        return true;
    }

    private function callUserMethod($method, $input)
    {
        $callable = array($this, $method);

        return @call_user_func_array($callable, $input);
    }

    private function isValidRules($rules)
    {
        if (!is_array($rules) || (count($rules) === 0)) {
            return false;
        }

        foreach ($rules as $name => $definition) {
            if (!self::isValidRuleName($name)) {
                return false;
            }

            if (!$this->isValidRuleDefinition($definition, $rules)) {
                return false;
            }
        }

        return true;
    }

    private static function isValidRuleName($name)
    {
        return is_string($name) && (0 < strlen($name));
    }

    private function isValidRuleDefinition($definition, $rules)
    {
        if (!is_array($definition)) {
            return false;
        }

        return $this->isValidStringRule($definition)
            || $this->isValidExpressionRule($definition)
            || $this->isValidMethodRule($definition)
            || $this->isValidAndRule($definition, $rules)
            || $this->isValidOrRule($definition, $rules)
            || $this->isValidManyRule($definition);
    }

    private function isValidStringRule($definition)
    {
        @list($type, $needle) = $definition;

        return ($type === self::TYPE_STRING)
            && self::isValidStringRuleNeedle($needle);
    }

    private static function isValidStringRuleNeedle($needle)
    {
        return is_string($needle) && (0 < strlen($needle));
    }

    private function isValidMethod($method)
    {
        $callable = array($this, $method);

        return is_callable($callable);
    }

    private function isValidExpressionRule($definition)
    {
        @list($type, $pattern, $method) = $definition;

        return ($type === self::TYPE_RE)
            && self::isValidRegularExpression($pattern)
            && (is_null($method) || self::isValidIndex($method) || $this->isValidMethod($method));
    }

    private static function isValidRegularExpression($pattern)
    {
        return is_string($pattern)
            && (0 < strlen($pattern))
            && (@preg_match(self::getPregPattern($pattern), null) !== false);
    }

    private static function isValidIndex($i)
    {
        return is_int($i) && (0 <= $i);
    }

    private function isValidMethodRule($definition)
    {
        @list($type, $method) = $definition;

        return ($type === self::TYPE_METHOD)
            && $this->isValidMethod($method);
    }

    private function isValidAndRule($definition, $rules)
    {
        @list($type, $ruleList, $method) = $definition;

        return ($type === self::TYPE_AND)
            && self::isValidRulesList($rules, $ruleList)
            && $this->isValidAndRuleMethod($method, $ruleList);
    }

    private function isValidAndRuleMethod($method, $rules)
    {
        if (is_null($method)) {
            return true;
        }

        if (is_int($method)) {
            return (0 <= $method) && ($method < count($rules));
        }

        if (is_string($method)) {
            return $this->isValidMethod($method);
        }

        return false;
    }

    private static function isValidRulesList($rules, $input)
    {
        if (!is_array($input) || (count($input) === 0)) {
            return false;
        }

        $unknownRules = array_diff($input, array_keys($rules));

        if (0 < count($unknownRules)) {
            return false;
        }

        foreach ($input as $value) {
            if (!self::isValidRuleName($value)) {
                return false;
            }
        }

        return true;
    }

    private function isValidOrRule($definition, $rules)
    {
        @list($type, $ruleList, $method) = $definition;

        return ($type === self::TYPE_OR)
            && self::isValidRulesList($rules, $ruleList)
            && (is_null($method) || $this->isValidMethod($method));
    }

    private function isValidManyRule($definition)
    {
        @list($type, $rule, $min, $max, $method) = $definition;

        return ($type === self::TYPE_MANY)
            && self::isValidRuleName($rule)
            && (is_null($min) || (is_int($min) && (0 <= $min)))
            && (is_null($max) || (is_int($max) && ($min <= $max)))
            && (is_null($method) || $this->isValidMethod($method));
    }

    private static function isValidInput($input)
    {
        return is_string($input);
    }
}
