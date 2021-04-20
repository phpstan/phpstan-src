<?php

namespace Bug4207;

use function PHPStan\Testing\assertType;

function (): void {
	assertType('array<int, int>&nonEmpty', range(1, 10000));
};
