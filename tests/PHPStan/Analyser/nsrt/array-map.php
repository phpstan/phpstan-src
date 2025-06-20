<?php

namespace ArrayMap;

use function array_map;
use function PHPStan\Testing\assertType;

/**
 * @param array<int, string> $array
 */
function foo(array $array): void {
	$mapped = array_map(
		static function(string $string): string {
			return (string) $string;
		},
		$array
	);

	assertType('array<int, string>', $mapped);
}

/**
 * @param non-empty-array<int, string> $array
 */
function foo2(array $array): void {
	$mapped = array_map(
		static function(string $string): string {
			return (string) $string;
		},
		$array
	);

	assertType('non-empty-array<int, string>', $mapped);
}

/**
 * @param list<string> $array
 */
function foo3(array $array): void {
	$mapped = array_map(
		static function(string $string): string {
			return (string) $string;
		},
		$array
	);

	assertType('list<string>', $mapped);
}

/**
 * @param non-empty-list<string> $array
 */
function foo4(array $array): void {
	$mapped = array_map(
		static function(string $string): string {
			return (string) $string;
		},
		$array
	);

	assertType('non-empty-list<string>', $mapped);
}

/** @param array{foo?: 0, bar?: 1, baz?: 2} $array */
function foo5(array $array): void {
	$mapped = array_map(
		static function(string $string): string {
			return (string) $string;
		},
		$array
	);

	assertType('array{foo?: string, bar?: string, baz?: string}', $mapped);
}

class Foo
{
	/**
	 * @template T of int
	 * @param T $n
	 * @return (T is 3 ? 'Fizz' : (T is 5 ? 'Buzz' : T))
	 */
	public static function fizzbuzz(int $n): int|string
	{
		return match ($n) {
			3 => 'Fizz',
			5 => 'Buzz',
			default => $n,
		};
	}

	public function doFoo(): void
	{
		$a = range(0, 1);

		assertType("array{'0', '1'}", array_map('strval', $a));
		assertType("array{'0', '1'}", array_map(strval(...), $a));
		assertType("array{'0'|'1', '0'|'1'}", array_map(fn ($v) => strval($v), $a));
		assertType("array{'0'|'1', '0'|'1'}", array_map(fn ($v) => (string)$v, $a));
	}

	public function doFizzBuzz(): void
	{
		assertType("array{1, 2, 'Fizz', 4, 'Buzz', 6}", array_map([__CLASS__, 'fizzbuzz'], range(1, 6)));
		assertType("array{1, 2, 'Fizz', 4, 'Buzz', 6}", array_map([$this, 'fizzbuzz'], range(1, 6)));
		assertType("array{1, 2, 'Fizz', 4, 'Buzz', 6}", array_map(self::fizzbuzz(...), range(1, 6)));
		assertType("array{1, 2, 'Fizz', 4, 'Buzz', 6}", array_map($this->fizzbuzz(...), range(1, 6)));
	}

	/**
	 * @param array<string, 'a'|'b'|'A'|'B'> $array
	 */
	public function doUppercase(array $array): void
	{
		assertType("array<string, 'A'|'B'>", array_map(strtoupper(...), $array));
		assertType("array{'A', 'B'}", array_map(strtoupper(...), ['A', 'B']));
	}

}
