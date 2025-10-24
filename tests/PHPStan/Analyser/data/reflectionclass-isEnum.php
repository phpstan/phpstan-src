<?php // lint >= 8.1

namespace ReflectionClassIsEnum;

use ReflectionClass;
use function PHPStan\Testing\assertType;

/**
 * @param class-string $class
 */
function testNarrowClassAfterIsEnum(string $class): void {
	$r = new ReflectionClass($class);
	assertType('class-string<object>', $r->name);
	if ($r->isEnum()) {
		assertType('ReflectionClass<\UnitEnum>', $r);
		assertType('class-string<\UnitEnum>', $r->name);
	}
}

