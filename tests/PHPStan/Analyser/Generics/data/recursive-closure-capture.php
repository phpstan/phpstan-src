<?php

namespace RecursiveClosureCapture;

use Closure;
use function PHPStan\Testing\assertType;

class Foo
{

	public function nestedClosure(): void
	{
		$fx = static function () use (&$fx) {
			(static function () use ($fx) {
				$fx();
				consume();
			})();
		};
		consume($fx);
	}

	public function nestedArrow(): void
	{
		$fx = static function () use (&$fx) {
			(static fn () => $fx())();
		};
		consume($fx);
	}

	public function nestedTwice(): void
	{
		$fx = static function () use (&$fx) {
			(static function () use ($fx) {
				(static function () use ($fx) {
					$fx();
					consume();
				})();
			})();
		};
		consume($fx);
	}

	public function unrelatedNestedScopes(): void
	{
		$count = 0;
		$increment = static function () use (&$count): void {
			assertType('0', $count);
			(static function (Closure $count): void {
				$count();
			})(static function (): void {});
			(static fn (Closure $count) => $count())(static function (): void {});
			$generator = static function () use ($count) {
				yield $count;
			};
			$count++;
		};
		$increment();
		assertType('1', $count);
	}

}
