<?php declare(strict_types = 1);

namespace Bug15679;

use function PHPStan\Testing\assertType;

function f(bool $b): void
{
	$x = $b ? [1 => 0, 0 => 0] : [0 => 1, 1 => 0];
	assertType('array{1, 0}|array{1: 0, 0: 0}', $x);
	assertType('bool', array_is_list($x));

	$y = $b ? [1 => 'a', 0 => 'b'] : [0 => 'c', 1 => 'a'];
	assertType("array{'c', 'a'}|array{1: 'a', 0: 'b'}", $y);
	assertType('bool', array_is_list($y));
}
