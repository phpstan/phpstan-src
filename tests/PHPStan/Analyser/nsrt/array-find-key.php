<?php // lint >= 8.4

namespace ArrayFindKey
{

	use function PHPStan\Testing\assertType;

	/**
	 * @param array<mixed> $array
	 * @phpstan-ignore missingType.callable
	 */
	function testMixed(array $array, callable $callback): void
	{
		assertType('int|string|null', array_find_key($array, $callback));
		assertType('int|string|null', array_find_key($array, 'is_int'));
	}

	/**
	 * @param array{1, 'foo', \DateTime} $array
	 * @phpstan-ignore missingType.callable
	 */
	function testConstant(array $array, callable $callback): void
	{
		assertType("0|1|2|null", array_find_key($array, $callback));
		assertType("0|1|2|null", array_find_key($array, 'is_int'));
	}

	function testCallback(): void
	{
		$subject = ['foo' => 1, 'bar' => null, 'buz' => ''];
		$result = array_find_key($subject, function ($value, $key) {
			assertType("array{value: 1|''|null, key: 'bar'|'buz'|'foo'}", compact('value', 'key'));

			return is_int($value);
		});

		assertType("'bar'|'buz'|'foo'|null", $result);
	}

}
