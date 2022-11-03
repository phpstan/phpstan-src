<?php

namespace Bug4091;

use function PHPStan\Testing\assertType;

if (mt_rand(0,10) > 3) {
	echo 'Fizz';
	assertType('int<0, 10>', mt_rand(0,10));
}
