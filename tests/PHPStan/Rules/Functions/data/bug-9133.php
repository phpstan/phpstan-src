<?php

namespace Bug9133;

/**
 * @param never $value
 */
function assertNever(mixed $value): never
{
	throw new \LogicException();
}

function testOk(int|string $value): void
{
	if (is_string($value)) {
		echo 'STRING';
	} elseif (is_int($value)) {
		echo 'INT';
	} else {
		assertNever($value);
	}
}

function testMissingCheck(int|string $value): void
{
	if (is_string($value)) {
		echo 'STRING';
	} else {
		assertNever($value); // this should return error, because int is not a subtype of never
	}
}
