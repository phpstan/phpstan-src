<?php

namespace Bug8272;

use function PHPStan\Testing\assertType;

function test(): void {
	assertType('int<1, 5>', mt_rand(1, 5));
	assertType('int<0, max>', mt_rand());
};
