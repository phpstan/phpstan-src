<?php // lint >= 8.1

namespace FunctionCallable;

use function function_exists;

class Foo
{

	public function doFoo(string $s): void
	{
		strlen(...);
		nonexistent(...);

		if (function_exists('blabla')) {
			blabla(...);
		}

		$s(...);
		if (function_exists($s)) {
			$s(...);
		}
	}

	public function doBar(): void
	{
		$f = function (): void {

		};
		$f(...);

		$i = 1;
		$i(...);
	}

	public function doBaz(): void
	{
		StrLen(...);
	}

	public function doLorem(callable $cb): void
	{
		if (rand(0, 1)) {
			$cb = 1;
		}

		$f = $cb(...);
	}

	public function doIpsum(Nonexistent $obj): void
	{
		$f = $obj(...);
	}

}
