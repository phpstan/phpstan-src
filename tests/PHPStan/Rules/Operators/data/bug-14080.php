<?php declare(strict_types = 1);

namespace Bug14080;

class Foo
{
	/**
	 * @param list<array{sql: string, time: int}> $queries
	 */
	public function doFoo(array $queries): void
	{
		$queryTotals = ['all' => 0, 'duplicates' => 0];
		$queryTypes = ['select', 'update', 'delete', 'insert'];

		$queryTotals['time'] = array_sum(array_column($queries, 'time'));

		foreach ($queryTypes as $type) {
			$tq = array_filter($queries, fn ($v) => str_starts_with(strtolower($v['sql']), $type));
			$queryTotals['all'] += count($tq);
			$queryTotals[$type] = [
				'count' => count($tq),
			];
		}
	}
}
