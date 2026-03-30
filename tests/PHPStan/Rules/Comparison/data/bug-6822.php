<?php declare(strict_types = 1);

namespace Bug6822Rule;

/** @phpstan-impure */
$closure = function (): bool {
	return (bool) rand(0, 1);
};

if ($closure()) {
	if ($closure()) { // should not be reported as "always true"
		echo 'yes';
	}
}

/** @phpstan-impure */
$impureFn = function (): int {
	return rand(0, 100);
};

if ($impureFn() > 50) {
	if ($impureFn() > 50) { // should not be reported as "always true"
		echo 'yes';
	}
}
