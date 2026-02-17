<?php declare(strict_types = 1);

namespace Bug13637;

use function PHPStan\Testing\assertType;

/**
 * @return array<int, array<int, array<int, array{abc: int, def: int, ghi: int}>>>
 */
function doesNotWork() : array {
	$final = [];

	for ($i = 0; $i < 5; $i++) {
		$j = $i * 2;
		$k = $j + 1;
		$final[$i][$j][$k]['abc'] = $i;
		$final[$i][$j][$k]['def'] = $i;
		$final[$i][$j][$k]['ghi'] = $i;
	}

	assertType("non-empty-array<int<0, 4>, non-empty-array<int<0, 8>, non-empty-array<int<1, 9>, array{abc: int<0, 4>, def: int<0, 4>, ghi: int<0, 4>}>>>", $final);

	return $final;
}

/**
 * @return array<int, array<int, array{abc: int, def: int, ghi: int}>>
 */
function thisWorks() : array {
	$final = [];

	for ($i = 0; $i < 5; $i++) {
		$j = $i * 2;
		$k = $j + 1;
		$final[$i][$j]['abc'] = $i;
		$final[$i][$j]['def'] = $i;
		$final[$i][$j]['ghi'] = $i;
	}

	assertType("non-empty-array<int<0, 4>, non-empty-array<int<0, 8>, array{abc: int<0, 4>, def: int<0, 4>, ghi: int<0, 4>}>>", $final);

	return $final;
}
