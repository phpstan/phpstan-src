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

function whileLoopOriginal(int $length, int $quantity): void
{
	if ($length < 8) {
		throw new \InvalidArgumentException();
	}
	$codes = [];
	while ($quantity >= 1 && count($codes) < $quantity) {
		$code = '';
		for ($i = 0; $i < $length; $i++) {
			$code .= 'x';
		}
		if (!in_array($code, $codes, true)) {
			$codes[] = $code;
		}
	}
}
