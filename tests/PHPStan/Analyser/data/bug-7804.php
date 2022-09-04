<?php declare(strict_types = 1);

namespace Bug7804;

use function PHPStan\Testing\assertType;

/** @param array<int, string> $headers */
function pop1(array $headers): void
{
	if (count($headers) >= 4) {
		assertType('int<4, max>', count($headers));
		array_pop($headers);
		assertType('int<3, max>', count($headers));
	}
}

/** @param array<int, string> $headers */
function pop2(array $headers): void
{
	assertType('int<0, max>', count($headers));
	array_pop($headers);
	assertType('int<0, max>', count($headers));
}

/** @param array<int, string> $headers */
function shift1(array $headers): void
{
	if (count($headers) >= 4) {
		assertType('int<4, max>', count($headers));
		array_shift($headers);
		assertType('int<3, max>', count($headers));
	}
}


/** @param array<int, string> $headers */
function shift2(array $headers): void
{
	assertType('int<0, max>', count($headers));
	array_shift($headers);
	assertType('int<0, max>', count($headers));
}
