<?php // lint >= 8.1

declare(strict_types = 1);

namespace FirstClassCallableCallbacks;

function compareInts(int $a, int $b): int
{
	return $a <=> $b;
}

function sum(int $carry, int $item): int
{
	return $carry + $item;
}

function increment(int &$value, int $key): void
{
	$value++;
}

function describe(string $value, string $key): void
{
}

class Callbacks
{

	public function compare(string $a, string $b): int
	{
		return strcmp($a, $b);
	}

	public static function concat(string $carry, string $item): string
	{
		return $carry . $item;
	}

}

/**
 * @param list<int> $ints
 * @param list<string> $strings
 * @param array<string, string> $map
 */
function compatible(array $ints, array $strings, array $map, Callbacks $callbacks): void
{
	usort($ints, compareInts(...));
	usort($strings, strcmp(...));
	uasort($map, $callbacks->compare(...));
	array_reduce($ints, sum(...), 0);
	array_reduce($strings, Callbacks::concat(...), '');
	array_walk($ints, increment(...));
	array_walk($map, describe(...));
	call_user_func_array(compareInts(...), [1, 2]);
	call_user_func_array(str_replace(...), [' ', '-', 'a b']);

	// the closure of a multi-variant function fits when any of its variants does
	array_reduce($ints, max(...), 0);
	array_map(max(...), $ints, $ints);
	array_map(mt_rand(...), $ints, $ints);
}

/**
 * @param list<int> $ints
 * @param list<string> $strings
 * @param array<string, string> $map
 */
function incompatible(array $ints, array $strings, array $map, Callbacks $callbacks): void
{
	usort($strings, compareInts(...));
	uasort($map, compareInts(...));
	usort($ints, $callbacks->compare(...));
	array_reduce($strings, sum(...), 0);
	array_reduce($ints, Callbacks::concat(...), '');
	array_walk($strings, increment(...));
	array_walk($ints, describe(...));
	array_map(strtok(...), $ints);
}

/**
 * @param list<int> $ints
 */
function differentCallees(array $ints, bool $flag): void
{
	// closures of different functions are alternatives, each of them has to fit
	array_map($flag ? strtoupper(...) : strtolower(...), $ints);
}
