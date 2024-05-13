<?php

namespace DynamicSprintf;

use function PHPStan\Testing\assertType;
use function sprintf;

class Foo
{

	/**
	 * @param 'a'|'aa' $a
	 * @param 'b'|'bb' $b
	 * @param 'c'|'cc' $c
	 */
	public function doFoo(string $a, string $b, string $c): void
	{
		assertType("'a b c'|'a b cc'|'a bb c'|'a bb cc'|'aa b c'|'aa b cc'|'aa bb c'|'aa bb cc'", sprintf('%s %s %s', $a, $b, $c));
	}

	/**
	 * @param int<0,3> $a
	 * @param 'b'|'bb' $b
	 */
	public function integerRange(int $a, string $b): void
	{
		assertType("'0 b'|'0 bb'|'1 b'|'1 bb'|'2 b'|'2 bb'|'3 b'|'3 bb'", sprintf('%d %s', $a, $b));
	}

	/**
	 * @param int<0,64> $a
	 * @param 'b'|'bb' $b
	 */
	public function tooBigRange(int $a, string $b): void
	{
		assertType("non-falsy-string", sprintf('%d %s', $a, $b));
	}

}
