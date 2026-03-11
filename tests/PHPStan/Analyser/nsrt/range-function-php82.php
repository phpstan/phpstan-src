<?php // lint < 8.3

use function PHPStan\Testing\assertType;

assertType('array{2.0, 3.0, 4.0, 5.0}', range(2, 5, 1.0));
