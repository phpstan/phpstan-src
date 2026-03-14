<?php declare(strict_types = 1);

namespace Bug14080;

$queryTotals = ['all' => 0, 'duplicates' => 0];
$queryTypes = ['select', 'update', 'delete', 'insert'];
$queries = [['sql' => 'select', 'time' => 8234], ['sql' => 'select', 'time' => 4558], ['sql' => 'insert', 'time' => 9928]];

$queryTotals['time'] = array_sum(array_column($queries, 'time'));

foreach ($queryTypes as $type) {
	$tq = array_filter($queries, fn ($v) => str_starts_with(strtolower($v['sql']), $type));
	$tq_time = array_sum(array_column($tq, 'time'));
	$queryTotals['all'] += count($tq);
	$queryTotals[$type] = [
		'count' => count($tq),
		'time' => $tq_time / $queryTotals['time'] * 100,
	];
}
