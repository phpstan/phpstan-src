<?php

namespace DecimalIntString;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param decimal-int-string $s
	 */
	public function doFoo(string $s): void
	{
		assertType('decimal-int-string' ,$s);
		$a = [$s => 1];
		assertType('non-empty-array<int, 1>', $a);

		assertType('bool', (bool) $s);
	}

	/**
	 * @param non-decimal-int-string $s
	 */
	public function doBar(string $s): void
	{
		assertType('non-decimal-int-string' ,$s);
		$a = [$s => 1];
		assertType('non-empty-array<non-decimal-int-string, 1>', $a);

		assertType('true', (bool) $s);
	}

}
