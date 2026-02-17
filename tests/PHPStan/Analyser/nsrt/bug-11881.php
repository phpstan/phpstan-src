<?php // lint >= 8.1

namespace Bug11881;

use ReflectionEnum;
use function PHPStan\Testing\assertType;

/** @param class-string<\UnitEnum> $class */
function testNarrowEnumAfterGetBackingTypeNotNull(string $class): void
{
	$r = new ReflectionEnum($class);
	assertType('ReflectionEnum<UnitEnum>', $r);
	assertType('ReflectionNamedType|null', $r->getBackingType());

	if ($r->getBackingType() !== null) {
		assertType('ReflectionEnum<BackedEnum>', $r);
		assertType('class-string<BackedEnum>', $r->getName());
	}
}

/** @param class-string<\UnitEnum> $class */
function testNarrowEnumAfterGetBackingTypeNull(string $class): void
{
	$r = new ReflectionEnum($class);

	if ($r->getBackingType() === null) {
		assertType('ReflectionEnum<UnitEnum~BackedEnum>', $r);
	} else {
		assertType('ReflectionEnum<BackedEnum>', $r);
		assertType('class-string<BackedEnum>', $r->getName());
	}
}
