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
		assertType('non-empty-array<decimal-int-string, 1>', $a);

		assertType('bool', (bool) $s);

		assertType('int', $s + $s);
	}

	/**
	 * @param non-decimal-int-string $s
	 */
	public function doBar(string $s): void
	{
		assertType('non-decimal-int-string' ,$s);
		$a = [$s => 1];
		assertType('non-empty-array<non-decimal-int-string, 1>', $a);

		assertType('bool', (bool) $s);

		assertType('float|int', $s + $s);
	}

	public function doBaz(string $s): void
	{
		$a = [$s => 1];
		assertType('non-empty-array<string, 1>', $a);

		$b = [];
		$b[$s] = 2;
		assertType('non-empty-array<string, 2>', $b);
	}

	/**
	 * @param non-decimal-int-string $s
	 */
	public function emptyStringIsNonDecimal(string $s): void
	{
		if ($s === '') {
			assertType("''", $s); // '' is a valid non-decimal-int-string
		}
	}

}
