<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PHPStan\TrinaryLogic;

/**
 * Asserts the static type of a value.
 *
 * @phpstan-pure
 * @param mixed $value
 * @return mixed
 *
 * @throws void
 */
// the parameters are consumed by the analyser, not by the function body
// @phpstan-ignore function.unusedParameter, function.unusedParameter
function assertType(string $type, $value) // phpcs:ignore
{
	return null;
}

/**
 * Asserts the static type of a value.
 *
 * The difference from assertType() is that it doesn't resolve
 * method/function parameter phpDocs.
 *
 * @phpstan-pure
 * @param mixed $value
 * @return mixed
 *
 * @throws void
 */
// the parameters are consumed by the analyser, not by the function body
// @phpstan-ignore function.unusedParameter, function.unusedParameter
function assertNativeType(string $type, $value) // phpcs:ignore
{
	return null;
}

/**
 * Asserts a super type of a value.
 *
 * @phpstan-pure
 * @param mixed $value
 * @return mixed
 *
 * @throws void
 */
// the parameters are consumed by the analyser, not by the function body
// @phpstan-ignore function.unusedParameter, function.unusedParameter
function assertSuperType(string $superType, $value) // phpcs:ignore
{
	return null;
}

/**
 * @phpstan-pure
 * @param mixed $variable
 * @return mixed
 *
 * @throws void
 */
// the parameters are consumed by the analyser, not by the function body
// @phpstan-ignore function.unusedParameter, function.unusedParameter
function assertVariableCertainty(TrinaryLogic $certainty, $variable) // phpcs:ignore
{
	return null;
}
