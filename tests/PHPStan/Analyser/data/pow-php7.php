<?php

namespace PowFunction;

use function PHPStan\Testing\assertType;

assertType('4', '4a' ** 1);
assertType('0', 'a' ** 1);
