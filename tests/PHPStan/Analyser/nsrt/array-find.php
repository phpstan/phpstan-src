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
	 */
	function testMixed(array $array, callable $callback): void
	{
		assertType('mixed', array_find($array, $callback));
		assertType('int|null', array_find($array, 'is_int'));
	}

	/**
	 * @param array{1, 'foo', \DateTime} $array
	 */
	function testConstant(array $array, callable $callback): void
	{
		assertType("1|'foo'|DateTime|null", array_find($array, $callback));
		assertType("1|null", array_find($array, 'is_int'));
	}

}
