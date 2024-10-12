<?php declare(strict_types = 1);

/**
 * @template TKey of array-key
 * @template TValue
 */
final class Collection
{
	/** @return self<int, static> */
	public function chunk(int $size): self
	{
		return $this;
	}

	/**
	 * @template TMapValue
	 *
	 * @param  callable(TValue, TKey): TMapValue  $callback
	 * @return self<TKey, TMapValue>
	 */
	public function map(callable $callback): self
	{
		return $this;
	}
}

class DateHeader {}

class ProjectionCumulativeHeadersResolver
{
	/**
	 * @param Collection<int, DateHeader> $projectionMonthsHeaders
	 * @return Collection<int, non-falsy-string>
	 */
	public function resolve(Collection $projectionMonthsHeaders): Collection
	{
		return $projectionMonthsHeaders->chunk(2)
			->map(fn ($_, $index) => $index * 2 + 2 . ' month');
	}

	/**
	 * @param lowercase-string $s
	 * @return false
	 */
	public function test(string $s)
	{
		return $s;
	}
}
