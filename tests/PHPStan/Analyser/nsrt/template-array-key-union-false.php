<?php

namespace TemplateArrayKeyUnionFalse;

use function PHPStan\Testing\assertType;

/**
 * @template TKey of array-key
 * @template TValue
 */
class Collection
{

	/**
	 * @param TValue|callable(TValue, TKey): bool $value
	 * @return TKey|false
	 */
	public function search($value, bool $strict = false)
	{
		return false;
	}

}

/**
 * @param Collection<int, string> $ints
 * @param Collection<string, string> $strings
 * @param Collection<int|string, string> $keys
 */
function test(Collection $ints, Collection $strings, Collection $keys): void
{
	assertType('int|false', $ints->search('foo'));
	assertType('string|false', $strings->search('foo'));
	assertType('int|string|false', $keys->search('foo'));
}
