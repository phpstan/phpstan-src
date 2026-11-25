<?php declare(strict_types = 1);

namespace Bug15775;

use function PHPStan\Testing\assertType;

/**
 * @param list<1|2|3|4|5> $indices
 * @return array<int<1, 5>, int>
 */
function f(array $indices): array {
	$test = [ ];
	for ($i = 1; $i <= 5; $i++) {
		if (in_array($i, $indices, true)) {
			$test[$i] = random_int(0, 100);
		}
	}
	assertType('array<int<1, 5>, int<0, 100>>', $test);
	return  $test;
}

/** @param list<1|2|3|4|5> $indices */
function decrementing(array $indices): void {
	$test = [];
	for ($i = 5; $i >= 1; $i--) {
		if (in_array($i, $indices, true)) {
			$test[$i] = 1;
		}
	}
	assertType('array<int<1, 5>, 1>', $test);
}

/** @param list<1|2|3|4|5> $indices */
function whileLoop(array $indices): void {
	$test = [];
	$i = 1;
	while ($i <= 5) {
		if (in_array($i, $indices, true)) {
			$test[$i] = 1;
		}
		$i++;
	}
	assertType('array<int<1, 5>, 1>', $test);
}

/** @param list<1|2|3|4|5> $indices */
function nested(array $indices): void {
	$test = [];
	for ($i = 1; $i <= 5; $i++) {
		if (in_array($i, $indices, true)) {
			$test[$i]['x'] = 1;
		}
	}
	assertType('array<int<1, 5>, array{x: 1}>', $test);
}

function identicalChain(): void {
	$test = [];
	for ($i = 1; $i <= 5; $i++) {
		if ($i === 2 || $i === 4) {
			$test[$i] = 1;
		}
	}
	assertType('array<int<2, 4>, 1>', $test);
}

/** @param list<string> $words */
function stillWidensGrowingValues(array $words): void {
	$counts = [];
	foreach ($words as $w) {
		$counts[$w] = ($counts[$w] ?? 0) + 1;
	}
	assertType('array<string, int<1, max>>', $counts);

	$a = [];
	$n = 0;
	while (rand()) {
		$n++;
		$a[$n] = $n;
	}
	assertType('array<int<1, max>, int<1, max>>', $a);
}

function keysGrowingFromTheArrayItself(): void {
	$a = [5 => 1];
	while (rand()) {
		$k = max(array_keys($a));
		$a[$k + 2] = 1;
	}
	assertType('non-empty-array<int<5, max>, 1>', $a);
}

/** @param list<'y'> $points */
function appendedKeys(array $points): void {
	$a = [];
	foreach ($points as $point) {
		if (rand()) {
			$a[] = $point;
		}
	}
	assertType('list<int<0, max>>', array_keys($a));

	$b = [];
	while (rand()) {
		if (rand()) {
			$b[] = 'x';
		}
	}
	assertType('list<int<0, max>>', array_keys($b));
}

/** @param list<1|2|3|4|5> $indices */
function doWhileLoop(array $indices): void {
	$test = [];
	$i = 1;
	do {
		if (in_array($i, $indices, true)) {
			$test[$i] = 1;
		}
		$i++;
	} while ($i <= 5);
	assertType('array<int<1, 5>, 1>', $test);
}
