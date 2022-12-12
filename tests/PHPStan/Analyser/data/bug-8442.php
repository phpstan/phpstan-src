<?php declare(strict_types = 1);

namespace Bug8442;

use function PHPStan\Testing\assertType;

assertType('false', \DateInterval::createFromDateString('foo'));
assertType('DateInterval', \DateInterval::createFromDateString('1 Day'));
