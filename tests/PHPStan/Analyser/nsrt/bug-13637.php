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
	}

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
	}

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
	}

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
	}

	return $final;
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
	}

	return $final;
}
