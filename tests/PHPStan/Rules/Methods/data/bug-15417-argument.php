<?php declare(strict_types = 1);

namespace Bug15417Argument;

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

	/** @param object{x: Collection<int, string>} $o */
	public static function take(object $o): void
	{
	}

	public static function doFoo(): void
	{
		self::take((object) ['x' => new Collection(['a', 'b'])]);
	}

}
