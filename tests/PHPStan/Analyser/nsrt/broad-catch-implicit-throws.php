<?php

namespace BroadCatchImplicitThrows;

use function PHPStan\Testing\assertType;

function mayFail(): void
{
	if (rand(0, 1)) {
		throw new \RuntimeException();
	}
}

/** @throws \InvalidArgumentException */
function invalidArgument(): void
{
	throw new \InvalidArgumentException();
}

/** @throws \TypeError */
function typeError(): void
{
	throw new \TypeError();
}

function catchException(bool $condition): void
{
	try {
		mayFail();
		if ($condition) {
			invalidArgument();
		}
	} catch (\Exception $e) {
		assertType('bool', $condition);
		assertType('Exception', $e);
	}
}

function catchError(bool $condition): void
{
	try {
		mayFail();
		if ($condition) {
			typeError();
		}
	} catch (\Error $e) {
		assertType('bool', $condition);
		assertType('Error', $e);
	}
}
