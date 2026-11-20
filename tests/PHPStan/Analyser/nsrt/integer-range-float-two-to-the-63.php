<?php declare(strict_types = 1);

namespace IntegerRangeFloatTwoToThe63;

use function PHPStan\Testing\assertType;

// PHP compares an int with a float as two floats, and the ints from 2**63 - 512 up convert to 2**63

function smaller(int $i): void
{
	if ($i < 9.2233720368547758E18) {
		assertType('int<min, 9223372036854775295>', $i);
	} else {
		assertType('int<9223372036854775296, max>', $i);
	}
	assertType('int', $i);
}

function greaterOrEqual(int $i): void
{
	if ($i >= 9.2233720368547758E18) {
		assertType('int<9223372036854775296, max>', $i);
	} else {
		assertType('int<min, 9223372036854775295>', $i);
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

function intMax(): void
{
	assertType('true', 9223372036854775807 >= 9.2233720368547758E18);
	assertType('false', 9223372036854775807 < 9.2233720368547758E18);
	assertType('true', 9223372036854775296 >= 9.2233720368547758E18);
	assertType('false', 9223372036854775295 >= 9.2233720368547758E18);
}

// 2**53 + 3 and 2**53 + 5 both round to 2**53 + 4

function roundedToEven(int $i): void
{
	if ($i < 9007199254740996.0) {
		assertType('int<min, 9007199254740994>', $i);
	} else {
		assertType('int<9007199254740995, max>', $i);
	}
	if ($i <= 9007199254740996.0) {
		assertType('int<min, 9007199254740997>', $i);
	} else {
		assertType('int<9007199254740998, max>', $i);
	}
}
