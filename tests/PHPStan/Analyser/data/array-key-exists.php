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
	public function doFoo(array $a, string $key): void
	{
		assertType('false', array_key_exists(2, $a));
		assertType('bool', array_key_exists('foo', $a));
		assertType('false', array_key_exists('2', $a));

		$a = ['foo' => 2, 3 => 'bar'];
		assertType('true', array_key_exists('foo', $a));
		assertType('true', array_key_exists('3', $a));
		assertType('false', array_key_exists(4, $a));

		$empty = [];
		assertType('false', array_key_exists('foo', $empty));
		assertType('false', array_key_exists($key, $empty));
	}

}
