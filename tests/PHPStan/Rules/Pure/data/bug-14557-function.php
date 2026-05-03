<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14557Function;

enum MyEnum: string
{
	case Foo = 'foo';
	case Bar = 'bar';
}

/**
 * @param enum-string<MyEnum> $enum
 * @phpstan-pure
 */
function fromEnumString(string $enum): MyEnum
{
	return $enum::from('foo');
}

/**
 * @param enum-string<MyEnum> $enum
 * @phpstan-pure
 */
function tryFromEnumString(string $enum): ?MyEnum
{
	return $enum::tryFrom('foo');
}

/**
 * @param class-string<MyEnum> $enum
 * @phpstan-pure
 */
function fromClassString(string $enum): MyEnum
{
	return $enum::from('foo');
}

/**
 * @param class-string<MyEnum> $enum
 * @phpstan-pure
 */
function tryFromClassString(string $enum): ?MyEnum
{
	return $enum::tryFrom('foo');
}

/**
 * @phpstan-pure
 */
function fromEnum(MyEnum $enum): MyEnum
{
	return $enum::from('foo');
}
