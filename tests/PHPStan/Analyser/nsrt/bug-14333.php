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

function testNonConstantKeyBreaksImplicitIndex(int $key): void
{
	$a = 1;
	$c = 'test';

	$b = [$key => 'x', &$a, &$c];
	assertType('1', $a);
	assertType("'test'", $c);

	// Since $key is non-constant, we don't know the implicit indices of &$a and &$c
	// so we can't correctly track the reference propagation
	$b[2] = 2;
	assertType("1|2|'test'|'x'", $a); // Could be 1|2
	assertType("1|2|'test'|'x'", $c); // Could be 'test'|2
}

function testNested(): void
{
	$a = 1;

	$b = [[&$a]];
	assertType('1', $a);

	$b[0][0] = 2;

	assertType('2', $a);

	$b[0] = [];

	assertType('2', $a);

	$b[0][0] = 3;

	assertType('2', $a);
}

function testMultipleScalarKeyValues(bool $key): void
{
	$a = 1;

	// $key is true|false, so it maps to int 1 or 0 — two possible scalar values
	$b = [$key => &$a];
	assertType('1', $a);

	// $key could be 0 (false) so $b[0] = 2 might update $a through the reference
	$b[0] = 2;
	assertType('1|2', $a);

	// $key could be 1 (true) so $b[1] = 3 might also update $a
	$b[1] = 3;
	assertType('2|3', $a);
}

/** @param 'a'|'b' $key */
function testMultipleScalarKey(string $key): void
{
	$a = 1;

	$b = [$key => 'x', &$a];
	assertType('1', $a);

	// $key has multiple possible values but both are strings,
	// so the implicit index for &$a is still 0
	$b[0] = 2;
	assertType('2', $a);
}

/** @param 0|1 $key */
function testMultipleIntScalarKey(int $key): void
{
	$a = 1;

	$b = [$key => 'x', &$a];
	assertType('1', $a);

	// $key could be 0 or 1, so index could be 1 or 2 — unpredictable
	$b[1] = 2;
	assertType("1|2|'x'", $a); // Could be 1|2
}

function testStringNumericKey(): void
{
	$a = 1;

	// PHP coerces string "2" to int 2 as array key, so next implicit index is 3
	$b = ['2' => 'x', &$a];
	assertType('1', $a);

	$b[3] = 2;
	assertType('2', $a);
}

/** @param int<0, 10> $key */
function testIntegerRangeKey(int $key): void
{
	$a = 1;
	$c = 'test';

	$b = [$key => 'x', &$a, &$c];
	assertType('1', $a);
	assertType("'test'", $c);

	// $key is int<0, 10>, so implicit indices for &$a and &$c are unpredictable
	$b[5] = 2;
	assertType("1|2|'test'|'x'", $a);
	assertType("1|2|'test'|'x'", $c);
}

/** @param int<0, 5> $key */
function testIntegerRangeKeyDirect(int $key): void
{
	$a = 1;

	// Direct by-ref with integer-range key
	$b = [$key => &$a];
	assertType('1', $a);

	$b[3] = 2;
	assertType('1|2', $a);
}

function foo(array &$a): void {}

function testFunctionCall() {
	$b = 1;

	$c = [&$b];
	assertType('array{1}', $c);

	foo($c);
	assertType('array', $c);
	assertType('mixed', $b);
}

function moreTest(bool $bool, int $int) {
	$a = 1;
	$b = 2;
	$c = 3;
	$d = 4;
	$e = 5;
	$f = 6;

	$array = [&$a, '2' => &$b, &$c, $int => &$d, &$e, 'key' => &$f];

	$array[0] = 'a0';
	$array[1] = 'a1';
	$array[2] = 'a2';
	$array[3] = 'a3';
	$array[4] = 'a4';
	$array[5] = 'a5';
	$array['key'] = 'aKey';

	assertType("'a0'", $a);
	assertType("'a2'", $b);
	assertType("'a3'", $c);
	// $d's slot is at $int (general int), so it accumulates every int-keyed
	// value that has ever been at $array[$int] across the lifetime of the
	// byref, but never the string-keyed `'key'` slot ($int can't equal 'key').
	assertType("1|2|3|4|5|'a0'|'a1'|'a2'|'a3'|'a4'|'a5'", $d);
	assertType("1|2|3|4|5|'a0'|'a1'|'a2'|'a3'|'a4'|'a5'", $e);
	assertType("'aKey'", $f);
}
