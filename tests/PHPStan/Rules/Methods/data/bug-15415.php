<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15415;

/**
 * @template TKey of array-key
 * @template TValue
 */
final class Coll
{
	/**
	 * @param array<TKey, TValue> $items
	 */
	public function __construct(public array $items = [])
	{
	}

	/**
	 * @template TNewKey of array-key
	 * @param callable(TValue): TNewKey $callback
	 * @return Coll<TNewKey, TValue>
	 */
	public function keyBy(callable $callback): Coll
	{
		return new Coll();
	}

	/**
	 * @template TMapKey of array-key
	 * @template TMapValue
	 * @param callable(TValue): Coll<TMapKey, TMapValue> $callback
	 * @return Coll<TMapKey, TMapValue>
	 */
	public function mapWithKeys(callable $callback): Coll
	{
		return new Coll();
	}

	/**
	 * @template TMakeKey of array-key
	 * @template TMakeValue
	 * @param array<TMakeKey, TMakeValue> $items
	 * @return Coll<TMakeKey, TMakeValue>
	 */
	public static function make(array $items): Coll
	{
		return new Coll($items);
	}
}

/**
 * @template TFnKey of array-key
 * @template TFnValue
 * @param array<TFnKey, TFnValue> $items
 * @return Coll<TFnKey, TFnValue>
 */
function collect(array $items): Coll
{
	return new Coll($items);
}

/** @template T */
class Box
{
}

/** @template T */
class ParentBox
{
	/** @param T $value */
	public function __construct(public mixed $value)
	{
	}
}

/**
 * @template T
 * @extends ParentBox<T>
 */
class ChildBox extends ParentBox
{
}

/**
 * @template T
 * @param callable(): T $callback
 * @return T
 */
function run(callable $callback): mixed
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
		return run(function () use ($c): Coll {
			return $c->keyBy(fn (int $i): string => $i > 0 ? 'positive' : 'negative');
		});
	}

	/**
	 * @param Coll<int, int> $c
	 * @return Coll<string, int>
	 */
	public function arrowFunction(Coll $c): Coll
	{
		return run(fn (): Coll => $c->keyBy(fn (int $i): string => $i > 0 ? 'positive' : 'negative'));
	}

	/**
	 * @param Coll<int, int> $c
	 * @return Coll<string, int>
	 */
	public function direct(Coll $c): Coll
	{
		return $c->keyBy(fn (int $i): string => $i > 0 ? 'positive' : 'negative');
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

		return $f();
	}

	/**
	 * @param Coll<int, int> $c
	 * @return Coll<string, int>
	 */
	public function staticBlockClosure(Coll $c): Coll
	{
		return run(static function () use ($c): Coll {
			return $c->keyBy(fn (int $i): string => $i > 0 ? 'positive' : 'negative');
		});
	}

	/**
	 * @param Coll<int, int> $c
	 * @return Coll<string, int>
	 */
	public function nestedBlockClosures(Coll $c): Coll
	{
		return run(function () use ($c): Coll {
			return run(function () use ($c): Coll {
				return $c->keyBy(fn (int $i): string => $i > 0 ? 'positive' : 'negative');
			});
		});
	}

	/**
	 * @param Coll<int, int> $c
	 * @return Coll<string, int>
	 */
	public function blockClosureInArrowFunction(Coll $c): Coll
	{
		return run(fn (): Coll => run(function () use ($c): Coll {
			return $c->keyBy(fn (int $i): string => $i > 0 ? 'positive' : 'negative');
		}));
	}

	/**
	 * @param Coll<int, int> $c
	 * @return Coll<string, int>
	 */
	public function immediatelyInvoked(Coll $c): Coll
	{
		return (function () use ($c): Coll {
			return $c->keyBy(fn (int $i): string => $i > 0 ? 'positive' : 'negative');
		})();
	}

	/**
	 * @return Coll<string, int>
	 */
	public function newInBlockClosure(): Coll
	{
		return run(function (): Coll {
			return new Coll(['a' => 1, 'b' => 2]);
		});
	}

	/**
	 * @return Coll<string, int>
	 */
	public function functionCallInBlockClosure(): Coll
	{
		return run(function (): Coll {
			return collect(['a' => 1, 'b' => 2]);
		});
	}

	/**
	 * @return Coll<string, int>
	 */
	public function staticCallInBlockClosure(): Coll
	{
		return run(function (): Coll {
			return Coll::make(['a' => 1, 'b' => 2]);
		});
	}

	/**
	 * @param Coll<int, Coll<int, int>> $c
	 * @return Coll<string, int>
	 */
	public function mapWithKeysBlockCallback(Coll $c): Coll
	{
		return $c->mapWithKeys(function (Coll $inner): Coll {
			return $inner->keyBy(fn (int $i): string => $i > 0 ? 'positive' : 'negative');
		});
	}

	/**
	 * @return Box<string>
	 */
	public function newWithoutConstructorInClosureVariable(): Box
	{
		$f = function (): Box {
			return new Box();
		};

		return $f();
	}

	/**
	 * @return ChildBox<string>
	 */
	public function newWithParentConstructorInBlockClosure(): ChildBox
	{
		return run(function (): ChildBox {
			return new ChildBox('a');
		});
	}

	/**
	 * @return ChildBox<string>
	 */
	public function newWithParentConstructorInClosureVariable(): ChildBox
	{
		$f = function (): ChildBox {
			return new ChildBox('a');
		};

		return $f();
	}
}
