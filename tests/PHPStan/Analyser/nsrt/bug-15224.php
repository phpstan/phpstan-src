<?php // lint >= 8.3

namespace Bug15224;

use function PHPStan\Testing\assertType;

function rememberMbStrPad(string $s): void
{
	if (mb_str_pad($s, 5) === 'x') {
		assertType("'x'", mb_str_pad($s, 5));
	} else {
		assertType('string', mb_str_pad($s, 5));
	}

	assertType('string', mb_str_pad($s, 5));
}
