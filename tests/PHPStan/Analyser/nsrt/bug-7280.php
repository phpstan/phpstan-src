<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug7280;

use function PHPStan\Testing\assertType;

function doFoo(): void
{
	$result = array_reduce(
		['test1', 'test2'],
		static function (array $carry, string $value): array{
			assertType("array{starts: list<'test1'|'test2'>, ends: list<'test1'|'test2'>}", $carry);
			$carry['starts'][] = $value;
			$carry['ends'][] = $value;

			return $carry;
		},
		initial: ['starts' => [], 'ends' => []],
	);
	assertType("array{starts: non-empty-list<'test1'|'test2'>, ends: non-empty-list<'test1'|'test2'>}", $result);
}

function doBar(): void
{
	/**
	 * @var array<int, int> $numbers
	 */
	$numbers = [1, 2];

	$sum = array_reduce(
		$numbers,
		fn ($curr, $n) => $curr + $n,
		0
	);
	assertType('int', $sum);
}

class Foo
{

	public int $count = 0;

}

/**
 * @param list<string> $strings
 * @param non-empty-list<string> $nonEmptyStrings
 * @param list<list<int>> $matrix
 */
function doBaz(array $strings, array $nonEmptyStrings, array $matrix): void
{
	$lengths = array_reduce($strings, function ($carry, $string) {
		assertType('array<string, int<0, max>>', $carry);
		$carry[$string] = strlen($string);
		return $carry;
	}, []);
	assertType('array<string, int<0, max>>', $lengths);

	$grouped = array_reduce($strings, static function (array $carry, string $string): array {
		assertType('array<string, non-empty-list<string>>', $carry);
		$carry[$string[0] ?? ''][] = $string;
		return $carry;
	}, []);
	assertType('array<string, non-empty-list<string>>', $grouped);

	$count = array_reduce($strings, fn ($carry) => $carry + 1, 0);
	assertType('int<0, max>', $count);

	$nonEmptyCount = array_reduce($nonEmptyStrings, fn ($carry) => $carry + 1, 0);
	assertType('int<1, max>', $nonEmptyCount);

	$longest = array_reduce($strings, static function (?string $carry, string $string): string {
		assertType('string|null', $carry);
		return $carry === null || strlen($string) > strlen($carry) ? $string : $carry;
	});
	assertType('string|null', $longest);

	$sum = array_reduce($matrix, fn ($carry, $row) => $carry + array_reduce($row, fn ($rowCarry, $cell) => $rowCarry + $cell, 0), 0);
	assertType('int', $sum);

	$foo = array_reduce($strings, static function (Foo $carry, string $string): Foo {
		$carry->count++;
		return $carry;
	}, new Foo());
	assertType('Bug7280\Foo', $foo);

	$flipFlop = array_reduce($strings, static fn ($carry) => $carry === false ? 1 : 'one', false);
	assertType("1|'one'|false", $flipFlop);

	$fromEmpty = array_reduce([], static fn ($carry, $item) => $carry . $item, 'initial');
	assertType("'initial'", $fromEmpty);
}

/** @param list<string> $strings */
function doNever(array $strings): void
{
	// the body's inference gives up on $carry[count($carry)], so the carry is not inferred from it
	$untyped = array_reduce($strings, function ($carry, $string) {
		$carry[count($carry)] = $string;
		return $carry;
	}, []);
	assertType('mixed', $untyped);

	$typed = array_reduce($strings, static function (array $carry, string $string): array {
		$carry[count($carry)] = $string;
		return $carry;
	}, []);
	assertType('array', $typed);

	$alwaysThrows = array_reduce($strings, static function ($carry, $string) {
		throw new \Exception();
	}, 0);
	assertType('0', $alwaysThrows);
}
