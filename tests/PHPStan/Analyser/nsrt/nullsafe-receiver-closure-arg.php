<?php // lint >= 8.0

declare(strict_types = 1);

namespace NullsafeReceiverClosureArg;

use function PHPStan\Testing\assertType;

class Foo
{

	/** @param callable(): void $cb */
	public function denied(callable $cb): void
	{
	}

	public function doFoo(?Foo $maybe, int $i): void
	{
		$maybe?->denied(function () use ($i): void {
			assertType('int', $i);
		});
		$maybe?->denied(fn () => assertType('int', $i));
	}

}
