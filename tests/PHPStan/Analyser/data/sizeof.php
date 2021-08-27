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

	/**
	 * @param string[] $arr
	 */
	function doFoo3(array $arr): string
	{
		if (0 != count($arr)) {
			assertType('string', reset($arr));
		}
		return "";
	}

	/**
	 * @param string[] $arr
	 */
	function doFoo4(array $arr): string
	{
		if (0 != sizeof($arr)) {
			assertType('string', reset($arr));
		}
		return "";
	}
}
