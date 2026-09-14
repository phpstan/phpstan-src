<?php declare(strict_types = 1);

namespace Bug13637;

use function PHPStan\Testing\assertType;

function doesNotWork(): void
{
	$final = [];

	for ($i = 0; $i < 5; $i++) {
		$j = $i * 2;
		$k = $j + 1;
		$final[$i][$j][$k]['abc'] = $i;
		$final[$i][$j][$k]['def'] = $i;
		$final[$i][$j][$k]['ghi'] = $i;
	}

	// The reported regression (innermost values widening to `int<0, max>`) is
	// fixed: they stay `int<0, 4>`, and the middle key keeps the loop's
	// `int<0, 8>` bound now that widening no longer expands optional-key
	// shapes into every variant before merging them back.
	assertType('non-empty-array<int<0, 4>, non-empty-array<int<0, 8>, non-empty-array{3?: array{abc: int<0, 4>, def?: int<0, 4>, ghi?: int<0, 4>}, 1?: array{abc: int<0, 4>, def?: int<0, 4>, ghi?: int<0, 4>}, 2?: array{abc: int<0, 4>, def?: int<0, 4>, ghi?: int<0, 4>}, 4?: array{abc: int<0, 4>, def?: int<0, 4>, ghi?: int<0, 4>}, 5?: array{abc: int<0, 4>, def?: int<0, 4>, ghi?: int<0, 4>}, 6?: array{abc: int<0, 4>, def?: int<0, 4>, ghi?: int<0, 4>}, 7?: array{abc: int<0, 4>, def?: int<0, 4>, ghi?: int<0, 4>}, 8?: array{abc: int<0, 4>, def?: int<0, 4>, ghi?: int<0, 4>}, 9?: array{abc: int<0, 4>, def?: int<0, 4>, ghi?: int<0, 4>}}>>', $final);
}

function thisWorks(): void
{
	$final = [];

	for ($i = 0; $i < 5; $i++) {
		$j = $i * 2;
		$final[$i][$j]['abc'] = $i;
		$final[$i][$j]['def'] = $i;
		$final[$i][$j]['ghi'] = $i;
	}

	assertType('non-empty-array<int<0, 4>, non-empty-array<int<0, 8>, array{abc: int<0, 4>, def: int<0, 4>, ghi: int<0, 4>}>>', $final);
}
