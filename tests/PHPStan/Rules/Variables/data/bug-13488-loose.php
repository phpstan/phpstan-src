<?php declare(strict_types = 1);

namespace Bug13488Loose;

/**
 * @param array<string, bool> $foo
 */
function looseEqualsFalse(array $foo): void
{
	$randomString = bin2hex(random_bytes(10));

	// ($foo[$key] ?? null) == false is true for BOTH false and null (missing
	// offset, because null == false), so the offset may still be missing:
	// no narrowing, isset()/?? stay meaningful.
	if (($foo[$randomString] ?? null) == false) {
		if (isset($foo[$randomString])) {}
		echo $foo[$randomString] ?? 'x';
	}
}

/**
 * @param array<string, bool> $foo
 */
function looseEqualsTrue(array $foo): void
{
	$randomString = bin2hex(random_bytes(10));

	// ($foo[$key] ?? null) == true is true ONLY for the true value (null == true
	// is false), so the offset must exist: narrowing is correct here and the
	// follow-up isset()/?? are genuinely redundant.
	if (($foo[$randomString] ?? null) == true) {
		if (isset($foo[$randomString])) {}
		echo $foo[$randomString] ?? 'x';
	}
}

/**
 * @param array<string, bool> $foo
 */
function looseNotEqualsFalse(array $foo): void
{
	$randomString = bin2hex(random_bytes(10));

	// ($foo[$key] ?? null) != false is true ONLY for the true value (null == false
	// is true, so null != false is false), so the offset must exist: narrowing is
	// correct here as well.
	if (($foo[$randomString] ?? null) != false) {
		if (isset($foo[$randomString])) {}
		echo $foo[$randomString] ?? 'x';
	}
}

/**
 * @param array<string, bool> $foo
 */
function looseNotEqualsTrue(array $foo): void
{
	$randomString = bin2hex(random_bytes(10));

	// ($foo[$key] ?? null) != true is true for both false and null (missing
	// offset), so the offset may still be missing: no narrowing.
	if (($foo[$randomString] ?? null) != true) {
		if (isset($foo[$randomString])) {}
		echo $foo[$randomString] ?? 'x';
	}
}
