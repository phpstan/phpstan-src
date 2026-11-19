<?php // lint >= 8.4

namespace Bug15165Types;

use ReflectionEnum;
use UnitEnum;
use function PHPStan\Testing\assertType;

/** @param class-string<UnitEnum> $class */
function testNarrowGetNameTypeAfterIsBacked(string $class): void
{
	$r = new ReflectionEnum($class);
	assertType('class-string<UnitEnum>', $r->getName());
	assertType('class-string<UnitEnum>', $r->name);
	assertType('ReflectionNamedType|null', $r->getBackingType());
	assertType('array<ReflectionEnumUnitCase>', $r->getCases());
	assertType('ReflectionEnumUnitCase', $r->getCase('A'));
	if ($r->isBacked()) {
		assertType('array<ReflectionEnumBackedCase>', $r->getCases());
		assertType('ReflectionEnumBackedCase', $r->getCase('A'));
		assertType('class-string<BackedEnum>', $r->getName());
		assertType('class-string<BackedEnum>', $r->name);
		assertType('ReflectionNamedType', $r->getBackingType());
	}
}

/**
 * @template T of UnitEnum
 * @param ReflectionEnum<T> $enum
 */
function testTemplate(ReflectionEnum $enum): void
{
	assertType('ReflectionEnumUnitCase', $enum->getCase('A'));
	if ($enum->isBacked()) {
		assertType('ReflectionEnum<T of UnitEnum (function Bug15165Types\testTemplate(), argument)>', $enum);
		assertType('ReflectionNamedType', $enum->getBackingType());
		assertType('array<ReflectionEnumBackedCase>', $enum->getCases());
		assertType('ReflectionEnumBackedCase', $enum->getCase('A'));
	}
}
