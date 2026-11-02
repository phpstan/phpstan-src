<?php

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

    protected function arrowFunction(): void
    {
		$fx = static function () use (&$fx) {
			(static fn () => assertType('static-Closure(): void', $fx) && $fx())();
		};

		dummy($fx); // @phpstan-ignore-line
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

    public function insideClosure(): void
    {
		$outer = static function (): void {
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

    public function unrelatedNestedScopes(): void
    {
		$count = 0;
		$increment = static function () use (&$count): void {
			assertType('0', $count);
			(static function (\Closure $count): void {
				$count();
			})(static function (): void {});
			(static fn (\Closure $count) => $count())(static function (): void {});
			$count++;
		};
		$increment();
		assertType('1', $count);
    }
}
