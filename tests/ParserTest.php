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

use PHPUnit_Framework_TestCase;

class ParserTest extends PHPUnit_Framework_TestCase
{
    /** @var array */
    private $validCallable;

    /** @var string */
    private $invalidCallable;

    public function __construct()
    {
        $this->validCallable = array($this, 'getArguments');
        $this->invalidCallable = "\x00";
    }

    public function testGrammarStringRuleValid()
    {
        $grammar = array(
            '✓' => array(Parser::TYPE_STRING, 'text')
        );

        $this->verifyGrammar($grammar, '✓', 'text', 'text');
    }

    public function testGrammarStringRuleValidValue()
    {
        $grammar = array(
            '✓' => array(Parser::TYPE_STRING, 'text', true)
        );

        $this->verifyGrammar($grammar, '✓', 'text', true);
    }

    public function testGrammarStringRuleInvalidType()
    {
        $grammar = array(
            '✓' => array(Parser::TYPE_STRING, 'text'),
            '✗' => array(Parser::TYPE_STRING, false)
        );

        $this->verifyGrammar($grammar, '✓', 'text', null);
    }

    public function testGrammarStringRuleInvalidEmptyString()
    {
        $grammar = array(
            '✓' => array(Parser::TYPE_STRING, 'text'),
            '✗' => array(Parser::TYPE_STRING, '')
        );

        $this->verifyGrammar($grammar, '✓', 'text', null);
    }

    public function testGrammarStringRuleInvalidExcessArguments()
    {
        $grammar = array(
            '✓' => array(Parser::TYPE_STRING, 'text'),
            '✗' => array(Parser::TYPE_STRING, 'text', true, true)
        );

        $this->verifyGrammar($grammar, '✓', 'text', null);
    }

    public function testGrammarExpressionRuleValidMatch()
    {
        $grammar = array(
            '✓' => array(Parser::TYPE_RE, '-?[1-9][0-9]*')
        );

        $this->verifyGrammar($grammar, '✓', '42', '42');
    }

    public function testGrammarExpressionRuleValidNonMatch()
    {
        $grammar = array(
            '✓' => array(Parser::TYPE_RE, '-?[1-9][0-9]*')
        );

        $this->verifyGrammar($grammar, '✓', 'a + b', null);
    }

    public function testGrammarExpressionRuleValidCallable()
    {
        $grammar = array(
            '✓' => array(Parser::TYPE_RE, '-?[1-9][0-9]*', 'intval')
        );

        $this->verifyGrammar($grammar, '✓', '42', 42);
    }

    public function testGrammarExpressionRuleInvalidType()
    {
        $grammar = array(
            '✗' => array(Parser::TYPE_RE, false),
            '✓' => array(Parser::TYPE_RE, '-?[1-9][0-9]*')
        );

        $this->verifyGrammar($grammar, '✓', '42', null);
    }

    public function testGrammarExpressionRuleInvalidEmptyString()
    {
        $grammar = array(
            '✗' => array(Parser::TYPE_RE, ''),
            '✓' => array(Parser::TYPE_RE, '-?[1-9][0-9]*')
        );

        $this->verifyGrammar($grammar, '✓', '42', null);
    }

    public function testGrammarExpressionRuleInvalidCallable()
    {
        $grammar = array(
            '✗' => array(Parser::TYPE_RE, '-?[1-9][0-9]*', $this->invalidCallable),
            '✓' => array(Parser::TYPE_RE, '-?[1-9][0-9]*')
        );

        $this->verifyGrammar($grammar, '✓', '42', null);
    }

    public function testGrammarExpressionRuleInvalidArgumentsExcess()
    {
        $grammar = array(
            '✗' => array(Parser::TYPE_RE, '-?[1-9][0-9]*', 'intval', true),
            '✓' => array(Parser::TYPE_RE, '-?[1-9][0-9]*')
        );

        $this->verifyGrammar($grammar, '✓', '42', null);
    }

    public function testGrammarAndRuleValidMatch()
    {
        $grammar = array(
            '✓' => array(Parser::TYPE_AND, array('true', 'space', 'false')),
            'true' => array(Parser::TYPE_STRING, 'true', true),
            'space' => array(Parser::TYPE_RE, '\s+'),
            'false' => array(Parser::TYPE_STRING, 'false', false)
        );

        $this->verifyGrammar($grammar, '✓', 'true false', array(true, ' ', false));
    }

    public function testGrammarAndRuleValidNonMatch()
    {
        $grammar = array(
            '✓' => array(Parser::TYPE_AND, array('true', 'space', 'false')),
            'true' => array(Parser::TYPE_STRING, 'true', true),
            'space' => array(Parser::TYPE_RE, '\s+'),
            'false' => array(Parser::TYPE_STRING, 'false', false)
        );

        $this->verifyGrammar($grammar, '✓', 'false false', null);
    }

    public function testGrammarAndRuleValidCallable()
    {
        $grammar = array(
            '✓' => array(Parser::TYPE_AND, array('true', 'space', 'false'), $this->validCallable),
            'true' => array(Parser::TYPE_STRING, 'true', true),
            'space' => array(Parser::TYPE_RE, '\s+'),
            'false' => array(Parser::TYPE_STRING, 'false', false)
        );

        $this->verifyGrammar($grammar, '✓', 'true false', '[true," ",false]');
    }

    public function testGrammarAndRuleInvalidType()
    {
        $grammar = array(
            '✗' => array(Parser::TYPE_AND, false),
            '✓' => array(Parser::TYPE_AND, array('true', 'space', 'false')),
            'true' => array(Parser::TYPE_STRING, 'true', true),
            'space' => array(Parser::TYPE_RE, '\s+'),
            'false' => array(Parser::TYPE_STRING, 'false', false)
        );

        $this->verifyGrammar($grammar, '✓', 'true false', null);
    }

    public function testGrammarAndRuleInvalidInsufficientOptions()
    {
        $grammar = array(
            '✗' => array(Parser::TYPE_AND, array('true')),
            '✓' => array(Parser::TYPE_AND, array('true', 'space', 'false')),
            'true' => array(Parser::TYPE_STRING, 'true', true),
            'space' => array(Parser::TYPE_RE, '\s+'),
            'false' => array(Parser::TYPE_STRING, 'false', false)
        );

        $this->verifyGrammar($grammar, '✓', 'true false', null);
    }

    public function testGrammarAndRuleInvalidUnknownOption()
    {
        $grammar = array(
            '✗' => array(Parser::TYPE_AND, array('true', 'space', 'unknown')),
            '✓' => array(Parser::TYPE_AND, array('true', 'space', 'false')),
            'true' => array(Parser::TYPE_STRING, 'true', true),
            'space' => array(Parser::TYPE_RE, '\s+'),
            'false' => array(Parser::TYPE_STRING, 'false', false)
        );

        $this->verifyGrammar($grammar, '✓', 'true false', null);
    }

    public function testGrammarAndRuleInvalidCallable()
    {
        $grammar = array(
            '✗' => array(Parser::TYPE_AND, array('true', 'space', 'false'), $this->invalidCallable),
            '✓' => array(Parser::TYPE_AND, array('true', 'space', 'false')),
            'true' => array(Parser::TYPE_STRING, 'true', true),
            'space' => array(Parser::TYPE_RE, '\s+'),
            'false' => array(Parser::TYPE_STRING, 'false', false)
        );

        $this->verifyGrammar($grammar, '✓', 'true false', null);
    }

    public function testGrammarAndRuleInvalidArgumentsExcess()
    {
        $grammar = array(
            '✗' => array(Parser::TYPE_AND, array('true', 'space', 'false'), $this->validCallable, false),
            '✓' => array(Parser::TYPE_AND, array('true', 'space', 'false')),
            'true' => array(Parser::TYPE_STRING, 'true', true),
            'space' => array(Parser::TYPE_RE, '\s+'),
            'false' => array(Parser::TYPE_STRING, 'false', false)
        );

        $this->verifyGrammar($grammar, '✓', 'true false', null);
    }

    public function testGrammarOrRuleValidMatchA()
    {
        $grammar = array(
            '✓' => array(Parser::TYPE_OR, array('true', 'false')),
            'true' => array(Parser::TYPE_STRING, 'true', true),
            'false' => array(Parser::TYPE_STRING, 'false', false)
        );

        $this->verifyGrammar($grammar, '✓', 'true', true);
    }

    public function testGrammarOrRuleValidMatchB()
    {
        $grammar = array(
            '✓' => array(Parser::TYPE_OR, array('true', 'false')),
            'true' => array(Parser::TYPE_STRING, 'true', true),
            'false' => array(Parser::TYPE_STRING, 'false', false)
        );

        $this->verifyGrammar($grammar, '✓', 'false', false);
    }

    public function testGrammarOrRuleValidNonMatch()
    {
        $grammar = array(
            '✓' => array(Parser::TYPE_OR, array('true', 'false')),
            'true' => array(Parser::TYPE_STRING, 'true', true),
            'false' => array(Parser::TYPE_STRING, 'false', false)
        );

        $this->verifyGrammar($grammar, '✓', 'unknown', null);
    }

    public function testGrammarOrRuleValidCallable()
    {
        $grammar = array(
            '✓' => array(Parser::TYPE_OR, array('true', 'false'), $this->validCallable),
            'true' => array(Parser::TYPE_STRING, 'true', true),
            'false' => array(Parser::TYPE_STRING, 'false', false)
        );

        $this->verifyGrammar($grammar, '✓', 'true', '["true",true]');
    }

    public function testGrammarOrRuleInvalidType()
    {
        $grammar = array(
            '✗' => array(Parser::TYPE_OR, false),
            '✓' => array(Parser::TYPE_OR, array('true', 'false')),
            'true' => array(Parser::TYPE_STRING, 'true', true),
            'false' => array(Parser::TYPE_STRING, 'false', false)
        );

        $this->verifyGrammar($grammar, '✓', 'true', null);
    }

    public function testGrammarOrRuleInvalidInsufficientOptions()
    {
        $grammar = array(
            '✗' => array(Parser::TYPE_OR, array('true')),
            '✓' => array(Parser::TYPE_OR, array('true', 'false')),
            'true' => array(Parser::TYPE_STRING, 'true', true),
            'false' => array(Parser::TYPE_STRING, 'false', false)
        );

        $this->verifyGrammar($grammar, '✓', 'true', null);
    }

    public function testGrammarOrRuleInvalidUnknownOption()
    {
        $grammar = array(
            '✗' => array(Parser::TYPE_OR, array('true', 'unknown')),
            '✓' => array(Parser::TYPE_OR, array('true', 'false')),
            'true' => array(Parser::TYPE_STRING, 'true', true),
            'false' => array(Parser::TYPE_STRING, 'false', false)
        );

        $this->verifyGrammar($grammar, '✓', 'true', null);
    }

    public function testGrammarOrRuleInvalidCallable()
    {
        $grammar = array(
            '✗' => array(Parser::TYPE_OR, array('true', 'false'), $this->invalidCallable),
            '✓' => array(Parser::TYPE_OR, array('true', 'false')),
            'true' => array(Parser::TYPE_STRING, 'true', true),
            'false' => array(Parser::TYPE_STRING, 'false', false)
        );

        $this->verifyGrammar($grammar, '✓', 'true', null);
    }

    public function testGrammarOrRuleInvalidArgumentsExcess()
    {
        $grammar = array(
            '✗' => array(Parser::TYPE_OR, array('true', 'false'), $this->validCallable, false),
            '✓' => array(Parser::TYPE_OR, array('true', 'false')),
            'true' => array(Parser::TYPE_STRING, 'true', true),
            'false' => array(Parser::TYPE_STRING, 'false', false)
        );

        $this->verifyGrammar($grammar, '✓', 'true', null);
    }

    public function testGrammarUnknownRuleInvalid()
    {
        $grammar = array(
            '✗' => array(-1, 'unknown'),
            '✓' => array(Parser::TYPE_STRING, 'true')
        );

        $this->verifyGrammar($grammar, '✓', 'true', null);
    }

    public function testGrammarInvalidRule()
    {
        $grammar = array(
            '✗' => false,
            '✓' => array(Parser::TYPE_STRING, 'true')
        );

        $this->verifyGrammar($grammar, '✓', 'true', null);
    }

    public function testGrammarInvalidType()
    {
        $grammar = false;

        $this->verifyGrammar($grammar, '✓', 'true', null);
    }

    public function testGrammarInvalidMissing()
    {
        $grammar = null;

        $this->verifyGrammar($grammar, '✓', 'true', null);
    }

    public function testInputRuleUnknown()
    {
        $grammar = array(
            '✓' => array(Parser::TYPE_STRING, 'true')
        );

        $this->verifyGrammar($grammar, 'unknown', 'true', null);
    }

    public function testInputRuleInvalid()
    {
        $grammar = array(
            '✓' => array(Parser::TYPE_STRING, 'true')
        );

        $this->verifyGrammar($grammar, array(), 'true', null);
    }

    public function testInputTextInvalid()
    {
        $grammar = array(
            '✓' => array(Parser::TYPE_STRING, 'true')
        );

        $this->verifyGrammar($grammar, '✓', false, null);
    }

    public function testInputTextExcess()
    {
        $grammar = array(
            '✓' => array(Parser::TYPE_STRING, 'true')
        );

        $this->verifyGrammar($grammar, '✓', 'trueness', null);
    }

    private function verifyGrammar($grammar, $rule, $input, $expectedOutput)
    {
        $parser = new Parser($grammar);
        $actualOutput = $parser->parse($rule, $input);

        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function getArguments()
    {
        return json_encode(func_get_args());
    }
}
