<?php declare(strict_types = 1); // onlyif PHP_VERSION_ID < 80000

namespace ArraySearchPhp7;

use function PHPStan\Testing\assertType;

class Foo
{

	public function mixedAndSubtractedArray($mixed, string $string): void
	{
		if (is_array($mixed)) {
			assertType('int|string|false', array_search('foo', $mixed, true));
			assertType('int|string|false', array_search('foo', $mixed));
			assertType('int|string|false', array_search($string, $mixed, true));
		} else {
			assertType('mixed~array', $mixed);
			assertType('null', array_search('foo', $mixed, true));
			assertType('null', array_search('foo', $mixed));
			assertType('null', array_search($string, $mixed, true));
		}
	}

}
