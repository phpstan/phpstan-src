<?php

namespace Bug2869;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array<string, string|null> $bar
	 */
	public function doFoo(array $bar): void
	{
		if (array_key_exists('foo', $bar)) {
			$foobar = isset($bar['foo']);
			assertType('bool' ,$foobar);
		}
	}

	/**
	 * @param array<string, string> $bar
	 */
	public function doBar(array $bar): void
	{
		if (array_key_exists('foo', $bar)) {
			$foobar = isset($bar['foo']);
			assertType('true' ,$foobar);
		}
	}

}
