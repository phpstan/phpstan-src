<?php

namespace Bug14047;

use function is_numeric;
use function PHPStan\Testing\assertType;

function test_strings(string $a, string $b): void
{
	if (strtolower($a) !== $b) {
		assertType('string', $a);
		assertType('string', $b);
	}

	if ($a !== '' && strtolower($a) == $b) {
		assertType('non-empty-string', $a);
		assertType('lowercase-string&non-empty-string', $b);
	}

	if ($a !== '' && strtolower($a) === $b) {
		assertType('non-empty-string', $a);
		assertType('lowercase-string&non-empty-string', $b);
	}

	if ($a !== '' && strtolower($a) == $b && $b !== '0') {
		assertType('non-empty-string', $a);
		assertType('lowercase-string&non-falsy-string', $b);
	}

	if ($a !== '' && strtolower($a) === $a) {
		assertType('lowercase-string&non-empty-string', $a);
	} elseif ($a !== '' && strtoupper($a) === $a) {
		assertType('non-empty-string&uppercase-string', $a);
	}

	if ($a !== '') {
		if (strtolower($a) === $a) {
			assertType('lowercase-string&non-empty-string', $a);
		}
	}

	if (strtolower($b) === $b && $b !== '') {
		assertType('lowercase-string&non-empty-string', $b);
	} elseif (strtoupper($b) === $b && $b !== '') {
		assertType('non-empty-string&uppercase-string', $b);
	}

	if ($b !== '' && is_numeric($b)) {
		assertType('non-empty-string&numeric-string', $b);
	}

	if (strtolower($b) === $b && $b !== '' && is_numeric($b)) {
		assertType('lowercase-string&non-empty-string&numeric-string', $b);
	}

	assertType('string', $b);
	if (is_numeric($b)) {
		assertType('numeric-string', $b);
		if (strtolower($b) === $b) {
			assertType('lowercase-string&non-empty-string&numeric-string', $b);
			if ($b !== '') {
				assertType('lowercase-string&non-empty-string&numeric-string', $b);
			}
		}
		if ($b !== '') {
			assertType('numeric-string', $b);
		}
	}
}
