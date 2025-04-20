<?php

namespace Bug7804;

use function PHPStan\Testing\assertType;

/** @param array<int, string> $headers */
function headers(array $headers): void
{
	assertType('int<0, max>', count($headers));
	if (count($headers) >= 4) {
		assertType('int<4, max>', count($headers));
		array_pop($headers);
		assertType('int<3, max>', count($headers));
		array_pop($headers);
		assertType('int<2, max>', count($headers));
		array_pop($headers);
		assertType('int<1, max>', count($headers));
		array_pop($headers);
		assertType('int<0, max>', count($headers));
		array_pop($headers);
		assertType('int<0, max>', count($headers));
	}
	assertType('int<0, max>', count($headers));
}

function doPop(array $arr) {
	assertType('int<0, max>', count($arr));
	array_pop($arr);
	assertType('int<0, max>', count($arr));

	if (count($arr) === 2) {
		assertType('2', count($arr));
		array_pop($arr);
		assertType('1', count($arr));
	}
	assertType('int<0, 1>|int<3, max>', count($arr));
}

function doShift(array $arr) {
	assertType('int<0, max>', count($arr));
	array_shift($arr);
	assertType('int<0, max>', count($arr));
}

function doPush(array $arr, int $i) {
	assertType('int<0, max>', count($arr));
	array_push($arr, $i);
	assertType('int<1, max>', count($arr));
	array_push($arr, 3, $i, false, null);
	assertType('int<5, max>', count($arr));
}

function doPushVariadic(array $arr, array $arr2) {
	assertType('int<0, max>', count($arr));
	array_push($arr, ...$arr2);
	assertType('int<0, max>', count($arr));
}

function doUnshift(array $arr, bool $b) {
	assertType('int<0, max>', count($arr));
	array_unshift($arr, $b);
	assertType('int<1, max>', count($arr));
}
