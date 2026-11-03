<?php

namespace IntegerOperandNumericString;

use function PHPStan\Testing\assertType;

// Numeric strings out of the int range saturate when they become integer
// operands, like an (int) cast; they do not wrap around like a float does.
function doFoo(): void
{
	assertType('1', '9223372036854775808' & 1);
	assertType('9223372036854775807', -1 & '9223372036854775808');
	assertType('9223372036854775807', '9223372036854775808' | 0);
	assertType('9223372036854775806', '9223372036854775808' ^ 1);
	assertType('-9223372036854775808', -1 & '-9223372036854775809');
	assertType('9223372036854775807', -1 & '1e19');
	assertType('0', 1 << '9223372036854775808');
	assertType('-1', -1 >> '9223372036854775808');
	assertType('-2', '9223372036854775808' << 1);
	assertType('4611686018427387903', '9223372036854775808' >> 1);
	assertType('1', '9223372036854775808' % 3);
	assertType('0', 9223372036854775807 % '9223372036854775808');
}

/**
 * @param int<0, 3> $range
 */
function doBar(int $range): void
{
	assertType('0|1|2|3', '9223372036854775808' & $range);
	assertType('9223372036854775807', $range | '9223372036854775808');
}
