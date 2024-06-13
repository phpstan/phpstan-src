<?php // onlyif PHP_VERSION_ID >= 80100

namespace PHPStan\Generics\GenericEnumClassStringType;

use function PHPStan\Testing\assertType;

function testEnumExists(string $str)
{
	assertType('string', $str);
	if (enum_exists($str)) {
		assertType('class-string<UnitEnum>', $str);
	}
}

