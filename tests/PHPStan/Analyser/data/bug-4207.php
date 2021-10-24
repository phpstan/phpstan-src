<?php

namespace Bug4207;

use function PHPStan\Testing\assertType;

function (): void {
	assertType('non-empty-array<int, int>', range(1, 10000));
};
