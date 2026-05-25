<?php

namespace ClosureStaticType;

use Closure;
use function PHPStan\Testing\assertType;

final class Foo
{

	public function doFoo(): void
	{
		$static = static function (): void {};
		assertType('Closure(): void', $static);

		$nonStatic = function (): void {};
		assertType('Closure(): void', $nonStatic);

		$staticArrow = static fn (): int => 1;
		assertType('Closure(): 1', $staticArrow);

		$nonStaticArrow = fn (): int => 1;
		assertType('Closure(): 1', $nonStaticArrow);
	}

	public function doBindTo(): void
	{
		$static = static function (): void {};
		assertType('Closure(): void', $static->bindTo($this));

		$nonStatic = function (): void {};
		assertType('Closure(): void', $nonStatic->bindTo($this));
	}

	public function doBind(): void
	{
		$static = static function (): void {};
		assertType('Closure(): void', Closure::bind($static, $this));

		$nonStatic = function (): void {};
		assertType('Closure(): void', Closure::bind($nonStatic, $this));
	}

	/**
	 * @param Closure(): void $unknownClosure
	 */
	public function doUnknown(Closure $unknownClosure): void
	{
		assertType('Closure(): void', $unknownClosure->bindTo($this));
		assertType('Closure(): void', Closure::bind($unknownClosure, $this));
	}

	public function doFromCallable(): void
	{
		$fn = Closure::fromCallable(static function (): void {});
		assertType('Closure(): void', $fn->bindTo($this));

		$fn2 = Closure::fromCallable(function (): void {});
		assertType('Closure(): void', $fn2->bindTo($this));
	}

}
