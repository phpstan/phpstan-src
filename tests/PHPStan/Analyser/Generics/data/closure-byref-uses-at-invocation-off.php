<?php // lint >= 8.0

namespace ClosureByRefUsesAtInvocationOff;

use function PHPStan\Testing\assertType;

class Foo
{

	public function invokedOnce(): void
	{
		$x = 1;
		$c = function () use (&$x): void {
			assertType("1|'a'", $x);
			$x = 'a';
		};
		assertType("1|'a'", $x);
		$c();
		assertType("1|'a'", $x);
	}

	public function neverInvoked(): void
	{
		$i = 0;
		$inc = function () use (&$i): void {
			assertType('int<0, max>', $i);
			$i++;
		};
		assertType('int<0, max>', $i);
	}

}
