<?php // lint >= 8.0

namespace GetClassWithoutArgumentPhp8;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(): void
	{
		assertType('\'GetClassWithoutArgumentPhp8\\\\Foo\'', get_class());
	}

}

function doBar(): void
{
	assertType('*NEVER*', get_class());
}

function (): void {
	assertType('class-string', get_class());
};

assertType('class-string', get_class());
