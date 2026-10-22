<?php declare(strict_types = 1);

namespace ArrayLiteralByRefLiteralKeys;

use function PHPStan\Testing\assertType;

function doFoo(int $r, int $s): void
{
	$a = ['x' => ['y' => 1, 2 => 'two'], 'z' => &$r, 'w' => ['v' => &$s, 'u' => 'lit']];
	$r = 'changed';
	$s = 'also';
	assertType("array{x: array{y: 1, 2: 'two'}, z: 'changed', w: array{v: 'also', u: 'lit'}}", $a);

	$b = [5 => 'five', ...['a', &$r], 'b'];
	$r = 3.5;
	assertType("array{5: 'five', 6: 'a', 7: 3.5, 8: 'b'}", $b);
}
