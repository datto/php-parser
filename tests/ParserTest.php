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

use PHPUnit_Framework_TestCase;

class ParserTest extends PHPUnit_Framework_TestCase
{
    /** @var Parser */
    private $parser;

    public function __construct()
    {
        $grammar = array(
            'boolean' => [Parser::TYPE_OR, ['compound', 'unit']],
            'compound' => [
                Parser::TYPE_AND,
                ['unit', 'space', 'operator', 'space', 'unit'],
                [[2, 0], [0], [4]]
            ],
            'operator' => [Parser::TYPE_OR, ['and', 'or']],
            'and' => [Parser::TYPE_STRING, 'and', ['AND']],
            'or' => [Parser::TYPE_STRING, 'or', ['OR']],
            'unit' => [Parser::TYPE_OR, ['group', 'atom']],
            'group' => [Parser::TYPE_AND, ['(', 'boolean', ')'], 1],
            '(' => [Parser::TYPE_STRING, '('],
            ')' => [Parser::TYPE_STRING, ')'],
            'atom' => [Parser::TYPE_OR, ['variable', 'true', 'false']],
            'true' => [Parser::TYPE_STRING, 'true', [true]],
            'false' => [Parser::TYPE_AND, ['f', 'alse'], [false]],
            'f' => [Parser::TYPE_STRING, 'f'],
            'alse' => [Parser::TYPE_STRING, 'alse'],
            'variable' => [Parser::TYPE_RE, ':([a-z]+)', ['VAR', [1]]],
            'space' => [Parser::TYPE_RE, '\s+']
        );

        $this->parser = new Parser($grammar);
    }

    public function testInvalidPattern()
    {
        $this->verify('', 'false', null);
    }

    public function testStringOverMatch()
    {
        $this->verify('and', 'and :x', null);
    }

    public function testStringExactMatch()
    {
        $this->verify('and', 'and', ['AND']);
    }

    public function testStringUnderMatch()
    {
        $this->verify('and', 'a', null);
    }

    public function testStringNonMatch()
    {
        $this->verify('and', 'or', null);
    }

    public function testRegularExpressionOverMatch()
    {
        $this->verify('variable', ':rain?', null);
    }

    public function testRegularExpressionExactMatch()
    {
        $this->verify('variable', ':rain', ['VAR', 'rain']);
    }

    public function testRegularExpressionUnderMatch()
    {
        $this->verify('variable', ':', null);
    }

    public function testRegularExpressionNonMatch()
    {
        $this->verify('variable', 'true', null);
    }

    public function testDisjunctionMatchA()
    {
        $this->verify('atom', ':variable', ['VAR', 'variable']);
    }

    public function testDisjunctionMatchB()
    {
        $this->verify('atom', 'true', [true]);
    }

    public function testDisjunctionMatchC()
    {
        $this->verify('atom', 'false', [false]);
    }

    public function testDisjunctionNonMatch()
    {
        $this->verify('atom', 'or', null);
    }

    public function testConjunctionOverMatch()
    {
        $this->verify('false', 'false and true', null);
    }

    public function testConjunctionExactMatch()
    {
        $this->verify('false', 'false', [false]);
    }

    public function testConjunctionUnderMatch()
    {
        $this->verify('false', 'f', null);
    }

    public function testConjunctionNonMatch()
    {
        $this->verify('false', 'true', null);
    }

    public function testGrammarMatchA()
    {
        $this->verify('boolean', 'false or (true and false)',
            ['OR', [false], ['AND', [true], [false]]]);
    }

    public function testGrammarMatchB()
    {
        $this->verify('boolean', '(false or true) and :x',
            ['AND', ['OR', [false], [true]], ['VAR', 'x']]);
    }

    public function testGrammarNonMatch()
    {
        $this->verify('boolean', '(false or true) and', null);
    }

    public function testGrammarMissingGrammar()
    {
        $grammar = null;

        $this->verifyGrammar($grammar, 'boolean', 'false', null);
    }

    public function testGrammarMissingDefinition()
    {
        $grammar = array(
            'variable' => null
        );

        $this->verifyGrammar($grammar, 'variable', 'x', null);
    }

    public function testGrammarInvalidRuleType()
    {
        $grammar = array(
            'variable' => [-1, ':([a-z]+)', ['VAR', [1]]]
        );

        $this->verifyGrammar($grammar, 'variable', 'x', null);
    }

    public function testGrammarEmptyStringLiteral()
    {
        $grammar = array(
            'false' => [Parser::TYPE_STRING, '', [false]]
        );

        $this->verifyGrammar($grammar, 'false', '', null);
    }

    public function testGrammarEmptyRegularExpression()
    {
        $grammar = array(
            'variable' => [Parser::TYPE_RE, '', ['VAR', [0]]]
        );

        $this->verifyGrammar($grammar, 'variable', 'x', null);
    }

    public function testGrammarEmptyConjunction()
    {
        $grammar = array(
            'compound' => [Parser::TYPE_AND, [], null]
        );

        $this->verifyGrammar($grammar, 'compound', 'true or false', null);
    }

    public function testGrammarEmptyDisjunction()
    {
        $grammar = array(
            'options' => [Parser::TYPE_OR, [], null]
        );

        $this->verifyGrammar($grammar, 'options', 'a', null);
    }

    private function verify($pattern, $input, $expectedOutput)
    {
        $actualOutput = $this->parser->parse($pattern, $input);

        $this->assertSame($expectedOutput, $actualOutput);
    }

    private function verifyGrammar($grammar, $pattern, $input, $expectedOutput)
    {
        $parser = new Parser($grammar);
        $actualOutput = $parser->parse($pattern, $input);

        $this->assertSame($expectedOutput, $actualOutput);
    }
}
