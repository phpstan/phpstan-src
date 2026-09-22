<?php declare(strict_types = 1);

namespace RangeArrayCountLimit;

use function PHPStan\Testing\assertType;

function doFoo(): void
{
	assertType('non-empty-list<int<0, 2000000000>>', range(0, 2000000000));
	assertType('non-empty-list<int<0, 100000000>>', range(0, 100000000));
	assertType('non-empty-list<int<-100000000, 0>>', range(0, -100000000));
	assertType('non-empty-list<int<0, 2000000000>>', range(0, 2000000000, 3));
	assertType('non-empty-list<float>', range(0, 100000000, 0.5));
	assertType('non-empty-list<int<0, 300>>', range(0, 300));
}
