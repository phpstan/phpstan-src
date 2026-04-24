<?php

namespace Bug12677;

use function PHPStan\Testing\assertType;

class Foo
{
	public function getBool(): bool
	{
		return true;
	}
}

function test1(?Foo $foo): void
{
	$hasFoo = $foo !== null;

	$bool = $foo?->getBool() ?? false;

	if ($hasFoo) {
		assertType(Foo::class, $foo);
	}
}

function test2(?Foo $foo): void
{
	$hasFoo = $foo !== null;

	$bool = $foo !== null ? $foo->getBool() : false;

	if ($hasFoo) {
		assertType(Foo::class, $foo);
	}
}

function test3(?Foo $foo): void
{
	$hasFoo = $foo !== null;

	$bool = $hasFoo ? $foo->getBool() : false;

	if ($hasFoo) {
		assertType(Foo::class, $foo);
	}
}
