<?php

namespace Bug15250Comparison;

/**
 * @param array<'a'|'b', int> $array
 */
function narrowedForeachKey(array $array): void
{
	foreach ($array as $key => $value) {
		if ($key === 'a') {
			throw new \LogicException();
		}
	}

	foreach ($array as $key => $value) {
		if (is_int($key)) {
			echo $key;
		}
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
		$lastKey = $key;
		$lastValue = $value;
	}

	if (is_int($lastKey)) {
		echo $lastKey;
	}
	if (is_float($lastValue)) {
		echo $lastValue;
	}
}

/**
 * @param array{int, string} $x
 */
function destructuring(array $x): void
{
	[$a, $b] = $x;
	if (is_float($a)) {
		echo $a;
	}
	if (is_float($b)) {
		echo $b;
	}
}

/**
 * @param int $x
 */
function byRefParam(&$x): void
{
	$x = 1;
}

/**
 * @param-out int $y
 */
function byRefParamOut(&$y): void
{
	$y = 1;
}

function byRef($v, $w): void
{
	byRefParam($v);
	if (is_int($v)) {
		echo $v;
	}

	byRefParamOut($w);
	if (is_int($w)) {
		echo $w;
	}
}
