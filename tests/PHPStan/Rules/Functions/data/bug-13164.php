<?php declare(strict_types = 1);

namespace Bug13164Rule;

class Arr {
	/**
	 * @template TValue
	 * @template TMapValue
	 *
	 * @param array<TValue> $array
	 * @param callable(TValue): TMapValue $callback
	 * @return array<TMapValue>
	 */
	public static function map(array $array, callable $callback): array
	{
		return array_map($callback, $array);
	}
}

/** @param array<int> $bar */
function foo(array $bar): void
{
	//	
}

$people = [
	['name' => 'Liam', 'age' => 21],
	['name' => 'Sam', 'age' => 19],
];

$ages = array_map(fn(array $person) => $person['age'], $people);
$ages2 = Arr::map($people, fn(array $person) => $person['age']);

foo($ages);
foo($ages2);
