<?php declare(strict_types = 1);

namespace PHPStan;

/**
 * @phpstan-pure
 *
 * @throws void
 */
// the parameters are consumed by the analyser, not by the function body
// @phpstan-ignore function.unusedParameter, function.unusedParameter
function dumpType(mixed $value, mixed ...$values): mixed // phpcs:ignore Squiz.Functions.GlobalFunction.Found
{
	return null;
}

/**
 * @phpstan-pure
 *
 * @throws void
 */
// the parameters are consumed by the analyser, not by the function body
// @phpstan-ignore function.unusedParameter, function.unusedParameter
function dumpNativeType(mixed $value, mixed ...$values): mixed // phpcs:ignore Squiz.Functions.GlobalFunction.Found
{
	return null;
}

/**
 * @phpstan-pure
 *
 * @throws void
 */
// the parameters are consumed by the analyser, not by the function body
// @phpstan-ignore function.unusedParameter, function.unusedParameter
function dumpPhpDocType(mixed $value, mixed ...$values): mixed // phpcs:ignore Squiz.Functions.GlobalFunction.Found
{
	return null;
}
