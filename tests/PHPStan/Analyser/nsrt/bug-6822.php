<?php declare(strict_types = 1);

namespace Bug6822;

use function PHPStan\Testing\assertType;

// Closures marked as @phpstan-impure should not have their return type narrowed

/** @phpstan-impure */
$closure = function (): bool {
	return (bool) rand(0, 1);
};

assertType('bool', $closure());

if ($closure()) {
	assertType('bool', $closure());
}

// Same with an explicit impure closure assigned to a variable
/** @phpstan-impure */
$impureFn = function (): int {
	return rand(0, 100);
};

if ($impureFn() > 50) {
	assertType('int<0, 100>', $impureFn());
}
