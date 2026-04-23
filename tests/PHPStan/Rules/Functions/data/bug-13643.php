<?php declare(strict_types = 1);

namespace Bug13643;

/**
 * @param list<array<string, mixed>> $records
 */
function testA(array $records): void {
	foreach ($records as $record) {
		foreach (['aaa', 'bbb', 'ccc', 'ddd'] as $col) {
			if (!array_key_exists($col, $record) || !is_int($record[$col])) {
				$record[$col] = 0;
			}
		}

		useRecord(
			$record['aaa'],
			$record['bbb'],
			$record['ccc'],
			$record['ddd'],
		);
	}
}

/**
 * @param list<array<string, mixed>> $records
 */
function testB(array $records): void {
	foreach ($records as $record) {
		foreach (['aaa', 'bbb', 'ccc', 'ddd'] as $col) {
			if (array_key_exists($col, $record) && is_int($record[$col])) {
				continue;
			}
			$record[$col] = 0;
		}

		useRecord(
			$record['aaa'],
			$record['bbb'],
			$record['ccc'],
			$record['ddd'],
		);
	}
}

function useRecord(int ...$v): void {}
