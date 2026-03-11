<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14249;

use function PHPStan\Testing\assertType;

/**
 * @phpstan-assert-if-true positive-int $value
 */
function is_positive_int(mixed $value): bool {
	return is_int($value) && $value > 0;
}

function f(mixed $v): void {
	$f1 = is_positive_int(...);
	$f2 = 'Bug14249\is_positive_int';

	if (is_positive_int($v)) {
		assertType('int<1, max>', $v);
	}

	if ($f1($v)) {
		assertType('int<1, max>', $v);
	}

	if ($f2($v)) {
		assertType('int<1, max>', $v);
	}
}
