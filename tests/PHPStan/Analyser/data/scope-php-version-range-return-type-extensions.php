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

function strSplitAndMbFunctions(string $s, int $i): void
{
	assertType("array{}|array{''}", str_split(''));
	assertType('list<string>', str_split($s));
	assertType('false', str_split($s, 0));
	assertType('list<string>|false', str_split($s, $i));
	assertType('false', mb_strlen($s, 'foo'));
	assertType('false', mb_ord($s, 'foo'));
}

function mbSubstituteCharacter(): void
{
	assertType('true', mb_substitute_character(1));
	assertType('bool', mb_substitute_character(null));
	assertType('true', mb_substitute_character(''));
	assertType('bool', mb_substitute_character(new \stdClass()));
	assertType('false', mb_substitute_character('foo'));
	assertType('false', mb_substitute_character(0x110000));
}
