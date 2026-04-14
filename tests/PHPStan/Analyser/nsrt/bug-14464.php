<?php declare(strict_types = 1);

namespace Bug14464;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	protected function columnOrAlias(string $columnName): void
	{
		$colParts = preg_split('/\s+/', $columnName, -1, \PREG_SPLIT_NO_EMPTY);
		if ($colParts === false) {
			throw new \RuntimeException('preg error');
		}
		assertType('list<non-empty-string>', $colParts);
		$numParts = count($colParts);

		if ($numParts == 3) {
			assertType('array{non-empty-string, non-empty-string, non-empty-string}', $colParts);
		} elseif ($numParts == 2) {
			assertType('array{non-empty-string, non-empty-string}', $colParts);
		} elseif ($numParts == 1) {
			assertType('array{non-empty-string}', $colParts);
		}
	}

	/** @param list<string> $list */
	public function indirectCountCheck(array $list): void
	{
		$n = count($list);
		if ($n === 3) {
			assertType('array{string, string, string}', $list);
		}
		if ($n === 2) {
			assertType('array{string, string}', $list);
		}
		if ($n === 1) {
			assertType('array{string}', $list);
		}
	}

	/** @param list<string> $list */
	public function directCountCheck(array $list): void
	{
		if (count($list) === 3) {
			assertType('array{string, string, string}', $list);
		}
		if (count($list) === 2) {
			assertType('array{string, string}', $list);
		}
		if (count($list) === 1) {
			assertType('array{string}', $list);
		}
	}

	/** @param list<string> $list */
	public function sizeofIndirect(array $list): void
	{
		$n = sizeof($list);
		if ($n === 2) {
			assertType('array{string, string}', $list);
		}
	}

	/** @param list<int> $list */
	public function looseEqualityCheck(array $list): void
	{
		$n = count($list);
		if ($n == 3) {
			assertType('array{int, int, int}', $list);
		}
	}

	/**
	 * Non-list arrays should not get specific shapes since keys are unknown
	 * @param array<string, int> $map
	 */
	public function nonListArray(array $map): void
	{
		$n = count($map);
		if ($n === 2) {
			assertType('non-empty-array<string, int>', $map);
		}
	}

	/** @param array{string}|array{string, string}|array{string, string, string} $list */
	public function constantArrayUnionIndirect(array $list): void
	{
		$n = count($list);
		if ($n === 2) {
			assertType('array{string, string}', $list);
		}
		if ($n === 3) {
			assertType('array{string, string, string}', $list);
		}
	}

	/** @param array{a: string, b: int}|array{x: float, y: float, z: float} $map */
	public function constantNonListDifferentShapes(array $map): void
	{
		$n = count($map);
		if ($n === 2) {
			assertType('array{a: string, b: int}', $map);
		}
		if ($n === 3) {
			assertType('array{x: float, y: float, z: float}', $map);
		}
	}
}
