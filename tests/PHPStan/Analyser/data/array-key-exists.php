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

}
