<?php declare(strict_types=1);

namespace Shopware\Core\Profiling\Doctrine;

use function PHPStan\Testing\assertType;

class ParameterType {}

/**
 * @phpstan-type SanitizedQueryInfo array{sql: string, executionMS: float, types: array<(int | string), ParameterType|int>}
 */
abstract class ConnectionProfiler
{
	/**
	 * @return array<string, array<int, SanitizedQueryInfo>>
	 */
	abstract public function getQueries(): array;

	public function getGroupedQueries(): void
	{
		foreach ($this->getQueries() as $queries) {
			$connectionGroupedQueries = [];
			foreach ($queries as $i => $query) {
				$key = $query['sql'];
				if (!isset($connectionGroupedQueries[$key])) {
					$connectionGroupedQueries[$key] = $query;
					$connectionGroupedQueries[$key]['executionMS'] = 0;
					$connectionGroupedQueries[$key]['count'] = 0;
					$connectionGroupedQueries[$key]['index'] = $i; // "Explain query" relies on query index in 'queries'.
				}

				assertType('non-empty-array<string, array{sql: string, executionMS: float, types: array<int|string, int|Shopware\Core\Profiling\Doctrine\ParameterType>, count: int<1, max>, index: int}>|non-empty-array<string, array{sql: string, executionMS: 0, types: array<int|string, int|Shopware\Core\Profiling\Doctrine\ParameterType>, count: 0, index: int}>', $connectionGroupedQueries);
				$connectionGroupedQueries[$key]['executionMS'] += $query['executionMS'];
				assertType("non-empty-array<string, array{sql: string, executionMS: float, types: array<int|string, int|Shopware\Core\Profiling\Doctrine\ParameterType>, count: int<0, max>, index: int}>", $connectionGroupedQueries);
				++$connectionGroupedQueries[$key]['count'];
			}

			assertType("array<string, array{sql: string, executionMS: float, types: array<int|string, int|Shopware\Core\Profiling\Doctrine\ParameterType>, count: int<1, max>, index: int}>", $connectionGroupedQueries);
			usort($connectionGroupedQueries, static fn (array $a, array $b): int => $b['executionMS'] <=> $a['executionMS']);
		}
	}

}
