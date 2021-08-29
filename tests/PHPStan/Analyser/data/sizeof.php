<?php

namespace sizeof;

use function PHPStan\Testing\assertType;


class Sizeof
{
	/**
	 * @param int[] $ints
	 */
	function doFoo1(array $ints): string
	{
		if (count($ints) <= 0) {
			assertType('false', min($ints));
			assertType('false', max($ints));
		}
	}

	/**
	 * @param int[] $ints
	 */
	function doFoo2(array $ints): string
	{
		if (sizeof($ints) <= 0) {
			assertType('false', min($ints));
			assertType('false', max($ints));
		}
	}

	function doFoo3(array $arr): string
	{
		if (0 != count($arr)) {
			assertType('array&nonEmpty', $arr);
		}
		return "";
	}

	function doFoo4(array $arr): string
	{
		if (0 != sizeof($arr)) {
			assertType('array&nonEmpty', $arr);
		}
		return "";
	}

	function doFoo5(array $arr): void
	{
		if ([] != $arr) {
			assertType('array&nonEmpty', $arr);
		}
		assertType('array', $arr);
	}

	function doFoo6(array $arr): void
	{
		if ($arr != []) {
			assertType('array&nonEmpty', $arr);
		}
		assertType('array', $arr);
	}
}
