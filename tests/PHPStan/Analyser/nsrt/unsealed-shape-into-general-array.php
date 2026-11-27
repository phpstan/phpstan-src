<?php declare(strict_types = 1);

namespace UnsealedShapeIntoGeneralArray;

use function PHPStan\Testing\assertType;

/**
 * @param array{id: mixed, ...<int|string, mixed>} $a
 * @param array<string, string|null> $b
 */
function doFoo(array $a, array $b, bool $c): void
{
	assertType('array<int|string, mixed>', $c ? $a : $b);

	$s = [];
	foreach ($b as $k => $v) {
		$s[$k] = $v;
		if (rand(0, 1)) {
			$s = $a;
		}
	}
	assertType('array<int|string, mixed>', $s);
}
