<?php // onlyif PHP_INT_SIZE == 8

use function PHPStan\Testing\assertType;

// core, https://www.php.net/manual/en/reserved.constants.php
assertType('2147483647|9223372036854775807', PHP_INT_MAX);
assertType('-9223372036854775808|-2147483648', PHP_INT_MIN);
