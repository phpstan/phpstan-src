<?php declare(strict_types=1); // lint >= 8.0

namespace Bug6896;

use IteratorIterator;
use LimitIterator;
use Traversable;
use ArrayObject;

/**
 * @template TKey as array-key
 * @template TValue
 *
 * @extends ArrayObject<TKey, TValue>
 *
 * Basic generic iterator, with additional helper functions.
 */
abstract class XIterator extends ArrayObject
{
}

final class RandHelper
{

	/**
	 * @template TRandKey as array-key
	 * @template TRandVal
	 * @template TRandList as array<TRandKey, TRandVal>|XIterator<TRandKey, TRandVal>|Traversable<TRandKey, TRandVal>
	 *
	 * @param TRandList $list
	 *
	 * @return (
	 *        TRandList is array ? array<TRandKey, TRandVal> : (
	 *        TRandList is XIterator ?    XIterator<TRandKey, TRandVal> :
	 *        IteratorIterator<TRandKey, TRandVal>|LimitIterator<TRandKey, TRandVal>
	 * ))
	 */
	public static function getPseudoRandomWithUrl(
		array|XIterator|Traversable $list,
	): array|XIterator|IteratorIterator|LimitIterator
	{
		return $list;
	}
}
