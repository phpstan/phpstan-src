<?php // lint >= 8.4

namespace {

	if (!function_exists('array_find')) {
		function (array $array, callable $callback)
		{
			foreach ($array as $key => $value) {
				if ($callback($value, $key)) {
					return $value;
				}
			}

			return null;
		}
	}

}

namespace ArrayFind
{

	use function PHPStan\Testing\assertType;

	/**
	 * @param array<mixed> $array
	 * @param non-empty-array<mixed> $non_empty_array
	 */
	function testMixed(array $array, array $non_empty_array, callable $callback): void
	{
		assertType('mixed', array_find($array, $callback));
		assertType('int|null', array_find($array, 'is_int'));
		assertType('mixed', array_find($non_empty_array, $callback));
		assertType('int|null', array_find($non_empty_array, 'is_int'));
	}

	/**
	 * @param array{1, 'foo', \DateTime} $array
	 */
	function testConstant(array $array, callable $callback): void
	{
		assertType("1|'foo'|DateTime|null", array_find($array, $callback));
		assertType('1', array_find($array, 'is_int'));
	}

	/**
	 * @param array<int> $array
	 * @param non-empty-array<int> $non_empty_array
	 */
	function testInt(array $array, array $non_empty_array, callable $callback): void
	{
		assertType('int|null', array_find($array, $callback));
		assertType('int|null', array_find($array, 'is_int'));
		assertType('int|null', array_find($non_empty_array, $callback));
		// should be 'int'
		assertType('int|null', array_find($non_empty_array, 'is_int'));
	}

}
