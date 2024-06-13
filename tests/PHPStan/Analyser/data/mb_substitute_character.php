<?php // onlyif PHP_VERSION_ID < 80000

\PHPStan\Testing\assertType('\'entity\'|\'long\'|\'none\'|int<1, 55295>|int<57344, 1114111>', mb_substitute_character());
\PHPStan\Testing\assertType('true', mb_substitute_character(''));
\PHPStan\Testing\assertType('false', mb_substitute_character(null));
\PHPStan\Testing\assertType('true', mb_substitute_character('none'));
\PHPStan\Testing\assertType('true', mb_substitute_character('long'));
\PHPStan\Testing\assertType('true', mb_substitute_character('entity'));
\PHPStan\Testing\assertType('false', mb_substitute_character('foo'));
\PHPStan\Testing\assertType('true', mb_substitute_character('123'));
\PHPStan\Testing\assertType('true', mb_substitute_character('123.4'));
\PHPStan\Testing\assertType('true', mb_substitute_character(0xFFFD));
\PHPStan\Testing\assertType('true', mb_substitute_character(0x10FFFF));
\PHPStan\Testing\assertType('false', mb_substitute_character(-1));
\PHPStan\Testing\assertType('false', mb_substitute_character(0x110000));
\PHPStan\Testing\assertType('bool', mb_substitute_character($undefined));
\PHPStan\Testing\assertType('bool', mb_substitute_character(new stdClass()));
\PHPStan\Testing\assertType('bool', mb_substitute_character(function () {}));
\PHPStan\Testing\assertType('false', mb_substitute_character(rand(0xD800, 0xDFFF)));
\PHPStan\Testing\assertType('bool', mb_substitute_character(rand(0, 0xDFFF)));
\PHPStan\Testing\assertType('bool', mb_substitute_character(rand(0xD800, 0x10FFFF)));
