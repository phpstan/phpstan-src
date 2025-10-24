<?php // lint >= 8.1

namespace EnumReflection81;

use ReflectionClass;
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

function testNarrowClassAfterIsEnum() {
	/**
	 * @var class-string
	 */
	$classString = Foo::class;
	$r = new ReflectionClass($classString);
	assertType('class-string', $classString);
	if ($r->isEnum()) {
		assertType('class-string<\UnitEnum>', $classString);
	}
}

