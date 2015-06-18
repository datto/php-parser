# Parser for PHP

Specifies the rules that define your context-free grammar, and then gain
the ability to parse any text source. The output is an abstract syntax tree
ready for semantic analysis.

## Features

* Powerful recursive-descent parser
* 100% unit-test coverage
* Ultra-lightweight (just one small file)

## Requirements

* PHP >= 5.3

## License

This package is released under an open-source license: [LGPL-3.0](https://www.gnu.org/licenses/lgpl-3.0.html)

## Installation

If you're using [Composer](https://getcomposer.org/) as your dependency
management system, you can include this package by inserting this line
into the "require" section of your "composer.json" file:
```
        "datto/php-parser": "~2.0"
```

Then update your Composer dependencies:
```composer update```

## Unit tests

You can run the suite of unit tests from the project directory like this:
```./vendor/bin/phpunit```

## Author

[Spencer Mortensen](http://spencermortensen.com/contact/)
