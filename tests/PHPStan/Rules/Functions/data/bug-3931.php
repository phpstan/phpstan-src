<?php

namespace Bug3931;

use function PHPStan\Testing\assertType;

/**
 * @template T of array
 * @param T $arr
 * @return T & array{mykey: int, ...}
 */
function addSomeKey(array $arr, int $value): array {
	$arr['mykey'] = $value;
	assertType("T of array (function Bug3931\addSomeKey(), argument)&hasOffsetValue('mykey', int)&non-empty-array", $arr);
	return $arr;
}

/**
 * @param array<string> $arr
 * @return void
 */
function test(array $arr): void
{
	$r = addSomeKey($arr, 1);
	assertType("T of array (function Bug3931\addSomeKey(), argument)&hasOffsetValue('mykey', int)&non-empty-array", $r);
}
