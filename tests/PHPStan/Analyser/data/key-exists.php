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
	public function doFoo(array $a, string $key): void
	{
		assertType('false', key_exists(2, $a));
		assertType('bool', key_exists('foo', $a));
		assertType('false', key_exists('2', $a));

		$a = ['foo' => 2, 3 => 'bar'];
		assertType('true', key_exists('foo', $a));
		assertType('true', key_exists('3', $a));
		assertType('false', key_exists(4, $a));

		$empty = [];
		assertType('false', key_exists('foo', $empty));
		assertType('false', key_exists($key, $empty));
	}
}
