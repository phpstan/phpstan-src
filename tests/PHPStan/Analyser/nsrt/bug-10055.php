<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug10055;

use function PHPStan\Testing\assertType;

/**
 * @param 'value1'|'value2'|'value3' $param1
 * @param ($param1 is 'value3' ? bool : int) $param2
 */
function test(string $param1, int|bool $param2): void
{
	match ($param1) {
		'value1' => assertType('int', $param2),
		'value2' => assertType('int', $param2),
		'value3' => assertType('bool', $param2),
	};
}

function testScopeMerging(mixed $foo): void
{
	$a = 0;
	if (\is_string($foo) || \is_int($foo)) {
		$a = 1;
	}

	if (\is_int($foo)) {
		assertType('1', $a);
	}
}
