<?php declare(strict_types = 1);

namespace ImpureCallAssertsNotRemembered;

use function PHPStan\Testing\assertType;

function doFoo(string $path): void
{
	// realpath() carries @phpstan-assert-if-true on $path but is impure - the
	// assert narrows $path, the call's own value is never remembered
	if (realpath($path)) {
		assertType('non-empty-string', $path);
		assertType('non-empty-string|false', realpath($path));
	}
	if (realpath($path) ?: null) {
		assertType('non-empty-string|false', realpath($path));
	}
}
