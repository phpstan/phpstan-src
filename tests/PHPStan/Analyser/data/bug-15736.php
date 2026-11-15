<?php declare(strict_types = 1);

namespace Bug15736;

use stdClass;
use function PHPStan\Testing\assertType;

/** @return array{value: int}|false */
function fetchRow(object $result)
{
	return rand(0, 1) === 1 ? ['value' => 1] : false;
}

function query(): object
{
	return new stdClass();
}

function doFoo(object $rows): void
{
	$values = [];
	$header = 0;
	$cols = 0;
	$rowCount = 0;
	$items = query();
	while ($row = fetchRow($rows)) {
		$header++;
		if ($header === 1) {
			while ($item = fetchRow($items)) {
				$cols++;
			}
		}

		$col = 0;
		$items = query();
		while ($item = fetchRow($items)) {
			$values[$col][$rowCount] = $item['value'];
			$col++;
		}
		$rowCount++;
	}

	assertType('array<int<0, max>, non-empty-array<int<0, max>, int>>', $values);
	for ($col = 0; $col < $cols; $col++) {
		assertType('array<int<0, max>, non-empty-array<int<0, max>, int>>', $values);
		for ($r = 0; $r < $rowCount; $r++) {
			echo $values[$col][$r];
		}
	}
}
