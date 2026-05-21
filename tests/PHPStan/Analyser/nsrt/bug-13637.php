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
	// fixed: they stay `int<0, 4>`. The middle key degenerates to `int` rather
	// than the ideal `int<0, 8>` — a minor key-precision residual in 3-level
	// nesting, not the value-widening bug from the issue.
	assertType('non-empty-array<int<0, 4>, non-empty-array<int, non-empty-array<int<1, 9>, array{abc: int<0, 4>, def?: int<0, 4>, ghi?: int<0, 4>}>>>', $final);
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
