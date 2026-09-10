<?php declare(strict_types = 1);

namespace PHPStan;

/**
 * @phpstan-pure
 * @param mixed $value
 * @param mixed $values
 * @return mixed
 *
 * @throws void
 */
// the parameters are consumed by the analyser, not by the function body
// @phpstan-ignore function.unusedParameter, function.unusedParameter
function dumpType($value, ...$values) // phpcs:ignore Squiz.Functions.GlobalFunction.Found
{
	return null;
}

/**
 * @phpstan-pure
 * @param mixed $value
 * @param mixed $values
 * @return mixed
 *
 * @throws void
 */
// the parameters are consumed by the analyser, not by the function body
// @phpstan-ignore function.unusedParameter, function.unusedParameter
function dumpNativeType($value, ...$values) // phpcs:ignore Squiz.Functions.GlobalFunction.Found
{
	return null;
}

/**
 * @phpstan-pure
 * @param mixed $value
 * @param mixed $values
 * @return mixed
 *
 * @throws void
 */
// the parameters are consumed by the analyser, not by the function body
// @phpstan-ignore function.unusedParameter, function.unusedParameter
function dumpPhpDocType($value, ...$values) // phpcs:ignore Squiz.Functions.GlobalFunction.Found
{
	return null;
}
