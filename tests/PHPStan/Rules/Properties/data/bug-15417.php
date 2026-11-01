<?php declare(strict_types = 1);

namespace Bug15417Property;

/**
 * @template TKey of array-key
 * @template-covariant TValue
 */
final class Collection
{
	/** @param iterable<TKey, TValue> $a */
	public function __construct($a) { }
}

final class Repro
{

	/** @var object{x: Collection<int, string>} */
	public object $p;

	public function doFoo(): void
	{
		$this->p = (object) ['x' => new Collection(['a', 'b'])];
	}

}
