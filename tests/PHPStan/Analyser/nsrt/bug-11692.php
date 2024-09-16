<?php

namespace Bug11692;

use function PHPStan\Testing\assertType;

function doFoo(int $i, float $f): void {
	assertType('non-empty-list<float|int>', range(1, 9, .01));
	assertType('array{1, 4, 7}', range(1, 9, 3));

	assertType('non-empty-list<float|int>', range(1, 9999, .01));
	assertType('non-empty-list<int<1, 9999>>', range(1, 9999, 3));

	assertType('list<int>', range(1, 3, $i));
	assertType('list<float|int>', range(1, 3, $f));

	assertType('list<int>', range(1, 9999, $i));
	assertType('list<float|int>', range(1, 9999, $f));
}

