<?php // lint >= 8.2

declare(strict_types = 1);

namespace SensitiveParameterValueGenerics;

use SensitiveParameterValue;
use function PHPStan\Testing\assertType;

function inference(string $password, int $pin): void
{
	$value = new SensitiveParameterValue($password);
	assertType('SensitiveParameterValue<string>', $value);
	assertType('string', $value->getValue());

	$value = new SensitiveParameterValue([$pin]);
	assertType('SensitiveParameterValue<array{int}>', $value);
	assertType('array{int}', $value->getValue());
}

/**
 * @param SensitiveParameterValue<non-empty-string> $value
 */
function withTypeArgs(SensitiveParameterValue $value): void
{
	assertType('non-empty-string', $value->getValue());
}

function withoutTypeArgs(SensitiveParameterValue $value): void
{
	assertType('SensitiveParameterValue', $value);
	assertType('mixed', $value->getValue());
}

/**
 * @param SensitiveParameterValue<non-empty-string> $value
 * @return SensitiveParameterValue<string>
 */
function covariance(SensitiveParameterValue $value): SensitiveParameterValue
{
	assertType('SensitiveParameterValue<non-empty-string>', $value);

	return $value;
}
