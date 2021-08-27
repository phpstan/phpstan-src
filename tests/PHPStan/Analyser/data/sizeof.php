<?php

namespace sizeof;

use function PHPStan\Testing\assertType;


class x
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
}
