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
		$k = $j +1;
		$l = $i * 3;
		$final[$i][$j][$k]['abc'] = $i;
		$final[$i][$j][$k]['def'] = $i;
		$final[$i][$j][$k]['ghi'] = $i;

		assertType("array{abc: int<0, 4>, def: int<0, 4>, ghi: int<0, 4>}", $final[$i][$j][$k]);
		assertType("non-empty-array<int<0, 4>, non-empty-array<int<0, 8>, non-empty-array<int<1, 9>, array{abc: int<0, 4>, def: int<0, 4>, ghi: int<0, 4>}>>>", $final);
	}

	assertType("non-empty-array<int<0, 4>, non-empty-array<int<0, 8>, non-empty-array<int<1, 9>, array{abc: int<0, 4>, def: int<0, 4>, ghi: int<0, 4>}>>>", $final);
	return $final;
}

/**
* @return array<int, array<int, array<int, array<int, array{abc: int, def: int, ghi: int}>>>>
*/
function fourLevelsDeep() : array {
	$final = [];

	for ($i = 0; $i < 5; $i++) {
		$j = $i * 2;
		$k = $j + 1;
		$l = $i * 3;
		$final[$i][$j][$k][$l]['abc'] = $i;
		$final[$i][$j][$k][$l]['def'] = $i;
		$final[$i][$j][$k][$l]['ghi'] = $i;

		assertType("array{abc: int<0, 4>, def: int<0, 4>, ghi: int<0, 4>}", $final[$i][$j][$k][$l]);
		assertType("non-empty-array<int<0, 4>, non-empty-array<int<0, 8>, non-empty-array<int<1, 9>, non-empty-array<int<0, 12>, array{abc: int<0, 4>, def: int<0, 4>, ghi: int<0, 4>}>>>>", $final);
	}

	assertType("non-empty-array<int<0, 4>, non-empty-array<int<0, 8>, non-empty-array<int<1, 9>, non-empty-array<int<0, 12>, array{abc: int<0, 4>, def: int<0, 4>, ghi: int<0, 4>}>>>>", $final);
	return $final;
}

/**
* @return array<int, array<int, array<int, array<int, array<int, array{abc: int, def: int, ghi: int}>>>>>
*/
function fiveLevelsDeep() : array {
	$final = [];

	for ($i = 0; $i < 5; $i++) {
		$j = $i * 2;
		$k = $j + 1;
		$l = $i * 3;
		$m = $i + 10;
		$final[$i][$j][$k][$l][$m]['abc'] = $i;
		$final[$i][$j][$k][$l][$m]['def'] = $i;
		$final[$i][$j][$k][$l][$m]['ghi'] = $i;

		assertType("array{abc: int<0, 4>, def: int<0, 4>, ghi: int<0, 4>}", $final[$i][$j][$k][$l][$m]);
		assertType("non-empty-array<int<0, 4>, non-empty-array<int<0, 8>, non-empty-array<int<1, 9>, non-empty-array<int<0, 12>, non-empty-array<int<10, 14>, array{abc: int<0, 4>, def: int<0, 4>, ghi: int<0, 4>}>>>>>", $final);
	}

	assertType("non-empty-array<int<0, 4>, non-empty-array<int<0, 8>, non-empty-array<int<1, 9>, non-empty-array<int<0, 12>, non-empty-array<int<10, 14>, array{abc: int<0, 4>, def: int<0, 4>, ghi: int<0, 4>}>>>>>", $final);
	return $final;
}

/**
* @return array<int, array<int, array<int, array<int, array<int, array<int, array{abc: int, def: int, ghi: int}>>>>>>
*/
function sixLevelsDeep() : array {
	$final = [];

	for ($i = 0; $i < 5; $i++) {
		$j = $i * 2;
		$k = $j + 1;
		$l = $i * 3;
		$m = $i + 10;
		$n = $i + 20;
		$final[$i][$j][$k][$l][$m][$n]['abc'] = $i;
		$final[$i][$j][$k][$l][$m][$n]['def'] = $i;
		$final[$i][$j][$k][$l][$m][$n]['ghi'] = $i;

		assertType("array{abc: int<0, 4>, def: int<0, 4>, ghi: int<0, 4>}", $final[$i][$j][$k][$l][$m][$n]);
		assertType("non-empty-array<int<0, 4>, non-empty-array<int<0, 8>, non-empty-array<int<1, 9>, non-empty-array<int<0, 12>, non-empty-array<int<10, 14>, non-empty-array<int<20, 24>, array{abc: int<0, 4>, def: int<0, 4>, ghi: int<0, 4>}>>>>>>", $final);
	}

	assertType("non-empty-array<int<0, 4>, non-empty-array<int<0, 8>, non-empty-array<int<1, 9>, non-empty-array<int<0, 12>, non-empty-array<int<10, 14>, non-empty-array<int<20, 24>, array{abc: int<0, 4>, def: int<0, 4>, ghi: int<0, 4>}>>>>>>", $final);
	return $final;
}

/** Tests that maybe-array item type (union with non-array) skips the recursive path */
function maybeArrayItemType(bool $flag): void {
	$final = [];

	for ($i = 0; $i < 5; $i++) {
		$j = $i * 2;
		$k = $j + 1;
		if ($flag) {
			$final[$i][$j][$k]['abc'] = $i;
			$final[$i][$j][$k]['def'] = $i;
		} else {
			$final[$i] = $i;
		}
	}

	assertType("non-empty-array<int<0, 4>, non-empty-array<int<0, 8>, non-empty-array<int<1, 9>, array{abc: int<0, 4>, def: int<0, 4>}>>|int<0, 4>>", $final);
}

/**
* @return array<int, array<int, array{abc: int, def: int, ghi: int}>>
*/
function thisWorks() : array {
	$final = [];

	for ($i = 0; $i < 5; $i++) {
		$j = $i * 2;
		$k = $j +1;
		$l = $i * 3;
		$final[$i][$j]['abc'] = $i;
		$final[$i][$j]['def'] = $i;
		$final[$i][$j]['ghi'] = $i;

		assertType("array{abc: int<0, 4>, def: int<0, 4>, ghi: int<0, 4>}", $final[$i][$j]);
		assertType("non-empty-array<int<0, 4>, non-empty-array<int<0, 8>, array{abc: int<0, 4>, def: int<0, 4>, ghi: int<0, 4>}>>", $final);
	}

	assertType("non-empty-array<int<0, 4>, non-empty-array<int<0, 8>, array{abc: int<0, 4>, def: int<0, 4>, ghi: int<0, 4>}>>", $final);
	return $final;
}
