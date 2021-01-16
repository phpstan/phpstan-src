<?php // lint >= 8.0

namespace NativeMixedType;

use function PHPStan\Analyser\assertType;

class Foo
{

	public mixed $fooProp;

	public function doFoo(mixed $foo): mixed
	{
		assertType('mixed', $foo);
		assertType('mixed', $this->fooProp);
	}

}

class Bar
{

}

function doFoo(mixed $foo): mixed
{
	assertType('mixed', $foo);
}

function (Foo $foo): void {
	assertType('mixed', $foo->fooProp);
	assertType('mixed', $foo->doFoo(1));
	assertType('mixed', doFoo(1));
};

function (): void {
	$f = function (mixed $foo): mixed {
		assertType('mixed', $foo);
	};

	assertType('void', $f(1));
};
