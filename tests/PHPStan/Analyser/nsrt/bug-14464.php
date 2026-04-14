<?php declare(strict_types = 1);

namespace Bug14464;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/** Variable count with == (loose comparison from the issue) */
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
			$this->columnName($colParts[0]);
			$this->columnName($colParts[1]);
			$this->columnName($colParts[2]);
		} elseif ($numParts == 2) {
			assertType('array{non-empty-string, non-empty-string}', $colParts);
			$this->columnName($colParts[0]);
			$this->columnName($colParts[1]);
		} elseif ($numParts == 1) {
			assertType('array{non-empty-string}', $colParts);
			$this->columnName($colParts[0]);
		} else {
			throw new \LogicException('invalid');
		}
	}

	/** Variable count with === (strict comparison) */
	protected function strictComparison(string $input): void
	{
		$parts = preg_split('/,/', $input, -1, \PREG_SPLIT_NO_EMPTY);
		if ($parts === false) {
			throw new \RuntimeException('preg error');
		}
		$count = count($parts);

		if ($count === 3) {
			assertType('array{non-empty-string, non-empty-string, non-empty-string}', $parts);
		} elseif ($count === 1) {
			assertType('array{non-empty-string}', $parts);
		}
	}

	/**
	 * Variable count on a PHPDoc list type
	 * @param list<int> $items
	 */
	protected function phpdocList(array $items): void
	{
		$count = count($items);
		if ($count === 3) {
			assertType('array{int, int, int}', $items);
		} elseif ($count === 5) {
			assertType('array{int, int, int, int, int}', $items);
		} else {
			assertType('list<int>', $items);
		}
	}

	public function columnName(string $columnName): string
	{
		return 'abc';
	}
}
