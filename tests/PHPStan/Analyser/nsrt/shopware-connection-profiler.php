<?php declare(strict_types=1);

namespace Shopware\Core\Profiling\Doctrine;

use function PHPStan\Testing\assertType;

class Data {}
class ParameterType {}

/**
 * @phpstan-type Backtrace list<array{function: string, line?: int, file?: string, class?: class-string, type?: '->'|'::', args?: list<mixed>, object?: object}>
 * @phpstan-type QueryInfo array{
 *      sql: string,
 *      executionMS: float,
 *      types: array<int|string, int>,
 *      params:  array<mixed>,
 *      backtrace?: Backtrace
 *  }
 * @phpstan-type SanitizedQueryInfo array{sql: string, executionMS: float, types: array<(int | string), ParameterType|int>, params: Data, runnable: bool, explainable: bool, backtrace?: Backtrace}
 * @phpstan-type SanitizedQueryInfoGroup array{sql: string, executionMS: float, types: array<(int | string), ParameterType|int>, params: Data, runnable: bool, explainable: bool, backtrace?: Backtrace, count: int, index: int, executionPercent?: float}
 */
abstract class ConnectionProfiler
{
	/**
	 * @var ?array<string, array<int, SanitizedQueryInfoGroup>>
	 */
	public ?array $groupedQueries = null;

	/**
	 * @return array<string, array<int, SanitizedQueryInfo>>
	 */
	abstract public function getQueries(): array;

	/**
	 * @return array<string, array<int, SanitizedQueryInfoGroup>>
	 */
	public function getGroupedQueries(): array
	{
		if ($this->groupedQueries !== null) {
			return $this->groupedQueries;
		}

		$this->groupedQueries = [];
		$totalExecutionMS = 0;
		foreach ($this->getQueries() as $connection => $queries) {
			$connectionGroupedQueries = [];
			foreach ($queries as $i => $query) {
				$key = $query['sql'];
				if (!isset($connectionGroupedQueries[$key])) {
					$connectionGroupedQueries[$key] = $query;
					$connectionGroupedQueries[$key]['executionMS'] = 0;
					$connectionGroupedQueries[$key]['count'] = 0;
					$connectionGroupedQueries[$key]['index'] = $i; // "Explain query" relies on query index in 'queries'.
				}

				assertType("array<string, array{sql: string, executionMS: 0, types: array<int|string, int|Shopware\Core\Profiling\Doctrine\ParameterType>, params: Shopware\Core\Profiling\Doctrine\Data, runnable: bool, explainable: bool, backtrace?: list<array{function: string, line?: int, file?: string, class?: class-string, type?: '->'|'::', args?: list<mixed>, object?: object}>, count: 0, index: int}|array{sql: string, executionMS: float, types: array<int|string, int|Shopware\Core\Profiling\Doctrine\ParameterType>, params: Shopware\Core\Profiling\Doctrine\Data, runnable: bool, explainable: bool, backtrace?: list<array{function: string, line?: int, file?: string, class?: class-string, type?: '->'|'::', args?: list<mixed>, object?: object}>, count: int<1, max>, index: int}>", $connectionGroupedQueries);
				$connectionGroupedQueries[$key]['executionMS'] += $query['executionMS'];
				assertType("non-empty-array<string, array{sql: string, executionMS: float, types: array<int|string, int|Shopware\Core\Profiling\Doctrine\ParameterType>, params: Shopware\Core\Profiling\Doctrine\Data, runnable: bool, explainable: bool, backtrace?: list<array{function: string, line?: int, file?: string, class?: class-string, type?: '->'|'::', args?: list<mixed>, object?: object}>, count: int<0, max>, index: int}>", $connectionGroupedQueries);
				++$connectionGroupedQueries[$key]['count'];
				$totalExecutionMS += $query['executionMS'];
			}

			assertType("array<string, array{sql: string, executionMS: float, types: array<int|string, int|Shopware\Core\Profiling\Doctrine\ParameterType>, params: Shopware\Core\Profiling\Doctrine\Data, runnable: bool, explainable: bool, backtrace?: list<array{function: string, line?: int, file?: string, class?: class-string, type?: '->'|'::', args?: list<mixed>, object?: object}>, count: int<1, max>, index: int}>", $connectionGroupedQueries);
			usort($connectionGroupedQueries, static fn (array $a, array $b): int => $b['executionMS'] <=> $a['executionMS']);
			$this->groupedQueries[$connection] = $connectionGroupedQueries;
		}

		foreach ($this->groupedQueries as &$queries) {
			foreach ($queries as &$query) {
				$query['executionPercent'] = $this->executionTimePercentage($query['executionMS'], $totalExecutionMS);
			}
			unset($query);
		}
		unset($queries);

		assertType("list<array{sql: string, executionMS: float, types: array<int|string, int|Shopware\Core\Profiling\Doctrine\ParameterType>, params: Shopware\Core\Profiling\Doctrine\Data, runnable: bool, explainable: bool, backtrace?: list<array{function: string, line?: int, file?: string, class?: class-string, type?: '->'|'::', args?: list<mixed>, object?: object}>, count: int<1, max>, index: int}>", $connectionGroupedQueries);

		return $this->groupedQueries;
	}

	private function executionTimePercentage(float $executionTimeMS, float $totalExecutionTimeMS): float
	{
		if (!$totalExecutionTimeMS) {
			return 0;
		}

		return $executionTimeMS / $totalExecutionTimeMS * 100;
	}
}
