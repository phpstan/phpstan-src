<?php // lint >= 8.4

namespace Bug12973Php84;

use function PHPStan\Testing\assertType;

function mbtrim($value): void
{
	if (mb_trim($value) === '') {
		assertType('mixed', $value);
	} else {
		assertType('non-empty-string', $value);
	}
	assertType('mixed', $value);

	if (mb_ltrim($value) === '') {
		assertType('mixed', $value);
	} else {
		assertType('non-empty-string', $value);
	}
	assertType('mixed', $value);

	if (mb_rtrim($value) === '') {
		assertType('mixed', $value);
	} else {
		assertType('non-empty-string', $value);
	}
	assertType('mixed', $value);
}
