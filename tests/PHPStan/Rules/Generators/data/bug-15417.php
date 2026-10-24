<?php declare(strict_types = 1);

namespace Bug15417Generator;

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

	/** @return \Generator<int, object{x: Collection<int, string>}> */
	public static function gen(): \Generator
	{
		yield (object) ['x' => new Collection(['a', 'b'])];
	}

}
