<?php

namespace Bug4207;

use function PHPStan\Testing\assertType;

function (): void {
	assertType('non-empty-list<int<1, 10000>>', range(1, 10000));
	assertType('non-empty-list<int<1, 10000>>', range(10000, 1));
};
