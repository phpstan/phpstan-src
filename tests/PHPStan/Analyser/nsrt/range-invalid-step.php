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

function doBar(bool $flag, string $s): void
{
	// a guard with an upper bound only would leave the analysed PHP version at 8.0
	if (PHP_VERSION_ID >= 70400 && PHP_VERSION_ID < 80100) {
		// PHP 7 returns false, PHP 8.0 throws a ValueError
		assertType('false', range(2, 5, 0));
	}

	if (PHP_VERSION_ID >= 80000) {
		// only a part of an argument is a constant, the rest might make a valid range
		assertType('non-empty-list<(float|int|string)>', range($flag ? 5 : $s, 6, 3));
		assertType('non-empty-list<(float|int|string)>', range(5, $flag ? 6 : $s, 3));
		assertType('non-empty-list<float|int>', range(2, 5, $flag ? 0 : $s));
	}
}
