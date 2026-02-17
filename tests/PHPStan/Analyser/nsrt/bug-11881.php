<?php // lint >= 8.1

namespace Bug11881;

use ReflectionEnum;
use function PHPStan\Testing\assertType;

enum UnitFoo
{
	case A;
	case B;
}

enum BackedFoo: string
{
	case A = 'a';
	case B = 'b';
}

function testNarrowEnumAfterGetBackingTypeNotNull(ReflectionEnum $r): void
{
	if ($r->getBackingType() !== null) {
		assertType('ReflectionEnum<BackedEnum>', $r);
	} else {
		assertType('ReflectionEnum<UnitEnum~BackedEnum>', $r);
	}
}

function testNarrowEnumAfterGetBackingTypeNull(ReflectionEnum $r): void
{
	if ($r->getBackingType() === null) {
		assertType('ReflectionEnum<UnitEnum~BackedEnum>', $r);
	} else {
		assertType('ReflectionEnum<BackedEnum>', $r);
	}
}

function testNarrowKnownBackedEnum(): void
{
	$r = new ReflectionEnum(BackedFoo::class);
	assertType('ReflectionEnum<Bug11881\BackedFoo>', $r);
	assertType('ReflectionNamedType', $r->getBackingType());
}

function testNarrowKnownUnitEnum(): void
{
	$r = new ReflectionEnum(UnitFoo::class);
	assertType('ReflectionEnum<Bug11881\UnitFoo>', $r);
	assertType('null', $r->getBackingType());
}
