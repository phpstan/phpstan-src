<?php declare(strict_types = 1);

namespace Bug10349Nsrt;

use function PHPStan\Testing\assertType;

class Foo
{
	/**
	 * @param array<string, array<string, bool|float|int|string>> $expected
	 */
	public function testTypePreservationAfterErrorAssignOp(array $expected, int $ptr, string $key): void
	{
		assertType('array<string, array<string, bool|float|int|string>>', $expected);

		$expected[$key]['number-1'] += $ptr;

		// After += with ErrorType result, the array type should be preserved
		assertType('bool|float|int|string', $expected[$key]['number-2']);
	}

	/**
	 * @param array<string, bool|float|int|string> $arr
	 */
	public function testSimpleArrayTypePreservation(array $arr, int $ptr): void
	{
		assertType('bool|float|int|string', $arr['a']);

		$arr['a'] += $ptr;

		// After += with ErrorType result, sibling keys should keep their type
		assertType('bool|float|int|string', $arr['b']);
	}
}
