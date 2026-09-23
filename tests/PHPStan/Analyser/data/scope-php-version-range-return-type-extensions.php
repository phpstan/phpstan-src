<?php

namespace ScopePhpVersionRangeReturnTypeExtensions;

use function PHPStan\Testing\assertType;

function validOperatorNeverReturnsNull(string $a, string $b, string $s): void
{
	// version_compare() only returns null for an invalid operator, so a constant valid one
	// rules null out no matter which version of the range is analysed.
	assertType('bool', version_compare($a, $b, '<'));
	assertType('bool', version_compare($a, $b, 'ge'));

	assertType('(bool|null)', version_compare($a, $b, 'nope'));
	assertType('(bool|null)', version_compare($a, $b, $s));
}
