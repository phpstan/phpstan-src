<?php declare(strict_types = 1);

namespace IntegerRangeFiniteTypesPhpIntMax;

use function PHPStan\Testing\assertType;

/**
 * @param int<9223372036854775806, 9223372036854775807> $i
 * @param int<0, 1> $j
 */
function foo(int $i, int $j): void
{
	assertType('int<9223372036854775806, 9223372036854775807>', $i);
	assertType('int<9223372036854775805, 9223372036854775807>', $i - $j);
	assertType('9223372036854775806|9223372036854775807', $i | $j);
}
