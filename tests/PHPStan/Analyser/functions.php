<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\TrinaryLogic;

/**
 * Asserts the static type of a value.
 *
 * @phpstan-pure
 * @param string $type
 * @param mixed $value
 * @return mixed
 */
function assertType(string $type, $value) // phpcs:ignore
{
}

/**
 * Asserts the static type of a value.
 *
 * The difference from assertType() is that it doesn't resolve
 * method/function parameter phpDocs.
 *
 * @phpstan-pure
 * @param string $type
 * @param mixed $value
 * @return mixed
 */
function assertNativeType(string $type, $value) // phpcs:ignore
{
}

/**
 * @phpstan-pure
 * @param TrinaryLogic $certainty
 * @param mixed $variable
 * @return mixed
 */
function assertVariableCertainty(TrinaryLogic $certainty, $variable) // phpcs:ignore
{
}
