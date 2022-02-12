<?php

namespace Bug6329;

use function PHPStan\Testing\assertType;

function foo($a): void
{
	if (is_string($a) && '' !== $a) {
		assertType('non-empty-string', $a);
	}

	if (is_string($a) && '' !== $a || null === $a) {
		assertType('non-empty-string|null', $a);
	}

	if (is_string($a) || null === $a) {
		assertType('string|null', $a);
		if ('' !== $a) {
			assertType('non-empty-string|null', $a);
		}
	}

	if ((is_string($a) || null === $a) && '' !== $a) {
		assertType('non-empty-string|null', $a);
	}
}
