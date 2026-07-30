<?php

namespace ClosureBindNullableThis;

use Closure;
use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class Foo
{

	public int $prop = 1;

}

function (?Foo $foo): void {
	Closure::bind(function () {
		assertType('ClosureBindNullableThis\Foo|null', $this);
		assertNativeType('ClosureBindNullableThis\Foo|null', $this);
	}, $foo);
};

function (Foo $foo): void {
	Closure::bind(function () {
		assertType('ClosureBindNullableThis\Foo', $this);
	}, $foo);
};

function (): void {
	Closure::bind(function () {
		assertType('*ERROR*', $this);
	}, null);
};
