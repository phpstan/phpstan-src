<?php declare(strict_types = 1);

namespace Bug13705;

use function PHPStan\Testing\assertType;

function whileLoop(): void
{
	$quantity = random_int(1, 42);
	$codes = [];
	while (count($codes) < $quantity) {
		assertType('list<non-empty-string>', $codes);
		$code = random_bytes(16);
		if (!in_array($code, $codes, true)) {
			$codes[] = $code;
		}
	}
}

function doWhileLoop(): void
{
	$quantity = random_int(1, 42);
	$codes = [];
	do {
		$code = random_bytes(16);
		if (!in_array($code, $codes, true)) {
			$codes[] = $code;
		}
	} while (count($codes) < $quantity);
}

function whileLoopSimple(): void
{
	$quantity = random_int(1, 42);
	$codes = [];
	while (count($codes) < $quantity) {
		assertType('list<non-empty-string>', $codes);
		$codes[] = random_bytes(16);
	}
}
