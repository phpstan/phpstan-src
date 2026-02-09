<?php // lint >= 8.1

namespace Bug13283;

use function PHPStan\Testing\assertType;

enum Test: string
{
	case NAME = 'name';
	case VALUE = 'value';
}

/**
 * @template T of \BackedEnum
 * @param class-string<T> $enum
 * @phpstan-assert null|value-of<T> $value
 */
function assertValue(mixed $value, string $enum): void
{
	if (null === $value) {
		return;
	}

	if (! is_int($value) && ! is_string($value)) {
		throw new \Exception();
	}

	if (null === $enum::tryFrom($value)) {
		throw new \Exception();
	}
}

function getFromRequest(): mixed
{
	return 'name';
}

$v = getFromRequest();

assertType('mixed', $v);

assertValue($v, Test::class);

assertType("'name'|'value'|null", $v);

$a = null !== $v ? Test::from($v) : null;
