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

function benevolentBranchNotReported(mixed $value): void
{
	// stream_get_contents() returns a *benevolent* string|false. PHPStan intentionally
	// accepts benevolent unions for a string parameter, so the equivalent direct call
	// expectsString(stream_get_contents($r)) is error-free too — reporting it here would
	// re-introduce the pg_escape_bytea false positive this branch inspection guards
	// against. is_resource() also narrows asymmetrically (@phpstan-assert-if-true only),
	// so the else branch must keep the type the ternary actually produces (mixed,
	// accepted) instead of a spurious narrowing. No error should be reported here.
	expectsString(
		is_resource($value)
			? stream_get_contents($value)
			: $value,
	);
}

function strictFalseBranchReported(mixed $value, string|false $sf): void
{
	// A *strict* (non-benevolent) string|false branch is not accepted by the string
	// parameter, so branch inspection reports it even though is_resource() narrows
	// asymmetrically and the ternary's resulting type normalizes to mixed.
	expectsString(
		is_resource($value)
			? $sf
			: $value,
	);
}
