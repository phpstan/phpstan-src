<?php declare(strict_types = 1);

namespace Bug14333;

use function PHPStan\Testing\assertType;

function testByRefInArrayWithKey(): void
{
	$a = 'hello';
	assertType("'hello'", $a);

	$b = ['key' => &$a];
	assertType("'hello'", $a);

	$b['key'] = 42;
	assertType('42', $a);
}

function testMultipleByRefInArray(): void
{
	$a = 1;
	$c = 'test';

	$b = [&$a, 'normal', &$c];
	assertType('1', $a);
	assertType("'test'", $c);

	$b[0] = 2;
	$b[1] = 'foo';
	$b[2] = 'bar';

	assertType('2', $a);
	assertType("'bar'", $c);
}
