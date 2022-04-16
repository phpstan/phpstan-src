<?php

namespace ArrayMerge;

use function array_merge;
use function PHPStan\Testing\assertType;

function foo(): void
{
	$foo = ['foo' => 17, 'a', 'bar' => 18, 'b'];
	$bar = [99 => 'b', 'bar' => 19, 98 => 'c'];
	$baz = array_merge($foo, $bar);

	assertType('array{foo: 17, 0: \'a\', bar: 19, 1: \'b\', 2: \'b\', 3: \'c\'}', $baz);
}
