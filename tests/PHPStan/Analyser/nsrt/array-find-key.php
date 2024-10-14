<?php

namespace {

	if (!function_exists('array_find_key')) {
		function array_find_key(array $array, callable $callback)
		{
			foreach ($array as $key => $value) {
				if ($callback($value, $key)) {
					return $key;
				}
			}

			return null;
		}
	}

}

namespace ArrayFindKey
{

	use function PHPStan\Testing\assertType;

	/**
	 * @param array<mixed> $array
	 */
	function testMixed(array $array, callable $callback): void
	{
		assertType('int|string|null', array_find_key($array, $callback));
		assertType('int|string|null', array_find_key($array, 'is_int'));
	}

	/**
	 * @param array{1, 'foo', \DateTime} $array
	 */
	function testConstant(array $array, callable $callback): void
	{
		assertType("0|1|2|null", array_find_key($array, $callback));
		assertType("0|1|2|null", array_find_key($array, 'is_int'));
	}

}
