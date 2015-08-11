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

namespace Datto\Tests;

use PHPUnit_Framework_TestCase;

class ParserTest extends PHPUnit_Framework_TestCase
{
    public function testRuleStringMatchYesValueNo()
    {
        $rules = array(
            'null' => array(Parser::TYPE_STRING, 'n/a')
        );

        $this->check($rules, 'null', 'n/a', 'n/a');
    }

    public function testRuleStringMatchYesValueNull()
    {
        $rules = array(
            'null' => array(Parser::TYPE_STRING, 'n/a', null)
        );

        $this->check($rules, 'null', 'n/a', null);
    }

    public function testRuleStringMatchYesValueTrue()
    {
        $rules = array(
            'true' => array(Parser::TYPE_STRING, 'y', true)
        );

        $this->check($rules, 'true', 'y', true);
    }

    public function testRuleStringMatchYesValueString()
    {
        $rules = array(
            'CT' => array(Parser::TYPE_STRING, 'CT', 'Connecticut')
        );

        $this->check($rules, 'CT', 'CT', 'Connecticut');
    }

    public function testRuleStringMatchNo()
    {
        $rules = array(
            'true' => array(Parser::TYPE_STRING, 'y', true)
        );

        $this->check($rules, 'true', 'n', null);
    }

    public function testRuleStringMatchNoCaseSensitive()
    {
        $rules = array(
            'CT' => array(Parser::TYPE_STRING, 'CT')
        );

        $this->check($rules, 'CT', 'ct', null);
    }

    public function testRuleStringMatchEmpty()
    {
        $rules = array(
            'true' => array(Parser::TYPE_STRING, 'y', true)
        );

        $this->check($rules, 'true', '', null);
    }

    public function testRuleStringMatchUnder()
    {
        $rules = array(
            'CT' => array(Parser::TYPE_STRING, 'CT', 'Connecticut')
        );

        $this->check($rules, 'CT', 'C', null);
    }

    public function testRuleStringMatchOver()
    {
        $rules = array(
            'CT' => array(Parser::TYPE_STRING, 'CT', 'Connecticut')
        );

        $this->check($rules, 'CT', 'CTO', null);
    }

    public function testRuleStringValidNo()
    {
        $rules = array(
            'CT' => array(Parser::TYPE_STRING, false)
        );

        $this->check($rules, 'CT', 'CT', null);
    }

    public function testRuleExpressionMatchYesValueNo()
    {
        $rules = array(
            'zip' => array(Parser::TYPE_RE, '([0-9]{5})(?:-([0-9]{4}))?')
        );

        $this->check($rules, 'zip', '06851-3622', '06851-3622');
    }

    public function testRuleExpressionMatchYesValueIndexValidYes()
    {
        $rules = array(
            'zip' => array(Parser::TYPE_RE, '([0-9]{5})(?:-([0-9]{4}))?', 2)
        );

        $this->check($rules, 'zip', '06851-3622', '3622');
    }

    public function testRuleExpressionMatchYesValueIndexValidNo()
    {
        $rules = array(
            'zip' => array(Parser::TYPE_RE, '([0-9]{5})(?:-([0-9]{4}))?', 2)
        );

        $this->check($rules, 'zip', '06851', null);
    }

    public function testRuleExpressionMatchYesValueMethodValidYes()
    {
        $rules = array(
            'zip' => array(Parser::TYPE_RE, '([0-9]{5})(?:-([0-9]{4}))?', 'encode')
        );

        $this->check($rules, 'zip', '06851-3622', '["06851-3622","06851","3622"]');
    }

    public function testRuleExpressionMatchYesValueMethodValidNo()
    {
        $rules = array(
            'zip' => array(Parser::TYPE_RE, '([0-9]{5})(?:-([0-9]{4}))?', '')
        );

        $this->check($rules, 'zip', '06851-3622', null);
    }

    public function testRuleExpressionMatchYesValueInvalid()
    {
        $rules = array(
            'zip' => array(Parser::TYPE_RE, '([0-9]{5})(?:-([0-9]{4}))?', false)
        );

        $this->check($rules, 'zip', '06851-3622', null);
    }

    public function testRuleExpressionMatchNo()
    {
        $rules = array(
            'zip' => array(Parser::TYPE_RE, '([0-9]{5})(?:-([0-9]{4}))?')
        );

        $this->check($rules, 'zip', 'zip', null);
    }

    public function testRuleExpressionMatchEmpty()
    {
        $rules = array(
            'zip' => array(Parser::TYPE_RE, '([0-9]{5})(?:-([0-9]{4}))?')
        );

        $this->check($rules, 'zip', '', null);
    }

    public function testRuleExpressionValidNo()
    {
        $rules = array(
            'zip' => array(Parser::TYPE_RE, '([0-9]{5})(?:-([0-9')
        );

        $this->check($rules, 'zip', '06851-3662', null);
    }

    public function testRuleMethodValidYes()
    {
        $rules = array(
            'json' => array(Parser::TYPE_METHOD, 'decode')
        );

        $this->check($rules, 'json', '{"a":"A"}', array('a' => 'A'));
    }

    public function testRuleMethodValidNoMethodIncompatible()
    {
        $rules = array(
            'json' => array(Parser::TYPE_METHOD, 'encode')
        );

        $this->check($rules, 'json', '{"a":"A"}', null);
    }

    public function testRuleMethodValidNoMethodUndefined()
    {
        $rules = array(
            'json' => array(Parser::TYPE_METHOD, 'undefined')
        );

        $this->check($rules, 'json', '{"a":"A"}', null);
    }

    public function testRuleMethodValidNoMethodInvalid()
    {
        $rules = array(
            'json' => array(Parser::TYPE_METHOD, 1)
        );

        $this->check($rules, 'json', '{"a":"A"}', null);
    }

    public function testRuleAndMatchYesRulesOne()
    {
        $rules = array(
            'and' => array(Parser::TYPE_AND, array('a')),
            'a' => array(Parser::TYPE_STRING, 'A')
        );

        $this->check($rules, 'and', 'A', array('A'));
    }

    public function testRuleAndMatchYesRulesTwo()
    {
        $rules = array(
            'and' => array(Parser::TYPE_AND, array('a', 'b')),
            'a' => array(Parser::TYPE_STRING, 'A'),
            'b' => array(Parser::TYPE_STRING, 'B')
        );

        $this->check($rules, 'and', 'AB', array('A', 'B'));
    }

    public function testRuleAndMatchYesRulesThree()
    {
        $rules = array(
            'and' => array(Parser::TYPE_AND, array('a', 'b', 'c')),
            'a' => array(Parser::TYPE_STRING, 'A'),
            'b' => array(Parser::TYPE_STRING, 'B'),
            'c' => array(Parser::TYPE_STRING, 'C')
        );

        $this->check($rules, 'and', 'ABC', array('A', 'B', 'C'));
    }

    public function testRuleAndMatchYesRulesTwoMethodValid()
    {
        $rules = array(
            'and' => array(Parser::TYPE_AND, array('a', 'b'), 'encode'),
            'a' => array(Parser::TYPE_STRING, 'A'),
            'b' => array(Parser::TYPE_STRING, 'B')
        );

        $this->check($rules, 'and', 'AB', '["A","B"]');
    }

    public function testRuleAndMatchYesRulesTwoIndexValid()
    {
        $rules = array(
            'and' => array(Parser::TYPE_AND, array('a', 'b'), 1),
            'a' => array(Parser::TYPE_STRING, 'A'),
            'b' => array(Parser::TYPE_STRING, 'B')
        );

        $this->check($rules, 'and', 'AB', 'B');
    }

    public function testRuleAndMatchYesRulesTwoIndexInvalid()
    {
        $rules = array(
            'and' => array(Parser::TYPE_AND, array('a', 'b'), 2),
            'a' => array(Parser::TYPE_STRING, 'A'),
            'b' => array(Parser::TYPE_STRING, 'B')
        );

        $this->check($rules, 'and', 'AB', null);
    }

    public function testRuleAndMatchYesRulesTwoValueInvalid()
    {
        $rules = array(
            'and' => array(Parser::TYPE_AND, array('a', 'b'), false),
            'a' => array(Parser::TYPE_STRING, 'A'),
            'b' => array(Parser::TYPE_STRING, 'B')
        );

        $this->check($rules, 'and', 'AB', null);
    }

    public function testRuleAndMatchNo()
    {
        $rules = array(
            'and' => array(Parser::TYPE_AND, array('a', 'b', 'c')),
            'a' => array(Parser::TYPE_STRING, 'A'),
            'b' => array(Parser::TYPE_STRING, 'B'),
            'c' => array(Parser::TYPE_STRING, 'C')
        );

        $this->check($rules, 'and', 'ABD', null);
    }

    public function testRuleAndValidNoRulesInvalid()
    {
        $rules = array(
            'and' => array(Parser::TYPE_AND, 'a'),
            'a' => array(Parser::TYPE_STRING, 'A')
        );

        $this->check($rules, 'and', 'A', null);
    }

    public function testRuleAndValidNoRulesUnknown()
    {
        $rules = array(
            'and' => array(Parser::TYPE_AND, array('a'))
        );

        $this->check($rules, 'and', 'A', null);
    }

    public function testRuleAndValidNoRulesUnnamed()
    {
        $rules = array(
            'and' => array(Parser::TYPE_AND, array('')),
            '' => array(Parser::TYPE_STRING, 'A')
        );

        $this->check($rules, 'and', 'A', null);
    }

    public function testRuleAndValidNoRulesEmpty()
    {
        $rules = array(
            'and' => array(Parser::TYPE_AND, array())
        );

        $this->check($rules, 'and', 'A', null);
    }

    public function testRuleOrMatchYesRulesOne()
    {
        $rules = array(
            'or' => array(Parser::TYPE_OR, array('a')),
            'a' => array(Parser::TYPE_STRING, 'A')
        );

        $this->check($rules, 'or', 'A', 'A');
    }

    public function testRuleOrMatchYesRulesTwo()
    {
        $rules = array(
            'or' => array(Parser::TYPE_OR, array('a', 'b')),
            'a' => array(Parser::TYPE_STRING, 'A'),
            'b' => array(Parser::TYPE_STRING, 'B')
        );

        $this->check($rules, 'or', 'B', 'B');
    }

    public function testRuleOrMatchYesRulesThree()
    {
        $rules = array(
            'or' => array(Parser::TYPE_OR, array('a', 'b', 'c')),
            'a' => array(Parser::TYPE_STRING, 'A'),
            'b' => array(Parser::TYPE_STRING, 'B'),
            'c' => array(Parser::TYPE_STRING, 'C')
        );

        $this->check($rules, 'or', 'C', 'C');
    }

    public function testRuleOrMatchYesRulesTwoMethodValid()
    {
        $rules = array(
            'or' => array(Parser::TYPE_OR, array('a', 'b'), 'encode'),
            'a' => array(Parser::TYPE_STRING, 'A'),
            'b' => array(Parser::TYPE_STRING, 'B')
        );

        $this->check($rules, 'or', 'B', '["B","b"]');
    }

    public function testRuleOrMatchYesRulesTwoMethodInvalid()
    {
        $rules = array(
            'or' => array(Parser::TYPE_OR, array('a', 'b'), 1),
            'a' => array(Parser::TYPE_STRING, 'A'),
            'b' => array(Parser::TYPE_STRING, 'B')
        );

        $this->check($rules, 'or', 'B', null);
    }

    public function testRuleOrMatchNo()
    {
        $rules = array(
            'or' => array(Parser::TYPE_OR, array('a', 'b', 'c')),
            'a' => array(Parser::TYPE_STRING, 'A'),
            'b' => array(Parser::TYPE_STRING, 'B'),
            'c' => array(Parser::TYPE_STRING, 'C')
        );

        $this->check($rules, 'or', 'D', null);
    }

    public function testRuleOrValidNoRulesInvalid()
    {
        $rules = array(
            'or' => array(Parser::TYPE_OR, 'a'),
            'a' => array(Parser::TYPE_STRING, 'A')
        );

        $this->check($rules, 'or', 'A', null);
    }

    public function testRuleOrValidNoRulesUnknown()
    {
        $rules = array(
            'or' => array(Parser::TYPE_OR, array('a'))
        );

        $this->check($rules, 'or', 'A', null);
    }

    public function testRuleOrValidNoRulesUnnamed()
    {
        $rules = array(
            'or' => array(Parser::TYPE_OR, array('')),
            '' => array(Parser::TYPE_STRING, 'A')
        );

        $this->check($rules, 'or', 'A', null);
    }

    public function testRuleOrValidNoRulesEmpty()
    {
        $rules = array(
            'or' => array(Parser::TYPE_OR, array())
        );

        $this->check($rules, 'or', 'A', null);
    }

    public function testRuleManyMatchZero()
    {
        $rules = array(
            'many' => array(Parser::TYPE_MANY, 'a'),
            'a' => array(Parser::TYPE_STRING, 'A')
        );

        $this->check($rules, 'many', '', array());
    }

    public function testRuleManyMatchOne()
    {
        $rules = array(
            'many' => array(Parser::TYPE_MANY, 'a'),
            'a' => array(Parser::TYPE_STRING, 'A')
        );

        $this->check($rules, 'many', 'A', array('A'));
    }

    public function testRuleManyMatchTwo()
    {
        $rules = array(
            'many' => array(Parser::TYPE_MANY, 'a'),
            'a' => array(Parser::TYPE_STRING, 'A')
        );

        $this->check($rules, 'many', 'AA', array('A', 'A'));
    }

    public function testRuleManyMinZeroMatchZero()
    {
        $rules = array(
            'many' => array(Parser::TYPE_MANY, 'a', 0),
            'a' => array(Parser::TYPE_STRING, 'A')
        );

        $this->check($rules, 'many', '', array());
    }

    public function testRuleManyMinOneMatchZero()
    {
        $rules = array(
            'many' => array(Parser::TYPE_MANY, 'a', 1),
            'a' => array(Parser::TYPE_STRING, 'A')
        );

        $this->check($rules, 'many', '', null);
    }

    public function testRuleManyMinOneMatchOne()
    {
        $rules = array(
            'many' => array(Parser::TYPE_MANY, 'a', 1),
            'a' => array(Parser::TYPE_STRING, 'A')
        );

        $this->check($rules, 'many', 'A', array('A'));
    }

    public function testRuleManyMinInvalidMatchOne()
    {
        $rules = array(
            'many' => array(Parser::TYPE_MANY, 'a', -1),
            'a' => array(Parser::TYPE_STRING, 'A')
        );

        $this->check($rules, 'many', 'A', null);
    }

    public function testRuleManyMinTwoMaxOneMatchTwo()
    {
        $rules = array(
            'many' => array(Parser::TYPE_MANY, 'a', 2, 1),
            'a' => array(Parser::TYPE_STRING, 'A')
        );

        $this->check($rules, 'many', 'AA', null);
    }

    public function testRuleManyMinTwoMaxTwoMatchTwo()
    {
        $rules = array(
            'many' => array(Parser::TYPE_MANY, 'a', 2, 2),
            'a' => array(Parser::TYPE_STRING, 'A')
        );

        $this->check($rules, 'many', 'AA', array('A', 'A'));
    }

    public function testRuleManyMinTwoMaxTwoMatchThree()
    {
        $rules = array(
            'many' => array(Parser::TYPE_MANY, 'a', 2, 2),
            'a' => array(Parser::TYPE_STRING, 'A')
        );

        $this->check($rules, 'many', 'AAA', null);
    }

    public function testRuleManyMinTwoMaxThreeMatchOne()
    {
        $rules = array(
            'many' => array(Parser::TYPE_MANY, 'a', 2, 3),
            'a' => array(Parser::TYPE_STRING, 'A')
        );

        $this->check($rules, 'many', 'A', null);
    }

    public function testRuleManyMinTwoMaxThreeMatchTwo()
    {
        $rules = array(
            'many' => array(Parser::TYPE_MANY, 'a', 2, 3),
            'a' => array(Parser::TYPE_STRING, 'A')
        );

        $this->check($rules, 'many', 'AA', array('A', 'A'));
    }

    public function testRuleManyMinTwoMaxThreeMatchThree()
    {
        $rules = array(
            'many' => array(Parser::TYPE_MANY, 'a', 2, 3),
            'a' => array(Parser::TYPE_STRING, 'A')
        );

        $this->check($rules, 'many', 'AAA', array('A', 'A', 'A'));
    }

    public function testRuleManyMinTwoMaxThreeMatchFour()
    {
        $rules = array(
            'many' => array(Parser::TYPE_MANY, 'a', 2, 3),
            'a' => array(Parser::TYPE_STRING, 'A')
        );

        $this->check($rules, 'many', 'AAAA', null);
    }

    public function testRuleManyMethodValidMatchZero()
    {
        $rules = array(
            'many' => array(Parser::TYPE_MANY, 'a', null, null, 'encode'),
            'a' => array(Parser::TYPE_STRING, 'A')
        );

        $this->check($rules, 'many', '', '[[]]');
    }

    public function testRuleManyMethodValidMatchOne()
    {
        $rules = array(
            'many' => array(Parser::TYPE_MANY, 'a', null, null, 'encode'),
            'a' => array(Parser::TYPE_STRING, 'A')
        );

        $this->check($rules, 'many', 'A', '[["A"]]');
    }

    public function testRuleManyMethodValidMatchTwo()
    {
        $rules = array(
            'many' => array(Parser::TYPE_MANY, 'a', null, null, 'encode'),
            'a' => array(Parser::TYPE_STRING, 'A')
        );

        $this->check($rules, 'many', 'AA', '[["A","A"]]');
    }

    public function testInputInvalidTextInvalid()
    {
        $rules = array(
            'true' => array(Parser::TYPE_STRING, 'y', true)
        );

        $this->check($rules, 'true', true, null);
    }

    public function testInputInvalidRuleUnknown()
    {
        $rules = array(
            'true' => array(Parser::TYPE_STRING, 'y', true)
        );

        $this->check($rules, 'false', 'n', null);
    }

    public function testRulesInvalidRuleNameInvalid()
    {
        $rules = array(
            '' => array(Parser::TYPE_STRING, 'text', true)
        );

        $this->check($rules, '', 'text', null);
    }

    public function testRulesInvalidRuleTypeInvalid()
    {
        $rules = array(
            'rule' => array(-1, 'text')
        );

        $this->check($rules, 'rule', 'text', null);
    }

    public function testRulesInvalidRuleDefinitionInvalid()
    {
        $rules = array(
            'rule' => 'text'
        );

        $this->check($rules, 'rule', 'text', null);
    }

    public function testRulesInvalidRulesMissing()
    {
        $rules = array();

        $this->check($rules, 'rule', 'text', null);
    }

    public function testRulesInvalidTypeInvalid()
    {
        $rules = 'rules';

        $this->check($rules, 'rule', 'text', null);
    }

    public function testParserReset()
    {
        $rules = array(
            'true' => array(Parser::TYPE_STRING, 'y', true)
        );

        $parser = new Parser($rules);
        $parser->evaluate('true', 'y');

        $this->checkParser($parser, 'true', 'y', true);
    }

    // PRIVATE

    private function check($rules, $rule, $input, $expectedOutput)
    {
        $parser = new Parser($rules);

        $this->checkParser($parser, $rule, $input, $expectedOutput);
    }

    private function checkParser(Parser $parser, $rule, $input, $expectedOutput)
    {
        $actualOutput = $parser->evaluate($rule, $input);

        $this->assertSame($expectedOutput, $actualOutput);
    }
}
