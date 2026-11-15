<?php declare(strict_types = 1);

/*
 * instructions: 430.8M
 * cycles: 552.0M
 * memory: 3.067MiB
 */

namespace BenchClosureNestedShapeDescription;

/**
 * Each map() wraps the value in one more single-key shape. The callable
 * parameter of map() is described for the closure scope cache key, and
 * ConstantArrayType::toPhpDocNode() described each value once for the
 * key-value items and once for the value-only items, so describing a value
 * nested N shapes deep was exponential in N.
 *
 * @template T
 */
final class Collection
{

	/** @var list<T> */
	private array $items;

	/** @param list<T> $items */
	public function __construct(array $items)
	{
		$this->items = $items;
	}

	/**
	 * @template U
	 * @param callable(T): U $callback
	 * @return Collection<U>
	 */
	public function map(callable $callback): Collection
	{
		return new Collection(array_map($callback, $this->items));
	}

	/** @return list<T> */
	public function all(): array
	{
		return $this->items;
	}

}

function run(): int
{
	$collection = new Collection([1]);
	$collection = $collection->map(static fn ($item) => [$item]);
	$collection = $collection->map(static fn ($item) => [$item]);
	$collection = $collection->map(static fn ($item) => [$item]);
	$collection = $collection->map(static fn ($item) => [$item]);
	$collection = $collection->map(static fn ($item) => [$item]);
	$collection = $collection->map(static fn ($item) => [$item]);
	$collection = $collection->map(static fn ($item) => [$item]);
	$collection = $collection->map(static fn ($item) => [$item]);
	$collection = $collection->map(static fn ($item) => [$item]);
	$collection = $collection->map(static fn ($item) => [$item]);
	$collection = $collection->map(static fn ($item) => [$item]);
	$collection = $collection->map(static fn ($item) => [$item]);
	$collection = $collection->map(static fn ($item) => [$item]);
	$collection = $collection->map(static fn ($item) => [$item]);
	$collection = $collection->map(static fn ($item) => [$item]);
	$collection = $collection->map(static fn ($item) => [$item]);
	$collection = $collection->map(static fn ($item) => [$item]);
	$collection = $collection->map(static fn ($item) => [$item]);

	return count($collection->all());
}
