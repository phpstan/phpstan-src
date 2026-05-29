<?php declare(strict_types = 1);

namespace NegatedBooleanAndConditionalHolders;

use function PHPStan\Testing\assertType;

// The negation of a `&&` condition specifies the inner BooleanAnd in a mixed
// truthy-and-false context. The cross-kind conditional holders must still be
// created from the falsey narrowing in that context (and the isset() truthy
// fallback must NOT fire there).

/**
 * @param array<string, mixed> $data
 */
function negatedIsset(array $data): void
{
	if (!(isset($data['subtitle']) && !is_string($data['subtitle']))) {
		// key is either unset or a string
		assertType('string', $data['subtitle'] ?? 'fallback');
		if (isset($data['subtitle'])) {
			assertType('string', $data['subtitle']);
		}
	}
}

/**
 * @param mixed $y
 */
function negatedSimpleBool(bool $a, $y): void
{
	if (!($a && !is_string($y))) {
		if ($a) {
			assertType('string', $y);
		}
	}
}

/**
 * @param mixed $y
 */
function negatedSimpleInt(bool $a, $y): void
{
	if (!($a && !is_int($y))) {
		if ($a) {
			assertType('int', $y);
		}
	}
}

/**
 * @param mixed $y
 */
function negatedNotNull(?int $x, $y): void
{
	if (!($x !== null && !is_string($y))) {
		if ($x !== null) {
			assertType('string', $y);
		}
	}
}

/**
 * @param array<string, mixed> $data
 */
function negatedArrayKeyExists(array $data): void
{
	if (!(array_key_exists('subtitle', $data) && !is_string($data['subtitle']))) {
		if (array_key_exists('subtitle', $data)) {
			assertType('string', $data['subtitle']);
		}
	}
}
