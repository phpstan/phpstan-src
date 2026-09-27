<?php

namespace ClosureSignatureFromUsagesOff;

use function PHPStan\Testing\assertType;

/**
 * @param callable(string): void $cb
 */
function doFoo(callable $cb): void
{
}

/**
 * @param callable(): (callable(string): void) $cb
 */
function takesFactory(callable $cb): void
{
}

function (): void {
	$c = function ($a) {
		assertType('mixed', $a);
	};
	$c(1);
	$c(2);
	doFoo($c);
	assertType('Closure(mixed): void', $c);

	$f = fn ($a) => $a;
	assertType('mixed', $f(1));

	$factory = function () {
		return function ($x): void {
			assertType('mixed', $x);
		};
	};
	takesFactory($factory);
};
