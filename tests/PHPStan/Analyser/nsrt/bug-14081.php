<?php

declare(strict_types = 1);

namespace Bug14081;

use function PHPStan\Testing\assertType;

/** @param list<string> $array */
function first(array $array): mixed
{
	if (($key = array_key_first($array))) {
		assertType('int<1, max>', $key);
		assertType('non-empty-list<string>', $array);
		assertType('string', $array[$key]);
		return $array[$key];
	}
	return null;
}

/** @param list<string> $array */
function last(array $array): mixed
{
	if (($key = array_key_last($array))) {
		assertType('int<1, max>', $key);
		assertType('non-empty-list<string>', $array);
		assertType('string', $array[$key]);
		return $array[$key];
	}
	return null;
}

/** @param list<string> $array */
function firstNotNull(array $array): mixed
{
	if (($key = array_key_first($array)) !== null) {
		assertType('int<0, max>', $key); // could be int<1, max>
		assertType('list<string>', $array); // could be non-empty-list<string>
		assertType('string', $array[$key]);
		return $array[$key];
	}
	return null;
}

/** @param list<string> $array */
function lastNotNull(array $array): mixed
{
	if (($key = array_key_last($array)) !== null) {
		assertType('int<0, max>', $key); // could be int<1, max>
		assertType('list<string>', $array); // could be non-empty-list<string>
		assertType('string', $array[$key]);
		return $array[$key];
	}
	return null;
}
