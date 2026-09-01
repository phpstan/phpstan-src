<?php // lint >= 8.1

declare(strict_types = 1);

namespace GenericBackedEnum;

use BackedEnum;
use UnitEnum;
use function PHPStan\Testing\assertType;

enum StringEnum: string
{

	case A = 'a';
	case B = 'b';

}

enum IntEnum: int
{

	case One = 1;

}

enum PureEnum
{

	case X;

}

interface HasLabel extends BackedEnum
{

}

/**
 * @extends BackedEnum<string>
 */
interface StringBackedInterface extends BackedEnum
{

}

enum ViaInterface: string implements HasLabel
{

	case A = 'a';

}

enum ViaStringInterface: string implements StringBackedInterface
{

	case A = 'a';

}

function bare(BackedEnum $e): void
{
	assertType('int|string', $e->value);
	assertType('non-decimal-int-string&non-falsy-string', $e->name);
}

/**
 * @param BackedEnum<string> $e
 * @param BackedEnum<int> $i
 */
function withTypes(BackedEnum $e, BackedEnum $i): void
{
	assertType('string', $e->value);
	assertType('int', $i->value);
	assertType('BackedEnum<string>', $e::from('a'));
	assertType('BackedEnum<string>|null', $e::tryFrom('a'));
}

function unresolvedInterface(HasLabel $e): void
{
	assertType('int|string', $e->value);
}

function resolvedInterface(StringBackedInterface $e): void
{
	assertType('string', $e->value);
}

/**
 * @template T of BackedEnum
 * @param T $e
 * @return value-of<T>
 */
function valueOfAny(BackedEnum $e)
{
	return $e->value;
}

/**
 * @template T of BackedEnum<string>
 * @param class-string<T> $className
 * @return T
 */
function fromString(string $className, string $value): BackedEnum
{
	return $className::from($value);
}

function templates(): void
{
	assertType("'a'", valueOfAny(StringEnum::A));
	assertType('1', valueOfAny(IntEnum::One));
	assertType('GenericBackedEnum\StringEnum', fromString(StringEnum::class, 'a'));
}

function unitEnumIsNotGeneric(UnitEnum $e, PureEnum $p): void
{
	assertType('non-decimal-int-string&non-falsy-string', $e->name);
	assertType("'X'", $p->name);
}
