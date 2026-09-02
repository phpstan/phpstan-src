<?php declare(strict_types = 1);

namespace Bug15153;

use function PHPStan\Testing\assertType;

class MyClass
{

	public function check(string $method): bool
	{
		[$class, $function] = explode('::', $method);

		assertType('string', $class);
		assertType('string|null', $function);

		return !($class === '' || is_null($function) || $function === '');
	}

	/**
	 * @param non-empty-list<string> $list
	 */
	public function nonEmptyList(array $list): void
	{
		[$first, $second] = $list;

		assertType('string', $first);
		assertType('string|null', $second);
	}

	/**
	 * @param list<string> $list
	 */
	public function possiblyEmptyList(array $list): void
	{
		[$first] = $list;

		assertType('string|null', $first);
	}

	/**
	 * @param array{string, string} $pair
	 */
	public function arrayShape(array $pair): void
	{
		[$first, $second] = $pair;

		assertType('string', $first);
		assertType('string', $second);
	}

	/**
	 * @param array{a: int, b?: string} $shape
	 */
	public function optionalKey(array $shape): void
	{
		['a' => $a, 'b' => $b] = $shape;

		assertType('int', $a);
		assertType('string|null', $b);
	}

	/**
	 * @param array<string, int> $map
	 */
	public function generalArray(array $map): void
	{
		['a' => $a] = $map;

		assertType('int|null', $a);
	}

	/**
	 * @param non-empty-list<array{string, string}> $rows
	 */
	public function nestedDestructuring(array $rows): void
	{
		[[$a, $b], [$c, $d]] = $rows;

		assertType('string', $a);
		assertType('string', $b);
		assertType('string|null', $c);
		assertType('string|null', $d);
	}

	/**
	 * @param non-empty-list<string> $list
	 */
	public function listSyntax(array $list): void
	{
		list($first, $second) = $list;

		assertType('string', $first);
		assertType('string|null', $second);
	}

	/**
	 * @param non-empty-list<non-empty-list<string>> $rows
	 */
	public function foreachDestructuring(array $rows): void
	{
		foreach ($rows as [$first, $second]) {
			assertType('string', $first);
			assertType('string|null', $second);
		}
	}

	/**
	 * @param list<string> $list
	 */
	public function narrowedByIsset(array $list): void
	{
		if (isset($list[1])) {
			[$first, $second] = $list;

			assertType('string', $first);
			assertType('string', $second);
		}
	}

	/**
	 * @param list<string> $list
	 */
	public function narrowedByCount(array $list): void
	{
		if (count($list) >= 2) {
			[$first, $second] = $list;

			assertType('string', $first);
			assertType('string', $second);
		}
	}

	/**
	 * @param array<string, int> $map
	 */
	public function narrowedByArrayKeyExists(array $map): void
	{
		if (array_key_exists('a', $map)) {
			['a' => $a] = $map;

			assertType('int', $a);
		}
	}

	/**
	 * @param non-empty-list<string> $list
	 */
	public function byRef(array $list): void
	{
		[&$first, &$second] = $list;

		assertType('string', $first);
		assertType('string|null', $second);
	}

	/**
	 * @param \ArrayAccess<int, string> $offsets
	 */
	public function arrayAccess(\ArrayAccess $offsets): void
	{
		[$first, $second] = $offsets;

		assertType('string|null', $first);
		assertType('string|null', $second);
	}

}
