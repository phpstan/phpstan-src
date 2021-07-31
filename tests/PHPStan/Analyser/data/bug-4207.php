<?php

namespace Bug4207;

use function PHPStan\Testing\assertType;

function (): void {
	assertType('array<int, int<1, 10000>>&nonEmpty', range(1, 10000));
	assertType('array<int, int>&nonEmpty', range(1, 20000, 2));
};
