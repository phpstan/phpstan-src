<?php declare(strict_types = 1);

namespace Bug14469Variants;

use function PHPStan\Testing\assertType;

// Variant 1: if/else where one branch doesn't access the array key
function variant1(array $R, bool $cond, int $other): void {
	$aa = null;

	if ($cond) {
		$aa = $other;
	} else {
		if ($R['key']) {
			$aa = $R['key'];
		}
	}

	if ($aa) {
		assertType('mixed', $R['key']);
	}
}

// Variant 2: Multiple elseif branches
function variant2(array $R, bool $var1, bool $var2, int $other1, int $other2): void {
	$aa = null;

	if ($var1) {
		$aa = $other1;
	} elseif ($var2) {
		$aa = $other2;
	} elseif ($R['key']) {
		$aa = $R['key'];
	}

	if ($aa) {
		assertType('mixed', $R['key']);
	}
}

// Variant 3: Nested array access
function variant3(array $R, bool $cond, int $other): void {
	$aa = null;

	if ($cond) {
		$aa = $other;
	} elseif ($R['nested']['key']) {
		$aa = $R['nested']['key'];
	}

	if ($aa) {
		assertType('mixed', $R['nested']['key']);
	}
}
