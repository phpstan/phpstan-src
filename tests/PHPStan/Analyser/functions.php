<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

/**
 * Asserts the static type of a value.
 *
 * @param string $type
 * @param mixed $value
 */
function assertType(string $type, $value): void // phpcs:ignore
{
}

/**
 * Asserts the static type of a value.
 *
 * The difference from assertType() is that it doesn't resolve
 * method/function parameter phpDocs.
 *
 * @param string $type
 * @param mixed $value
 */
function assertNativeType(string $type, $value): void // phpcs:ignore
{
}
