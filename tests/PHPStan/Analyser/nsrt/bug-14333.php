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

function testNested(): void
{
	$a = 1;

	$b = [[&$a]];
	assertType('1', $a);

	$b[0][0] = 2;

	assertType('1', $a); // Should be 2 in real PHP, but nested array reference tracking is not implemented

	$b[0] = [];

	assertType('1', $a);

	$b[0][0] = 3;

	assertType('1', $a);
}
