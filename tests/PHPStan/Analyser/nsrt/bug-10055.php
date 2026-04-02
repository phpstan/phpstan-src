<?php declare(strict_types = 1);

namespace Bug10055;

use function PHPStan\Testing\assertType;

/**
 * @param 'value1'|'value2'|'value3' $param1
 * @param ($param1 is 'value3' ? bool : int) $param2
 */
function test(string $param1, $param2): void
{
	if ($param1 === 'value1') {
		assertType('int', $param2);
	}
	if ($param1 === 'value2') {
		assertType('int', $param2);
	}
	if ($param1 === 'value3') {
		assertType('bool', $param2);
	}

	match ($param1) {
		'value1' => assertType('int', $param2),
		'value2' => assertType('int', $param2),
		'value3' => assertType('bool', $param2),
	};
}
