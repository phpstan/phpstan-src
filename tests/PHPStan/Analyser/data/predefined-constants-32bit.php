<?php // onlyif PHP_INT_SIZE == 4

use function PHPStan\Testing\assertType;

// core, https://www.php.net/manual/en/reserved.constants.php
assertType('2147483647', PHP_INT_MAX);
assertType('-2147483648', PHP_INT_MIN);
