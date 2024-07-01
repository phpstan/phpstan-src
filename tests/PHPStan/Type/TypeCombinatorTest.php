<?php declare(strict_types = 1);

namespace PHPStan\Type;

use Bug9006\TestInterface;
use CheckTypeFunctionCall\FinalClassWithMethodExists;
use CheckTypeFunctionCall\FinalClassWithPropertyExists;
use Closure;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DynamicProperties\FinalFoo;
use Exception;
use InvalidArgumentException;
use Iterator;
use ObjectShapesAcceptance\ClassWithFooIntProperty;
use PHPStan\Fixture\FinalClass;
use PHPStan\Reflection\Callables\SimpleImpurePoint;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\HasMethodType;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Accessory\OversizedArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateBenevolentUnionType;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\Generic\TemplateObjectType;
use PHPStan\Type\Generic\TemplateObjectWithoutClassType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use RecursionCallable\Foo;
use stdClass;
use Test\ClassWithNullableProperty;
use Test\ClassWithToString;
use Test\FirstInterface;
use Throwable;
use Traversable;
use function array_map;
use function array_reverse;
use function implode;
use function sprintf;
use const PHP_VERSION_ID;

class TypeCombinatorTest extends PHPStanTestCase
{

	public function dataAddNull(): array
	{
		return [
			[
				new MixedType(),
				MixedType::class,
				'mixed',
			],
			[
				new NullType(),
				NullType::class,
				'null',
			],
			[
				new VoidType(),
				UnionType::class,
				'void|null',
			],
			[
				new StringType(),
				UnionType::class,
				'string|null',
			],
			[
				new UnionType([
					new StringType(),
					new IntegerType(),
				]),
				UnionType::class,
				'int|string|null',
			],
			[
				new UnionType([
					new StringType(),
					new IntegerType(),
					new NullType(),
				]),
				UnionType::class,
				'int|string|null',
			],
			[
				new IntersectionType([
					new IterableType(new MixedType(), new StringType()),
					new ObjectType('ArrayObject'),
				]),
				UnionType::class,
				'(ArrayObject&iterable<string>)|null',
			],
			[
				new UnionType([
					new IntersectionType([
						new IterableType(new MixedType(), new StringType()),
						new ObjectType('ArrayObject'),
					]),
					new NullType(),
				]),
				UnionType::class,
				'(ArrayObject&iterable<string>)|null',
			],
		];
	}

	/**
	 * @dataProvider dataAddNull
	 * @param class-string<Type> $expectedTypeClass
	 */
	public function testAddNull(
		Type $type,
		string $expectedTypeClass,
		string $expectedTypeDescription,
	): void
	{
		$result = TypeCombinator::addNull($type);
		$this->assertSame($expectedTypeDescription, $result->describe(VerbosityLevel::precise()));
		$this->assertInstanceOf($expectedTypeClass, $result);
	}

	/**
	 * @dataProvider dataAddNull
	 * @param class-string<Type> $expectedTypeClass
	 */
	public function testUnionWithNull(
		Type $type,
		string $expectedTypeClass,
		string $expectedTypeDescription,
	): void
	{
		$result = TypeCombinator::union($type, new NullType());
		$this->assertSame($expectedTypeDescription, $result->describe(VerbosityLevel::precise()));
		$this->assertInstanceOf($expectedTypeClass, $result);
	}

	public function dataRemoveNull(): array
	{
		$reflectionProvider = $this->createReflectionProvider();

		return [
			[
				new MixedType(),
				MixedType::class,
				'mixed',
			],
			[
				new NullType(),
				NeverType::class,
				'*NEVER*',
			],
			[
				new VoidType(),
				VoidType::class,
				'void',
			],
			[
				new StringType(),
				StringType::class,
				'string',
			],
			[
				new UnionType([
					new StringType(),
					new IntegerType(),
					new NullType(),
				]),
				UnionType::class,
				'int|string',
			],
			[
				new UnionType([
					new StringType(),
					new IntegerType(),
				]),
				UnionType::class,
				'int|string',
			],
			[
				new UnionType([
					new IntersectionType([
						new IterableType(new MixedType(), new StringType()),
						new ObjectType('ArrayObject'),
					]),
					new NullType(),
				]),
				IntersectionType::class,
				'ArrayObject&iterable<string>',
			],
			[
				new IntersectionType([
					new IterableType(new MixedType(), new StringType()),
					new ObjectType('ArrayObject'),
				]),
				IntersectionType::class,
				'ArrayObject&iterable<string>',
			],
			[
				new UnionType([
					new ThisType($reflectionProvider->getClass(Exception::class)),
					new NullType(),
				]),
				ThisType::class,
				'$this(Exception)',
			],
			[
				new UnionType([
					new ThisType($reflectionProvider->getClass(Exception::class)),
					new NullType(),
				]),
				ThisType::class,
				'$this(Exception)',
			],
			[
				new UnionType([
					new IterableType(new MixedType(), new StringType()),
					new NullType(),
				]),
				IterableType::class,
				'iterable<string>',
			],
		];
	}

	/**
	 * @dataProvider dataRemoveNull
	 * @param class-string<Type> $expectedTypeClass
	 */
	public function testRemoveNull(
		Type $type,
		string $expectedTypeClass,
		string $expectedTypeDescription,
	): void
	{
		$result = TypeCombinator::removeNull($type);
		$this->assertSame($expectedTypeDescription, $result->describe(VerbosityLevel::precise()));
		$this->assertInstanceOf($expectedTypeClass, $result);
	}

	public function dataUnion(): iterable
	{
		yield from [
			[
				[
					new StringType(),
					new NullType(),
				],
				UnionType::class,
				'string|null',
			],
			[
				[
					new MixedType(),
					new IntegerType(),
				],
				MixedType::class,
				'mixed=implicit',
			],
			[
				[
					new ConstantBooleanType(true),
					new ConstantBooleanType(false),
				],
				BooleanType::class,
				'bool',
			],
			[
				[
					new StringType(),
					new IntegerType(),
				],
				UnionType::class,
				'int|string',
			],
			[
				[
					new UnionType([
						new StringType(),
						new IntegerType(),
					]),
					new StringType(),
				],
				UnionType::class,
				'int|string',
			],
			[
				[
					new UnionType([
						new StringType(),
						new IntegerType(),
					]),
					new ConstantBooleanType(true),
				],
				UnionType::class,
				'int|string|true',
			],
			[
				[
					new UnionType([
						new StringType(),
						new IntegerType(),
					]),
					new NullType(),
				],
				UnionType::class,
				'int|string|null',
			],
			[
				[
					new UnionType([
						new StringType(),
						new IntegerType(),
						new NullType(),
					]),
					new NullType(),
				],
				UnionType::class,
				'int|string|null',
			],
			[
				[
					new UnionType([
						new StringType(),
						new IntegerType(),
					]),
					new StringType(),
				],
				UnionType::class,
				'int|string',
			],
			[
				[
					new IntersectionType([
						new IterableType(new MixedType(), new IntegerType()),
						new ObjectType('ArrayObject'),
					]),
					new StringType(),
				],
				UnionType::class,
				'(ArrayObject&iterable<int>)|string',
			],
			[
				[
					new IntersectionType([
						new IterableType(new MixedType(), new IntegerType()),
						new ObjectType('ArrayObject'),
					]),
					new ArrayType(new MixedType(), new StringType()),
				],
				UnionType::class,
				'array<string>|(ArrayObject&iterable<int>)',
			],
			[
				[
					new UnionType([
						new ConstantBooleanType(true),
						new IntegerType(),
					]),
					new ArrayType(new MixedType(), new StringType()),
				],
				UnionType::class,
				'array<string>|int|true',
			],
			[
				[
					new UnionType([
						new ArrayType(new MixedType(), new ObjectType('Foo')),
						new ArrayType(new MixedType(), new ObjectType('Bar')),
					]),
					new ArrayType(new MixedType(), new MixedType()),
				],
				ArrayType::class,
				'array',
			],
			[
				[
					new IterableType(new MixedType(), new MixedType()),
					new ArrayType(new MixedType(), new StringType()),
				],
				IterableType::class,
				'iterable',
			],
			[
				[
					new IterableType(new MixedType(), new MixedType()),
					new ArrayType(new MixedType(), new MixedType()),
				],
				IterableType::class,
				'iterable',
			],
			[
				[
					new ArrayType(new MixedType(), new StringType()),
				],
				ArrayType::class,
				'array<string>',
			],
			[
				[
					new ObjectType('ArrayObject'),
					new ObjectType('ArrayIterator'),
					new ArrayType(new MixedType(), new StringType()),
				],
				UnionType::class,
				'array<string>|ArrayIterator|ArrayObject',
			],
			[
				[
					new ObjectType('ArrayObject'),
					new ObjectType('ArrayIterator'),
					new ArrayType(new MixedType(), new StringType()),
					new ArrayType(new MixedType(), new IntegerType()),
				],
				UnionType::class,
				'array<int|string>|ArrayIterator|ArrayObject',
			],
			[
				[
					new IntersectionType([
						new IterableType(new MixedType(), new IntegerType()),
						new ObjectType('ArrayObject'),
					]),
					new ArrayType(new MixedType(), new IntegerType()),
				],
				UnionType::class,
				'array<int>|(ArrayObject&iterable<int>)',
			],
			[
				[
					new ObjectType('UnknownClass'),
					new ObjectType('UnknownClass'),
				],
				ObjectType::class,
				'UnknownClass',
			],
			[
				[
					new IntersectionType([
						new ObjectType('DateTimeInterface'),
						new ObjectType('Traversable'),
					]),
					new IntersectionType([
						new ObjectType('DateTimeInterface'),
						new ObjectType('Traversable'),
					]),
				],
				IntersectionType::class,
				'DateTimeInterface&Traversable',
			],
			[
				[
					new ObjectType('UnknownClass'),
					new ObjectType('UnknownClass'),
				],
				ObjectType::class,
				'UnknownClass',
			],
			[
				[
					new StringType(),
					new NeverType(),
				],
				StringType::class,
				'string',
			],
			[
				[
					new IntersectionType([
						new ObjectType('ArrayObject'),
						new IterableType(new MixedType(), new StringType()),
					]),
					new NeverType(),
				],
				IntersectionType::class,
				'ArrayObject&iterable<string>',
			],
			[
				[
					new IterableType(new MixedType(), new MixedType()),
					new IterableType(new MixedType(), new StringType()),
				],
				IterableType::class,
				'iterable',
			],
			[
				[
					new IterableType(new MixedType(), new IntegerType()),
					new IterableType(new MixedType(), new StringType()),
				],
				IterableType::class,
				'iterable<int|string>',
			],
			[
				[
					new IterableType(new MixedType(), new IntegerType()),
					new IterableType(new IntegerType(), new StringType()),
				],
				IterableType::class,
				'iterable<int|string>',
			],
			[
				[
					new IterableType(new StringType(), new IntegerType()),
					new IterableType(new IntegerType(), new StringType()),
				],
				IterableType::class,
				'iterable<int|string, int|string>',
			],
			[
				[
					new ArrayType(new MixedType(), new MixedType()),
					new ArrayType(new MixedType(), new StringType()),
				],
				ArrayType::class,
				'array',
			],
			[
				[
					new ArrayType(new MixedType(), new IntegerType()),
					new ArrayType(new MixedType(), new StringType()),
				],
				ArrayType::class,
				'array<int|string>',
			],
			[
				[
					new ArrayType(new MixedType(), new IntegerType()),
					new ArrayType(new IntegerType(), new StringType()),
				],
				ArrayType::class,
				'array<int|string>',
			],
			[
				[
					new ArrayType(new StringType(), new IntegerType()),
					new ArrayType(new IntegerType(), new StringType()),
				],
				ArrayType::class,
				'array<int|string, int|string>',
			],
			[
				[
					new UnionType([
						new StringType(),
						new NullType(),
					]),
					new UnionType([
						new StringType(),
						new NullType(),
					]),
					new UnionType([
						new ObjectType('Unknown'),
						new NullType(),
					]),
				],
				UnionType::class,
				'string|Unknown|null',
			],
			[
				[
					new ObjectType(Foo::class),
					new CallableType(),
				],
				UnionType::class,
				'(callable(): mixed)|RecursionCallable\Foo',
			],
			[
				[
					new IntegerType(),
					new ConstantIntegerType(1),
				],
				IntegerType::class,
				'int',
			],
			[
				[
					new ConstantIntegerType(1),
					new ConstantIntegerType(1),
				],
				ConstantIntegerType::class,
				'1',
			],
			[
				[
					new ConstantIntegerType(1),
					new ConstantIntegerType(2),
				],
				UnionType::class,
				'1|2',
			],
			[
				[
					new FloatType(),
					new ConstantFloatType(1.0),
				],
				FloatType::class,
				'float',
			],
			[
				[
					new ConstantFloatType(1.0),
					new ConstantFloatType(1.0),
				],
				ConstantFloatType::class,
				'1.0',
			],
			[
				[
					new ConstantFloatType(1.0),
					new ConstantFloatType(2.0),
				],
				UnionType::class,
				'1.0|2.0',
			],
			[
				[
					new StringType(),
					new ConstantStringType('A'),
				],
				StringType::class,
				'string',
			],
			[
				[
					new ConstantStringType('A'),
					new ConstantStringType('A'),
				],
				ConstantStringType::class,
				'\'A\'',
			],
			[
				[
					new ConstantStringType('A'),
					new ConstantStringType('B'),
				],
				UnionType::class,
				'\'A\'|\'B\'',
			],
			[
				[
					new BooleanType(),
					new ConstantBooleanType(true),
				],
				BooleanType::class,
				'bool',
			],
			[
				[
					new ConstantBooleanType(true),
					new ConstantBooleanType(true),
				],
				ConstantBooleanType::class,
				'true',
			],
			[
				[
					new ConstantBooleanType(false),
					new ConstantBooleanType(false),
				],
				ConstantBooleanType::class,
				'false',
			],
			[
				[
					new ConstantBooleanType(true),
					new ConstantBooleanType(false),
				],
				BooleanType::class,
				'bool',
			],
			[
				[
					new ObjectType(Closure::class),
					new ClosureType([], new MixedType(), false),
				],
				ObjectType::class,
				'Closure',
			],
			[
				[
					new ClosureType([], new MixedType(), false),
					new CallableType(),
				],
				CallableType::class,
				'callable(): mixed',
			],
			[
				// same keys - can remain ConstantArrayType
				[
					new ConstantArrayType([
						new ConstantStringType('foo'),
						new ConstantStringType('bar'),
					], [
						new ObjectType(DateTimeImmutable::class),
						new IntegerType(),
					]),
					new ConstantArrayType([
						new ConstantStringType('foo'),
						new ConstantStringType('bar'),
					], [
						new NullType(),
						new StringType(),
					]),
				],
				UnionType::class,
				'array{foo: DateTimeImmutable, bar: int}|array{foo: null, bar: string}',
			],
			[
				[
					new ConstantArrayType([
						new ConstantStringType('foo'),
						new ConstantStringType('bar'),
					], [
						new ObjectType(DateTimeImmutable::class),
						new IntegerType(),
					]),
					new ConstantArrayType([
						new ConstantStringType('foo'),
					], [
						new NullType(),
					]),
				],
				UnionType::class,
				'array{foo: DateTimeImmutable, bar: int}|array{foo: null}',
			],
			[
				[
					new ConstantArrayType([
						new ConstantStringType('foo'),
						new ConstantStringType('bar'),
					], [
						new ObjectType(DateTimeImmutable::class),
						new IntegerType(),
					]),
					new ConstantArrayType([
						new ConstantStringType('foo'),
						new ConstantStringType('bar'),
						new ConstantStringType('baz'),
					], [
						new NullType(),
						new StringType(),
						new IntegerType(),
					]),
				],
				UnionType::class,
				'array{foo: DateTimeImmutable, bar: int}|array{foo: null, bar: string, baz: int}',
			],
			[
				[
					new ArrayType(
						new IntegerType(),
						new ObjectType(stdClass::class),
					),
					new ConstantArrayType([
						new ConstantStringType('foo'),
						new ConstantStringType('bar'),
					], [
						new ObjectType(DateTimeImmutable::class),
						new IntegerType(),
					]),
				],
				ArrayType::class,
				'array<\'bar\'|\'foo\'|int, DateTimeImmutable|int|stdClass>',
			],
			[
				[
					new ConstantArrayType([new ConstantIntegerType(0)], [new StringType()]),
					new ArrayType(new MixedType(), new StringType()),
				],
				ArrayType::class,
				'array<string>',
			],
			[
				[
					new ConstantArrayType([], []),
					new ConstantArrayType([new ConstantIntegerType(0)], [new StringType()]),
					new ArrayType(new MixedType(), new StringType()),
				],
				ArrayType::class,
				'array<string>',
			],
			[
				[
					new UnionType([new IntegerType(), new StringType()]),
					new BenevolentUnionType([new IntegerType(), new StringType()]),
				],
				BenevolentUnionType::class,
				'(int|string)',
			],
			[
				[
					new IntegerType(),
					new BenevolentUnionType([new IntegerType(), new StringType()]),
				],
				BenevolentUnionType::class,
				'(int|string)',
			],
			[
				[
					new StringType(),
					new BenevolentUnionType([new IntegerType(), new StringType()]),
				],
				BenevolentUnionType::class,
				'(int|string)',
			],
			[
				[
					new UnionType([new IntegerType(), new StringType(), new FloatType()]),
					new BenevolentUnionType([new IntegerType(), new StringType()]),
				],
				UnionType::class,
				'float|int|string',
			],
			[
				[
					new UnionType([new StringType(), new FloatType()]),
					new BenevolentUnionType([new IntegerType(), new StringType()]),
				],
				UnionType::class,
				'float|int|string',
			],
			[
				[
					new UnionType([new IntegerType(), new FloatType()]),
					new BenevolentUnionType([new IntegerType(), new StringType()]),
				],
				UnionType::class,
				'float|int|string',
			],
			[
				[
					new ConstantStringType('foo'),
					new ConstantStringType('foo'),
					new ConstantStringType('bar'),
					new ConstantStringType('baz'),
					new ConstantStringType('lorem'),
				],
				UnionType::class,
				"'bar'|'baz'|'foo'|'lorem'",
			],
			[
				[
					new ConstantStringType('foo'),
					new ConstantStringType('foo'),
					new ConstantStringType('fooo'),
					new ConstantStringType('bar'),
					new ConstantStringType('barr'),
					new ConstantStringType('baz'),
					new ConstantStringType('bazz'),
					new ConstantStringType('lorem'),
					new ConstantStringType('loremm'),
					new ConstantStringType('loremmm'),
				],
				UnionType::class,
				"'bar'|'barr'|'baz'|'bazz'|'foo'|'fooo'|'lorem'|'loremm'|'loremmm'",
			],
			[
				[
					new IntersectionType([
						new ArrayType(new MixedType(), new StringType()),
						new HasOffsetType(new StringType()),
					]),
					new IntersectionType([
						new ArrayType(new MixedType(), new StringType()),
						new HasOffsetType(new StringType()),
					]),
				],
				IntersectionType::class,
				'array<string>&hasOffset(string)',
			],
			[
				[
					new IntersectionType([
						new ObjectWithoutClassType(),
						new HasPropertyType('foo'),
					]),
					new IntersectionType([
						new ObjectWithoutClassType(),
						new HasPropertyType('foo'),
					]),
				],
				IntersectionType::class,
				'object&hasProperty(foo)',
			],
			[
				[
					new IntersectionType([
						new ConstantArrayType(
							[
								new ConstantIntegerType(0),
								new ConstantIntegerType(1),
							],
							[
								new ObjectWithoutClassType(),
								new ConstantStringType('foo'),
							],
						),
						new CallableType(),
					]),
					new IntersectionType([
						new ConstantArrayType(
							[
								new ConstantIntegerType(0),
								new ConstantIntegerType(1),
							],
							[
								new ObjectWithoutClassType(),
								new ConstantStringType('foo'),
							],
						),
						new CallableType(),
					]),
				],
				IntersectionType::class,
				'array{object, \'foo\'}&callable(): mixed',
			],
			[
				[
					new IntersectionType([new ArrayType(new MixedType(), new MixedType()), new NonEmptyArrayType()]),
					new ConstantArrayType([], []),
				],
				ArrayType::class,
				'array',
			],
			[
				[
					new IntersectionType([
						new ArrayType(new MixedType(), new MixedType()),
						new HasOffsetType(new ConstantStringType('foo')),
					]),
					new ArrayType(new MixedType(), new MixedType()),
				],
				ArrayType::class,
				'array',
			],
			[
				[
					new IntersectionType([
						new ArrayType(new MixedType(), new MixedType()),
						new HasOffsetType(new ConstantStringType('foo')),
					]),
					new IntersectionType([
						new ArrayType(new MixedType(), new MixedType()),
						new HasOffsetType(new ConstantStringType('bar')),
					]),
				],
				ArrayType::class,
				'array',
			],
			[
				[
					new IntersectionType([
						new ArrayType(new MixedType(), new MixedType()),
						new HasOffsetType(new ConstantStringType('foo')),
					]),
					new IntersectionType([
						new ArrayType(new MixedType(), new MixedType()),
						new HasOffsetType(new ConstantStringType('foo')),
						new HasOffsetType(new ConstantStringType('bar')),
					]),
				],
				IntersectionType::class,
				'array&hasOffset(\'foo\')',
			],
			[
				[
					new BenevolentUnionType([new IntegerType(), new StringType()]),
					new BenevolentUnionType([new IntegerType(), new StringType()]),
				],
				BenevolentUnionType::class,
				'(int|string)',
			],
			[
				[
					new MixedType(false, new IntegerType()),
					new MixedType(false, new StringType()),
				],
				MixedType::class,
				'mixed=implicit',
			],
			[
				[
					new MixedType(false, new IntegerType()),
					new MixedType(false, new UnionType([
						new IntegerType(),
						new StringType(),
					])),
				],
				MixedType::class,
				'mixed~int=implicit',
			],
			[
				[
					new MixedType(false, new IntegerType()),
					new MixedType(false, new UnionType([
						new ConstantIntegerType(1),
						new StringType(),
					])),
				],
				MixedType::class,
				'mixed~1=implicit',
			],
			[
				[
					new MixedType(false, new ConstantIntegerType(2)),
					new MixedType(false, new UnionType([
						new ConstantIntegerType(1),
						new StringType(),
					])),
				],
				MixedType::class,
				'mixed=implicit',
			],
			[
				[
					new MixedType(false, new IntegerType()),
					new MixedType(false, new ConstantIntegerType(1)),
				],
				MixedType::class,
				'mixed~1=implicit',
			],
			[
				[
					new MixedType(false),
					new MixedType(false, new ConstantIntegerType(1)),
				],
				MixedType::class,
				'mixed=implicit',
			],
			[
				[
					new MixedType(false, new NullType()),
					new UnionType([
						new StringType(),
						new NullType(),
					]),
				],
				MixedType::class,
				'mixed=implicit',
			],
			[
				[
					new MixedType(),
					new ObjectWithoutClassType(),
				],
				MixedType::class,
				'mixed=implicit',
			],
			[
				[
					new MixedType(),
					new ObjectWithoutClassType(new ObjectType('A')),
				],
				MixedType::class,
				'mixed=implicit',
			],
			[
				[
					new MixedType(false, new IntegerType()),
					new ObjectWithoutClassType(new ObjectType('A')),
				],
				MixedType::class,
				'mixed=implicit',
			],
			[
				[
					new MixedType(false, new ObjectType('A')),
					new ObjectWithoutClassType(new ObjectType('A')),
				],
				MixedType::class,
				'mixed~A=implicit',
			],
			[
				[
					new MixedType(false, new NullType()),
					new NullType(),
				],
				MixedType::class,
				'mixed=implicit',
			],
			[
				[
					new MixedType(false, new IntegerType()),
					new IntegerType(),
				],
				MixedType::class,
				'mixed=implicit',
			],
			[
				[
					new MixedType(false, new ConstantIntegerType(1)),
					new ConstantIntegerType(1),
				],
				MixedType::class,
				'mixed=implicit',
			],
			[
				[
					new MixedType(false, new ObjectType('Exception')),
					new ObjectType('Throwable'),
				],
				MixedType::class,
				'mixed=implicit',
			],
			[
				[
					new MixedType(false, new ObjectType('Exception')),
					new ObjectType('Exception'),
				],
				MixedType::class,
				'mixed=implicit',
			],
			[
				[
					new MixedType(false, new ObjectType('Exception')),
					new ObjectType('InvalidArgumentException'),
				],
				MixedType::class,
				'mixed=implicit', // should be MixedType~Exception+InvalidArgumentException
			],
			[
				[
					new NullType(),
					new MixedType(false, new NullType()),
				],
				MixedType::class,
				'mixed=implicit',
			],
			[
				[
					new MixedType(),
					new MixedType(false, new NullType()),
				],
				MixedType::class,
				'mixed=implicit',
			],
			[
				[
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'T',
						null,
						TemplateTypeVariance::createInvariant(),
					),
					new ObjectType('DateTime'),
				],
				UnionType::class,
				'DateTime|T (function a(), parameter)',
			],
			[
				[
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'T',
						new ObjectType('DateTime'),
						TemplateTypeVariance::createInvariant(),
					),
					new ObjectType('DateTime'),
				],
				ObjectType::class,
				'DateTime',
			],
			[
				[
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'T',
						new ObjectType('DateTime'),
						TemplateTypeVariance::createInvariant(),
					),
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'T',
						new ObjectType('DateTime'),
						TemplateTypeVariance::createInvariant(),
					),
				],
				TemplateType::class,
				'T of DateTime (function a(), parameter)',
			],
			[
				[
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'T',
						new ObjectType('DateTime'),
						TemplateTypeVariance::createInvariant(),
					),
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'U',
						new ObjectType('DateTime'),
						TemplateTypeVariance::createInvariant(),
					),
				],
				UnionType::class,
				'T of DateTime (function a(), parameter)|U of DateTime (function a(), parameter)',
			],
			'bug6210-1' => [
				[
					new ObjectWithoutClassType(),
					new IntersectionType([
						new ObjectWithoutClassType(),
						new HasMethodType('getId'),
					]),
				],
				ObjectWithoutClassType::class,
				'object',
			],
			'bug6210-2' => [
				[
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'T',
						null,
						TemplateTypeVariance::createInvariant(),
					),
					new IntersectionType([
						TemplateTypeFactory::create(
							TemplateTypeScope::createWithFunction('a'),
							'T',
							null,
							TemplateTypeVariance::createInvariant(),
						),
						new HasMethodType('getId'),
					]),
				],
				TemplateMixedType::class,
				'T (function a(), parameter)=explicit',
			],
			'bug6210-3' => [
				[
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'T',
						new ObjectWithoutClassType(),
						TemplateTypeVariance::createInvariant(),
					),
					new IntersectionType([
						TemplateTypeFactory::create(
							TemplateTypeScope::createWithFunction('a'),
							'T',
							new ObjectWithoutClassType(),
							TemplateTypeVariance::createInvariant(),
						),
						new HasMethodType('getId'),
					]),
				],
				TemplateObjectWithoutClassType::class,
				'T of object (function a(), parameter)',
			],
			'bug6210-4' => [
				[
					new ObjectWithoutClassType(),
					new IntersectionType([
						new ObjectWithoutClassType(),
						new HasPropertyType('getId'),
					]),
				],
				ObjectWithoutClassType::class,
				'object',
			],
			'bug6210-5' => [
				[
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'T',
						null,
						TemplateTypeVariance::createInvariant(),
					),
					new IntersectionType([
						TemplateTypeFactory::create(
							TemplateTypeScope::createWithFunction('a'),
							'T',
							null,
							TemplateTypeVariance::createInvariant(),
						),
						new HasPropertyType('getId'),
					]),
				],
				TemplateMixedType::class,
				'T (function a(), parameter)=explicit',
			],
			'bug6210-6' => [
				[
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'T',
						new ObjectWithoutClassType(),
						TemplateTypeVariance::createInvariant(),
					),
					new IntersectionType([
						TemplateTypeFactory::create(
							TemplateTypeScope::createWithFunction('a'),
							'T',
							new ObjectWithoutClassType(),
							TemplateTypeVariance::createInvariant(),
						),
						new HasPropertyType('getId'),
					]),
				],
				TemplateObjectWithoutClassType::class,
				'T of object (function a(), parameter)',
			],
			[
				[
					new BenevolentUnionType([new IntegerType(), new StringType()]),
				],
				BenevolentUnionType::class,
				'(int|string)',
			],
			[
				[
					new BenevolentUnionType([new IntegerType(), new StringType()]),
					new IntegerType(),
				],
				BenevolentUnionType::class,
				'(int|string)',
			],
			[
				[
					new BenevolentUnionType([new IntegerType(), new StringType()]),
					new StringType(),
				],
				BenevolentUnionType::class,
				'(int|string)',
			],
			[
				[
					new BenevolentUnionType([new IntegerType(), new StringType()]),
					new IntegerType(),
					new StringType(),
				],
				BenevolentUnionType::class,
				'(int|string)',
			],
			[
				[
					new BenevolentUnionType([new IntegerType(), new StringType()]),
					new UnionType([new IntegerType(), new StringType()]),
				],
				BenevolentUnionType::class,
				'(int|string)',
			],
			[
				[
					new BenevolentUnionType([new IntegerType(), new StringType()]),
					new IntegerType(),
					new StringType(),
					new FloatType(),
				],
				UnionType::class,
				'float|int|string',
			],
			[
				[
					new BenevolentUnionType([new IntegerType(), new StringType()]),
					new UnionType([new IntegerType(), new StringType(), new FloatType()]),
				],
				UnionType::class,
				'float|int|string',
			],
			[
				[
					new BenevolentUnionType([new IntegerType(), new StringType()]),
					new UnionType([new ConstantIntegerType(1), new ConstantIntegerType(2)]),
				],
				BenevolentUnionType::class,
				'(int|string)',
			],
			[
				[
					new BenevolentUnionType([new IntegerType(), new StringType()]),
					new UnionType([new ConstantIntegerType(1), new ConstantIntegerType(2), new FloatType()]),
				],
				UnionType::class,
				'float|int|string',
			],
			[
				[
					new StringType(),
					new ClassStringType(),
				],
				StringType::class,
				'string',
			],
			[
				[
					new ClassStringType(),
					new ConstantStringType(stdClass::class),
				],
				ClassStringType::class,
				'class-string',
			],
			[
				[
					new ClassStringType(),
					new ConstantStringType('Nonexistent'),
				],
				UnionType::class,
				'\'Nonexistent\'|class-string',
			],
			[
				[
					new ClassStringType(),
					new IntegerType(),
				],
				UnionType::class,
				'class-string|int',
			],
			[
				[
					new ConstantStringType(Exception::class),
					new GenericClassStringType(new ObjectType(Exception::class)),
				],
				GenericClassStringType::class,
				'class-string<Exception>',
			],
			[
				[
					new GenericClassStringType(new ObjectType(Exception::class)),
					new ClassStringType(),
				],
				ClassStringType::class,
				'class-string',
			],
			[
				[
					new GenericClassStringType(new ObjectType(Exception::class)),
					new StringType(),
				],
				StringType::class,
				'string',
			],
			[
				[
					new GenericClassStringType(new ObjectType(Exception::class)),
					new GenericClassStringType(new ObjectType(Exception::class)),
				],
				GenericClassStringType::class,
				'class-string<Exception>',
			],
			[
				[
					new GenericClassStringType(new ObjectType(Exception::class)),
					new GenericClassStringType(new ObjectType(Throwable::class)),
				],
				GenericClassStringType::class,
				'class-string<Throwable>',
			],
			[
				[
					new GenericClassStringType(new ObjectType(Exception::class)),
					new GenericClassStringType(new ObjectType(InvalidArgumentException::class)),
				],
				GenericClassStringType::class,
				'class-string<Exception>',
			],
			[
				[
					new GenericClassStringType(new ObjectType(Exception::class)),
					new GenericClassStringType(new ObjectType(stdClass::class)),
				],
				UnionType::class,
				'class-string<Exception>|class-string<stdClass>',
			],
			[
				[
					new GenericClassStringType(new ObjectType(Exception::class)),
					new ConstantStringType(Exception::class),
				],
				GenericClassStringType::class,
				'class-string<Exception>',
			],
			[
				[
					new GenericClassStringType(new ObjectType(Throwable::class)),
					new ConstantStringType(Exception::class),
				],
				GenericClassStringType::class,
				'class-string<Throwable>',
			],
			[
				[
					new GenericClassStringType(new ObjectType(InvalidArgumentException::class)),
					new ConstantStringType(Exception::class),
				],
				UnionType::class,
				'\'Exception\'|class-string<InvalidArgumentException>',
			],
			[
				[
					new GenericClassStringType(new ObjectType(Exception::class)),
					new ConstantStringType(stdClass::class),
				],
				UnionType::class,
				'\'stdClass\'|class-string<Exception>',
			],
			[
				[
					new StringType(),
					new IntersectionType([new StringType(), new CallableType()]),
				],
				StringType::class,
				'string',
			],
			[
				[
					new IntersectionType([new StringType(), new CallableType()]),
					new ConstantStringType('test_function'),
				],
				UnionType::class,
				'\'test_function\'|callable-string',
			],
			[
				[
					new IntersectionType([new StringType(), new CallableType()]),
					new IntegerType(),
				],
				UnionType::class,
				'callable-string|int',
			],
			[
				[
					IntegerRangeType::fromInterval(1, 3),
					IntegerRangeType::fromInterval(2, 5),
				],
				IntegerRangeType::class,
				'int<1, 5>',
			],
			[
				[
					IntegerRangeType::fromInterval(1, 2),
					IntegerRangeType::fromInterval(3, 5),
				],
				IntegerRangeType::class,
				'int<1, 5>',
			],
			[
				[
					IntegerRangeType::fromInterval(1, 3),
					IntegerRangeType::fromInterval(7, 9),
				],
				UnionType::class,
				'int<1, 3>|int<7, 9>',
			],
			[
				[
					IntegerRangeType::fromInterval(7, 9),
					IntegerRangeType::fromInterval(1, 3),
				],
				UnionType::class,
				'int<1, 3>|int<7, 9>',
			],
			[
				[
					IntegerRangeType::fromInterval(1, 3),
					new ConstantIntegerType(3),
				],
				IntegerRangeType::class,
				'int<1, 3>',
			],
			[
				[
					IntegerRangeType::fromInterval(1, 3),
					new ConstantIntegerType(4),
				],
				IntegerRangeType::class,
				'int<1, 4>',
			],
			[
				[
					IntegerRangeType::fromInterval(1, 3),
					new ConstantIntegerType(5),
				],
				UnionType::class,
				'5|int<1, 3>',
			],
			[
				[
					new UnionType([
						IntegerRangeType::fromInterval(null, 1),
						IntegerRangeType::fromInterval(3, null),
					]),
					new ConstantIntegerType(2),
				],
				IntegerType::class,
				'int',
			],
			[
				[
					new MixedType(),
					new MixedType(),
				],
				MixedType::class,
				'mixed=implicit',
			],
			[
				[
					new MixedType(true),
					new MixedType(),
				],
				MixedType::class,
				'mixed=implicit',
			],
			[
				[
					new MixedType(true),
					new MixedType(true),
				],
				MixedType::class,
				'mixed=explicit',
			],
			[
				[
					new GenericObjectType(Variance\Invariant::class, [
						new ObjectType(DateTimeInterface::class),
					]),
					new GenericObjectType(Variance\Invariant::class, [
						new ObjectType(DateTimeInterface::class),
					]),
				],
				GenericObjectType::class,
				'PHPStan\Type\Variance\Invariant<DateTimeInterface>',
			],
			[
				[
					new GenericObjectType(Variance\Invariant::class, [
						new ObjectType(DateTimeInterface::class),
					]),
					new GenericObjectType(Variance\Invariant::class, [
						new ObjectType(DateTime::class),
					]),
				],
				UnionType::class,
				'PHPStan\Type\Variance\Invariant<DateTime>|PHPStan\Type\Variance\Invariant<DateTimeInterface>',
			],
			[
				[
					new GenericObjectType(Variance\Covariant::class, [
						new ObjectType(DateTimeInterface::class),
					]),
					new GenericObjectType(Variance\Covariant::class, [
						new ObjectType(DateTime::class),
					]),
				],
				GenericObjectType::class,
				'PHPStan\Type\Variance\Covariant<DateTimeInterface>',
			],
			[
				[
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'T',
						new ObjectWithoutClassType(),
						TemplateTypeVariance::createInvariant(),
					),
					new ObjectWithoutClassType(),
				],
				ObjectWithoutClassType::class,
				'object',
			],
			[
				[
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'T',
						new ObjectWithoutClassType(),
						TemplateTypeVariance::createInvariant(),
					),
					new ObjectType(stdClass::class),
				],
				UnionType::class,
				'stdClass|T of object (function a(), parameter)',
			],
			[
				[
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'T',
						new ObjectWithoutClassType(),
						TemplateTypeVariance::createInvariant(),
					),
					new MixedType(),
				],
				MixedType::class,
				'mixed=implicit',
			],
			[
				[
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'T',
						null,
						TemplateTypeVariance::createInvariant(),
					),
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'K',
						null,
						TemplateTypeVariance::createInvariant(),
					),
				],
				UnionType::class,
				'K (function a(), parameter)|T (function a(), parameter)',
			],
			[
				[
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'T',
						new ObjectWithoutClassType(),
						TemplateTypeVariance::createInvariant(),
					),
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'K',
						new ObjectWithoutClassType(),
						TemplateTypeVariance::createInvariant(),
					),
				],
				UnionType::class,
				'K of object (function a(), parameter)|T of object (function a(), parameter)',
			],
			[
				[
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'T',
						new ObjectType(Exception::class),
						TemplateTypeVariance::createInvariant(),
					),
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'K',
						new ObjectType(stdClass::class),
						TemplateTypeVariance::createInvariant(),
					),
				],
				UnionType::class,
				'K of stdClass (function a(), parameter)|T of Exception (function a(), parameter)',
			],
			[
				[
					new ObjectType(DateTimeImmutable::class),
					new ObjectType(DateTimeInterface::class, new ObjectType(DateTimeImmutable::class)),
				],
				ObjectType::class,
				DateTimeInterface::class,
			],
			[
				[
					new StringType(),
					new MixedType(false, new StringType()),
				],
				MixedType::class,
				'mixed=implicit',
			],
			[
				[
					new ConstantArrayType([], []),
					new ConstantArrayType([
						new ConstantIntegerType(0),
					], [
						new StringType(),
					]),
				],
				UnionType::class,
				'array{}|array{string}',
			],
			[
				[
					new ConstantArrayType([], []),
					new ConstantArrayType([
						new ConstantIntegerType(0),
					], [
						new StringType(),
					], 1, [0]),
				],
				UnionType::class,
				'array{}|array{0?: string}',
			],
			[
				[
					new ConstantArrayType([
						new ConstantStringType('a'),
						new ConstantStringType('b'),
					], [
						new IntegerType(),
						new IntegerType(),
					]),
					new ConstantArrayType([
						new ConstantStringType('c'),
						new ConstantStringType('d'),
					], [
						new IntegerType(),
						new IntegerType(),
					]),
				],
				UnionType::class,
				'array{a: int, b: int}|array{c: int, d: int}',
			],
			[
				[
					new ConstantArrayType([
						new ConstantStringType('a'),
					], [
						new IntegerType(),
					]),
					new ConstantArrayType([
						new ConstantStringType('a'),
						new ConstantStringType('b'),
					], [
						new IntegerType(),
						new IntegerType(),
					]),
				],
				ConstantArrayType::class,
				'array{a: int, b?: int}',
			],
			[
				[
					new ConstantArrayType([
						new ConstantStringType('a'),
						new ConstantStringType('b'),
					], [
						new IntegerType(),
						new IntegerType(),
					]),
					new ConstantArrayType([
						new ConstantStringType('b'),
						new ConstantStringType('c'),
					], [
						new IntegerType(),
						new IntegerType(),
					]),
				],
				UnionType::class,
				'array{a: int, b: int}|array{b: int, c: int}',
			],
			[
				[
					TypeCombinator::intersect(new StringType(), new HasOffsetType(new IntegerType())),
					TypeCombinator::intersect(new StringType(), new HasOffsetType(new IntegerType())),
				],
				IntersectionType::class,
				'string&hasOffset(int)',
			],
			[
				[
					TypeCombinator::intersect(new ConstantStringType('abc'), new HasOffsetType(new IntegerType())),
					TypeCombinator::intersect(new ConstantStringType('abc'), new HasOffsetType(new IntegerType())),
				],
				IntersectionType::class,
				'\'abc\'&hasOffset(int)',
			],
			[
				[
					StaticTypeFactory::falsey(),
					StaticTypeFactory::falsey(),
				],
				UnionType::class,
				'0|0.0|\'\'|\'0\'|array{}|false|null',
			],
			[
				[
					StaticTypeFactory::truthy(),
					StaticTypeFactory::truthy(),
				],
				MixedType::class,
				'mixed~0|0.0|\'\'|\'0\'|array{}|false|null=implicit',
			],
			[
				[
					StaticTypeFactory::falsey(),
					StaticTypeFactory::truthy(),
				],
				MixedType::class,
				'mixed=implicit',
			],
			[
				[
					TemplateTypeFactory::create(TemplateTypeScope::createWithFunction('foo'), 'T', new BenevolentUnionType([new IntegerType(), new StringType()]), TemplateTypeVariance::createInvariant()),
				],
				TemplateBenevolentUnionType::class,
				'T of (int|string) (function foo(), parameter)',
			],
			[
				[
					new ConstantStringType(''),
					new IntersectionType([
						new StringType(),
						new AccessoryNonEmptyStringType(),
					]),
				],
				StringType::class,
				'string',
			],
			[
				[
					new ConstantStringType('0'),
					new IntersectionType([
						new StringType(),
						new AccessoryNonFalsyStringType(),
					]),
				],
				IntersectionType::class,
				'non-empty-string',
			],
			[
				[
					new ConstantStringType(''),
					new IntersectionType([
						new StringType(),
						new AccessoryNonFalsyStringType(),
					]),
				],
				StringType::class,
				'string',
			],
			[
				[
					new StringType(),
					new UnionType([
						new ConstantStringType(''),
						new ConstantStringType('0'),
						new ConstantBooleanType(false),
					]),
				],
				UnionType::class,
				'string|false',
			],
			[
				[
					new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()]),
					new UnionType([
						new ConstantStringType(''),
						new ConstantStringType('0'),
						new ConstantBooleanType(false),
					]),
				],
				UnionType::class,
				'string|false',
			],
			[
				[
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('doFoo'),
						'T',
						new UnionType([
							new StringType(),
							new IntegerType(),
							new FloatType(),
							new BooleanType(),
						]),
						TemplateTypeVariance::createInvariant(),
					),
					new NullType(),
				],
				UnionType::class,
				'(T of bool|float|int|string (function doFoo(), parameter))|null',
			],
			[
				[
					new UnionType([
						new ArrayType(new MixedType(), new MixedType()),
						IntegerRangeType::fromInterval(null, -1),
						IntegerRangeType::fromInterval(1, null),
					]),
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithClass('Foo'),
						'TCode',
						new UnionType([new ArrayType(new IntegerType(), new IntegerType()), new IntegerType()]),
						TemplateTypeVariance::createInvariant(),
					),
				],
				UnionType::class,
				'array|int<min, -1>|int<1, max>|(TCode of array<int, int>|int (class Foo, parameter))',
			],
			[
				[
					new UnionType([
						new ArrayType(new MixedType(), new MixedType()),
						new CallableType(),
					]),
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithClass('Foo'),
						'TCode',
						new UnionType([new ArrayType(new IntegerType(), new IntegerType()), new IntegerType()]),
						TemplateTypeVariance::createInvariant(),
					),
				],
				UnionType::class,
				'array|(callable(): mixed)|(TCode of array<int, int>|int (class Foo, parameter))',
			],
			[
				[
					new MixedType(),
					new StrictMixedType(),
				],
				MixedType::class,
				'mixed=implicit',
			],
		];

		if (PHP_VERSION_ID < 80100) {
			return;
		}

		yield [
			[
				new ObjectType('PHPStan\Fixture\TestEnum'),
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			],
			ObjectType::class,
			'PHPStan\Fixture\TestEnum',
		];
		yield [
			[
				new ObjectType('PHPStan\Fixture\TestEnum'),
				new EnumCaseObjectType('PHPStan\Fixture\AnotherTestEnum', 'ONE'),
			],
			UnionType::class,
			'PHPStan\Fixture\AnotherTestEnum::ONE|PHPStan\Fixture\TestEnum',
		];
		yield [
			[
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			],
			EnumCaseObjectType::class,
			'PHPStan\Fixture\TestEnum::ONE',
		];
		yield [
			[
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'TWO'),
			],
			UnionType::class,
			'PHPStan\Fixture\TestEnum::ONE|PHPStan\Fixture\TestEnum::TWO',
		];
		yield [
			[
				new ObjectType('PHPStan\Fixture\TestEnum'),
				new ObjectType('PHPStan\Fixture\TestEnumInterface'),
			],
			ObjectType::class,
			'PHPStan\Fixture\TestEnumInterface',
		];
		yield [
			[
				new ObjectType('PHPStan\Fixture\TestEnumInterface'),
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			],
			ObjectType::class,
			'PHPStan\Fixture\TestEnumInterface',
		];
		yield [
			[
				new ObjectType('PHPStan\Fixture\TestEnumInterface'),
				new EnumCaseObjectType('PHPStan\Fixture\AnotherTestEnum', 'ONE'),
			],
			UnionType::class,
			'PHPStan\Fixture\AnotherTestEnum::ONE|PHPStan\Fixture\TestEnumInterface',
		];
		yield [
			[
				new ObjectWithoutClassType(),
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			],
			ObjectWithoutClassType::class,
			'object',
		];
		yield [
			[
				new ObjectType('stdClass'),
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			],
			UnionType::class,
			'PHPStan\Fixture\TestEnum::ONE|stdClass',
		];
		yield [
			[
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
				new EnumCaseObjectType('PHPStan\Fixture\AnotherTestEnum', 'ONE'),
			],
			UnionType::class,
			'PHPStan\Fixture\AnotherTestEnum::ONE|PHPStan\Fixture\TestEnum::ONE',
		];
		yield [
			[
				new MixedType(false, IntegerRangeType::fromInterval(17, null)),
				IntegerRangeType::fromInterval(19, null),
			],
			MixedType::class,
			'mixed~int<17, 18>=implicit',
		];

		$reflectionProvider = $this->createReflectionProvider();
		yield [
			[
				new StaticType($reflectionProvider->getClass(stdClass::class)),
				new ThisType($reflectionProvider->getClass(stdClass::class)),
			],
			StaticType::class,
			'static(stdClass)',
		];

		yield [
			[
				new StaticType($reflectionProvider->getClass(stdClass::class)),
				new ObjectType(stdClass::class),
			],
			ObjectType::class,
			'stdClass',
		];

		yield [
			[
				new StaticType($reflectionProvider->getClass(stdClass::class)),
				new EnumCaseObjectType(stdClass::class, 'foo'),
			],
			UnionType::class,
			'static(stdClass)|stdClass::foo',
		];

		yield [
			[
				new ThisType($reflectionProvider->getClass(stdClass::class)),
				new EnumCaseObjectType(stdClass::class, 'foo'),
			],
			UnionType::class,
			'$this(stdClass)|stdClass::foo',
		];

		yield [
			[
				new ObjectType('PHPStan\Fixture\ManyCasesTestEnum', new UnionType([
					new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'A'),
					new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'B'),
				])),
				new ObjectType('PHPStan\Fixture\ManyCasesTestEnum', new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'A')),
			],
			ObjectType::class,
			'PHPStan\Fixture\ManyCasesTestEnum~PHPStan\Fixture\ManyCasesTestEnum::A',
		];

		yield [
			[
				new ThisType(
					$reflectionProvider->getClass(\ThisSubtractable\Foo::class), // phpcs:ignore
					new UnionType([new ObjectType(\ThisSubtractable\Bar::class), new ObjectType(\ThisSubtractable\Baz::class)]), // phpcs:ignore
				),
				new UnionType([
					new IntersectionType([
						new ThisType($reflectionProvider->getClass(\ThisSubtractable\Foo::class)), // phpcs:ignore
						new ObjectType(\ThisSubtractable\Bar::class), // phpcs:ignore
					]),
					new IntersectionType([
						new ThisType($reflectionProvider->getClass(\ThisSubtractable\Foo::class)), // phpcs:ignore
						new ObjectType(\ThisSubtractable\Baz::class), // phpcs:ignore
					]),
				]),
			],
			ThisType::class,
			'$this(ThisSubtractable\Foo)',
		];

		yield [
			[
				TypeCombinator::intersect(new StringType(), new AccessoryNonEmptyStringType()),
				TypeCombinator::intersect(new StringType(), new AccessoryNonFalsyStringType()),
			],
			IntersectionType::class,
			'non-empty-string',
		];

		yield [
			[
				TypeCombinator::intersect(new StringType(), new AccessoryNonEmptyStringType()),
				new ConstantStringType('0'),
			],
			IntersectionType::class,
			'non-empty-string',
		];

		yield [
			[
				TypeCombinator::intersect(new StringType(), new AccessoryNonFalsyStringType()),
				new ConstantStringType('0'),
			],
			IntersectionType::class,
			'non-empty-string',
		];

		yield [
			[
				TypeCombinator::intersect(new StringType(), new AccessoryLiteralStringType(), new AccessoryNonFalsyStringType()),
				new ConstantStringType('bar'),
				new ConstantStringType('baz'),
				new ConstantStringType('foo'),
			],
			IntersectionType::class,
			'literal-string&non-falsy-string',
		];

		yield [
			[
				TypeCombinator::intersect(new StringType(), new AccessoryLiteralStringType(), new AccessoryNonEmptyStringType()),
				new ConstantStringType('bar'),
				new ConstantStringType('baz'),
				new ConstantStringType('foo'),
			],
			IntersectionType::class,
			'literal-string&non-empty-string',
		];

		yield [
			[
				new HasOffsetValueType(new ConstantStringType('a'), new ConstantIntegerType(1)),
				new HasOffsetValueType(new ConstantStringType('a'), new IntegerType()),
			],
			HasOffsetValueType::class,
			'hasOffsetValue(\'a\', int)',
		];

		yield [
			[
				TypeCombinator::intersect(new ArrayType(new MixedType(), new MixedType()), new HasOffsetValueType(new ConstantStringType('a'), new ConstantIntegerType(1))),
				TypeCombinator::intersect(new ArrayType(new MixedType(), new MixedType()), new HasOffsetValueType(new ConstantStringType('a'), new IntegerType())),
			],
			IntersectionType::class,
			'array&hasOffsetValue(\'a\', int)',
		];

		yield [
			[
				new IntersectionType([
					new ArrayType(new MixedType(), new MixedType()),
					new HasOffsetValueType(
						new ConstantStringType('a'),
						StaticTypeFactory::falsey(),
					),
				]),
				new IntersectionType([
					new ArrayType(new MixedType(), new MixedType()),
					new HasOffsetValueType(
						new ConstantStringType('a'),
						StaticTypeFactory::truthy(),
					),
				]),
			],
			IntersectionType::class,
			"array&hasOffsetValue('a', mixed)",
		];

		yield [
			[
				new IntersectionType([
					new ArrayType(new IntegerType(), new ArrayType(new MixedType(), new MixedType())),
					new HasOffsetValueType(
						new ConstantIntegerType(0),
						new IntersectionType([
							new ArrayType(new MixedType(), new MixedType()),
							new HasOffsetValueType(new ConstantStringType('code'), new ConstantIntegerType(1)),
						]),
					),
				]),
				new IntersectionType([
					new ArrayType(new IntegerType(), new ArrayType(new MixedType(), new MixedType())),
					new HasOffsetValueType(
						new ConstantIntegerType(0),
						new IntersectionType([
							new ArrayType(new MixedType(), new MixedType()),
							new HasOffsetValueType(new ConstantStringType('code'), new MixedType(true, new ConstantIntegerType(1))),
						]),
					),
				]),
			],
			IntersectionType::class,
			"array<int, array>&hasOffsetValue(0, array&hasOffsetValue('code', mixed))",
		];

		yield [
			[
				new ConstantArrayType(
					[new ConstantStringType('default'), new ConstantStringType('range')],
					[new ObjectType(Foo::class), new ObjectType(Foo::class)],
					[0],
					[0, 1],
				),
				new ConstantArrayType(
					[new ConstantStringType('range')],
					[new ObjectType(Foo::class)],
					[0],
					[0],
				),
			],
			ConstantArrayType::class,
			'array{default?: RecursionCallable\Foo, range?: RecursionCallable\Foo}',
		];

		yield [
			[
				new IntersectionType([
					new ConstantArrayType(
						[new ConstantStringType('default'), new ConstantStringType('range')],
						[new ObjectType(Foo::class), new ObjectType(Foo::class)],
						[0],
						[0, 1],
					),
					new NonEmptyArrayType(),
				]),
				new ConstantArrayType(
					[new ConstantStringType('range')],
					[new ObjectType(Foo::class)],
					[0],
					[0],
				),
			],
			ConstantArrayType::class,
			'array{default?: RecursionCallable\Foo, range?: RecursionCallable\Foo}',
		];
		yield [
			[
				new IntersectionType([new ArrayType(new IntegerType(), new StringType()), new OversizedArrayType()]),
				new ArrayType(new IntegerType(), new StringType()),
			],
			IntersectionType::class,
			'array<int, string>&oversized-array',
		];
		yield [
			[
				new ObjectType(TestInterface::class),
				new ClosureType([], new MixedType(), false),
			],
			UnionType::class,
			'Bug9006\TestInterface|(Closure(): mixed)',
		];
		yield [
			[
				new ObjectType(TestInterface::class),
				new ObjectType(Closure::class),
			],
			UnionType::class,
			'Bug9006\TestInterface|Closure',
		];
		yield [
			[
				new ObjectShapeType([], []),
				new ObjectWithoutClassType(),
			],
			ObjectWithoutClassType::class,
			'object',
		];
		yield [
			[
				new ObjectShapeType([], []),
				new ObjectType(stdClass::class),
			],
			UnionType::class,
			'object{}|stdClass',
		];
		yield [
			[
				new ObjectShapeType(['foo' => new IntegerType()], []),
				new ObjectShapeType(['foo' => new IntegerType()], []),
			],
			ObjectShapeType::class,
			'object{foo: int}',
		];
		yield [
			[
				new ObjectShapeType(['foo' => new IntegerType()], []),
				new ObjectShapeType(['foo' => new ConstantIntegerType(1)], []),
			],
			ObjectShapeType::class,
			'object{foo: int}',
		];
		yield [
			[
				new ObjectShapeType(['foo' => new IntegerType()], []),
				new ObjectShapeType(['foo' => new StringType()], []),
			],
			UnionType::class,
			'object{foo: int}|object{foo: string}',
		];
		yield [
			[
				new ObjectShapeType(['foo' => new IntegerType()], []),
				new ObjectShapeType(['bar' => new StringType()], []),
			],
			UnionType::class,
			'object{bar: string}|object{foo: int}',
		];

		yield [
			[
				new ObjectShapeType(['foo' => new IntegerType()], []),
				new ObjectType(Traversable::class),
			],
			UnionType::class,
			'object{foo: int}|Traversable',
		];
		yield [
			[
				new ObjectShapeType(['foo' => new IntegerType()], []),
				new ObjectType(\ObjectShape\Foo::class),
			],
			UnionType::class,
			'ObjectShape\Foo|object{foo: int}',
		];
		yield [
			[
				new ObjectShapeType(['foo' => new IntegerType()], []),
				new ObjectType(ClassWithFooIntProperty::class),
			],
			ObjectShapeType::class,
			'object{foo: int}',
		];
		yield [
			[
				new ObjectShapeType(['foo' => new IntegerType()], []),
				new ObjectType(\ObjectShapesAcceptance\FinalClass::class),
			],
			UnionType::class,
			'ObjectShapesAcceptance\FinalClass|object{foo: int}',
		];
		yield [
			[
				new NeverType(),
				new NonAcceptingNeverType(),
			],
			NeverType::class,
			'*NEVER*',
		];
		yield [
			[
				new ConstantArrayType([
					new ConstantStringType('a'),
					new ConstantStringType('b'),
				], [
					new ConstantBooleanType(true),
					new ConstantBooleanType(true),
				], [0], [0]),
				new ConstantArrayType([
					new ConstantStringType('a'),
					new ConstantStringType('c'),
				], [
					new ConstantBooleanType(true),
					new ConstantBooleanType(true),
				], [0], [0, 1]),
			],
			UnionType::class,
			'array{a?: true, b: true}|array{a?: true, c?: true}',
		];

		yield [
			[
				new ConstantArrayType([
					new ConstantStringType('a'),
					new ConstantStringType('b'),
				], [
					new ConstantBooleanType(true),
					new ConstantBooleanType(true),
				], [0], [0]),
				new IntersectionType([
					new ConstantArrayType([
						new ConstantStringType('a'),
						new ConstantStringType('c'),
					], [
						new ConstantBooleanType(true),
						new ConstantBooleanType(true),
					], [0], [0, 1]),
					new NonEmptyArrayType(),
				]),
			],
			UnionType::class,
			'array{a?: true, b: true}|(array{a?: true, c?: true}&non-empty-array)',
		];

		yield [
			[
				new EnumCaseObjectType('PHPStan\Fixture\AnotherTestEnum', 'ONE'),
				new EnumCaseObjectType('PHPStan\Fixture\AnotherTestEnum', 'TWO'),
				new ObjectType('PHPStan\Fixture\TestEnumInterface'),
			],
			UnionType::class,
			'PHPStan\Fixture\AnotherTestEnum::ONE|PHPStan\Fixture\AnotherTestEnum::TWO|PHPStan\Fixture\TestEnumInterface',
		];
		yield [
			[
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'TWO'),
				new ObjectType('PHPStan\Fixture\TestEnumInterface'),
			],
			ObjectType::class,
			'PHPStan\Fixture\TestEnumInterface',
		];
		yield [
			[
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'TWO'),
				new ObjectWithoutClassType(),
			],
			ObjectWithoutClassType::class,
			'object',
		];
		yield [
			[
				new CallableType(null, null, true, null, null, [], TrinaryLogic::createYes()),
				new CallableType(),
			],
			CallableType::class,
			'callable(): mixed',
		];
		yield [
			[
				new CallableType(null, null, true, null, null, [], TrinaryLogic::createYes()),
				ClosureType::createPure(),
			],
			CallableType::class,
			'pure-callable(): mixed',
		];
		yield [
			[
				new CallableType(null, null, true, null, null, [], TrinaryLogic::createMaybe()),
				new CallableType(null, null, true, null, null, [], TrinaryLogic::createYes()),
			],
			CallableType::class,
			'callable(): mixed',
		];
		yield [
			[
				new ClosureType([], new MixedType(), true, null, null, null, [], [], [
					new SimpleImpurePoint('functionCall', 'foo', true),
				]),
				ClosureType::createPure(),
			],
			UnionType::class,
			'(Closure(): mixed)|(pure-Closure)',
		];
		yield [
			[
				new ClosureType([], new MixedType(), true, null, null, null, [], [], [
					new SimpleImpurePoint('functionCall', 'foo', false),
				]),
				ClosureType::createPure(),
			],
			ClosureType::class,
			'Closure(): mixed',
		];
	}

	/**
	 * @dataProvider dataUnion
	 * @param Type[] $types
	 * @param class-string<Type> $expectedTypeClass
	 */
	public function testUnion(
		array $types,
		string $expectedTypeClass,
		string $expectedTypeDescription,
	): void
	{
		$actualType = TypeCombinator::union(...$types);
		$actualTypeDescription = $actualType->describe(VerbosityLevel::precise());
		if ($actualType instanceof MixedType) {
			if ($actualType->isExplicitMixed()) {
				$actualTypeDescription .= '=explicit';
			} else {
				$actualTypeDescription .= '=implicit';
			}
		}

		$this->assertSame(
			$expectedTypeDescription,
			$actualTypeDescription,
			sprintf('union(%s)', implode(', ', array_map(
				static fn (Type $type): string => $type->describe(VerbosityLevel::precise()),
				$types,
			))),
		);

		$this->assertInstanceOf($expectedTypeClass, $actualType);

		$hasSubtraction = false;
		foreach ($types as $type) {
			if (!($type instanceof SubtractableType) || $type->getSubtractedType() === null) {
				continue;
			}

			$hasSubtraction = true;
		}

		if ($hasSubtraction) {
			return;
		}
	}

	/**
	 * @dataProvider dataUnion
	 * @param Type[] $types
	 * @param class-string<Type> $expectedTypeClass
	 */
	public function testUnionInversed(
		array $types,
		string $expectedTypeClass,
		string $expectedTypeDescription,
	): void
	{
		$types = array_reverse($types);
		$actualType = TypeCombinator::union(...$types);
		$actualTypeDescription = $actualType->describe(VerbosityLevel::precise());
		if ($actualType instanceof MixedType) {
			if ($actualType->isExplicitMixed()) {
				$actualTypeDescription .= '=explicit';
			} else {
				$actualTypeDescription .= '=implicit';
			}
		}
		$this->assertSame(
			$expectedTypeDescription,
			$actualTypeDescription,
			sprintf('union(%s)', implode(', ', array_map(
				static fn (Type $type): string => $type->describe(VerbosityLevel::precise()),
				$types,
			))),
		);
		$this->assertInstanceOf($expectedTypeClass, $actualType);
	}

	public function dataIntersect(): iterable
	{
		$reflectionProvider = $this->createReflectionProvider();

		yield from [
			[
				[
					new IterableType(new MixedType(), new StringType()),
					new ObjectType('ArrayObject'),
				],
				IntersectionType::class,
				'ArrayObject&iterable<string>',
			],
			[
				[
					new IterableType(new MixedType(), new StringType()),
					new ArrayType(new MixedType(), new StringType()),
				],
				ArrayType::class,
				'array<string>',
			],
			[
				[
					new IterableType(new MixedType(true), new StringType()),
					new ObjectType('Iterator'),
				],
				IntersectionType::class,
				'iterable<string>&Iterator',
			],
			[
				[
					new ObjectType('Iterator'),
					new IterableType(
						new MixedType(true),
						TemplateTypeFactory::create(
							TemplateTypeScope::createWithFunction('_'),
							'T',
							null,
							TemplateTypeVariance::createInvariant(),
						),
					),
				],
				IntersectionType::class,
				'iterable<T (function _(), parameter)>&Iterator',
			],
			[
				[
					new ObjectType('Foo'),
					new StaticType($reflectionProvider->getClass('Foo')),
				],
				StaticType::class,
				'static(Foo)',
			],
			[
				[
					new VoidType(),
					new MixedType(),
				],
				VoidType::class,
				'void',
			],
			[
				[
					new ObjectType('UnknownClass'),
					new ObjectType('UnknownClass'),
				],
				ObjectType::class,
				'UnknownClass',
			],
			[
				[
					new UnionType([new ObjectType('UnknownClassA'), new ObjectType('UnknownClassB')]),
					new UnionType([new ObjectType('UnknownClassA'), new ObjectType('UnknownClassB')]),
				],
				UnionType::class,
				'UnknownClassA|UnknownClassB',
			],
			[
				[
					new ConstantBooleanType(true),
					new BooleanType(),
				],
				ConstantBooleanType::class,
				'true',
			],
			[
				[
					new ConstantBooleanType(false),
					new BooleanType(),
				],
				ConstantBooleanType::class,
				'false',
			],
			[
				[
					StaticTypeFactory::truthy(),
					new BooleanType(),
				],
				ConstantBooleanType::class,
				'true',
			],
			[
				[
					StaticTypeFactory::falsey(),
					new BooleanType(),
				],
				ConstantBooleanType::class,
				'false',
			],
			[
				[
					StaticTypeFactory::falsey(),
					StaticTypeFactory::truthy(),
				],
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				[
					new StringType(),
					new NeverType(),
				],
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				[
					new ObjectType('Iterator'),
					new ObjectType('Countable'),
					new ObjectType('Traversable'),
				],
				IntersectionType::class,
				'Countable&Iterator',
			],
			[
				[
					new ObjectType('Iterator'),
					new ObjectType('Traversable'),
					new ObjectType('Countable'),
				],
				IntersectionType::class,
				'Countable&Iterator',
			],
			[
				[
					new ObjectType('Traversable'),
					new ObjectType('Iterator'),
					new ObjectType('Countable'),
				],
				IntersectionType::class,
				'Countable&Iterator',
			],
			[
				[
					new IterableType(new MixedType(), new MixedType()),
					new IterableType(new MixedType(), new StringType()),
				],
				IterableType::class,
				'iterable<string>',
			],
			[
				[
					new ArrayType(new MixedType(), new MixedType()),
					new IterableType(new MixedType(), new StringType()),
				],
				ArrayType::class,
				'array<string>',
			],
			[
				[
					new ArrayType(new IntegerType(), new MixedType()),
					new IterableType(new MixedType(), new StringType()),
				],
				ArrayType::class,
				'array<int, string>',
			],
			[
				[
					new ArrayType(new IntegerType(), new MixedType()),
					new IterableType(new StringType(), new MixedType()),
				],
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				[
					new ArrayType(new MixedType(), new IntegerType()),
					new IterableType(new MixedType(), new StringType()),
				],
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				[
					new ArrayType(new IntegerType(), new MixedType()),
					new ArrayType(new MixedType(), new StringType()),
				],
				ArrayType::class,
				'array<int, string>',
			],
			[
				[
					new IterableType(new IntegerType(), new MixedType()),
					new IterableType(new MixedType(), new StringType()),
				],
				IterableType::class,
				'iterable<int, string>',
			],
			[
				[
					new MixedType(),
					new IterableType(new MixedType(), new MixedType()),
				],
				IterableType::class,
				'iterable',
			],
			[
				[
					new IntegerType(),
					new BenevolentUnionType([new IntegerType(), new StringType()]),
				],
				IntegerType::class,
				'int',
			],
			[
				[
					new ConstantIntegerType(1),
					new BenevolentUnionType([new IntegerType(), new StringType()]),
				],
				ConstantIntegerType::class,
				'1',
			],
			[
				[
					new ConstantStringType('foo'),
					new BenevolentUnionType([new IntegerType(), new StringType()]),
				],
				ConstantStringType::class,
				'\'foo\'',
			],
			[
				[
					new StringType(),
					new BenevolentUnionType([new IntegerType(), new StringType()]),
				],
				StringType::class,
				'string',
			],
			[
				[
					new UnionType([new StringType(), new IntegerType()]),
					new BenevolentUnionType([new IntegerType(), new StringType()]),
				],
				UnionType::class,
				'(int|string)',
			],
			[
				[
					new ObjectType(\Test\Foo::class),
					new HasMethodType('__toString'),
				],
				IntersectionType::class,
				'Test\Foo&hasMethod(__toString)',
			],
			[
				[
					new ObjectType(ClassWithToString::class),
					new HasMethodType('__toString'),
				],
				ObjectType::class,
				'Test\ClassWithToString',
			],
			[
				[
					new ObjectType(FinalClassWithMethodExists::class),
					new HasMethodType('doBar'),
				],
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				[
					new ObjectWithoutClassType(),
					new HasMethodType('__toString'),
				],
				IntersectionType::class,
				'object&hasMethod(__toString)',
			],
			[
				[
					new IntegerType(),
					new HasMethodType('__toString'),
				],
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				[
					new IntersectionType([
						new ObjectWithoutClassType(),
						new HasMethodType('__toString'),
					]),
					new HasMethodType('__toString'),
				],
				IntersectionType::class,
				'object&hasMethod(__toString)',
			],
			[
				[
					new IntersectionType([
						new ObjectWithoutClassType(),
						new HasMethodType('foo'),
					]),
					new HasMethodType('bar'),
				],
				IntersectionType::class,
				'object&hasMethod(bar)&hasMethod(foo)',
			],
			[
				[
					new UnionType([
						new ObjectType(\Test\Foo::class),
						new ObjectType(FirstInterface::class),
					]),
					new HasMethodType('__toString'),
				],
				UnionType::class,
				'(Test\FirstInterface&hasMethod(__toString))|(Test\Foo&hasMethod(__toString))',
			],
			[
				[
					new ObjectType(\Test\Foo::class),
					new HasPropertyType('fooProperty'),
				],
				IntersectionType::class,
				'Test\Foo&hasProperty(fooProperty)',
			],
			[
				[
					new ObjectType(FinalFoo::class),
					new HasPropertyType('fooProperty'),
				],
				PHP_VERSION_ID < 80200 ? IntersectionType::class : NeverType::class,
				PHP_VERSION_ID < 80200 ? 'DynamicProperties\FinalFoo&hasProperty(fooProperty)' : '*NEVER*=implicit',
			],
			[
				[
					new ObjectType(ClassWithNullableProperty::class),
					new HasPropertyType('foo'),
				],
				ObjectType::class,
				'Test\ClassWithNullableProperty',
			],
			[
				[
					new ObjectType(FinalClassWithPropertyExists::class),
					new HasPropertyType('barProperty'),
				],
				IntersectionType::class,
				'CheckTypeFunctionCall\FinalClassWithPropertyExists&hasProperty(barProperty)',
			],
			[
				[
					new ObjectWithoutClassType(),
					new HasPropertyType('fooProperty'),
				],
				IntersectionType::class,
				'object&hasProperty(fooProperty)',
			],
			[
				[
					new IntegerType(),
					new HasPropertyType('fooProperty'),
				],
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				[
					new IntersectionType([
						new ObjectWithoutClassType(),
						new HasPropertyType('fooProperty'),
					]),
					new HasPropertyType('fooProperty'),
				],
				IntersectionType::class,
				'object&hasProperty(fooProperty)',
			],
			[
				[
					new IntersectionType([
						new ObjectWithoutClassType(),
						new HasPropertyType('foo'),
					]),
					new HasPropertyType('bar'),
				],
				IntersectionType::class,
				'object&hasProperty(bar)&hasProperty(foo)',
			],
			[
				[
					new UnionType([
						new ObjectType(\Test\Foo::class),
						new ObjectType(FirstInterface::class),
					]),
					new HasPropertyType('fooProperty'),
				],
				UnionType::class,
				'(Test\FirstInterface&hasProperty(fooProperty))|(Test\Foo&hasProperty(fooProperty))',
			],
			[
				[
					new UnionType([
						new ObjectType(FinalFoo::class),
						new ObjectType(FirstInterface::class),
					]),
					new HasPropertyType('fooProperty'),
				],
				PHP_VERSION_ID < 80200 ? UnionType::class : IntersectionType::class,
				PHP_VERSION_ID < 80200 ? '(DynamicProperties\FinalFoo&hasProperty(fooProperty))|(Test\FirstInterface&hasProperty(fooProperty))' : 'Test\FirstInterface&hasProperty(fooProperty)',
			],
			[
				[
					new ArrayType(new StringType(), new StringType()),
					new HasOffsetType(new ConstantStringType('a')),
				],
				IntersectionType::class,
				'array<string, string>&hasOffset(\'a\')',
			],
			[
				[
					new ArrayType(new StringType(), new StringType()),
					new HasOffsetType(new ConstantStringType('a')),
					new HasOffsetType(new ConstantStringType('a')),
				],
				IntersectionType::class,
				'array<string, string>&hasOffset(\'a\')',
			],
			[
				[
					new ArrayType(new StringType(), new StringType()),
					new HasOffsetType(new StringType()),
					new HasOffsetType(new StringType()),
				],
				IntersectionType::class,
				'array<string, string>&hasOffset(string)',
			],
			[
				[
					new ArrayType(new MixedType(), new MixedType()),
					new HasOffsetType(new StringType()),
					new HasOffsetType(new StringType()),
				],
				IntersectionType::class,
				'array&hasOffset(string)',
			],
			[
				[
					new ConstantArrayType(
						[new ConstantStringType('a')],
						[new ConstantStringType('foo')],
					),
					new HasOffsetType(new ConstantStringType('a')),
				],
				ConstantArrayType::class,
				'array{a: \'foo\'}',
			],
			[
				[
					new ConstantArrayType(
						[new ConstantStringType('a')],
						[new ConstantStringType('foo')],
					),
					new HasOffsetType(new ConstantStringType('b')),
				],
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				[
					new ClosureType([], new MixedType(), false),
					new HasOffsetType(new ConstantStringType('a')),
				],
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				[
					TypeCombinator::union(
						new ConstantArrayType(
							[new ConstantStringType('a')],
							[new ConstantStringType('foo')],
						),
						new ConstantArrayType(
							[new ConstantStringType('b')],
							[new ConstantStringType('foo')],
						),
					),
					new HasOffsetType(new ConstantStringType('b')),
				],
				ConstantArrayType::class,
				'array{b: \'foo\'}',
			],
			[
				[
					TypeCombinator::union(
						new ConstantArrayType(
							[new ConstantStringType('a')],
							[new ConstantStringType('foo')],
						),
						new ClosureType([], new MixedType(), false),
					),
					new HasOffsetType(new ConstantStringType('a')),
				],
				ConstantArrayType::class,
				'array{a: \'foo\'}',
			],
			[
				[
					new ClosureType([], new MixedType(), false),
					new ObjectType(Closure::class),
				],
				ClosureType::class,
				'Closure(): mixed',
			],
			[
				[
					new ClosureType([], new MixedType(), false),
					new CallableType(),
				],
				ClosureType::class,
				'Closure(): mixed',
			],
			[
				[
					new ClosureType([], new MixedType(), false),
					new ObjectWithoutClassType(),
				],
				ClosureType::class,
				'Closure(): mixed',
			],
			[
				[
					new UnionType([
						new ArrayType(new MixedType(), new StringType()),
						new NullType(),
					]),
					new HasOffsetType(new StringType()),
				],
				IntersectionType::class,
				'array<string>&hasOffset(string)',
			],
			[
				[
					new ArrayType(new MixedType(), new MixedType()),
					new NonEmptyArrayType(),
				],
				IntersectionType::class,
				'non-empty-array',
			],
			[
				[
					new StringType(),
					new NonEmptyArrayType(),
				],
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				[
					new IntersectionType([
						new ArrayType(new MixedType(), new MixedType()),
						new NonEmptyArrayType(),
					]),
					new NonEmptyArrayType(),
				],
				IntersectionType::class,
				'non-empty-array',
			],
			[
				[
					TypeCombinator::union(
						new ConstantArrayType([], []),
						new ConstantArrayType([
							new ConstantIntegerType(0),
						], [
							new StringType(),
						]),
					),
					new NonEmptyArrayType(),
				],
				ConstantArrayType::class,
				'array{string}',
			],
			[
				[
					new ConstantArrayType([], []),
					new NonEmptyArrayType(),
				],
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				[
					new IntersectionType([
						new ArrayType(new MixedType(), new MixedType()),
						new HasOffsetType(new ConstantStringType('foo')),
					]),
					new IntersectionType([
						new ArrayType(new MixedType(), new MixedType()),
						new HasOffsetType(new ConstantStringType('bar')),
					]),
				],
				IntersectionType::class,
				'array&hasOffset(\'bar\')&hasOffset(\'foo\')',
			],
			[
				[
					new StringType(),
					new IntegerType(),
				],
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				[
					new MixedType(false, new StringType()),
					new StringType(),
				],
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				[
					new MixedType(false, new StringType()),
					new ConstantStringType('foo'),
				],
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				[
					new MixedType(false, new StringType()),
					new ConstantIntegerType(1),
				],
				ConstantIntegerType::class,
				'1',
			],
			[
				[
					new MixedType(false, new StringType()),
					new MixedType(false, new IntegerType()),
				],
				MixedType::class,
				'mixed~int|string=implicit',
			],
			[
				[
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'T',
						null,
						TemplateTypeVariance::createInvariant(),
					),
					new ObjectType('DateTime'),
				],
				IntersectionType::class,
				'DateTime&T (function a(), parameter)',
			],
			[
				[
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'T',
						new ObjectType('DateTime'),
						TemplateTypeVariance::createInvariant(),
					),
					new ObjectType('DateTime'),
				],
				TemplateObjectType::class,
				'T of DateTime (function a(), parameter)',
			],
			[
				[
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'T',
						new ObjectType('DateTime'),
						TemplateTypeVariance::createInvariant(),
					),
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'T',
						new ObjectType('DateTime'),
						TemplateTypeVariance::createInvariant(),
					),
				],
				TemplateType::class,
				'T of DateTime (function a(), parameter)',
			],
			[
				[
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'T',
						new ObjectType('DateTime'),
						TemplateTypeVariance::createInvariant(),
					),
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'U',
						new ObjectType('DateTime'),
						TemplateTypeVariance::createInvariant(),
					),
				],
				IntersectionType::class,
				'T of DateTime (function a(), parameter)&U of DateTime (function a(), parameter)',
			],
			[
				[
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'T',
						null,
						TemplateTypeVariance::createInvariant(),
					),
					new MixedType(),
				],
				TemplateType::class,
				'T (function a(), parameter)=explicit',
			],
			[
				[
					new StringType(),
					new ClassStringType(),
				],
				ClassStringType::class,
				'class-string',
			],
			[
				[
					new ClassStringType(),
					new ConstantStringType(stdClass::class),
				],
				ConstantStringType::class,
				'\'stdClass\'',
			],
			[
				[
					new ClassStringType(),
					new ConstantStringType('Nonexistent'),
				],
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				[
					new ClassStringType(),
					new IntegerType(),
				],
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				[
					new ConstantStringType(Exception::class),
					new GenericClassStringType(new ObjectType(Exception::class)),
				],
				ConstantStringType::class,
				'\'Exception\'',
			],
			[
				[
					new GenericClassStringType(new ObjectType(Exception::class)),
					new ClassStringType(),
				],
				GenericClassStringType::class,
				'class-string<Exception>',
			],
			[
				[
					new GenericClassStringType(new ObjectType(Exception::class)),
					new StringType(),
				],
				GenericClassStringType::class,
				'class-string<Exception>',
			],
			[
				[
					new GenericClassStringType(new ObjectType(Exception::class)),
					new GenericClassStringType(new ObjectType(Exception::class)),
				],
				GenericClassStringType::class,
				'class-string<Exception>',
			],
			[
				[
					new GenericClassStringType(new ObjectType(Exception::class)),
					new GenericClassStringType(new ObjectType(Throwable::class)),
				],
				GenericClassStringType::class,
				'class-string<Exception>',
			],
			[
				[
					new GenericClassStringType(new ObjectType(Exception::class)),
					new GenericClassStringType(new ObjectType(InvalidArgumentException::class)),
				],
				GenericClassStringType::class,
				'class-string<InvalidArgumentException>',
			],
			[
				[
					new GenericClassStringType(new ObjectType(Exception::class)),
					new GenericClassStringType(new ObjectType(stdClass::class)),
				],
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				[
					new GenericClassStringType(new ObjectType(Exception::class)),
					new ConstantStringType(Exception::class),
				],
				ConstantStringType::class,
				'\'Exception\'',
			],
			[
				[
					new GenericClassStringType(new ObjectType(Throwable::class)),
					new ConstantStringType(Exception::class),
				],
				ConstantStringType::class,
				'\'Exception\'',
			],
			[
				[
					new GenericClassStringType(new ObjectType(InvalidArgumentException::class)),
					new ConstantStringType(Exception::class),
				],
				IntersectionType::class,
				"'Exception'&class-string<InvalidArgumentException>",
			],
			[
				[
					new GenericClassStringType(new ObjectType(Exception::class)),
					new ConstantStringType(stdClass::class),
				],
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				[
					IntegerRangeType::fromInterval(1, 3),
					IntegerRangeType::fromInterval(2, 5),
				],
				IntegerRangeType::class,
				'int<2, 3>',
			],
			[
				[
					IntegerRangeType::fromInterval(1, 3),
					IntegerRangeType::fromInterval(3, 5),
				],
				ConstantIntegerType::class,
				'3',
			],
			[
				[
					IntegerRangeType::fromInterval(1, 3),
					IntegerRangeType::fromInterval(7, 9),
				],
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				[
					IntegerRangeType::fromInterval(1, 3),
					new ConstantIntegerType(3),
				],
				ConstantIntegerType::class,
				'3',
			],
			[
				[
					IntegerRangeType::fromInterval(1, 3),
					new ConstantIntegerType(4),
				],
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				[
					IntegerRangeType::fromInterval(1, 3),
					new IntegerType(),
				],
				IntegerRangeType::class,
				'int<1, 3>',
			],
			[
				[
					new ObjectType(Traversable::class),
					new IterableType(new MixedType(), new MixedType()),
				],
				ObjectType::class,
				'Traversable',
			],
			[
				[
					new ObjectType(Traversable::class),
					new IterableType(new MixedType(), new MixedType()),
				],
				ObjectType::class,
				'Traversable',
			],
			[
				[
					new ObjectType(Traversable::class),
					new IterableType(new MixedType(), new MixedType(true)),
				],
				IntersectionType::class,
				'iterable&Traversable',
			],
			[
				[
					new ObjectType(Traversable::class),
					new IterableType(new MixedType(true), new MixedType()),
				],
				IntersectionType::class,
				'iterable&Traversable',
			],
			[
				[
					new ObjectType(Traversable::class),
					new IterableType(new MixedType(true), new MixedType(true)),
				],
				IntersectionType::class,
				'iterable&Traversable',
			],
			[
				[
					new MixedType(),
					new MixedType(),
				],
				MixedType::class,
				'mixed=implicit',
			],
			[
				[
					new MixedType(true),
					new MixedType(),
				],
				MixedType::class,
				'mixed=explicit',
			],
			[
				[
					new MixedType(true),
					new MixedType(true),
				],
				MixedType::class,
				'mixed=explicit',
			],
			[
				[
					new GenericObjectType(Variance\Covariant::class, [
						new ObjectType(DateTimeInterface::class),
					]),
					new GenericObjectType(Variance\Covariant::class, [
						new ObjectType(DateTime::class),
					]),
				],
				GenericObjectType::class,
				'PHPStan\Type\Variance\Covariant<DateTime>',
			],
			[
				[
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'T',
						new ObjectWithoutClassType(),
						TemplateTypeVariance::createInvariant(),
					),
					new ObjectWithoutClassType(),
				],
				TemplateObjectWithoutClassType::class,
				'T of object (function a(), parameter)',
			],
			[
				[
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'T',
						new ObjectWithoutClassType(),
						TemplateTypeVariance::createInvariant(),
					),
					new ObjectType(stdClass::class),
				],
				IntersectionType::class,
				'stdClass&T of object (function a(), parameter)',
			],
			[
				[
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('a'),
						'T',
						new ObjectWithoutClassType(),
						TemplateTypeVariance::createInvariant(),
					),
					new MixedType(),
				],
				TemplateObjectWithoutClassType::class,
				'T of object (function a(), parameter)',
			],
			[
				[
					new ConstantStringType('NonexistentClass'),
					new ClassStringType(),
				],
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				[
					new ConstantStringType(stdClass::class),
					new ClassStringType(),
				],
				ConstantStringType::class,
				'\'stdClass\'',
			],
			[
				[
					new ObjectType(DateTimeInterface::class),
					new ObjectType(Iterator::class),
				],
				IntersectionType::class,
				'DateTimeInterface&Iterator',
			],
			[
				[
					new ObjectType(DateTimeInterface::class),
					new GenericObjectType(Iterator::class, [new MixedType(), new MixedType()]),
				],
				IntersectionType::class,
				'DateTimeInterface&Iterator<mixed, mixed>',
			],
			[
				[
					new ConstantArrayType([
						new ConstantStringType('a'),
						new ConstantStringType('b'),
					], [
						new IntegerType(),
						new IntegerType(),
					], 2, [0]),
					new HasOffsetType(new ConstantStringType('a')),
				],
				ConstantArrayType::class,
				'array{a: int, b: int}',
			],
			[
				[
					new StringType(),
					new HasOffsetType(new IntegerType()),
				],
				IntersectionType::class,
				'string&hasOffset(int)',
			],
			[
				[
					new BenevolentUnionType([new IntegerType(), new StringType()]),
					new MixedType(),
				],
				BenevolentUnionType::class,
				'(int|string)',
			],
			[
				[
					new ConstantStringType('abc'),
					new AccessoryNumericStringType(),
				],
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				[
					new ConstantStringType('123'),
					new AccessoryNumericStringType(),
				],
				ConstantStringType::class,
				'\'123\'',
			],
			[
				[
					new StringType(),
					new AccessoryNumericStringType(),
				],
				IntersectionType::class,
				'numeric-string',
			],
			[
				[
					new IntegerType(),
					new AccessoryNumericStringType(),
				],
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				[
					new IntersectionType([
						new ArrayType(new StringType(), new IntegerType()),
						new NonEmptyArrayType(),
					]),
					new NeverType(),
				],
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				[
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('my_array_keys'),
						'T',
						new BenevolentUnionType([new IntegerType(), new StringType()]),
						TemplateTypeVariance::createInvariant(),
					),
					new UnionType([new IntegerType(), new StringType()]),
				],
				TemplateBenevolentUnionType::class,
				'T of (int|string) (function my_array_keys(), parameter)',
			],
			[
				[
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('my_array_keys'),
						'T',
						new BenevolentUnionType([new IntegerType(), new StringType()]),
						TemplateTypeVariance::createInvariant(),
					),
					new BenevolentUnionType([new IntegerType(), new StringType()]),
				],
				TemplateBenevolentUnionType::class,
				'T of (int|string) (function my_array_keys(), parameter)',
			],
			[
				[
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithFunction('my_array_keys'),
						'T',
						new UnionType([new IntegerType(), new StringType()]),
						TemplateTypeVariance::createInvariant(),
					),
					new UnionType([new IntegerType(), new StringType()]),
				],
				UnionType::class,
				'T of int|string (function my_array_keys(), parameter)',
			],
			[
				[
					new MixedType(),
					new StrictMixedType(),
				],
				StrictMixedType::class,
				'mixed',
			],
			[
				[
					new NeverType(true),
					new IntegerType(),
				],
				NeverType::class,
				'*NEVER*=explicit',
			],
			[
				[
					new NeverType(),
					new IntegerType(),
				],
				NeverType::class,
				'*NEVER*=implicit',
			],
		];

		if (PHP_VERSION_ID < 80100) {
			return;
		}

		yield [
			[
				new ObjectType('PHPStan\Fixture\TestEnum'),
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			],
			EnumCaseObjectType::class,
			'PHPStan\Fixture\TestEnum::ONE',
		];
		yield [
			[
				new ObjectType('PHPStan\Fixture\TestEnum'),
				new EnumCaseObjectType(stdClass::class, 'ONE'),
			],
			NeverType::class,
			'*NEVER*=implicit',
		];
		yield [
			[
				new ObjectType('PHPStan\Fixture\TestEnumInterface'),
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			],
			EnumCaseObjectType::class,
			'PHPStan\Fixture\TestEnum::ONE',
		];
		yield [
			[
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			],
			EnumCaseObjectType::class,
			'PHPStan\Fixture\TestEnum::ONE',
		];
		yield [
			[
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'TWO'),
			],
			NeverType::class,
			'*NEVER*=implicit',
		];
		yield [
			[
				new ObjectType('PHPStan\Fixture\TestEnum'),
				new ObjectType('PHPStan\Fixture\TestEnumInterface'),
			],
			ObjectType::class,
			'PHPStan\Fixture\TestEnum',
		];
		yield [
			[
				new ObjectType('PHPStan\Fixture\TestEnumInterface'),
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			],
			EnumCaseObjectType::class,
			'PHPStan\Fixture\TestEnum::ONE',
		];
		yield [
			[
				new ObjectType('PHPStan\Fixture\TestEnumInterface'),
				new EnumCaseObjectType('PHPStan\Fixture\AnotherTestEnum', 'ONE'),
			],
			NeverType::class,
			'*NEVER*=implicit',
		];
		yield [
			[
				new ObjectType(FinalClass::class),
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			],
			NeverType::class,
			'*NEVER*=implicit',
		];
		yield [
			[
				new ObjectWithoutClassType(),
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			],
			EnumCaseObjectType::class,
			'PHPStan\Fixture\TestEnum::ONE',
		];
		yield [
			[
				new ObjectType('stdClass'),
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			],
			NeverType::class,
			'*NEVER*=implicit',
		];
		yield [
			[
				new MixedType(false, IntegerRangeType::fromInterval(17, null)),
				new MixedType(),
			],
			MixedType::class,
			'mixed~int<17, max>=implicit',
		];
		yield [
			[
				new StaticType($reflectionProvider->getClass(stdClass::class)),
				new ThisType($reflectionProvider->getClass(stdClass::class)),
			],
			ThisType::class,
			'$this(stdClass)',
		];

		yield [
			[
				new StaticType($reflectionProvider->getClass(stdClass::class)),
				new ObjectType(stdClass::class),
			],
			StaticType::class,
			'static(stdClass)',
		];

		yield [
			[
				new StaticType($reflectionProvider->getClass(stdClass::class)),
				new EnumCaseObjectType(stdClass::class, 'foo'),
			],
			IntersectionType::class,
			'static(stdClass)&stdClass::foo',
		];

		yield [
			[
				new ThisType($reflectionProvider->getClass(stdClass::class)),
				new EnumCaseObjectType(stdClass::class, 'foo'),
			],
			IntersectionType::class,
			'$this(stdClass)&stdClass::foo',
		];

		yield [
			[
				TypeCombinator::intersect(new StringType(), new AccessoryNonEmptyStringType()),
				TypeCombinator::intersect(new StringType(), new AccessoryNonFalsyStringType()),
			],
			IntersectionType::class,
			'non-falsy-string',
		];

		yield [
			[
				TypeCombinator::intersect(new StringType(), new AccessoryNonFalsyStringType()),
				new ConstantStringType('0'),
			],
			NeverType::class,
			'*NEVER*=implicit',
		];

		yield [
			[
				TypeCombinator::intersect(new StringType(), new AccessoryNonEmptyStringType()),
				new ConstantStringType('0'),
			],
			ConstantStringType::class,
			"'0'",
		];

		yield [
			[
				new HasOffsetValueType(new ConstantStringType('a'), new ConstantIntegerType(1)),
				new HasOffsetValueType(new ConstantStringType('a'), new IntegerType()),
			],
			HasOffsetValueType::class,
			'hasOffsetValue(\'a\', 1)',
		];

		yield [
			[
				TypeCombinator::intersect(new ArrayType(new MixedType(), new MixedType()), new HasOffsetValueType(new ConstantStringType('a'), new ConstantIntegerType(1))),
				TypeCombinator::intersect(new ArrayType(new MixedType(), new MixedType()), new HasOffsetValueType(new ConstantStringType('a'), new IntegerType())),
			],
			IntersectionType::class,
			'array&hasOffsetValue(\'a\', 1)',
		];
		yield [
			[
				new IntersectionType([new ArrayType(new IntegerType(), new StringType()), new OversizedArrayType()]),
				new ArrayType(new IntegerType(), new StringType()),
			],
			IntersectionType::class,
			'array<int, string>&oversized-array',
		];
		yield [
			[
				new ObjectType(TestInterface::class),
				new ClosureType([], new MixedType(), false),
			],
			NeverType::class,
			'*NEVER*=implicit',
		];
		yield [
			[
				new ObjectType(TestInterface::class),
				new ObjectType(Closure::class),
			],
			NeverType::class,
			'*NEVER*=implicit',
		];
		yield [
			[
				new ObjectShapeType([], []),
				new ObjectWithoutClassType(),
			],
			ObjectShapeType::class,
			'object{}',
		];
		yield [
			[
				new ObjectShapeType([], []),
				new ObjectType(stdClass::class),
			],
			IntersectionType::class,
			'object{}&stdClass',
		];
		yield [
			[
				new ObjectShapeType(['foo' => new IntegerType()], []),
				new HasPropertyType('foo'),
			],
			ObjectShapeType::class,
			'object{foo: int}',
		];
		yield [
			[
				new ObjectShapeType(['foo' => new IntegerType()], ['foo']),
				new HasPropertyType('foo'),
			],
			ObjectShapeType::class,
			'object{foo: int}',
		];
		yield [
			[
				new ObjectShapeType(['foo' => new IntegerType()], []),
				new HasPropertyType('bar'),
			],
			NeverType::class,
			'*NEVER*=implicit',
		];
		yield [
			[
				new ObjectShapeType(['foo' => new IntegerType()], []),
				new ObjectShapeType(['foo' => new IntegerType()], []),
			],
			ObjectShapeType::class,
			'object{foo: int}',
		];
		yield [
			[
				new ObjectShapeType(['foo' => new IntegerType()], []),
				new ObjectShapeType(['foo' => new ConstantIntegerType(1)], []),
			],
			ObjectShapeType::class,
			'object{foo: 1}',
		];
		yield [
			[
				new ObjectShapeType(['foo' => new IntegerType()], []),
				new ObjectShapeType(['foo' => new StringType()], []),
			],
			NeverType::class,
			'*NEVER*=implicit',
		];
		yield [
			[
				new ObjectShapeType(['foo' => new IntegerType()], []),
				new ObjectShapeType(['bar' => new StringType()], []),
			],
			NeverType::class,
			'*NEVER*=implicit',
		];
		yield [
			[
				new ObjectShapeType(['foo' => new IntegerType()], []),
				new ObjectType(Traversable::class),
			],
			IntersectionType::class,
			'object{foo: int}&Traversable',
		];
		yield [
			[
				new ObjectShapeType(['foo' => new IntegerType()], []),
				new ObjectType(\ObjectShapesAcceptance\Foo::class),
			],
			IntersectionType::class,
			'ObjectShapesAcceptance\Foo&object{foo: int}',
		];
		yield [
			[
				new ObjectShapeType(['foo' => new IntegerType()], []),
				new ObjectType(ClassWithFooIntProperty::class),
			],
			ObjectType::class,
			'ObjectShapesAcceptance\ClassWithFooIntProperty',
		];
		yield [
			[
				new ObjectShapeType(['foo' => new IntegerType()], []),
				new ObjectType(\ObjectShapesAcceptance\FinalClass::class),
			],
			PHP_VERSION_ID < 80200 ? IntersectionType::class : NeverType::class,
			PHP_VERSION_ID < 80200 ? 'ObjectShapesAcceptance\FinalClass&object{foo: int}' : '*NEVER*=implicit',
		];
		yield [
			[
				new NeverType(true),
				new NonAcceptingNeverType(),
			],
			NonAcceptingNeverType::class,
			'never=explicit',
		];
		yield [
			[
				new UnionType([
					new ConstantArrayType([], []),
					new ConstantArrayType([
						new ConstantStringType('a'),
						new ConstantStringType('b'),
					], [
						new ConstantBooleanType(true),
						new ConstantBooleanType(true),
					], [0], [0]),
					new ConstantArrayType([
						new ConstantStringType('a'),
						new ConstantStringType('c'),
					], [
						new ConstantBooleanType(true),
						new ConstantBooleanType(true),
					], [0], [0, 1]),
				]),
				new NonEmptyArrayType(),
			],
			UnionType::class,
			'array{a?: true, b: true}|(array{a?: true, c?: true}&non-empty-array)',
		];
		yield [
			[
				new ConstantArrayType([], []),
				new NonEmptyArrayType(),
			],
			NeverType::class,
			'*NEVER*=implicit',
		];
		yield [
			[
				new ConstantArrayType([
					new ConstantStringType('a'),
					new ConstantStringType('b'),
				], [
					new ConstantBooleanType(true),
					new ConstantBooleanType(true),
				], [0], [0]),
				new NonEmptyArrayType(),
			],
			ConstantArrayType::class,
			'array{a?: true, b: true}',
		];
		yield [
			[
				new ConstantArrayType([
					new ConstantStringType('a'),
					new ConstantStringType('c'),
				], [
					new ConstantBooleanType(true),
					new ConstantBooleanType(true),
				], [0], [0, 1]),
				new NonEmptyArrayType(),
			],
			IntersectionType::class,
			'array{a?: true, c?: true}&non-empty-array',
		];
		yield [
			[
				new CallableType(null, null, true, null, null, [], TrinaryLogic::createYes()),
				new CallableType(),
			],
			CallableType::class,
			'pure-callable(): mixed',
		];
		yield [
			[
				new CallableType(null, null, true, null, null, [], TrinaryLogic::createYes()),
				ClosureType::createPure(),
			],
			ClosureType::class,
			'pure-Closure',
		];
		yield [
			[
				new CallableType(null, null, true, null, null, [], TrinaryLogic::createMaybe()),
				new CallableType(null, null, true, null, null, [], TrinaryLogic::createYes()),
			],
			CallableType::class,
			'pure-callable(): mixed',
		];
		yield [
			[
				new ClosureType([], new MixedType(), true, null, null, null, [], [], [
					new SimpleImpurePoint('functionCall', 'foo', true),
				]),
				ClosureType::createPure(),
			],
			NeverType::class,
			'*NEVER*=implicit',
		];
		yield [
			[
				new ClosureType([], new MixedType(), true, null, null, null, [], [], [
					new SimpleImpurePoint('functionCall', 'foo', false),
				]),
				ClosureType::createPure(),
			],
			ClosureType::class,
			'pure-Closure',
		];
	}

	/**
	 * @dataProvider dataIntersect
	 * @param Type[] $types
	 * @param class-string<Type> $expectedTypeClass
	 */
	public function testIntersect(
		array $types,
		string $expectedTypeClass,
		string $expectedTypeDescription,
	): void
	{
		$actualType = TypeCombinator::intersect(...$types);
		$actualTypeDescription = $actualType->describe(VerbosityLevel::precise());
		if ($actualType instanceof MixedType) {
			if ($actualType->isExplicitMixed()) {
				$actualTypeDescription .= '=explicit';
			} else {
				$actualTypeDescription .= '=implicit';
			}
		}
		if ($actualType instanceof NeverType) {
			if ($actualType->isExplicit()) {
				$actualTypeDescription .= '=explicit';
			} else {
				$actualTypeDescription .= '=implicit';
			}
		}
		$this->assertSame($expectedTypeDescription, $actualTypeDescription);
		$this->assertInstanceOf($expectedTypeClass, $actualType);
	}

	/**
	 * @dataProvider dataIntersect
	 * @param Type[] $types
	 * @param class-string<Type> $expectedTypeClass
	 */
	public function testIntersectInversed(
		array $types,
		string $expectedTypeClass,
		string $expectedTypeDescription,
	): void
	{
		$actualType = TypeCombinator::intersect(...array_reverse($types));
		$actualTypeDescription = $actualType->describe(VerbosityLevel::precise());
		if ($actualType instanceof MixedType) {
			if ($actualType->isExplicitMixed()) {
				$actualTypeDescription .= '=explicit';
			} else {
				$actualTypeDescription .= '=implicit';
			}
		}
		if ($actualType instanceof NeverType) {
			if ($actualType->isExplicit()) {
				$actualTypeDescription .= '=explicit';
			} else {
				$actualTypeDescription .= '=implicit';
			}
		}
		$this->assertSame($expectedTypeDescription, $actualTypeDescription);
		$this->assertInstanceOf($expectedTypeClass, $actualType);
	}

	public function dataRemove(): array
	{
		return [
			[
				new ConstantBooleanType(true),
				new ConstantBooleanType(true),
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				new UnionType([
					new IntegerType(),
					new ConstantBooleanType(true),
				]),
				new ConstantBooleanType(true),
				IntegerType::class,
				'int',
			],
			[
				new UnionType([
					new ObjectType('Foo'),
					new ObjectType('Bar'),
				]),
				new ObjectType('Foo'),
				ObjectType::class,
				'Bar',
			],
			[
				new UnionType([
					new ObjectType('Foo'),
					new ObjectType('Bar'),
					new ObjectType('Baz'),
				]),
				new ObjectType('Foo'),
				UnionType::class,
				'Bar|Baz',
			],
			[
				new UnionType([
					new ArrayType(new MixedType(), new StringType()),
					new ArrayType(new MixedType(), new IntegerType()),
					new ObjectType('ArrayObject'),
				]),
				new ArrayType(new MixedType(), new IntegerType()),
				UnionType::class,
				'array<string>|ArrayObject',
			],
			[
				new ConstantBooleanType(true),
				new ConstantBooleanType(false),
				ConstantBooleanType::class,
				'true',
			],
			[
				new ConstantBooleanType(false),
				new ConstantBooleanType(true),
				ConstantBooleanType::class,
				'false',
			],
			[
				new ConstantBooleanType(true),
				new BooleanType(),
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				new ConstantBooleanType(false),
				new BooleanType(),
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				new BooleanType(),
				new ConstantBooleanType(true),
				ConstantBooleanType::class,
				'false',
			],
			[
				new BooleanType(),
				new ConstantBooleanType(false),
				ConstantBooleanType::class,
				'true',
			],
			[
				new BooleanType(),
				new BooleanType(),
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				StaticTypeFactory::falsey(),
				StaticTypeFactory::falsey(),
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				StaticTypeFactory::truthy(),
				StaticTypeFactory::truthy(),
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				StaticTypeFactory::truthy(),
				StaticTypeFactory::falsey(),
				MixedType::class,
				'mixed~0|0.0|\'\'|\'0\'|array{}|false|null',
			],
			[
				StaticTypeFactory::falsey(),
				StaticTypeFactory::truthy(),
				UnionType::class,
				'0|0.0|\'\'|\'0\'|array{}|false|null',
			],
			[
				new BooleanType(),
				StaticTypeFactory::falsey(),
				ConstantBooleanType::class,
				'true',
			],
			[
				new BooleanType(),
				StaticTypeFactory::truthy(),
				ConstantBooleanType::class,
				'false',
			],
			[
				new UnionType([
					new ConstantBooleanType(true),
					new IntegerType(),
				]),
				new BooleanType(),
				IntegerType::class,
				'int',
			],
			[
				new UnionType([
					new ConstantBooleanType(false),
					new IntegerType(),
				]),
				new BooleanType(),
				IntegerType::class,
				'int',
			],
			[
				new UnionType([
					new BooleanType(),
					new IntegerType(),
				]),
				new ConstantBooleanType(true),
				UnionType::class,
				'int|false',
			],
			[
				new UnionType([
					new BooleanType(),
					new IntegerType(),
				]),
				new ConstantBooleanType(false),
				UnionType::class,
				'int|true',
			],
			[
				new UnionType([
					new StringType(),
					new IntegerType(),
					new NullType(),
				]),
				new UnionType([
					new NullType(),
					new StringType(),
				]),
				IntegerType::class,
				'int',
			],
			[
				new IterableType(new MixedType(), new MixedType()),
				new ArrayType(new MixedType(), new MixedType()),
				ObjectType::class,
				'Traversable<mixed, mixed>',
			],
			[
				new IterableType(new MixedType(), new MixedType()),
				new ObjectType(Traversable::class),
				ArrayType::class,
				'array',
			],
			[
				new IterableType(new MixedType(), new MixedType()),
				new ObjectType(Iterator::class),
				IterableType::class,
				'iterable',
			],
			[
				new BenevolentUnionType([new IntegerType(), new StringType()]),
				new StringType(),
				IntegerType::class,
				'int',
			],
			[
				new BenevolentUnionType([new IntegerType(), new StringType()]),
				new IntegerType(),
				StringType::class,
				'string',
			],
			[
				new BenevolentUnionType([new IntegerType(), new StringType()]),
				new ConstantStringType('foo'),
				UnionType::class,
				'(int|string)',
			],
			[
				new BenevolentUnionType([new IntegerType(), new StringType()]),
				new ConstantIntegerType(1),
				UnionType::class,
				'(int<min, 0>|int<2, max>|string)',
			],
			[
				new BenevolentUnionType([new IntegerType(), new StringType()]),
				new UnionType([new IntegerType(), new StringType()]),
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				new ArrayType(new MixedType(), new MixedType()),
				new ConstantArrayType([], []),
				IntersectionType::class,
				'non-empty-array',
			],
			[
				TypeCombinator::union(
					new ConstantArrayType([], []),
					new ConstantArrayType([
						new ConstantIntegerType(0),
					], [
						new StringType(),
					]),
				),
				new ConstantArrayType([], []),
				ConstantArrayType::class,
				'array{string}',
			],
			[
				new IntersectionType([
					new ArrayType(new MixedType(), new MixedType()),
					new NonEmptyArrayType(),
				]),
				new NonEmptyArrayType(),
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				new ArrayType(new MixedType(), new MixedType()),
				new NonEmptyArrayType(),
				ConstantArrayType::class,
				'array{}',
			],
			[
				new ArrayType(new MixedType(), new MixedType()),
				new IntersectionType([
					new ArrayType(new MixedType(), new MixedType()),
					new HasOffsetType(new ConstantStringType('foo')),
				]),
				ArrayType::class,
				'array',
			],
			[
				new MixedType(),
				new IntegerType(),
				MixedType::class,
				'mixed~int',
			],
			[
				new MixedType(false, new IntegerType()),
				new IntegerType(),
				MixedType::class,
				'mixed~int',
			],
			[
				new MixedType(false, new IntegerType()),
				new StringType(),
				MixedType::class,
				'mixed~int|string',
			],
			[
				new MixedType(false),
				new MixedType(),
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				new MixedType(false, new StringType()),
				new MixedType(),
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				new MixedType(false),
				new MixedType(false, new StringType()),
				StringType::class,
				'string',
			],
			[
				new MixedType(false, new StringType()),
				new NeverType(),
				MixedType::class,
				'mixed~string',
			],
			[
				new ObjectType('Exception'),
				new ObjectType('InvalidArgumentException'),
				ObjectType::class,
				'Exception~InvalidArgumentException',
			],
			[
				new ObjectType('Exception', new ObjectType('InvalidArgumentException')),
				new ObjectType('LengthException'),
				ObjectType::class,
				'Exception~InvalidArgumentException|LengthException',
			],
			[
				new ObjectType('Exception'),
				new ObjectType('Throwable'),
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				new ObjectType('Exception', new ObjectType('InvalidArgumentException')),
				new ObjectType('InvalidArgumentException'),
				ObjectType::class,
				'Exception~InvalidArgumentException',
			],
			[
				IntegerRangeType::fromInterval(3, 7),
				IntegerRangeType::fromInterval(2, 4),
				IntegerRangeType::class,
				'int<5, 7>',
			],
			[
				IntegerRangeType::fromInterval(3, 7),
				IntegerRangeType::fromInterval(3, 4),
				IntegerRangeType::class,
				'int<5, 7>',
			],
			[
				IntegerRangeType::fromInterval(3, 7),
				IntegerRangeType::fromInterval(5, 7),
				IntegerRangeType::class,
				'int<3, 4>',
			],
			[
				IntegerRangeType::fromInterval(3, 7),
				new ConstantIntegerType(3),
				IntegerRangeType::class,
				'int<4, 7>',
			],
			[
				new IntegerType(),
				IntegerRangeType::fromInterval(null, 7),
				IntegerRangeType::class,
				'int<8, max>',
			],
			[
				IntegerRangeType::fromInterval(0, 2),
				IntegerRangeType::fromInterval(-1, 3),
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				IntegerRangeType::fromInterval(0, 2),
				IntegerRangeType::fromInterval(0, 3),
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				IntegerRangeType::fromInterval(0, 2),
				IntegerRangeType::fromInterval(-1, 2),
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				IntegerRangeType::fromInterval(0, 2),
				new IntegerType(),
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				IntegerRangeType::fromInterval(null, 1),
				IntegerRangeType::fromInterval(4, null),
				IntegerRangeType::class,
				'int<min, 1>',
			],
			[
				IntegerRangeType::fromInterval(1, null),
				IntegerRangeType::fromInterval(null, -4),
				IntegerRangeType::class,
				'int<1, max>',
			],
			[
				new UnionType([
					IntegerRangeType::fromInterval(3, null),
					IntegerRangeType::fromInterval(null, 1),
				]),
				IntegerRangeType::fromInterval(4, null),
				UnionType::class,
				'3|int<min, 1>',
			],
			[
				new ConstantArrayType([
					new ConstantIntegerType(0),
					new ConstantIntegerType(1),
				], [
					new StringType(),
					new StringType(),
				], 2),
				new HasOffsetType(new ConstantIntegerType(1)),
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				new ConstantArrayType([
					new ConstantIntegerType(0),
					new ConstantIntegerType(1),
				], [
					new StringType(),
					new StringType(),
				], 2, [1]),
				new HasOffsetType(new ConstantIntegerType(1)),
				ConstantArrayType::class,
				'array{string}',
			],
			[
				new ConstantArrayType([
					new ConstantIntegerType(0),
					new ConstantIntegerType(1),
				], [
					new StringType(),
					new StringType(),
				], 2, [1]),
				new HasOffsetType(new ConstantIntegerType(0)),
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				new MixedType(),
				new NeverType(),
				MixedType::class,
				'mixed',
			],
			[
				new ObjectWithoutClassType(),
				new NeverType(),
				ObjectWithoutClassType::class,
				'object',
			],
			[
				new ObjectType(stdClass::class),
				new NeverType(),
				ObjectType::class,
				'stdClass',
			],
			[
				TemplateTypeFactory::create(
					TemplateTypeScope::createWithClass('Foo'),
					'T',
					new BooleanType(),
					TemplateTypeVariance::createInvariant(),
				),
				new ConstantBooleanType(false),
				TemplateMixedType::class, // should be TemplateConstantBooleanType
				'T (class Foo, parameter)', // should be T of true
			],
			[
				new ObjectShapeType(['foo' => new IntegerType()], []),
				new HasPropertyType('foo'),
				NeverType::class,
				'*NEVER*=implicit',
			],
			[
				new ObjectShapeType(['foo' => new IntegerType()], ['foo']),
				new HasPropertyType('foo'),
				ObjectShapeType::class,
				'object{}',
			],
			[
				new ObjectShapeType(['foo' => new IntegerType()], []),
				new HasPropertyType('bar'),
				ObjectShapeType::class,
				'object{foo: int}',
			],
			[
				new ObjectShapeType(['foo' => new IntegerType()], ['foo']),
				new HasPropertyType('bar'),
				ObjectShapeType::class,
				'object{foo?: int}',
			],
		];
	}

	/**
	 * @dataProvider dataRemove
	 * @param class-string<Type> $expectedTypeClass
	 */
	public function testRemove(
		Type $fromType,
		Type $type,
		string $expectedTypeClass,
		string $expectedTypeDescription,
	): void
	{
		$result = TypeCombinator::remove($fromType, $type);
		$actualTypeDescription = $result->describe(VerbosityLevel::precise());
		if ($result instanceof NeverType) {
			if ($result->isExplicit()) {
				$actualTypeDescription .= '=explicit';
			} else {
				$actualTypeDescription .= '=implicit';
			}
		}
		$this->assertSame($expectedTypeDescription, $actualTypeDescription);
		$this->assertInstanceOf($expectedTypeClass, $result);
	}

	public function testSpecificUnionConstantArray(): void
	{
		$arrays = [];
		for ($i = 0; $i < 5; $i++) {
			$array = new ConstantArrayType([], []);
			for ($j = 0; $j < 5; $j++) {
				$arrays[] = $array = $array->setOffsetValueType(new ConstantIntegerType($j), new StringType());
				if ($i !== $j) {
					continue;
				}

				$arrays[] = $array = $array->setOffsetValueType(new ConstantStringType('test'), new StringType());
			}
		}
		$resultType = TypeCombinator::union(...$arrays);
		$this->assertInstanceOf(UnionType::class, $resultType);
		$this->assertSame('array{0: string, 1?: string, 2?: string, 3?: string, 4?: string, test: string}|array{0: string, 1?: string, 2?: string, 3?: string, 4?: string}', $resultType->describe(VerbosityLevel::precise()));
	}

	/**
	 * @dataProvider dataContainsNull
	 */
	public function testContainsNull(
		Type $type,
		bool $expectedResult,
	): void
	{
		$this->assertSame($expectedResult, TypeCombinator::containsNull($type));
	}

	public function dataContainsNull(): iterable
	{
		yield [new NullType(), true];
		yield [new UnionType([new IntegerType(), new NullType()]), true];
		yield [new MixedType(), false];
	}

}
