<?php declare(strict_types = 1);

namespace ScopePhpVersionArrayFunctions;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array<string, int> $arr
	 */
	public function nonArrayArgument(string $s, array $arr): void
	{
		if (PHP_VERSION_ID >= 80000) {
			assertType('*NEVER*', array_flip($s));
			assertType('*NEVER*', array_values($s));
			assertType('*NEVER*', array_keys($s));
			assertType('*NEVER*', array_slice($s, 1));
			assertType('*NEVER*', array_reverse($s));
			assertType('*NEVER*', array_search(1, $s));
			assertType('*NEVER*', array_fill_keys($s, 1));
			assertType('*NEVER*', array_intersect_key($s, $arr));
			assertType('*NEVER*', array_chunk($s, 1));
		}

		if (PHP_VERSION_ID < 80000) {
			assertType('null', array_flip($s));
			assertType('null', array_values($s));
			assertType('null', array_keys($s));
			assertType('null', array_slice($s, 1));
			assertType('null', array_reverse($s));
			assertType('null', array_search(1, $s));
			assertType('null', array_fill_keys($s, 1));
			assertType('null', array_intersect_key($s, $arr));
			assertType('null', array_chunk($s, 1));
		}
	}

	public function nonArrayArraySplice(string $s, string $t): void
	{
		if (PHP_VERSION_ID >= 80000) {
			assertType('*NEVER*', array_splice($s, 1));
		}

		if (PHP_VERSION_ID < 80000) {
			assertType('null', array_splice($t, 1));
		}
	}

	/**
	 * @param array<int, int> $arr
	 */
	public function invalidChunkLength(array $arr): void
	{
		if (PHP_VERSION_ID >= 80000) {
			assertType('*NEVER*', array_chunk($arr, 0));
		}

		if (PHP_VERSION_ID < 80000) {
			assertType('null', array_chunk($arr, 0));
		}
	}

	public function invalidFillCount(int $value): void
	{
		if (PHP_VERSION_ID >= 80000) {
			assertType('*NEVER*', array_fill(0, -1, $value));
		}

		if (PHP_VERSION_ID < 80000) {
			assertType('false', array_fill(0, -1, $value));
		}
	}

	/**
	 * @param list<array{id: array<int>, name: string}> $rows
	 */
	public function arrayColumnWithArrayIndex(array $rows): void
	{
		if (PHP_VERSION_ID >= 80000) {
			assertType('array<*NEVER*, string>', array_column($rows, 'name', 'id'));
		}

		if (PHP_VERSION_ID < 80000) {
			assertType('array<int, string>', array_column($rows, 'name', 'id'));
		}
	}

	/**
	 * @param array<int, string> $keys
	 * @param array<int, int> $values
	 */
	public function arrayCombineMismatch(array $keys, array $values): void
	{
		if (PHP_VERSION_ID >= 80000) {
			assertType('array<string, int>', array_combine($keys, $values));
		}

		if (PHP_VERSION_ID < 80000) {
			assertType('array<string, int>|false', array_combine($keys, $values));
		}
	}

	/**
	 * @param array<int, int> $arr
	 */
	public function minMaxOnPossiblyEmptyArray(array $arr): void
	{
		if (PHP_VERSION_ID >= 80000) {
			assertType('int', min($arr));
			assertType('int', max($arr));
		}

		if (PHP_VERSION_ID < 80000) {
			assertType('int|false', min($arr));
			assertType('int|false', max($arr));
		}
	}

}
