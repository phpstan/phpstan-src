<?php // lint >= 8.1

namespace ClosureSignatureFromUsagesFirstClassCallable;

use function PHPStan\Testing\assertType;

class Foo
{

	public function invokedThroughFirstClassCallable(): void
	{
		$c = function ($a) {
			assertType("5|'x'", $a);
		};
		$c('x');
		$b = $c(...);
		assertType("Closure(5|'x'): void", $b);
		$b(5);

		$f = fn ($a) => assertType("6|'y'", $a);
		$f('y');
		$g = $f(...);
		assertType("Closure(6|'y'): mixed", $g);
		$g(6);
	}

	public function invokedThroughMethodCallable(): void
	{
		$c = function ($a) {
			assertType('mixed', $a);
		};
		$c('x');
		$invoke = $c->__invoke(...);
		$invoke(5);
	}

	public function byRefUseInvokedThroughFirstClassCallable(): void
	{
		$x = 1;
		$c = function () use (&$x): void {
			assertType('1', $x);
			$x = 'a';
		};
		$b = $c(...);
		assertType('1', $x);
		$b();
		assertType("'a'", $x);
	}

	public function byRefUseInvokedThroughMethodCallable(): void
	{
		$x = 1;
		$c = function () use (&$x): void {
			assertType("1|'a'", $x);
			$x = 'a';
		};
		$invoke = $c->__invoke(...);
		$invoke();
		assertType("1|'a'", $x);
	}

}
