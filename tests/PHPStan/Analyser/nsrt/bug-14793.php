<?php declare(strict_types = 1);

namespace Bug14793;

use function PHPStan\Testing\assertType;

/**
 * @param decimal-int-string $a
 * @param non-decimal-int-string $b
 * @param numeric-string $n
 */
function foo(string $a, string $b, string $n): void
{
	// '2' == '02' and '2' == '2.0' are true in PHP, so these cannot be decided.
	assertType('bool', $a == $b);
	assertType('bool', $b == $a);
	assertType('bool', $a == $n);
	assertType('bool', $a == '2.0');
	assertType('bool', $a == '02');
}

/**
 * @param decimal-int-string $a
 */
function decimalAlwaysFalse(string $a): void
{
	// A decimal-int-string is numeric and non-empty: comparing it to a
	// non-numeric string or to null can never be loosely equal.
	assertType('false', $a == 'foo');
	assertType('false', $a == null);
}

/**
 * @param non-decimal-int-string $b
 */
function nonDecimalVsNull(string $b): void
{
	// '' is a valid non-decimal-int-string and '' == null is true.
	assertType('bool', $b == null);
}
