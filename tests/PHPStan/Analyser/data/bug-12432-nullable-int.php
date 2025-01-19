<?php

namespace Bug12432;

use function PHPStan\Testing\assertType;

function requireNullableInt(?int $nullable): ?int
{
	switch ($nullable) {
		case 1:
			assertType('1', $nullable);
		case 2:
			assertType('1|2', $nullable);
			break;
		case '':
			assertType('0|null', $nullable);
		case 0:
			assertType('0|null', $nullable);
			break;
		default:
			assertType('int<min, -1>|int<3, max>', $nullable);
			break;
	}

	return $nullable;
}
