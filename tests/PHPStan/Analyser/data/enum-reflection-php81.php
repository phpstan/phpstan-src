<?php // onlyif PHP_VERSION_ID >= 80100 && PHP_VERSION_ID < 80200

namespace EnumReflection81;

use ReflectionEnum;
use ReflectionEnumBackedCase;
use ReflectionEnumUnitCase;
use function PHPStan\Testing\assertType;

enum Foo: int
{

	case FOO = 1;
	case BAR = 2;
}

function testNarrowGetBackingTypeAfterIsBacked() {
	$r = new ReflectionEnum(Foo::class);
	assertType('ReflectionType|null', $r->getBackingType());
	if ($r->isBacked()) {
		assertType('ReflectionType', $r->getBackingType());
	}
}
