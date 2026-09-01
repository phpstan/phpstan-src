<?php declare(strict_types = 1);

namespace Bug14786;

use function PHPStan\Testing\assertType;

/** @param decimal-int-string $s */
function unaryPlus(string $s): void
{
	// big decimal-int-strings overflow PHP_INT_MAX and become float
	assertType('float|int', +$s);
}

/** @param non-decimal-int-string $s */
function unaryPlusNonDecimal(string $s): void
{
	assertType('float|int', +$s);
}

/** @param numeric-string $s */
function unaryPlusNumeric(string $s): void
{
	assertType('float|int', +$s);
}

/** @param decimal-int-string $s */
function intval(string $s): void
{
	// explicit int cast never overflows to float
	assertType('int', (int) $s);
}

/** @param decimal-int-string $s */
function arithmetic(string $s): void
{
	assertType('float|int', $s + 1);
	assertType('float|int', $s * 2);
}
