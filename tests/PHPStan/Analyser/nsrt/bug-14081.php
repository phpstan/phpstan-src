<?php // lint >= 8.0

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

function maybeNonEmpty(): void
{
	if (rand(0,1)) {
		$array = ['one', 'two'];
	} else {
		$array = [];
	}
	assertType("array{}|array{'one', 'two'}", $array);
	$key = array_key_last($array);
	assertType('0|1|null', $key);
	assertType("'one'|'two'", $array[$key]);
}

/** @param list<string> $array */
function firstNotNull(array $array): mixed
{
	if (($key = array_key_first($array)) !== null) {
		assertType('int<0, max>', $key);
		assertType('non-empty-list<string>', $array);
		assertType('string', $array[$key]);
		return $array[$key];
	}
	return null;
}

/** @param list<string> $array */
function lastNotNull(array $array): mixed
{
	if (($key = array_key_last($array)) !== null) {
		assertType('int<0, max>', $key);
		assertType('non-empty-list<string>', $array);
		assertType('string', $array[$key]);
		return $array[$key];
	}
	return null;
}

/** @param list<string> $array */
function noIf(array $array): void
{
	$key = array_key_first($array);
	assertType('int<0, max>|null', $key);
	assertType('list<string>', $array);
	assertType('string', $array[$key]);

	if ($array === []) {
		return;
	}
	$key = array_key_first($array);
	assertType('int<0, max>', $key);
	assertType('non-empty-list<string>', $array);
	assertType('string', $array[$key]);
}
