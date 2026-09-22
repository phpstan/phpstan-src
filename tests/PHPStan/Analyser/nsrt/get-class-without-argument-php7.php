<?php // lint < 8.0

namespace GetClassWithoutArgumentPhp7;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(): void
	{
		assertType('\'GetClassWithoutArgumentPhp7\\\\Foo\'', get_class());
	}

}

function doBar(): void
{
	assertType('false', get_class());
}

function (): void {
	assertType('false', get_class());
};

assertType('false', get_class());
