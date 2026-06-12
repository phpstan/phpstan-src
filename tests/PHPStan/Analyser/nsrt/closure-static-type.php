<?php

namespace ClosureStaticType;

use Closure;
use function PHPStan\Testing\assertType;

final class Foo
{

	public function doFoo(): void
	{
		$static = static function (): void {};
		assertType('static-Closure(): void', $static);

		$nonStatic = function (): void {};
		assertType('Closure(): void', $nonStatic);

		$staticArrow = static fn (): int => 1;
		assertType('static-Closure(): 1', $staticArrow);

		$nonStaticArrow = fn (): int => 1;
		assertType('Closure(): 1', $nonStaticArrow);
	}

	public function doBindTo(): void
	{
		$static = static function (): void {};
		assertType('null', $static->bindTo($this));

		$nonStatic = function (): void {};
		assertType('((Closure(): void)|null)', $nonStatic->bindTo($this));
	}

	public function doBind(): void
	{
		$static = static function (): void {};
		assertType('null', Closure::bind($static, $this));

		$nonStatic = function (): void {};
		assertType('((Closure(): void)|null)', Closure::bind($nonStatic, $this));
	}

	/**
	 * @param Closure(): void $unknownClosure
	 */
	public function doUnknown(Closure $unknownClosure): void
	{
		assertType('((Closure(): void)|null)', $unknownClosure->bindTo($this));
		assertType('((Closure(): void)|null)', Closure::bind($unknownClosure, $this));
	}

	public function doFromCallable(): void
	{
		$fn = Closure::fromCallable(static function (): void {});
		assertType('null', $fn->bindTo($this));

		$fn2 = Closure::fromCallable(function (): void {});
		assertType('((Closure(): void)|null)', $fn2->bindTo($this));
	}

}
