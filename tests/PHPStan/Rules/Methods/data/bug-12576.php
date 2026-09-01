<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug12576;

/**
 * @template TKey of array-key
 * @template TValue
 */
class Collection
{
	final public function __construct(
		/** @var array<TKey, TValue> */
		protected array $items = [],
	) {}

	/** @return static<int<0, max>, TValue> */
	public function values(): static
	{
        return new static(array_values($this->items));
	}

	/** @return array<TKey, TValue> */
	public function all(): array
	{
		return $this->items;
	}
}

/**
 * @param Collection<string, string> $foo
 * @return list<string>
 */
function test(Collection $foo): array
{
    return $foo->values()->all();
}
