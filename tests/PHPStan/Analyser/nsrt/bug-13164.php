<?php declare(strict_types = 1);

namespace Bug13164;

use function PHPStan\Testing\assertType;

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

$people = [
	['name' => 'Liam', 'age' => 21],
	['name' => 'Sam', 'age' => 19],
];

$ages = array_map(function(array $person) {
	assertType("array{name: 'Liam', age: 21}|array{name: 'Sam', age: 19}", $person);
	assertType('19|21', $person['age']);
	
	return $person['age'];
}, $people);
assertType('array{21, 19}', $ages);

$ages2 = Arr::map($people, function(array $person) {
	assertType("array{name: 'Liam', age: 21}|array{name: 'Sam', age: 19}", $person);
	assertType('19|21', $person['age']);
	
	return $person['age'];
});
assertType('array<19|21>', $ages2);
