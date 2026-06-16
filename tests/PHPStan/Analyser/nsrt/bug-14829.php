<?php declare(strict_types = 1);

namespace Bug14829;

use function PHPStan\Testing\assertType;

function testFunction(string $path): void
{
	// is_readable() has @phpstan-assert-if-true in stubs/file.stub
	if (is_readable($path)) {
		assertType('true', is_readable($path));
		assertType('non-empty-string', $path);
	}

	if (!is_readable($path)) {
		return;
	}
	assertType('true', is_readable($path));
}
