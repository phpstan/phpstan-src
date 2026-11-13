<?php // lint >= 8.0

namespace IdenticalFunctionCallExtension;

use function PHPStan\Testing\assertType;

/** @return 0|1 */
function matchesInt(mixed $value): int
{
	return is_int($value) ? 1 : 0;
}

/** @return 0|1|false */
function matchesIntOrFails(mixed $value): int|false
{
	return is_int($value) ? 1 : 0;
}

function matchesIntOrFalse(mixed $value): int|false
{
	return is_int($value) ? 1 : false;
}

function truthy(mixed $value): void
{
	if (matchesInt($value)) {
		assertType('int', $value);
	}
}

function identicalToOne(mixed $value): void
{
	if (matchesInt($value) === 1) {
		assertType('int', $value);
	}
	if (1 === matchesInt($value)) {
		assertType('int', $value);
	}
	if (matchesIntOrFails($value) === 1) {
		assertType('int', $value);
	}
}

function looselyEqualToOne(mixed $value): void
{
	if (matchesInt($value) == 1) {
		assertType('int', $value);
	}
	if (1 == matchesIntOrFails($value)) {
		assertType('int', $value);
	}
}

function notIdenticalToOne(mixed $value): void
{
	if (matchesInt($value) !== 1) {
		return;
	}
	assertType('int', $value);
}

function otherConstants(mixed $value): void
{
	if (matchesInt($value) === 0) {
		assertType('mixed', $value);
	}
	if (matchesIntOrFalse($value) === 1) {
		assertType('mixed', $value);
	}
	if (matchesIntOrFalse($value) == 1) {
		assertType('mixed', $value);
	}
	if (matchesInt($value) === 2) {
		assertType('mixed', $value);
	}
}
