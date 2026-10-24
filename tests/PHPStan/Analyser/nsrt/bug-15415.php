<?php declare(strict_types = 1);

namespace Bug15415Types;

use function PHPStan\Testing\assertType;

/**
 * @template TKey of array-key
 * @template TValue
 */
final class Coll
{
	/**
	 * @template TNewKey of array-key
	 * @param callable(TValue): TNewKey $callback
	 * @return Coll<TNewKey, TValue>
	 */
	public function keyBy(callable $callback): Coll
	{
		return new Coll();
	}
}

/**
 * @template T
 * @param callable(): T $callback
 * @return T
 */
function run(callable $callback)
{
	return $callback();
}

final class Test
{
	/**
	 * @param Coll<int, int> $c
	 * @return Coll<string, int>
	 */
	public function blockClosure(Coll $c): Coll
	{
		$r = run(function () use ($c): Coll {
			$keyed = $c->keyBy(fn (int $i): string => $i > 0 ? 'positive' : 'negative');
			assertType('Bug15415Types\Coll<string, int>', $keyed);

			return $keyed;
		});
		assertType('Bug15415Types\Coll<string, int>', $r);

		return $r;
	}

	/**
	 * @param Coll<int, int> $c
	 * @return Coll<string, int>
	 */
	public function closureInVariable(Coll $c): Coll
	{
		$f = function () use ($c): Coll {
			return $c->keyBy(fn (int $i): string => $i > 0 ? 'positive' : 'negative');
		};
		$r = $f();
		assertType('Bug15415Types\Coll<string, int>', $r);

		return $r;
	}
}
