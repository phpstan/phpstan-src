<?php

namespace Bug4207;

use function PHPStan\Testing\assertType;

function (): void {
	assertType('int<1, 10000>', range(1, 10000));
};
