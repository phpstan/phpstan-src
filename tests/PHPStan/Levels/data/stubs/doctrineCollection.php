<?php

namespace RecursiveTemplateProblem;

use Closure;

/**
 * @psalm-template TKey of array-key
 * @psalm-template T
 * @template-extends IteratorAggregate<TKey, T>
 * @template-extends ArrayAccess<TKey|null, T>
 */
interface Collection extends \Countable, \IteratorAggregate, \ArrayAccess
{

	/**
	 * Partitions this collection in two collections according to a predicate.
	 * Keys are preserved in the resulting collections.
	 *
	 * @param Closure $p The predicate on which to partition.
	 *
	 * @return Collection[] An array with two elements. The first element contains the collection
	 *                      of elements where the predicate returned TRUE, the second element
	 *                      contains the collection of elements where the predicate returned FALSE.
	 *
	 * @psalm-param Closure(TKey=, T=):bool $p
	 * @psalm-return array{0: Collection<TKey, T>, 1: Collection<TKey, T>}
	 */
	public function partition(Closure $p);

}
