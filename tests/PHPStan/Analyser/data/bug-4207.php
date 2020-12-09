<?php

namespace Bug4207;

use function PHPStan\Analyser\assertType;

function (): void {
	assertType('array<int, int>', range(1, 10000));
};
