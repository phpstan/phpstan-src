<?php

use function PHPStan\Testing\assertType;

assertType('non-falsy-string', PHP_BUILD_DATE);
assertType('string', PHP_BUILD_PROVIDER);
