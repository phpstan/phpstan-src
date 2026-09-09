<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug13374;

use function PHPStan\Testing\assertType;

/**
 * @template TKey of array-key
 * @template TValue
 */
class Collection
{

	/**
	 * @template TPushValue
	 * @param TPushValue $value
	 * @return $this
	 * @phpstan-this-out static<int|TKey, TValue|TPushValue>
	 */
	public function push(mixed $value): static
	{
		return $this;
	}

	/**
	 * @template TPutKey of array-key
	 * @template TPutValue
	 * @param TPutKey $key
	 * @param TPutValue $value
	 * @return $this
	 * @phpstan-this-out static<TKey|TPutKey, TValue|TPutValue>
	 */
	public function put(int|string $key, mixed $value): static
	{
		return $this;
	}

}

/**
 * @param Collection<string, int> $pushCollection
 * @param Collection<string, int> $putCollection
 */
function test(Collection $pushCollection, Collection $putCollection): void
{
	assertType('Bug13374\Collection<int|string, int>', $pushCollection->push(123));
	assertType('Bug13374\Collection<int|string, int|string>', $pushCollection->push('foo'));
	assertType('Bug13374\Collection<int|string, int>', $putCollection->put(123, 456));
	assertType('Bug13374\Collection<int|string, int|string>', $putCollection->put(789, 'foo'));
}
