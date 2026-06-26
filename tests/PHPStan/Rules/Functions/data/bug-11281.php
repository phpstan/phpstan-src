<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug11281Functions;

function sayHello(int $i): void
{
}

/**
 * @param array<string, mixed> $values
 */
function test(array $values): void
{
	// The ternary's resulting type normalizes to mixed (mixed|string),
	// but the else branch is definitely a string passed to an int parameter.
	sayHello(array_key_exists('key', $values) ? $values['key'] : ' a string');
}

/**
 * @param array<string, mixed> $values
 */
function noError(array $values): void
{
	// Numeric-ish coercible branches must not be flagged.
	sayHello(array_key_exists('key', $values) ? $values['key'] : 5);
}

/**
 * @param array<string, mixed> $values
 */
function nested(array $values, bool $other, bool $another): void
{
	sayHello($other ? $values['key'] : ($another ? 1 : ' nested string'));
}

function expectsString(string $s): void
{
}

function falsePositive(mixed $value): void
{
	// is_resource() only narrows asymmetrically (@phpstan-assert-if-true), so the
	// else branch must keep the type the ternary actually produces (mixed, accepted),
	// not a spurious narrowing. No error should be reported here.
	expectsString(
		is_resource($value)
			? stream_get_contents($value)
			: $value,
	);
}
