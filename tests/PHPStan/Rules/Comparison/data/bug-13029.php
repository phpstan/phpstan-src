<?php // lint >= 8.0

namespace Bug13029;

/**
 * @param array{a: bool, b: bool} $arr
 */
function matchWithBoolArrayExhaustive(array $arr): int
{
	return match(true) {
		$arr['a'] && $arr['b'] => 1,
		!$arr['a'] && !$arr['b'] => 2,
		!$arr['a'] && $arr['b'] => 3,
		$arr['a'] && !$arr['b'] => 4,
	};
}

/**
 * @param array{a: bool, b: bool} $arr
 */
function matchWithBoolArrayExhaustive2(array $arr): int
{
	return match(true) {
		$arr['a'] === true && $arr['b'] === true => 1,
		$arr['a'] === false && $arr['b'] === false => 2,
		$arr['a'] === false && $arr['b'] === true => 3,
		$arr['a'] === true && $arr['b'] === false => 4,
	};
}

/**
 * @param array{a: bool, b: bool} $arr
 */
function matchWithBoolArrayAndDefault(array $arr): int
{
	return match(true) {
		$arr['a'] && $arr['b'] => 1,
		$arr['a'] || $arr['b'] => 2,
		default => 3,
	};
}
