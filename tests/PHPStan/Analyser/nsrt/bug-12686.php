<?php declare(strict_types = 1);

namespace Bug12686;

use function PHPStan\Testing\assertType;

/** @phpstan-impure */
$f = function (): bool {
	return (bool) rand(0,1);
};

if ($f()) {
	assertType('bool', $f());
}

// Pure closure should still have narrowing
$h = function (): bool {
	return true;
};

if ($h()) {
	assertType('true', $h());
}
