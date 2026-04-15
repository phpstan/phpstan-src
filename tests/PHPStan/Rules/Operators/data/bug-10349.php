<?php declare(strict_types = 1);

namespace Bug10349;

class Foo
{
	/**
	 * @param array<string, array<string, bool|float|int|string>> $expected
	 */
	public function issue1A(array $expected, int $ptr): void
	{
		foreach ($expected as $key => $param) {
			if ($param['number-1'] !== false) {
				// This gets flagged
				$expected[$key]['number-1'] += $ptr;
			}

			if ($param['number-2'] !== false) {
				// This should also get flagged but doesn't
				$expected[$key]['number-2'] += $ptr;
			}
		}
	}

	/**
	 * @param array<string, array<string, bool|float|int|string>> $expected
	 */
	public function issue1B(array $expected, int $ptr): void
	{
		foreach ($expected as $key => $param) {
			if (is_int($expected[$key]['number-1'])) {
				$expected[$key]['number-1'] += $ptr;
			}

			// Even after fixing the first, the second one still doesn't get flagged
			if ($param['number-2'] !== false) {
				$expected[$key]['number-2'] += $ptr;
			}
		}
	}

	/**
	 * @param array<string, array<string, bool|float|int|string>> $expected
	 */
	public function multipleOpsNoLoop(array $expected, int $ptr, string $key): void
	{
		$expected[$key]['number-1'] += $ptr;
		// After the first += corrupts the array type, this should still be flagged
		$expected[$key]['number-2'] += $ptr;
	}

	/**
	 * @param array<string, bool|float|int|string> $arr
	 */
	public function simpleArray(array $arr, int $ptr): void
	{
		$arr['a'] += $ptr;
		// After the first += corrupts the array type, this should still be flagged
		$arr['b'] += $ptr;
	}

	/**
	 * @param array<string, bool|float|int|string> $arr
	 */
	public function otherAssignOps(array $arr, int $ptr): void
	{
		$arr['a'] -= $ptr;
		$arr['b'] -= $ptr;
		$arr['c'] *= $ptr;
		$arr['d'] *= $ptr;
	}

	/**
	 * @param array<string, array<int>|int> $arr
	 */
	public function concatAssignOps(array $arr): void
	{
		$arr['a'] .= 'foo';
		$arr['b'] .= 'foo';
	}

	/**
	 * @param array<string, bool|float|int|string> $arr
	 */
	public function divAndModAssignOps(array $arr, int $ptr): void
	{
		$arr['a'] /= $ptr;
		$arr['b'] /= $ptr;
		$arr['c'] %= $ptr;
		$arr['d'] %= $ptr;
	}

	/**
	 * @param array<string, bool|float|int|string> $arr
	 */
	public function bitwiseAssignOps(array $arr, int $ptr): void
	{
		$arr['a'] <<= $ptr;
		$arr['b'] <<= $ptr;
	}
}
