<?php // lint >= 8.1

namespace Bug7280;

use function PHPStan\Testing\assertType;

function namedArguments(): void
{
	$result = array_reduce(
		['test1', 'test2'],
		static function (array $carry, string $value): array{
			assertType('array{starts: array{}, ends: array{}}|array{starts: non-empty-list<string>, ends: non-empty-list<string>}', $carry);
			$carry['starts'][] = $value;
			$carry['ends'][] = $value;

			return $carry;
		},
		initial: ['starts' => [], 'ends' => []],
	);
	assertType('array{starts: non-empty-list<string>, ends: non-empty-list<string>}', $result);
}

function positionalArguments(): void
{
	$result = array_reduce(
		['test1', 'test2'],
		static function (array $carry, string $value): array{
			assertType('array{starts: array{}, ends: array{}}|array{starts: non-empty-list<string>, ends: non-empty-list<string>}', $carry);
			$carry['starts'][] = $value;
			$carry['ends'][] = $value;

			return $carry;
		},
		['starts' => [], 'ends' => []],
	);
	assertType('array{starts: non-empty-list<string>, ends: non-empty-list<string>}', $result);
}

function reducer(?int $carry, int $item): int
{
	return ($carry ?? 0) + $item;
}

/**
 * @param list<int> $integers
 */
function firstClassCallable(array $integers): void
{
	$result = array_reduce($integers, reducer(...), 0);
	assertType('int', $result);
}

/**
 * @param int[] $integers
 */
function nonConvergingCallback(array $integers): void
{
	// the carry type never reaches a fixed point here - the naive behaviour is kept
	$result = array_reduce($integers, function ($carry, $value) {
		assertType('array{}|array{x: mixed}', $carry);

		return ['x' => $carry];
	}, []);
	assertType('array{}|array{x: mixed}', $result);

	$result2 = array_reduce($integers, fn ($carry, $value) => ['x' => $carry], []);
	assertType('array{}|array{x: mixed}', $result2);
}

/**
 * @param array<string> $strings
 */
function arrowFunction(array $strings): void
{
	$result = array_reduce($strings, fn (int $carry, string $value): int => $carry + strlen($value), 0);
	assertType('int', $result);
}

/**
 * @param int[] $integers
 */
function intSum(array $integers): void
{
	$sum = array_reduce($integers, function ($carry, $n) {
		assertType('int', $carry);

		return $carry + $n;
	}, 0);
	assertType('int', $sum);
}

/**
 * @param array<string> $strings
 */
function stringConcat(array $strings): void
{
	$concatenated = array_reduce($strings, function (string $carry, string $string): string {
		assertType('string', $carry);

		return $carry . $string;
	}, '');
	assertType('string', $concatenated);
}

/**
 * @param array<string> $strings
 */
function withoutInitial(array $strings): void
{
	$concatenated = array_reduce($strings, function ($carry, string $string) {
		assertType('string|null', $carry);

		return ($carry ?? '') . $string;
	});
	assertType('string|null', $concatenated);
}
