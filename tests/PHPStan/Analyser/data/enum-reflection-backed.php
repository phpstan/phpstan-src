<?php // lint >= 8.1

namespace EnumReflection;

use ReflectionEnum;
use UnitEnum;
use function PHPStan\Testing\assertType;

/** @param class-string<UnitEnum> $class */
function testNarrowGetNameTypeAfterIsBacked(string $class) {
	$r = new ReflectionEnum($class);
	assertType('class-string<UnitEnum>', $r->getName());
	if ($r->isBacked()) {
		assertType('class-string<BackedEnum>', $r->getName());
	}
}
