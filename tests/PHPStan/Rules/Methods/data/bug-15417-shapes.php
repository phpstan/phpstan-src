<?php declare(strict_types = 1);

namespace Bug15417Shapes;

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

	/** @return object{x: Collection<int, string>} */
	public static function shape(): object
	{
		return (object) ['x' => new Collection(['a', 'b'])];
	}

	/** @return object{y: object{x: Collection<int, string>}} */
	public static function nested(): object
	{
		return (object) ['y' => (object) ['x' => new Collection(['a', 'b'])]];
	}

	/** @return object{x: Collection<int, string>}|null */
	public static function nullable(bool $b): ?object
	{
		return $b ? (object) ['x' => new Collection(['a', 'b'])] : null;
	}

	/** @return object{x: Collection<int, string>}&\stdClass */
	public static function intersection(): object
	{
		return (object) ['x' => new Collection(['a', 'b'])];
	}

	/** @return list<object{x: Collection<int, string>, y?: Collection<int, int>}> */
	public static function optional(): array
	{
		return [(object) ['x' => new Collection(['a', 'b'])]];
	}

	/** @return object{x: Collection<string, string>} */
	public static function stillReported(): object
	{
		return (object) ['x' => new Collection(['a', 'b'])];
	}

}
