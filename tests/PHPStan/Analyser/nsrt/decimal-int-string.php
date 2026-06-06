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

	/**
	 * @param decimal-int-string $s
	 */
	public function removingZeroMakesNonFalsy(string $s): void
	{
		if ($s !== '0') {
			assertType('decimal-int-string&non-falsy-string', $s);
		} else {
			assertType("'0'", $s);
		}

		if ($s != '0') {
			assertType('decimal-int-string&non-falsy-string', $s);
		}

		if ($s) {
			assertType('decimal-int-string&non-falsy-string', $s);
		} else {
			assertType("'0'", $s);
		}
	}

	/**
	 * @param numeric-string $s
	 */
	public function removingZeroMakesNumericNonFalsy(string $s): void
	{
		if ($s !== '0') {
			assertType('non-falsy-string&numeric-string', $s);
		} else {
			assertType("'0'", $s);
		}
	}

	/**
	 * @param non-decimal-int-string $s
	 */
	public function removingZeroFromNonDecimal(string $s): void
	{
		// '0' is a decimal-int-string, so it is not part of non-decimal-int-string
		if ($s !== '0') {
			assertType('non-decimal-int-string', $s);
		}
	}

	/**
	 * @param decimal-int-string $s
	 */
	public function unionWithZeroRoundTrips(string $s): void
	{
		$t = $s !== '0' ? $s : '0';
		assertType('decimal-int-string', $t);
	}

	/**
	 * @param numeric-string $s
	 */
	public function numericUnionWithZeroRoundTrips(string $s): void
	{
		$t = $s !== '0' ? $s : '0';
		assertType('numeric-string', $t);
	}

	/**
	 * @param non-empty-string $s
	 */
	public function nonEmptyUnionWithZeroRoundTrips(string $s): void
	{
		$t = $s !== '0' ? $s : '0';
		assertType('non-empty-string', $t);
	}

	/**
	 * @param uppercase-string $s
	 */
	public function removingZeroFromUppercase(string $s): void
	{
		// '' is a falsy uppercase-string too, so removing '0' alone does not
		// make an uppercase-string non-falsy.
		if ($s !== '0') {
			assertType('uppercase-string', $s);
		}
	}

	/**
	 * @param uppercase-string $s
	 */
	public function uppercaseUnionWithZeroRoundTrips(string $s): void
	{
		$t = $s !== '0' ? $s : '0';
		assertType('uppercase-string', $t);
	}

	/**
	 * @param lowercase-string $s
	 */
	public function removingZeroFromLowercase(string $s): void
	{
		// '' is a falsy lowercase-string too, so removing '0' alone does not
		// make a lowercase-string non-falsy.
		if ($s !== '0') {
			assertType('lowercase-string', $s);
		}
	}

	/**
	 * @param lowercase-string $s
	 */
	public function lowercaseUnionWithZeroRoundTrips(string $s): void
	{
		$t = $s !== '0' ? $s : '0';
		assertType('lowercase-string', $t);
	}

}
