<?php // lint >= 8.6

declare(strict_types = 1);

namespace PartialFunctionApplicationCallbacks;

function intToString(int $i, string $prefix): string
{
	return $prefix . $i;
}

function compareInts(int $a, int $b, bool $reverse): int
{
	return $reverse ? $b <=> $a : $a <=> $b;
}

/**
 * @template T
 * @template U
 * @param list<T> $items
 * @param callable(T): U $fn
 * @return list<U>
 */
function mapList(array $items, callable $fn): array
{
	return [];
}

/**
 * @template T
 * @param T $value
 * @return list<T>
 */
function repeat($value, int $times): array
{
	return [];
}

/**
 * @template T
 * @param T $value
 * @param T $other
 * @return list<T>
 */
function pair($value, $other): array
{
	return [$value, $other];
}

/**
 * @param list<int> $ints
 * @param list<string> $strings
 */
function compatible(array $ints, array $strings): void
{
	array_map(intToString(?, 'n'), $ints);
	array_map(intToString(prefix: 'n', i: ?), $ints);
	array_filter($strings, str_contains(?, 'x'));
	usort($ints, compareInts(?, ?, true));
	mapList($ints, intToString(?, 'n'));
	mapList($ints, repeat(?, 2));
	mapList($strings, pair(?, 'x'));
	mapList($ints, pair(?, 'x'));
	array_map(repeat(...), $ints, $ints);
}

/**
 * @param list<int> $ints
 * @param list<string> $strings
 */
function incompatible(array $ints, array $strings): void
{
	array_map(intToString(?, 'n'), $strings);
	array_map(intToString(?, ?), $ints);
	array_filter($ints, str_contains(?, 'x'));
	usort($strings, compareInts(?, ?, true));
	usort($ints, compareInts(?, true, ...));
	mapList($strings, intToString(?, 'n'));
}
