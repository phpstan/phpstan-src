<?php // lint >= 8.1

namespace ReflectionClassGetConstants;

use ReflectionClass;
use function PHPStan\Testing\assertType;

class Foo
{
	public const A = 1;
	public const B = 'hello';
	protected const C = 3.14;
	private const D = true;
}

class Bar
{
	public const X = 'x';
}

final class FinalClass
{
	public const ONE = 1;
	public const TWO = 2;
}

enum SimpleEnum
{
	case Hearts;
	case Diamonds;
}

enum BackedEnum: string
{
	case Active = 'active';
	case Inactive = 'inactive';
}

enum MixedEnum: int
{
	const SOME_CONST = 42;
	case One = 1;
	case Two = 2;
}

interface HasConstants
{
	public const IFACE_CONST = 'iface';
}

class ParentClass
{
	public const PARENT_CONST = 'parent';
}

class ChildClass extends ParentClass
{
	public const CHILD_CONST = 'child';
}

/**
 * @param ReflectionClass<Foo> $ref
 */
function testGetConstantKnown(ReflectionClass $ref): void
{
	assertType('1', $ref->getConstant('A'));
	assertType("'hello'", $ref->getConstant('B'));
	assertType('3.14', $ref->getConstant('C'));
	assertType('true', $ref->getConstant('D'));
}

/**
 * @param ReflectionClass<Foo> $ref
 */
function testGetConstantNonExistent(ReflectionClass $ref): void
{
	assertType('false', $ref->getConstant('nonExistent'));
}

/**
 * @param ReflectionClass<Foo> $ref
 */
function testGetConstantDynamic(ReflectionClass $ref, string $name): void
{
	assertType("1|3.14|'hello'|bool", $ref->getConstant($name));
}

function testGetConstantUnknownClass(ReflectionClass $ref): void
{
	assertType('mixed', $ref->getConstant('A'));
}

/**
 * @param ReflectionClass<Foo> $ref
 */
function testGetConstants(ReflectionClass $ref): void
{
	assertType("array{A: 1, B: 'hello', C: 3.14, D: true}", $ref->getConstants());
}

function testGetConstantsUnknownClass(ReflectionClass $ref): void
{
	assertType('array<string, mixed>', $ref->getConstants());
}

/**
 * @param ReflectionClass<FinalClass> $ref
 */
function testGetConstantsFinalClass(ReflectionClass $ref): void
{
	assertType('array{ONE: 1, TWO: 2}', $ref->getConstants());
}

/**
 * @param ReflectionClass<Bar> $ref
 */
function testGetConstantsSimple(ReflectionClass $ref): void
{
	assertType("array{X: 'x'}", $ref->getConstants());
}

/**
 * @param ReflectionClass<SimpleEnum> $ref
 */
function testGetConstantsEnum(ReflectionClass $ref): void
{
	assertType('array{Hearts: ReflectionClassGetConstants\SimpleEnum::Hearts, Diamonds: ReflectionClassGetConstants\SimpleEnum::Diamonds}', $ref->getConstants());
}

/**
 * @param ReflectionClass<BackedEnum> $ref
 */
function testGetConstantsBackedEnum(ReflectionClass $ref): void
{
	assertType('array{Active: ReflectionClassGetConstants\BackedEnum::Active, Inactive: ReflectionClassGetConstants\BackedEnum::Inactive}', $ref->getConstants());
}

/**
 * @param ReflectionClass<MixedEnum> $ref
 */
function testGetConstantsEnumWithConst(ReflectionClass $ref): void
{
	assertType('array{SOME_CONST: 42, One: ReflectionClassGetConstants\MixedEnum::One, Two: ReflectionClassGetConstants\MixedEnum::Two}', $ref->getConstants());
}

/**
 * @param ReflectionClass<SimpleEnum> $ref
 */
function testGetConstantEnumCase(ReflectionClass $ref): void
{
	assertType('ReflectionClassGetConstants\SimpleEnum::Hearts', $ref->getConstant('Hearts'));
	assertType('false', $ref->getConstant('nonExistent'));
}

/**
 * @param ReflectionClass<HasConstants> $ref
 */
function testGetConstantsInterface(ReflectionClass $ref): void
{
	assertType("array{IFACE_CONST: 'iface'}", $ref->getConstants());
}

/**
 * @param ReflectionClass<ChildClass> $ref
 */
function testGetConstantsInheritance(ReflectionClass $ref): void
{
	assertType("array{CHILD_CONST: 'child', PARENT_CONST: 'parent'}", $ref->getConstants());
}

/**
 * @param ReflectionClass<ChildClass> $ref
 */
function testGetConstantInherited(ReflectionClass $ref): void
{
	assertType("'parent'", $ref->getConstant('PARENT_CONST'));
	assertType("'child'", $ref->getConstant('CHILD_CONST'));
}

/**
 * @param ReflectionClass<Foo> $ref
 */
function testGetConstantsWithFilter(ReflectionClass $ref): void
{
	assertType("array{A: 1, B: 'hello'}", $ref->getConstants(\ReflectionClassConstant::IS_PUBLIC));
	assertType('array{C: 3.14}', $ref->getConstants(\ReflectionClassConstant::IS_PROTECTED));
	assertType('array{D: true}', $ref->getConstants(\ReflectionClassConstant::IS_PRIVATE));
}

/**
 * @param ReflectionClass<Foo> $ref
 */
function testGetConstantsWithDynamicFilter(ReflectionClass $ref, int $filter): void
{
	assertType("array{A?: 1, B?: 'hello', C?: 3.14, D?: true}", $ref->getConstants($filter));
}

/**
 * @param ReflectionClass<Foo> $ref
 * @param \ReflectionClassConstant::IS_PUBLIC|\ReflectionClassConstant::IS_PROTECTED $filter
 */
function testGetConstantsWithMultipleConstantFilters(ReflectionClass $ref, int $filter): void
{
	assertType("array{A: 1, B: 'hello'}|array{C: 3.14}", $ref->getConstants($filter));
}
