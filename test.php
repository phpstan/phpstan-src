<?php

use function PHPStan\Testing\assertType;

/** @return non-empty-string|null */
function bug12749(string $str): ?string
{
	if (preg_match('/[A-Z]/', $str, $match)) {
		assertType('array{non-empty-string}', $match);
		return $match[0];
	}
	return null;
}

/** @return non-falsy-string|null */
function doFoo(string $str): ?string
{
	if (preg_match('/[A-Z]{2,}/', $str, $match)) {
		assertType('array{non-falsy-string}', $match);
		return $match[0];
	}
	return null;
}

/** @return non-falsy-string|null */
function doBar(string $str): ?string
{
	if (preg_match('/[0-9][A-Z]/', $str, $match)) {
		assertType('array{non-falsy-string}', $match);
		return $match[0];
	}
	return null;
}

/** @return non-empty-string|null */
function doFooBar(string $str): ?string
{
	if (preg_match('/[0-9][A-Z]?/', $str, $match)) {
		assertType('array{non-empty-string}', $match);
		return $match[0];
	}
	return null;
}

/** @return non-falsy-string|null */
function doFooBar2(string $str): ?string
{
	if (preg_match('/[0-9]?[A-Z]/', $str, $match)) {
		assertType('array{non-falsy-string}', $match);
		return $match[0];
	}
	return null;
}
