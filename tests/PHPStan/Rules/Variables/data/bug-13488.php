<?php declare(strict_types = 1);

namespace Bug13488;

/**
 * @return array<string, array{foo: bool}>
 */
function foo(): array {
	return [
	   'bar' => ['foo' => true]
	];
}

function test(): void
{
	$foo = foo();

	$randomString = bin2hex(random_bytes(10));

	// The expression is true only when $randomString exists and 'foo' is false.
	// PHPStan must not memorize $foo[$randomString] as existing from this point.
	if (($foo[$randomString]['foo'] ?? null) === false) {
		return;
	}

	if (isset($foo[$randomString])) {}

	if (array_key_exists($randomString, $foo)) {}

	if (($foo[$randomString]['foo'] ?? null) === true) {}
	if (isset($foo[$randomString]['foo'])) {}

	if ($foo[$randomString]['foo']) {}
}

/**
 * @param array<string, bool> $foo
 */
function analogousNotIdentical(array $foo): void
{
	$randomString = bin2hex(random_bytes(10));

	// !== false is the negation of === false; the true branch must not
	// memorize the offset as existing either.
	if (($foo[$randomString] ?? null) !== false) {
		if (isset($foo[$randomString])) {}
	}
}

/**
 * @param array<string, bool> $foo
 */
function analogousFalseyRightOperand(array $foo): void
{
	$randomString = bin2hex(random_bytes(10));

	// A falsey (but non-null) right operand of ?? must be treated the same
	// way: the whole expression can still be produced by a missing offset.
	if (($foo[$randomString] ?? 0) === false) {
		return;
	}

	if (isset($foo[$randomString])) {}
}
