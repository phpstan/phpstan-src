<?php

namespace SetConstantUnionOffsetOnConstantArray;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array{foo: int}  $a
	 */
	public function doFoo(array $a): void
	{
		$k = rand(0, 1) ? 'a' : 'b';
		$a[$k] = 256;
		assertType('array{foo: int, a: 256}|array{foo: int, b: 256}', $a);
	}

	/**
	 * @param array{foo: int}  $a
	 * @param int<1,5> $intRange
	 */
	public function doBar(array $a, $intRange): void
	{
		$a[$intRange] = 256;
		assertType('array{foo: int, 1: 256}|array{foo: int, 2: 256}|array{foo: int, 3: 256}|array{foo: int, 4: 256}|array{foo: int, 5: 256}', $a);
	}

	/**
	 * @param array{foo: int}  $a
	 * @param int<0, max> $intRange
	 */
	public function doInfiniteRange(array $a, $intRange): void
	{
		$a[$intRange] = 256;
		assertType('non-empty-array<\'foo\'|int<0, max>, int>', $a);
	}

	/**
	 * @param array{foo: int}  $a
	 * @param int<0, 5>|int<10, 15> $intRange
	 */
	public function doUnionOfRanges(array $a, $intRange): void
	{
		$a[$intRange] = 256;
		assertType('non-empty-array<\'foo\'|int<0, 5>|int<10, 15>, int>', $a);
	}

	/**
	 * @param array{0: 'a', 1: 'b'}  $a
	 * @param int<0,1> $intRange
	 */
	public function doExistingKeys(array $a, $intRange): void
	{
		$a[$intRange] = 'c';
		assertType("array{'a'|'c', 'b'|'c'}", $a);
	}

}
