<?php // lint >= 8.1

namespace ReflectionClassIsEnum;

use ReflectionClass;
use function PHPStan\Testing\assertType;

/**
 * @param class-string $class
 */
function testNarrowClassAfterIsEnum(string $class): void {
	$r = new ReflectionClass($class);
	if ($r->isEnum()) {
		assertType('class-string<UnitEnum>', $r->name);
		assertType('class-string<UnitEnum>', $r->getName());

		assertType('UnitEnum', $r->newInstance());


		// Todo:
		//assertType('ReflectionClass<UnitEnum>', $r);

	}



}

function testTemplateAssertions(): void  {
	$enumR = new ReflectionClass(Foo::class);
	assertType('ReflectionClass<ReflectionClassIsEnum\\Foo>', $enumR);
	assertType('true', $enumR->isEnum());
}


enum Foo {
	case Bar;
}
