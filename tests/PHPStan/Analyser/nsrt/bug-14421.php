<?php declare(strict_types = 1);

namespace Bug14421;

use function PHPStan\Testing\assertType;

/** @phpstan-impure */
function get_optional_int(): ?int {
	return random_int(0, 1) ? 42 : null;
}

if (isset($_SESSION['a'])) {
	$b = $_SESSION['a'];
}
else {
	$b = get_optional_int();
}
if ($b !== null) {
	assertType('array<mixed>', $_SESSION);
	assertType('mixed~null', $b);
	if (!isset($_SESSION['a'])) {
		echo 'this is absolutely possible';
	}
}
