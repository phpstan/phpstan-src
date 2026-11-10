<?php declare(strict_types = 1);

namespace Bug15679;

use function PHPStan\Testing\assertType;

function differingAtOneKey(bool $b): void
{
	$x = $b ? [1 => 0, 0 => 0] : [0 => 1, 1 => 0];
	assertType('array{0|1, 0}', $x);
	assertType('bool', array_is_list($x));
	if (array_is_list($x)) {
		assertType('array{0|1, 0}', $x);
	} else {
		assertType('array{0|1, 0}', $x);
	}

	$y = $b ? [1 => 'a', 0 => 'b'] : [0 => 'c', 1 => 'a'];
	assertType("array{'b'|'c', 'a'}", $y);
	assertType('bool', array_is_list($y));
}

function listFirst(bool $b): void
{
	$x = $b ? [0 => 1, 1 => 0] : [1 => 0, 0 => 0];
	assertType('array{1: 0, 0: 0|1}', $x);
	assertType('bool', array_is_list($x));
}

function sameValues(bool $b): void
{
	$x = $b ? [1 => 0, 0 => 0] : [0 => 0, 1 => 0];
	assertType('array{0, 0}', $x);
	assertType('bool', array_is_list($x));

	$y = $b ? [0 => 0, 1 => 0] : [1 => 0, 0 => 0];
	assertType('array{1: 0, 0: 0}', $y);
	assertType('bool', array_is_list($y));
}

function differingAtTwoKeys(bool $b): void
{
	$x = $b ? [1 => 'a', 0 => 'b'] : [0 => 'c', 1 => 'd'];
	assertType("array{'c', 'd'}|array{1: 'a', 0: 'b'}", $x);
	assertType('bool', array_is_list($x));
}

function sameOrder(bool $b): void
{
	$x = $b ? [0 => 0, 1 => 0] : [0 => 1, 1 => 0];
	assertType('array{0|1, 0}', $x);
	assertType('true', array_is_list($x));

	$y = $b ? [1 => 0, 0 => 0] : [1 => 0, 0 => 1];
	assertType('array{1: 0, 0: 0|1}', $y);
	assertType('false', array_is_list($y));
}
