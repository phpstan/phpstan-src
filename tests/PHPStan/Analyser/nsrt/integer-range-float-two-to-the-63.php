<?php declare(strict_types = 1);

namespace IntegerRangeFloatTwoToThe63;

use function PHPStan\Testing\assertType;

function smaller(int $i): void
{
	if ($i < 9.2233720368547758E18) {
		assertType('int', $i);
	} else {
		assertType('*NEVER*', $i);
	}
	assertType('int', $i);
}

function greaterOrEqual(int $i): void
{
	if ($i >= 9.2233720368547758E18) {
		assertType('*NEVER*', $i);
	} else {
		assertType('int', $i);
	}
	assertType('int', $i);
}

function smallerOrEqual(int $i): void
{
	if ($i <= 9.2233720368547758E18) {
		assertType('int', $i);
	} else {
		assertType('*NEVER*', $i);
	}
}

function greater(int $i): void
{
	if ($i > 9.2233720368547758E18) {
		assertType('*NEVER*', $i);
	} else {
		assertType('int', $i);
	}
}
