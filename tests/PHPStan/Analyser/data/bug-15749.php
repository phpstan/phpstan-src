<?php declare(strict_types = 1);

namespace Bug15749;

use function PHPStan\Testing\assertType;

/** @return array<int<0, 3>, int> */
function foo(): array {
	$a = [ ];
	for ($i = 0; $i <= 3; $i++) {
		$a[$i] = random_int(0, 100);
	}
	assertType('non-empty-array<int<0, 3>, int<0, 100>>', $a);
	return $a;
}

function lessThan(): void {
	$arr = [];
	for ($i = 0; $i < 5; $i++) {
		$arr[$i] = 'v';
	}
	assertType("non-empty-array<int<0, 4>, 'v'>", $arr);
}

function withCondition(): void {
	$arr = [];
	for ($i = 0; $i < 5; $i++) {
		if (rand()) {
			$arr[$i] = 'v';
		}
	}
	assertType("array<int<0, 4>, 'v'>", $arr);
}

function existingKey(): void {
	$arr = ['x' => 0];
	for ($i = 0; $i < 5; $i++) {
		$arr[$i] = $i;
	}
	assertType("non-empty-array<'x'|int<0, 4>, int<0, 4>>", $arr);
}

function whileLoop(): void {
	$a = [];
	$i = 0;
	while ($i <= 3) {
		$a[$i] = 1;
		$i++;
	}
	assertType('non-empty-array<int<0, 3>, 1>', $a);
}

function foreachRange(): void {
	$a = [];
	foreach (range(0, 3) as $i) {
		$a[$i] = 1;
	}
	assertType('array{1, 1, 1, 1}', $a);
}

function stringKeys(): void {
	$a = [];
	foreach (['a', 'b', 'c'] as $k) {
		$a[$k] = 1;
	}
	assertType("array{a: 1, b: 1, c: 1}", $a);
}

function decrementing(): void {
	$a = [];
	for ($i = 3; $i >= 0; $i--) {
		$a[$i] = 1;
	}
	assertType('non-empty-array<int<0, 3>, 1>', $a);
}

function nested(): void {
	$a = [];
	for ($i = 0; $i <= 2; $i++) {
		for ($j = 0; $j <= 2; $j++) {
			$a[$i][$j] = 1;
		}
	}
	assertType('non-empty-array<int<0, 2>, non-empty-array<int<0, 2>, 1>>', $a);
}
