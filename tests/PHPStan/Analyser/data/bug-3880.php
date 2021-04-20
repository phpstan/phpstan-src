<?php

namespace Bug3880;

use function PHPStan\Testing\assertType;

function ($value): void {
	$num = (float) $value;
	if ((!is_numeric($value) && !is_bool($value)) || $num > 9223372036854775807 || $num < -9223372036854775808) {
		assertType('mixed', $value);
	}
};
