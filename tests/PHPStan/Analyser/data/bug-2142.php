<?php

namespace Bug2142;

use function PHPStan\Testing\assertType;

class Foo
{

	function doFoo1(array $arr): void
	{
		if (count($arr) > 0) {
			assertType('array&nonEmpty', $arr);
		}
	}

	/**
	 * @param string[] $arr
	 */
	function doFoo2(array $arr): void
	{
		if (count($arr) != 0) {
			assertType('array<string>&nonEmpty', $arr);
		}
	}

	/**
	 * @param string[] $arr
	 */
	function doFoo3(array $arr): void
	{
		if (count($arr) == 1) {
			assertType('array<string>&nonEmpty', $arr);
		}
	}

	/**
	 * @param string[] $arr
	 */
	function doFoo4(array $arr): void
	{
		if ($arr != []) {
			assertType('array<string>&nonEmpty', $arr);
		}
	}

	/**
	 * @param string[] $arr
	 */
	function doFoo5(array $arr): void
	{
		if (sizeof($arr) !== 0) {
			assertType('array<string>&nonEmpty', $arr);
		}
	}

	/**
	 * @param string[] $arr
	 */
	function doFoo6(array $arr): void
	{
		if (count($arr) !== 0) {
			assertType('array<string>&nonEmpty', $arr);
		}
	}


	/**
	 * @param string[] $arr
	 */
	function doFoo7(array $arr): void
	{
		if (!empty($arr)) {
			assertType('array<string>&nonEmpty', $arr);
		}
	}

	/**
	 * @param string[] $arr
	 */
	function doFoo8(array $arr): void
	{
		if (count($arr) === 1) {
			assertType('array<string>&nonEmpty', $arr);
		}
	}


	/**
	 * @param string[] $arr
	 */
	function doFoo9(array $arr): void
	{
		if ($arr !== []) {
			assertType('array<string>&nonEmpty', $arr);
		}
	}

}
