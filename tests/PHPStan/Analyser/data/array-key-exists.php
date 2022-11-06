<?php

namespace ArrayKeyExistsExtension;

use function array_key_exists;
use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array<string, string> $a
	 * @return void
	 */
	public function doFoo(array $a, string $key, int $anotherKey): void
	{
		assertType('false', array_key_exists(2, $a));
		assertType('bool', array_key_exists('foo', $a));
		assertType('false', array_key_exists('2', $a));

		$b = ['foo' => 2, 3 => 'bar'];
		assertType('true', array_key_exists('foo', $b));
		assertType('true', array_key_exists(3, $b));
		assertType('true', array_key_exists('3', $b));
		assertType('false', array_key_exists(4, $b));

		if (array_key_exists($key, $b)) {
			assertType("'3'|'foo'", $key);
		}
		if (array_key_exists($anotherKey, $b)) {
			assertType('3', $anotherKey);
		}

		$empty = [];
		assertType('false', array_key_exists('foo', $empty));
		assertType('false', array_key_exists($key, $empty));
	}



	/**
	 * @param array<int, mixed>    $a
	 * @param array<string, mixed> $b
	 * @param array<mixed>         $c
	 * @param array-key $key4
	 *
	 * @return void
	 */
	public function doBar(array $a, array $b, array $c, int $key1, string $key2, int|string $key3, $key4, mixed $key5): void
	{
		if (array_key_exists($key1, $a)) {
			assertType('int', $key1);
		}
		if (array_key_exists($key2, $a)) {
			assertType('numeric-string', $key2);
		}
		if (array_key_exists($key3, $a)) {
			assertType('int', $key3);
		}
		if (array_key_exists($key4, $a)) {
			assertType('int', $key4);
		}
		if (array_key_exists($key5, $a)) {
			assertType('int', $key5);
		}

		if (array_key_exists($key1, $b)) {
			assertType('*NEVER*', $key1);
		}
		if (array_key_exists($key2, $b)) {
			assertType('string', $key2);
		}
		if (array_key_exists($key3, $b)) {
			assertType('string', $key3);
		}
		if (array_key_exists($key4, $b)) {
			assertType('string', $key4);
		}
		if (array_key_exists($key5, $b)) {
			assertType('string', $key5);
		}

		if (array_key_exists($key1, $c)) {
			assertType('int', $key1);
		}
		if (array_key_exists($key2, $c)) {
			assertType('string', $key2);
		}
		if (array_key_exists($key3, $c)) {
			assertType('(int|string)', $key3);
		}
		if (array_key_exists($key4, $c)) {
			assertType('(int|string)', $key4);
		}
		if (array_key_exists($key5, $c)) {
			assertType('(int|string)', $key5);
		}
	}

}
