<?php

declare(strict_types=1);

namespace Bug12033;

use Iterator;
use PDO;
use PDOException;
use PHPStan\ShouldNotHappenException;
use function array_shift;
use function PHPStan\Testing\assertType;

/**
 * @phpstan-type ColumnMeta array{name: string, table: string, native_type: string, len: int, flags: array<int, string>, precision: int<0, max>, pdo_type: PDO::PARAM_* }
 * @phpstan-type PDOColumnMeta array{name: string, table?: string, native_type: string, len: int, flags: list<string>}
 */
final class PdoPgSqlQueryReflector
{
	/**
	 * @var array<string, PDOException|array<ColumnMeta>|null>
	 */
	protected array $cache = [];

	protected PDO $pdo;

	/**
	 * @return PDOException|list<PDOColumnMeta>|null
	 */
	protected function simulateQuery(string $queryString)
	{
		try {
			$stmt = $this->pdo->query($queryString);
		} catch (PDOException $e) {
			return $this->cache[$queryString] = $e;
		}

		if ($stmt === false) return null;

		$this->cache[$queryString] = [];
		$columnCount = $stmt->columnCount();
		$columnIndex = 0;
		while ($columnIndex < $columnCount) {
			$columnMeta = $stmt->getColumnMeta($columnIndex);

			if (false === $columnMeta || ! \array_key_exists('native_type', $columnMeta)) {
				throw new ShouldNotHappenException('Failed to get column meta for column index ' . $columnIndex);
			}

			$this->cache[$queryString][$columnIndex] = $columnMeta;
			++$columnIndex;
		}

		// the values of PDO::PARAM_* differ between platforms (PARAM_INPUT_OUTPUT is negative on Windows)
		assertType('true', is_array($this->cache[$queryString]));
		assertType('false', $this->cache[$queryString] instanceof PDOException);

		return $this->cache[$queryString];
	}

}

