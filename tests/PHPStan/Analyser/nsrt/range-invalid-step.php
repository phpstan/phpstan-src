<?php

namespace RangeInvalidStep;

use function PHPStan\Testing\assertType;

function doFoo(bool $flag): void
{
	if (PHP_VERSION_ID >= 80000) {
		// a step of 0 or one wider than the range throws a ValueError since PHP 8.0
		assertType('*NEVER*', range(2, 5, 0));
		assertType('*NEVER*', range(5, 6, 3));
		assertType('*NEVER*', range(1, 10, INF));
		assertType('array{6}', range($flag ? 5 : 6, 6, 3));
		assertType('non-empty-list<int<1, 300>>', range(1, $flag ? 2 : 300, 5));
	} else {
		// PHP 7 reports an invalid step with a warning and returns false instead
		assertType('false', range(2, 5, 0));
		assertType('false', range(5, 6, 3));
		assertType('false', range(1, 10, INF));
		assertType('array{6}|false', range($flag ? 5 : 6, 6, 3));
		assertType('non-empty-list<int<1, 300>>|false', range(1, $flag ? 2 : 300, 5));
	}
}
