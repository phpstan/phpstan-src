<?php

namespace ScopePhpVersionRangeReturnTypeExtensions;

use function PHPStan\Testing\assertType;

// The analysed PHP version is configured as the range 7.4 - 8.5, so every version check
// inside these extensions is uncertain. An uncertain check must not produce a type that is
// narrower than the truth on either end of the range.

function bothVersionsPossible(string $s): void
{
	assertType("''|false", substr('abc', 10));
	assertType("array{}|array{''}", str_split(''));
	assertType('bool', mb_substitute_character(null));
	assertType('bool', mb_substitute_character(0));
}

function validOperatorNeverReturnsNull(string $a, string $b, string $s): void
{
	// version_compare() only returns null for an invalid operator, so a constant valid one
	// rules null out no matter which version of the range is analysed.
	assertType('bool', version_compare($a, $b, '<'));
	assertType('bool', version_compare($a, $b, 'ge'));

	assertType('(bool|null)', version_compare($a, $b, 'nope'));
	assertType('(bool|null)', version_compare($a, $b, $s));
}
