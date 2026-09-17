<?php declare(strict_types = 1);

namespace Bug15250;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

/**
 * @param array<'a'|'b', int> $array
 */
function doFoo(array $array): void
{
	assertNativeType('array', $array);
	foreach ($array as $key => $value) {
		if ($key === 'a') {
			throw new \LogicException();
		}
	}
	assertType("array<'b', int>", $array);
	assertNativeType('array', $array);

	foreach ($array as $key => $value) {
		assertType("'b'", $key);
		assertNativeType('(int|string)', $key);
	}
}

/**
 * @param array{a: int, b: string} $array
 */
function unrolledShape(array $array): void
{
	$lastKey = null;
	$lastValue = null;
	foreach ($array as $key => $value) {
		assertType("'a'|'b'", $key);
		assertNativeType('(int|string)', $key);
		assertType('int|string', $value);
		assertNativeType('mixed', $value);
		$lastKey = $key;
		$lastValue = $value;
	}

	assertType("'b'", $lastKey);
	assertNativeType('(int|string)', $lastKey);
	assertType('string', $lastValue);
	assertNativeType('mixed', $lastValue);
}

function unrolledNativeShape(): void
{
	$array = ['a' => 1, 'b' => 'foo'];
	$lastKey = null;
	$lastValue = null;
	foreach ($array as $key => $value) {
		$lastKey = $key;
		$lastValue = $value;
	}

	assertType("'b'", $lastKey);
	assertNativeType("'b'", $lastKey);
	assertType("'foo'", $lastValue);
	assertNativeType("'foo'", $lastValue);
}

/**
 * @param array{int, string} $x
 * @param array<int, array{int, string}> $rows
 */
function destructuring(array $x, array $rows): void
{
	[$a, $b] = $x;
	assertType('int', $a);
	assertNativeType('mixed', $a);
	assertType('string', $b);
	assertNativeType('mixed', $b);

	foreach ($rows as [$c, $d]) {
		assertType('int', $c);
		assertNativeType('mixed', $c);
		assertType('string', $d);
		assertNativeType('mixed', $d);
	}
}

function nativeDestructuring(): void
{
	$x = [1, 'foo'];
	[$a, $b] = $x;
	assertType('1', $a);
	assertNativeType('1', $a);
	assertType("'foo'", $b);
	assertNativeType("'foo'", $b);
}

/** @param positive-int $i */
function byRefParam(int &$i): void
{
	$i = 1;
}

/** @param-out non-empty-string $s */
function byRefParamOut(string &$s): void
{
	$s = 'foo';
}

function byRef(int $v, string $s): void
{
	byRefParam($v);
	assertType('int<1, max>', $v);
	assertNativeType('int', $v);

	byRefParamOut($s);
	assertType('non-empty-string', $s);
	assertNativeType('string', $s);
}
