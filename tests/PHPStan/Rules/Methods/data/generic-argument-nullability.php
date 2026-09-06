<?php declare(strict_types = 1);

namespace GenericArgumentNullability;

/**
 * @template TKey of array-key
 * @template TValue
 */
class Collection
{

}

/** @template-covariant TValue */
class CovariantCollection
{

}

class Foo
{

	/**
	 * @template T
	 * @param callable(): T $cb
	 * @return T
	 */
	public function grab(callable $cb)
	{
		return $cb();
	}

	/**
	 * @template T
	 * @param callable(mixed, array<string, mixed>): T $cb
	 * @return T
	 */
	public function grabTwoArgs(callable $cb)
	{
		return $cb(null, []);
	}

	/** @param Collection<string, int|null> $collection */
	public function acceptNullable(Collection $collection): void
	{
	}

	/** @param Collection<string, int> $collection */
	public function acceptPlain(Collection $collection): void
	{
	}

	/** @param CovariantCollection<int> $collection */
	public function acceptCovariantPlain(CovariantCollection $collection): void
	{
	}

}

/**
 * @param Collection<string, int> $plain
 * @param Collection<string, int|null> $nullable
 * @param CovariantCollection<int|null> $covariantNullable
 * @param array<string, int|null> $array
 */
function test(Foo $foo, Collection $plain, Collection $nullable, CovariantCollection $covariantNullable, array $array, ?int $scalar): void
{
	$foo->grab(fn () => $plain);
	$foo->grab(fn () => $nullable);
	$foo->grab(fn () => $array);
	$foo->grab(fn () => $scalar);
	$foo->grabTwoArgs(fn () => $nullable);
	$foo->acceptNullable($nullable);
	$foo->acceptPlain($nullable);
	$foo->acceptNullable($plain);
	$foo->acceptCovariantPlain($covariantNullable);
}
