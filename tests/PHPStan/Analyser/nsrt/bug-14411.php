<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14411;

use function PHPStan\Testing\assertType;

/** @phpstan-impure */
function get_mixed(): mixed {
	return random_int(0, 1) ? 'foo' : null;
}

/** @phpstan-impure */
function get_optional_int(): ?int {
	return random_int(0, 1) ? 42 : null;
}

function (): void {
	$a = get_mixed();

	if ($a !== null) {
		$b = $a;
	}
	else {
		$b = get_optional_int();
	}
	if ($b !== null) {
		assertType('mixed', $a);
		if ($a === null) {
			echo 'this is absolutely possible';
		}
	}
};
