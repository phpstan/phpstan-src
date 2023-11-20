<?php // lint >= 8.1

namespace EnumReflection;

use ReflectionEnum;
use ReflectionEnumBackedCase;
use ReflectionEnumUnitCase;
use ReflectionType;
use function PHPStan\Testing\assertType;

enum Foo: int
{

	case FOO = 1;
	case BAR = 2;

	public function doFoo(): void
	{
		$r = new ReflectionEnum(self::class);
		foreach ($r->getCases() as $case) {
			assertType(ReflectionEnumBackedCase::class, $case);
		}

		assertType(ReflectionEnumBackedCase::class, $r->getCase('FOO'));
	}

}

enum Bar
{

	case FOO;
	case BAR;

	public function doFoo(): void
	{
		$r = new ReflectionEnum(self::class);
		foreach ($r->getCases() as $case) {
			assertType(ReflectionEnumUnitCase::class, $case);
		}
		assertType(ReflectionEnumUnitCase::class, $r->getCase('FOO'));
	}

}

$r = new ReflectionEnum(Foo::class);
assertType(ReflectionType::class . '|null', $r->getBackingType());
if ($r->isBacked()) {
	assertType(ReflectionType::class, $r->getBackingType());
}
