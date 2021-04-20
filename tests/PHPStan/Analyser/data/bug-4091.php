<?php

namespace Bug4091;

use function PHPStan\Testing\assertType;

if (mt_rand(0,10) > 3) {
	echo 'Fizz';
	assertType('int', mt_rand(0,10));
}
