<?php

namespace StrvalTest;

use function PHPStan\Testing\assertType;

/**
 * @param class-string<\stdClass> $class
 */
function test(string $class)
{
	assertType('\'foo\'', strval('foo'));
	assertType('\'\'', strval(null));
	assertType('\'\'|\'1\'', strval(rand(0, 1) === 0));
	assertType('string&numeric', strval(rand()));
	assertType('string&numeric', strval(rand() * 0.1));
	assertType('string&numeric', strval(strval(rand())));
	assertType('class-string<stdClass>', strval($class));
}
