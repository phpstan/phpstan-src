<?php // lint >= 8.2

declare(strict_types = 1);

namespace CoalesceNonNullLeftInFalseyContext;

use function PHPStan\Testing\assertType;

/**
 * @param int|true|null $m
 */
function doFoo(?bool $b, ?int $i, ?int $j, $m, ?true $t, ?int $n): void
{
	if (!($b ?? false)) {
		assertType('bool|null', $b);
	}

	if (($b ?? false) === false) {
		assertType('bool|null', $b);
	}

	if (($b ?? false) !== true) {
		assertType('bool|null', $b);
	}

	if (($i ?? 0) !== true) {
		assertType('int|null', $i);
	}

	if (!($j ?? 1)) {
		assertType('int|null', $j);
	}

	if (($m ?? false) !== true) {
		assertType('int|true|null', $m);
	}

	if (!($t ?? false)) {
		assertType('null', $t);
	}

	if (($t ?? 0) !== true) {
		assertType('null', $t);
	}

	if (($n ?? false) === false) {
		assertType('null', $n);
	}
}
