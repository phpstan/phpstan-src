<?php // lint >= 7.4

declare(strict_types=1);

namespace Bug15442;

use function PHPStan\Testing\assertType;

class GridLayout
{
	protected function buildTemplate(): void
	{
		$fx = static function () use (&$fx) {
			(static function () use ($fx) {
				assertType('static-Closure(): void', $fx);
				$fx();

				dummy(); // @phpstan-ignore-line
			})();
		};

		dummy($fx); // @phpstan-ignore-line
	}

	public function nestedArrowFunction(): void
	{
		$fx = static function () use (&$fx) {
			(static fn () => $fx())();
			assertType('static-Closure(): void', $fx);
		};
	}

	public function nestedByRefCapture(): void
	{
		$fx = static function () use (&$fx) {
			(static function () use (&$fx) {
				assertType('static-Closure(): void', $fx);
				$fx();
			})();
		};
	}

	public function callUserFunc(): void
	{
		$fx = static function () use (&$fx) {
			assertType('static-Closure(): void', $fx);
			call_user_func($fx);
		};
	}

	public function invokeMethod(): void
	{
		$fx = static function () use (&$fx) {
			assertType('static-Closure(): void', $fx);
			$fx->__invoke();
		};
	}

	public function alias(): void
	{
		$fx = static function () use (&$fx) {
			$alias = $fx;
			assertType('static-Closure(): void', $alias);
			$alias();
		};
	}

	/** @param list<int> $a */
	public function passedAsCallback(array $a): void
	{
		$fx = static function (array $a) use (&$fx): void {
			assertType('static-Closure(array): void', $fx);
			array_map($fx, $a);
		};
		$fx($a);
	}

	public function readOnly(): void
	{
		$fx = static function () use (&$fx) {
			assertType('static-Closure(): void', $fx);
		};
		$fx();
	}

	public function insideClosure(): void
	{
		$outer = function (): void {
			$fx = static function () use (&$fx) {
				(static function () use ($fx) {
					assertType('static-Closure(): void', $fx);
					$fx();
				})();
			};
			$fx();
		};
		$outer();
	}

	public function otherByRefUseStillFollowed(): void
	{
		$x = 1;
		$c = function () use (&$x): void {
			assertType('1', $x);
			(function () use ($x): void {
				assertType('1', $x);
			})();
			$x = 'a';
		};
		$c();
		assertType("'a'", $x);
	}
}
