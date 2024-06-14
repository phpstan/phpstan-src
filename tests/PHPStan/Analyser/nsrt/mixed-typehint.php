<?php

namespace MixedTypehint;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(mixed $foo)
	{
		assertType('mixed', $foo);
		assertType('mixed', $this->doBar());
	}

	public function doBar(): mixed
	{

	}

}

function doFoo(mixed $foo)
{
	assertType('mixed', $foo);
}

function (mixed $foo) {
	assertType('mixed', $foo);
	$f = function (): mixed {

	};
	assertType('void', $f());

	$f = function () use ($foo): mixed {
		return $foo;
	};
	assertType('mixed', $f());
};
