<?php // lint >= 8.0

declare(strict_types = 1);

namespace UnusedVariableIllegalOffset;

use TypeError;

/**
 * @param mixed[] $array
 * @param mixed $key
 * @return mixed[]
 */
function write(array $array, $key): array
{
	$x = 1;
	try {
		$array[$key] = 1;
		$x = 2;
	} catch (TypeError) {
		echo $x;
		return [];
	}

	echo $x;
	return $array;
}

/**
 * @param mixed[] $array
 * @param mixed $key
 * @return mixed[]
 */
function assignOp(array $array, $key): array
{
	$x = 1;
	try {
		$array[$key] .= 'a';
		$x = 2;
	} catch (TypeError) {
		echo $x;
		return [];
	}

	echo $x;
	return $array;
}

/**
 * @param mixed $key
 * @return mixed[]
 */
function literal($key): array
{
	$x = 1;
	try {
		$array = [$key => 1];
		$x = 2;
	} catch (TypeError) {
		echo $x;
		return [];
	}

	echo $x;
	return $array;
}

/**
 * @param mixed[] $array
 * @return mixed[]
 */
function intKey(array $array, int $key): array
{
	$x = 1;
	try {
		$array[$key] = 1;
		$x = 2;
	} catch (TypeError) {
		echo $x;
		return [];
	}

	echo $x;
	return $array;
}
