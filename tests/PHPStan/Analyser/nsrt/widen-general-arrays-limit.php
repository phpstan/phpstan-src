<?php // lint >= 8.0

namespace WidenGeneralArraysLimit;

use function PHPStan\Testing\assertType;

/**
 * @param array<int> $a
 * @param array<string> $b
 */
function joinsKeepArraysApart(bool $c, array $a, array $b): void
{
	if ($c) {
		$x = $a;
	} else {
		$x = $b;
	}
	assertType('array<int>|array<string>', $x);

	foreach ([1, 2] as $_) {
		if (rand(0, 1)) {
			$x = $b;
		} elseif (rand(0, 1)) {
			$x = $a;
		}
	}
	assertType('array<int>|array<string>', $x);
}

/**
 * @param array<int, null> $a
 * @param array<int, bool> $b
 * @param array<int, int> $c
 * @param array<int, float> $d
 * @param array<int, string> $e
 * @param array<int, \stdClass> $f
 * @param array<string, null> $g
 * @param array<string, bool> $h
 * @param array<string, int> $i
 * @param array<string, float> $j
 */
function joinsMergeTheClosestArraysPastTheLimit(int $n, $a, $b, $c, $d, $e, $f, $g, $h, $i, $j): void
{
	$x = match ($n) {
		1 => $a,
		2 => $b,
		3 => $c,
		4 => $d,
		5 => $e,
		6 => $f,
		7 => $g,
		8 => $h,
		9 => $i,
		default => $j,
	};
	assertType('array<int, bool>|array<int, float>|array<int, int>|array<int, null>|array<int, stdClass>|array<int, string>|array<string, bool>|array<string, float>|array<string, int>|array<string, null>', $x);

	if ($n === 1) {
		$y = $a;
	} elseif ($n === 2) {
		$y = $b;
	} elseif ($n === 3) {
		$y = $c;
	} elseif ($n === 4) {
		$y = $d;
	} elseif ($n === 5) {
		$y = $e;
	} elseif ($n === 6) {
		$y = $f;
	} elseif ($n === 7) {
		$y = $g;
	} elseif ($n === 8) {
		$y = $h;
	} elseif ($n === 9) {
		$y = $i;
	} else {
		$y = $j;
	}
	assertType('array<int, bool>|array<int, int>|array<int, null>|array<int, stdClass>|array<int, string>|array<int|string, float>|array<string, bool>|array<string, int|null>', $y);
}
