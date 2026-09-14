<?php declare(strict_types = 1);

namespace Bug14464;

use LogicException;
use RuntimeException;
use function count;
use function preg_last_error_msg;
use function preg_split;
use function sprintf;
use function strtolower;
use function PHPStan\Testing\assertType;

class HelloWorld
{

	protected function columnOrAlias(string $columnName): void
	{
		$colParts = preg_split('/\s+/', $columnName, -1, \PREG_SPLIT_NO_EMPTY);
		if ($colParts === false) {
			throw new RuntimeException(preg_last_error_msg());
		}
		$numParts = count($colParts);

		if ($numParts == 3) {
			assertType('array{non-empty-string, non-empty-string, non-empty-string}', $colParts);
			// columnAbc as aliasName
			$this->columnName($colParts[0]);
			if (strtolower($colParts[1]) !== 'as') {
				throw new LogicException(sprintf('"%s" is not a valid column name or alias', $columnName));
			}
			$this->columnName($colParts[2]);
		} elseif ($numParts == 2) {
			assertType('array{non-empty-string, non-empty-string}', $colParts);
			// columnAbc aliasName
			$this->columnName($colParts[0]);
			$this->columnName($colParts[1]);
		} elseif ($numParts == 1) {
			assertType('array{non-empty-string}', $colParts);
			if ($colParts[0] !== '*') {
				// columnAbc
				$this->columnName($colParts[0]);
			}
		} else {
			throw new LogicException(sprintf('"%s" is not a valid column or alias', $columnName));
		}
		assertType('list{0: non-empty-string, 1?: non-empty-string, 2?: non-empty-string}', $colParts);
	}

	public function columnName(string $columnName): string
	{
		return 'abc';
	}

}
