<?php

namespace KeyExists;

use function key_exists;
use function PHPStan\Testing\assertType;

class KeyExists
{
	/**
	 * @param array<string, string> $a
	 * @return void
	 */
	public function doFoo(array $a, string $key, int $anotherKey): void
	{
		assertType('false', key_exists(2, $a));
		assertType('bool', key_exists('foo', $a));
		assertType('false', key_exists('2', $a));

		$b = ['foo' => 2, 3 => 'bar'];
		assertType('true', key_exists('foo', $b));
		assertType('true', key_exists(3, $b));
		assertType('true', key_exists('3', $b));
		assertType('false', key_exists(4, $b));

		if (array_key_exists($key, $b)) {
			assertType("'3'|'foo'", $key);
		}
		if (array_key_exists($anotherKey, $b)) {
			assertType('3', $anotherKey);
		}

		$empty = [];
		assertType('false', key_exists('foo', $empty));
		assertType('false', key_exists($key, $empty));
	}
}
