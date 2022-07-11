<?php declare(strict_types=1);

namespace Bug6781;

/**
 * This should increment value0 and value1
 *
 * @param array{value0: int, value1: int} $values
 *
 * @return array{value0: int, value1: int}
 **/
function increment(array $values) : array
{
	return ["value0" => 1 + 1, "value1" => 2 + 2,];
}

/**
 * This just demonstrates function call that returns an array with string
 * string keys value0 and value1 and integer values.
 *
 * @return array{value0: int, value1: int}
 */
function getArray() : array
{
	return [
		"value0" => random_int(0, 100),
		"value1" => random_int(0, 100)
	];
}

/** @return array{value0: int, value1: int} */
function getNext() : array
{
	// starting values, e.g. some kind of baseline
	$startValues      = ["value0" => 1, "value1" => 2];

	// current maximum values
	$currentMaxValues = getArray();

	// if current values equals starting values, then don't increment
	if ($currentMaxValues === $startValues) {
		return $startValues;
	}

	// increment and return new values
	return increment($startValues);
}
