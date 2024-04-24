<?php

namespace PowFunction;

use function PHPStan\Testing\assertType;

assertType('4', '4a' ** 1);
assertType('*ERROR*', 'a' ** 1);
