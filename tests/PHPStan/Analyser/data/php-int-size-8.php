<?php

namespace PhpIntSize8;

use function PHPStan\Testing\assertType;

assertType('9223372036854775807', PHP_INT_MAX);
assertType('-9223372036854775808', PHP_INT_MIN);
assertType('8', PHP_INT_SIZE);

$max = PHP_INT_MAX;
assertType('9223372036854775806', $max - 1);

// Overflowing PHP_INT_MAX always produces a float. Without phpIntSize the 32bit branch
// of the union yields int(2147483648), a value no PHP build can ever hold.
assertType('9.223372036854776E+18', PHP_INT_MAX + 1);
