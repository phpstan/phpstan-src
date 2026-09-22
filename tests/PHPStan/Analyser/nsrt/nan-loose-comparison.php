<?php declare(strict_types = 1);

namespace NanLooseComparison;

use function PHPStan\Testing\assertType;

function test(): void
{
	// NAN compares equal to nothing, not even to its own string cast
	assertType('false', NAN == 'NAN');
	assertType('true', NAN != 'NAN');
	assertType('false', NAN == 'foo');
	assertType('false', 'NAN' == NAN);
	assertType('true', 'NAN' != NAN);
}
