<?php

declare(strict_types = 1);

namespace Bug13192;

use function PHPStan\Testing\assertType;

class Apple
{
}

class Orange
{
}

/**
 * @template TKey of array-key
 * @template TValue
 */
class Collection
{

	/**
	 * @template UKey of array-key
	 * @template UValue
	 * @param static<UKey, UValue> $items
	 * @return static<TKey|UKey, TValue|UValue>
	 */
	public function merge(self $items): static
	{
		return $this;
	}

}

/**
 * @param Collection<int, Orange> $oranges
 * @param Collection<string, Apple> $apples
 */
function test(Collection $oranges, Collection $apples): void
{
	assertType('Bug13192\Collection<int|string, Bug13192\Apple|Bug13192\Orange>', $oranges->merge($apples));
	assertType('Bug13192\Collection<int|string, Bug13192\Apple|Bug13192\Orange>', $apples->merge($oranges));
	assertType('Bug13192\Collection<int, Bug13192\Orange>', $oranges->merge($oranges));
	assertType('Bug13192\Collection<string, Bug13192\Apple>', $apples->merge($apples));
}
