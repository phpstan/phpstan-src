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

function keys(): void
{
	foreach (range(0, 50) as $key => $value) {
		assertType('int<0, 50>', $key);
	}
	foreach (range(10, 1000, 7) as $key => $value) {
		assertType('int<0, 141>', $key);
	}
	foreach (range(1000, 10, -7) as $key => $value) {
		assertType('int<0, 141>', $key);
	}
	foreach (range(0, -100000000) as $key => $value) {
		assertType('int<0, 100000000>', $key);
	}
	foreach (range(0, 2000000000, 3) as $key => $value) {
		assertType('int<0, 666666666>', $key);
	}
	foreach (range(PHP_INT_MIN, PHP_INT_MAX) as $key => $value) {
		assertType('int<0, max>', $key);
	}
}
