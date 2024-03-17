<?php declare(strict_types = 1);

namespace PHPStan\Type;

use DateTime;
use DateTimeImmutable;
use Exception;
use Iterator;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\HasMethodType;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\data\QueryType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use RecursionCallable\Foo;
use stdClass;
use function array_merge;
use function array_reverse;
use function get_class;
use function sprintf;
use const PHP_VERSION_ID;

class UnionTypeTest extends PHPStanTestCase
{

	public function dataIsCallable(): array
	{
		return [
			[
				TypeCombinator::union(
					new ConstantArrayType(
						[new ConstantIntegerType(0), new ConstantIntegerType(1)],
						[new ConstantStringType('Closure'), new ConstantStringType('bind')],
					),
					new ConstantStringType('array_push'),
				),
				TrinaryLogic::createYes(),
			],
			[
				new UnionType([
					new ArrayType(new MixedType(), new MixedType()),
					new StringType(),
				]),
				TrinaryLogic::createMaybe(),
			],
			[
				new UnionType([
					new ArrayType(new MixedType(), new MixedType()),
					new ObjectType('Closure'),
				]),
				TrinaryLogic::createMaybe(),
			],
			[
				new UnionType([
					new StringType(),
					new IntegerType(),
				]),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	/**
	 * @dataProvider dataIsCallable
	 */
	public function testIsCallable(UnionType $type, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isCallable();
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isCallable()', $type->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSelfCompare(): Iterator
	{
		$reflectionProvider = $this->createReflectionProvider();

		$integerType = new IntegerType();
		$stringType = new StringType();
		$mixedType = new MixedType();
		$constantStringType = new ConstantStringType('foo');
		$constantIntegerType = new ConstantIntegerType(42);
		$templateTypeScope = TemplateTypeScope::createWithClass('Foo');

		$mixedParam = new NativeParameterReflection('foo', false, $mixedType, PassedByReference::createNo(), false, null);
		$integerParam = new NativeParameterReflection('n', false, $integerType, PassedByReference::createNo(), false, null);

		yield [new AccessoryNumericStringType()];
		yield [new ArrayType($integerType, $stringType)];
		yield [new ArrayType($stringType, $mixedType)];
		yield [new BenevolentUnionType([$integerType, $stringType])];
		yield [new BooleanType()];
		yield [new CallableType()];
		yield [new CallableType([$mixedParam, $integerParam], $stringType, false)];
		yield [new ClassStringType()];
		yield [new ClosureType([$mixedParam, $integerParam], $stringType, false)];
		yield [new ConstantArrayType([$constantStringType, $constantIntegerType], [$mixedType, $stringType], 10, [1])];
		yield [new ConstantBooleanType(true)];
		yield [new ConstantFloatType(3.14)];
		yield [$constantIntegerType];
		yield [$constantStringType];
		yield [new ErrorType()];
		yield [new FloatType()];
		yield [new GenericClassStringType(new ObjectType(Exception::class))];
		yield [new GenericObjectType('Foo', [new ObjectType('DateTime')])];
		yield [new HasMethodType('Foo')];
		yield [new HasOffsetType($constantStringType)];
		yield [new HasPropertyType('foo')];
		yield [IntegerRangeType::fromInterval(3, 10)];
		yield [$integerType];
		yield [new IntersectionType([new HasMethodType('Foo'), new HasPropertyType('bar')])];
		yield [new IterableType($integerType, $stringType)];
		yield [$mixedType];
		yield [new NeverType()];
		yield [new NonEmptyArrayType()];
		yield [new NonexistentParentClassType()];
		yield [new NullType()];
		yield [new ObjectType('Foo')];
		yield [new ObjectWithoutClassType(new ObjectType('Foo'))];
		yield [new ResourceType()];
		yield [new StaticType($reflectionProvider->getClass('Foo'))];
		yield [new StrictMixedType()];
		yield [new StringAlwaysAcceptingObjectWithToStringType()];
		yield [$stringType];
		yield [TemplateTypeFactory::create($templateTypeScope, 'T', null, TemplateTypeVariance::createInvariant())];
		yield [TemplateTypeFactory::create($templateTypeScope, 'T', new ObjectType('Foo'), TemplateTypeVariance::createInvariant())];
		yield [TemplateTypeFactory::create($templateTypeScope, 'T', new ObjectWithoutClassType(), TemplateTypeVariance::createInvariant())];
		yield [new ThisType($reflectionProvider->getClass('Foo'))];
		yield [new UnionType([$integerType, $stringType])];
		yield [new VoidType()];
	}

	/**
	 * @dataProvider dataSelfCompare
	 *
	 */
	public function testSelfCompare(Type $type): void
	{
		$description = $type->describe(VerbosityLevel::precise());
		$this->assertTrue(
			$type->equals($type),
			sprintf('%s -> equals(itself)', $description),
		);
		$this->assertEquals(
			'Yes',
			$type->isSuperTypeOf($type)->describe(),
			sprintf('%s -> isSuperTypeOf(itself)', $description),
		);
		$this->assertInstanceOf(
			get_class($type),
			TypeCombinator::union($type, $type),
			sprintf('%s -> union with itself is same type', $description),
		);
	}

	public function dataIsSuperTypeOf(): Iterator
	{
		$unionTypeA = new UnionType([
			new IntegerType(),
			new StringType(),
		]);

		yield [
			$unionTypeA,
			$unionTypeA->getTypes()[0],
			TrinaryLogic::createYes(),
		];

		yield [
			$unionTypeA,
			$unionTypeA->getTypes()[1],
			TrinaryLogic::createYes(),
		];

		yield [
			$unionTypeA,
			$unionTypeA,
			TrinaryLogic::createYes(),
		];

		yield [
			$unionTypeA,
			new IntersectionType([new StringType(), new CallableType()]),
			TrinaryLogic::createYes(),
		];

		yield [
			$unionTypeA,
			new MixedType(),
			TrinaryLogic::createMaybe(),
		];

		yield [
			$unionTypeA,
			new CallableType(),
			TrinaryLogic::createMaybe(),
		];

		yield [
			$unionTypeA,
			new UnionType([new IntegerType(), new FloatType()]),
			TrinaryLogic::createMaybe(),
		];

		yield [
			$unionTypeA,
			new UnionType([new CallableType(), new FloatType()]),
			TrinaryLogic::createMaybe(),
		];

		yield [
			$unionTypeA,
			new IntersectionType([new MixedType(), new CallableType()]),
			TrinaryLogic::createMaybe(),
		];

		yield [
			$unionTypeA,
			new FloatType(),
			TrinaryLogic::createNo(),
		];

		yield [
			$unionTypeA,
			new UnionType([new ConstantBooleanType(true), new FloatType()]),
			TrinaryLogic::createNo(),
		];

		yield [
			$unionTypeA,
			new IterableType(new MixedType(), new MixedType()),
			TrinaryLogic::createNo(),
		];

		yield [
			$unionTypeA,
			new IntersectionType([new ArrayType(new MixedType(), new MixedType()), new CallableType()]),
			TrinaryLogic::createNo(),
		];

		$unionTypeB = new UnionType([
			new IntersectionType([
				new ObjectType('ArrayObject'),
				new IterableType(new MixedType(), new ObjectType('DatePeriod')),
			]),
			new ArrayType(new MixedType(), new ObjectType('DatePeriod')),
		]);

		yield [
			$unionTypeB,
			$unionTypeB->getTypes()[0],
			TrinaryLogic::createYes(),
		];

		yield [
			$unionTypeB,
			$unionTypeB->getTypes()[1],
			TrinaryLogic::createYes(),
		];

		yield [
			$unionTypeB,
			$unionTypeB,
			TrinaryLogic::createYes(),
		];

		yield [
			$unionTypeB,
			new MixedType(),
			TrinaryLogic::createMaybe(),
		];

		yield [
			$unionTypeB,
			new ObjectType('ArrayObject'),
			TrinaryLogic::createMaybe(),
		];

		yield [
			$unionTypeB,
			new IterableType(new MixedType(), new ObjectType('DatePeriod')),
			TrinaryLogic::createMaybe(),
		];

		yield [
			$unionTypeB,
			new IterableType(new MixedType(), new MixedType()),
			TrinaryLogic::createMaybe(),
		];

		yield [
			$unionTypeB,
			new StringType(),
			TrinaryLogic::createNo(),
		];

		yield [
			$unionTypeB,
			new IntegerType(),
			TrinaryLogic::createNo(),
		];

		yield [
			$unionTypeB,
			new ObjectType(stdClass::class),
			TrinaryLogic::createNo(),
		];

		yield [
			$unionTypeB,
			new IterableType(new MixedType(), new ObjectType('DateTime')),
			TrinaryLogic::createNo(),
		];

		yield [
			$unionTypeB,
			new CallableType(),
			TrinaryLogic::createMaybe(),
		];

		yield [
			$unionTypeB,
			new IntersectionType([new MixedType(), new CallableType()]),
			TrinaryLogic::createMaybe(),
		];

		yield [
			$unionTypeB,
			new IntersectionType([new StringType(), new CallableType()]),
			TrinaryLogic::createNo(),
		];

		yield 'is super type of template-of-union with same members' => [
			new UnionType([
				new IntegerType(),
				new FloatType(),
			]),
			TemplateTypeFactory::create(
				TemplateTypeScope::createWithClass('Foo'),
				'T',
				new UnionType([
					new IntegerType(),
					new FloatType(),
				]),
				TemplateTypeVariance::createInvariant(),
			),
			TrinaryLogic::createYes(),
		];

		yield 'is super type of template-of-union equal to a union member' => [
			new UnionType([
				TemplateTypeFactory::create(
					TemplateTypeScope::createWithClass('Foo'),
					'T',
					new UnionType([
						new IntegerType(),
						new FloatType(),
					]),
					TemplateTypeVariance::createInvariant(),
				),
				new NullType(),
			]),
			TemplateTypeFactory::create(
				TemplateTypeScope::createWithClass('Foo'),
				'T',
				new UnionType([
					new IntegerType(),
					new FloatType(),
				]),
				TemplateTypeVariance::createInvariant(),
			),
			TrinaryLogic::createYes(),
		];

		yield 'maybe super type of template-of-union equal to a union member' => [
			new UnionType([
				TemplateTypeFactory::create(
					TemplateTypeScope::createWithClass('Foo'),
					'T',
					new UnionType([
						new IntegerType(),
						new FloatType(),
					]),
					TemplateTypeVariance::createInvariant(),
				),
				new NullType(),
			]),
			TemplateTypeFactory::create(
				TemplateTypeScope::createWithClass('Bar'),
				'T',
				new UnionType([
					new IntegerType(),
					new FloatType(),
				]),
				TemplateTypeVariance::createInvariant(),
			),
			TrinaryLogic::createMaybe(),
		];

		yield 'is super type of template-of-string equal to a union member' => [
			new UnionType([
				TemplateTypeFactory::create(
					TemplateTypeScope::createWithClass('Foo'),
					'T',
					new StringType(),
					TemplateTypeVariance::createInvariant(),
				),
				new NullType(),
			]),
			TemplateTypeFactory::create(
				TemplateTypeScope::createWithClass('Foo'),
				'T',
				new StringType(),
				TemplateTypeVariance::createInvariant(),
			),
			TrinaryLogic::createYes(),
		];

		yield 'maybe super type of template-of-string sub type of a union member' => [
			new UnionType([
				TemplateTypeFactory::create(
					TemplateTypeScope::createWithClass('Foo'),
					'T',
					new StringType(),
					TemplateTypeVariance::createInvariant(),
				),
				new NullType(),
			]),
			TemplateTypeFactory::create(
				TemplateTypeScope::createWithClass('Bar'),
				'T',
				new StringType(),
				TemplateTypeVariance::createInvariant(),
			),
			TrinaryLogic::createMaybe(),
		];
	}

	/**
	 * @dataProvider dataIsSuperTypeOf
	 */
	public function testIsSuperTypeOf(UnionType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	public function dataIsSubTypeOf(): Iterator
	{
		$unionTypeA = new UnionType([
			new IntegerType(),
			new StringType(),
		]);

		yield [
			$unionTypeA,
			$unionTypeA,
			TrinaryLogic::createYes(),
		];

		yield [
			$unionTypeA,
			new UnionType(array_merge($unionTypeA->getTypes(), [new ResourceType()])),
			TrinaryLogic::createYes(),
		];

		yield [
			$unionTypeA,
			new MixedType(),
			TrinaryLogic::createYes(),
		];

		yield [
			$unionTypeA,
			$unionTypeA->getTypes()[0],
			TrinaryLogic::createMaybe(),
		];

		yield [
			$unionTypeA,
			$unionTypeA->getTypes()[1],
			TrinaryLogic::createMaybe(),
		];

		yield [
			$unionTypeA,
			new CallableType(),
			TrinaryLogic::createMaybe(),
		];

		yield [
			$unionTypeA,
			new UnionType([new IntegerType(), new FloatType()]),
			TrinaryLogic::createMaybe(),
		];

		yield [
			$unionTypeA,
			new UnionType([new CallableType(), new FloatType()]),
			TrinaryLogic::createMaybe(),
		];

		yield [
			$unionTypeA,
			new IntersectionType([new StringType(), new CallableType()]),
			TrinaryLogic::createMaybe(),
		];

		yield [
			$unionTypeA,
			new IntersectionType([new MixedType(), new CallableType()]),
			TrinaryLogic::createMaybe(),
		];

		yield [
			$unionTypeA,
			new FloatType(),
			TrinaryLogic::createNo(),
		];

		yield [
			$unionTypeA,
			new UnionType([new ConstantBooleanType(true), new FloatType()]),
			TrinaryLogic::createNo(),
		];

		yield [
			$unionTypeA,
			new IterableType(new MixedType(), new MixedType()),
			TrinaryLogic::createNo(),
		];

		yield [
			$unionTypeA,
			new IntersectionType([new ArrayType(new MixedType(), new MixedType()), new CallableType()]),
			TrinaryLogic::createNo(),
		];

		$unionTypeB = new UnionType([
			new IntersectionType([
				new ObjectType('ArrayObject'),
				new IterableType(new MixedType(), new ObjectType('Item')),
				new CallableType(),
			]),
			new ArrayType(new MixedType(), new ObjectType('Item')),
		]);

		yield [
			$unionTypeB,
			$unionTypeB,
			TrinaryLogic::createYes(),
		];

		yield [
			$unionTypeB,
			new UnionType(array_merge($unionTypeB->getTypes(), [new StringType()])),
			TrinaryLogic::createYes(),
		];

		yield [
			$unionTypeB,
			new MixedType(),
			TrinaryLogic::createYes(),
		];

		yield [
			$unionTypeB,
			$unionTypeB->getTypes()[0],
			TrinaryLogic::createMaybe(),
		];

		yield [
			$unionTypeB,
			$unionTypeB->getTypes()[1],
			TrinaryLogic::createMaybe(),
		];

		yield [
			$unionTypeB,
			new ObjectType('ArrayObject'),
			TrinaryLogic::createMaybe(),
		];

		yield [
			$unionTypeB,
			new CallableType(),
			TrinaryLogic::createMaybe(),
		];

		yield [
			$unionTypeB,
			new FloatType(),
			TrinaryLogic::createNo(),
		];

		yield [
			$unionTypeB,
			new ObjectType(stdClass::class),
			TrinaryLogic::createNo(),
		];
	}

	/**
	 * @dataProvider dataIsSubTypeOf
	 */
	public function testIsSubTypeOf(UnionType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSubTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSubTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	/**
	 * @dataProvider dataIsSubTypeOf
	 */
	public function testIsSubTypeOfInversed(UnionType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $otherType->isSuperTypeOf($type);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $otherType->describe(VerbosityLevel::precise()), $type->describe(VerbosityLevel::precise())),
		);
	}

	public function dataIsScalar(): array
	{
		return [
			[
				TypeCombinator::union(
					new BooleanType(),
					new IntegerType(),
					new FloatType(),
					new StringType(),
				),
				TrinaryLogic::createYes(),
			],
			[
				new UnionType([
					new BooleanType(),
					new ObjectType(DateTimeImmutable::class),
				]),
				TrinaryLogic::createMaybe(),
			],
			[
				new UnionType([
					new IntegerType(),
					new NullType(),
				]),
				TrinaryLogic::createMaybe(),
			],
			[
				new UnionType([
					new FloatType(),
					new MixedType(),
				]),
				TrinaryLogic::createMaybe(),
			],
			[
				new UnionType([
					new ArrayType(new IntegerType(), new StringType()),
					new StringType(),
				]),
				TrinaryLogic::createMaybe(),
			],
			[
				new UnionType([
					new ArrayType(new IntegerType(), new StringType()),
					new NullType(),
					new ObjectType(DateTimeImmutable::class),
					new ResourceType(),
				]),
				TrinaryLogic::createNo(),
			],
		];
	}

	/** @dataProvider dataIsScalar */
	public function testIsScalar(UnionType $type, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isScalar();
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isScalar()', $type->describe(VerbosityLevel::precise())),
		);
	}

	public function dataDescribe(): array
	{
		return [
			[
				new UnionType([new IntegerType(), new StringType()]),
				'int|string',
				'int|string',
			],
			[
				new UnionType([new IntegerType(), new StringType(), new NullType()]),
				'int|string|null',
				'int|string|null',
			],
			[
				new UnionType([
					new ConstantStringType('1aaa'),
					new ConstantStringType('11aaa'),
					new ConstantStringType('2aaa'),
					new ConstantStringType('10aaa'),
					new ConstantIntegerType(2),
					new ConstantIntegerType(1),
					new ConstantIntegerType(10),
					new ConstantFloatType(2.2),
					new NullType(),
					new ConstantStringType('10'),
					new ObjectType(stdClass::class),
					new ConstantBooleanType(true),
					new ConstantStringType('foo'),
					new ConstantStringType('2'),
					new ConstantStringType('1'),
				]),
				"1|2|2.2|10|'1'|'10'|'10aaa'|'11aaa'|'1aaa'|'2'|'2aaa'|'foo'|stdClass|true|null",
				'float|int|stdClass|string|true|null',
			],
			[
				TypeCombinator::union(
					new ConstantArrayType([
						new ConstantStringType('a'),
						new ConstantStringType('b'),
					], [
						new StringType(),
						new BooleanType(),
					]),
					new ConstantArrayType([
						new ConstantStringType('a'),
						new ConstantStringType('b'),
					], [
						new IntegerType(),
						new FloatType(),
					]),
					new ConstantStringType('aaa'),
				),
				'\'aaa\'|array{a: int, b: float}|array{a: string, b: bool}',
				'array<string, bool|float|int|string>|string',
			],
			[
				TypeCombinator::union(
					new ConstantArrayType([
						new ConstantStringType('a'),
						new ConstantStringType('b'),
					], [
						new StringType(),
						new BooleanType(),
					]),
					new ConstantArrayType([
						new ConstantStringType('b'),
						new ConstantStringType('c'),
					], [
						new IntegerType(),
						new FloatType(),
					]),
					new ConstantStringType('aaa'),
				),
				'\'aaa\'|array{a: string, b: bool}|array{b: int, c: float}',
				'array<string, bool|float|int|string>|string',
			],
			[
				TypeCombinator::union(
					new ConstantArrayType([
						new ConstantStringType('a'),
						new ConstantStringType('b'),
					], [
						new StringType(),
						new BooleanType(),
					]),
					new ConstantArrayType([
						new ConstantStringType('c'),
						new ConstantStringType('d'),
					], [
						new IntegerType(),
						new FloatType(),
					]),
					new ConstantStringType('aaa'),
				),
				'\'aaa\'|array{a: string, b: bool}|array{c: int, d: float}',
				'array<string, bool|float|int|string>|string',
			],
			[
				TypeCombinator::union(
					new ConstantArrayType([
						new ConstantIntegerType(0),
					], [
						new StringType(),
					]),
					new ConstantArrayType([
						new ConstantIntegerType(0),
						new ConstantIntegerType(1),
						new ConstantIntegerType(2),
					], [
						new IntegerType(),
						new BooleanType(),
						new FloatType(),
					]),
				),
				'array{int, bool, float}|array{string}',
				'array<int, bool|float|int|string>',
			],
			[
				TypeCombinator::union(
					new ConstantArrayType([], []),
					new ConstantArrayType([
						new ConstantStringType('foooo'),
					], [
						new ConstantStringType('barrr'),
					]),
				),
				'array{}|array{foooo: \'barrr\'}',
				'array<string, string>',
			],
			[
				TypeCombinator::union(
					new IntegerType(),
					new IntersectionType([
						new StringType(),
						new AccessoryNumericStringType(),
					]),
				),
				'int|numeric-string',
				'int|string',
			],
			[
				TypeCombinator::union(
					IntegerRangeType::fromInterval(0, 4),
					IntegerRangeType::fromInterval(6, 10),
				),
				'int<0, 4>|int<6, 10>',
				'int<0, 4>|int<6, 10>',
			],
			[
				TypeCombinator::union(
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithClass('foo'),
						'TFoo',
						new IntegerType(),
						TemplateTypeVariance::createInvariant(),
					),
					new NullType(),
				),
				'(TFoo of int)|null',
				'(TFoo of int)|null',
			],
			[
				TypeCombinator::union(
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithClass('foo'),
						'TFoo',
						new IntegerType(),
						TemplateTypeVariance::createInvariant(),
					),
					new GenericClassStringType(new ObjectType('Abc')),
				),
				'class-string<Abc>|TFoo of int',
				'class-string<Abc>|TFoo of int',
			],
			[
				TypeCombinator::union(
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithClass('foo'),
						'TFoo',
						new MixedType(true),
						TemplateTypeVariance::createInvariant(),
					),
					new NullType(),
				),
				'TFoo|null',
				'TFoo|null',
			],
			[
				TypeCombinator::union(
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithClass('foo'),
						'TFoo',
						TemplateTypeFactory::create(
							TemplateTypeScope::createWithClass('foo'),
							'TBar',
							new MixedType(true),
							TemplateTypeVariance::createInvariant(),
						),
						TemplateTypeVariance::createInvariant(),
					),
					new NullType(),
				),
				'(TFoo of TBar)|null',
				'(TFoo of TBar)|null',
			],
			[
				new UnionType([new QueryType('foo'), new QueryType('bar')]),
				'Doctrine\ORM\Query<mixed, mixed>',
				'Doctrine\ORM\Query<mixed, mixed>',
			],
		];
	}

	/**
	 * @dataProvider dataDescribe
	 */
	public function testDescribe(
		Type $type,
		string $expectedValueDescription,
		string $expectedTypeOnlyDescription,
	): void
	{
		$this->assertSame($expectedValueDescription, $type->describe(VerbosityLevel::value()));
		$this->assertSame($expectedTypeOnlyDescription, $type->describe(VerbosityLevel::typeOnly()));
	}

	public function dataAccepts(): iterable
	{
		yield from [
			[
				new UnionType([new CallableType(), new NullType()]),
				new ClosureType([], new StringType(), false),
				TrinaryLogic::createYes(),
			],
			[
				new UnionType([new CallableType(), new NullType()]),
				new UnionType([new ClosureType([], new StringType(), false), new BooleanType()]),
				TrinaryLogic::createMaybe(),
			],
			[
				new UnionType([new CallableType(), new NullType()]),
				new BooleanType(),
				TrinaryLogic::createNo(),
			],
			[
				new UnionType([
					new CallableType([
						new NativeParameterReflection('a', false, new IntegerType(), PassedByReference::createNo(), false, null),
						new NativeParameterReflection('a', false, new StringType(), PassedByReference::createNo(), false, null),
						new NativeParameterReflection('a', false, new StringType(), PassedByReference::createNo(), false, null),
						new NativeParameterReflection('a', false, new ArrayType(new MixedType(), new MixedType()), PassedByReference::createNo(), false, null),
					], new BooleanType(), false),
					new NullType(),
				]),
				new CallableType(),
				TrinaryLogic::createYes(),
			],
			[
				new UnionType([
					new CallableType([
						new NativeParameterReflection('a', false, new IntegerType(), PassedByReference::createNo(), false, null),
						new NativeParameterReflection('a', false, new StringType(), PassedByReference::createNo(), false, null),
						new NativeParameterReflection('a', false, new StringType(), PassedByReference::createNo(), false, null),
						new NativeParameterReflection('a', false, new ArrayType(new MixedType(), new MixedType()), PassedByReference::createNo(), false, null),
					], new BooleanType(), false),
					new NullType(),
				]),
				new BooleanType(),
				TrinaryLogic::createNo(),
			],
			[
				new UnionType([
					new ClosureType([
						new NativeParameterReflection('a', false, new IntegerType(), PassedByReference::createNo(), false, null),
						new NativeParameterReflection('a', false, new StringType(), PassedByReference::createNo(), false, null),
						new NativeParameterReflection('a', false, new StringType(), PassedByReference::createNo(), false, null),
						new NativeParameterReflection('a', false, new ArrayType(new MixedType(), new MixedType()), PassedByReference::createNo(), false, null),
					], new BooleanType(), false),
					new NullType(),
				]),
				new CallableType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new UnionType([
					new ClosureType([
						new NativeParameterReflection('a', false, new IntegerType(), PassedByReference::createNo(), false, null),
						new NativeParameterReflection('a', false, new StringType(), PassedByReference::createNo(), false, null),
						new NativeParameterReflection('a', false, new StringType(), PassedByReference::createNo(), false, null),
						new NativeParameterReflection('a', false, new ArrayType(new MixedType(), new MixedType()), PassedByReference::createNo(), false, null),
					], new BooleanType(), false),
					new NullType(),
				]),
				new ClosureType([], new MixedType(), false),
				TrinaryLogic::createYes(),
			],

		];

		if (PHP_VERSION_ID >= 80100) {
			yield [
				new UnionType([
					new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'A'),
					new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'B'),
					new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'C'),
					new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'D'),
				]),
				new ObjectType(
					'PHPStan\Fixture\ManyCasesTestEnum',
					new UnionType([
						new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'E'),
						new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'F'),
					]),
				),
				TrinaryLogic::createYes(),
			];

			yield [
				new UnionType([
					new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'A'),
					new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'B'),
					new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'C'),
					new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'D'),
					new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'E'),
				]),
				new ObjectType(
					'PHPStan\Fixture\ManyCasesTestEnum',
					new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'F'),
				),
				TrinaryLogic::createYes(),
			];

			yield [
				new UnionType([
					new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
					new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'TWO'),
				]),
				new ObjectType('PHPStan\Fixture\TestEnum'),
				TrinaryLogic::createYes(),
			];

			yield [
				new UnionType([
					new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
					new EnumCaseObjectType('PHPStan\Fixture\AnotherTestEnum', 'TWO'),
				]),
				new ObjectType('PHPStan\Fixture\TestEnum'),
				TrinaryLogic::createMaybe(),
			];

			yield [
				new UnionType([
					new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
					new NullType(),
				]),
				new ObjectType(
					'PHPStan\Fixture\TestEnum',
					new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'TWO'),
				),
				TrinaryLogic::createYes(),
			];

			yield [
				new UnionType([
					new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
					new NullType(),
				]),
				new UnionType([
					new ObjectType(
						'PHPStan\Fixture\TestEnum',
						new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'TWO'),
					),
					new NullType(),
				]),
				TrinaryLogic::createYes(),
			];
		}

		yield from [
			'accepts template-of-union with same members' => [
				new UnionType([
					new IntegerType(),
					new FloatType(),
				]),
				TemplateTypeFactory::create(
					TemplateTypeScope::createWithClass('Foo'),
					'T',
					new UnionType([
						new IntegerType(),
						new FloatType(),
					]),
					TemplateTypeVariance::createInvariant(),
				),
				TrinaryLogic::createYes(),
			],
			'accepts template-of-union equal to a union member' => [
				new UnionType([
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithClass('Foo'),
						'T',
						new UnionType([
							new IntegerType(),
							new FloatType(),
						]),
						TemplateTypeVariance::createInvariant(),
					),
					new NullType(),
				]),
				TemplateTypeFactory::create(
					TemplateTypeScope::createWithClass('Foo'),
					'T',
					new UnionType([
						new IntegerType(),
						new FloatType(),
					]),
					TemplateTypeVariance::createInvariant(),
				),
				TrinaryLogic::createYes(),
			],
			'accepts template-of-union sub type of a union member' => [
				new UnionType([
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithClass('Foo'),
						'T',
						new UnionType([
							new IntegerType(),
							new FloatType(),
						]),
						TemplateTypeVariance::createInvariant(),
					),
					new NullType(),
				]),
				TemplateTypeFactory::create(
					TemplateTypeScope::createWithClass('Bar'),
					'T',
					new UnionType([
						new IntegerType(),
						new FloatType(),
					]),
					TemplateTypeVariance::createInvariant(),
				),
				TrinaryLogic::createYes(),
			],
			'maybe accepts template-of-union sub type of a union member (argument)' => [
				new UnionType([
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithClass('Foo'),
						'T',
						new UnionType([
							new IntegerType(),
							new FloatType(),
						]),
						TemplateTypeVariance::createInvariant(),
					)->toArgument(),
					new NullType(),
				]),
				TemplateTypeFactory::create(
					TemplateTypeScope::createWithClass('Bar'),
					'T',
					new UnionType([
						new IntegerType(),
						new FloatType(),
					]),
					TemplateTypeVariance::createInvariant(),
				),
				TrinaryLogic::createMaybe(),
			],
			'accepts template-of-string equal to a union member' => [
				new UnionType([
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithClass('Foo'),
						'T',
						new StringType(),
						TemplateTypeVariance::createInvariant(),
					),
					new NullType(),
				]),
				TemplateTypeFactory::create(
					TemplateTypeScope::createWithClass('Foo'),
					'T',
					new StringType(),
					TemplateTypeVariance::createInvariant(),
				),
				TrinaryLogic::createYes(),
			],
			'accepts template-of-string sub type of a union member' => [
				new UnionType([
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithClass('Foo'),
						'T',
						new StringType(),
						TemplateTypeVariance::createInvariant(),
					),
					new NullType(),
				]),
				TemplateTypeFactory::create(
					TemplateTypeScope::createWithClass('Bar'),
					'T',
					new StringType(),
					TemplateTypeVariance::createInvariant(),
				),
				TrinaryLogic::createMaybe(),
			],
			'maybe accepts template-of-string sub type of a union member (argument)' => [
				new UnionType([
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithClass('Foo'),
						'T',
						new StringType(),
						TemplateTypeVariance::createInvariant(),
					)->toArgument(),
					new NullType(),
				]),
				TemplateTypeFactory::create(
					TemplateTypeScope::createWithClass('Bar'),
					'T',
					new StringType(),
					TemplateTypeVariance::createInvariant(),
				),
				TrinaryLogic::createMaybe(),
			],
			'accepts template-of-union containing a union member' => [
				new UnionType([
					new IntegerType(),
					new NullType(),
				]),
				TemplateTypeFactory::create(
					TemplateTypeScope::createWithClass('Foo'),
					'T',
					new UnionType([
						new IntegerType(),
						new FloatType(),
					]),
					TemplateTypeVariance::createInvariant(),
				),
				TrinaryLogic::createMaybe(),
			],
			'accepts intersection with template-of-union equal to a union member' => [
				new UnionType([
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithClass('Foo'),
						'T',
						new UnionType([
							new ObjectType('Iterator'),
							new ObjectType('IteratorAggregate'),
						]),
						TemplateTypeVariance::createInvariant(),
					),
					new NullType(),
				]),
				new IntersectionType([
					TemplateTypeFactory::create(
						TemplateTypeScope::createWithClass('Foo'),
						'T',
						new UnionType([
							new ObjectType('Iterator'),
							new ObjectType('IteratorAggregate'),
						]),
						TemplateTypeVariance::createInvariant(),
					),
					new ObjectType('Countable'),
				]),
				TrinaryLogic::createYes(),
			],
		];
	}

	/**
	 * @dataProvider dataAccepts
	 */
	public function testAccepts(
		UnionType $type,
		Type $acceptedType,
		TrinaryLogic $expectedResult,
	): void
	{
		$this->assertSame(
			$expectedResult->describe(),
			$type->accepts($acceptedType, true)->describe(),
			sprintf('%s -> accepts(%s)', $type->describe(VerbosityLevel::precise()), $acceptedType->describe(VerbosityLevel::precise())),
		);
	}

	public function dataHasMethod(): array
	{
		return [
			[
				new UnionType([new ObjectType(DateTimeImmutable::class), new IntegerType()]),
				'format',
				TrinaryLogic::createMaybe(),
			],
			[
				new UnionType([new ObjectType(DateTimeImmutable::class), new ObjectType(DateTime::class)]),
				'format',
				TrinaryLogic::createYes(),
			],
			[
				new UnionType([new FloatType(), new IntegerType()]),
				'format',
				TrinaryLogic::createNo(),
			],
			[
				new UnionType([new ObjectType(DateTimeImmutable::class), new NullType()]),
				'format',
				TrinaryLogic::createMaybe(),
			],
		];
	}

	/**
	 * @dataProvider dataHasMethod
	 */
	public function testHasMethod(
		UnionType $type,
		string $methodName,
		TrinaryLogic $expectedResult,
	): void
	{
		$this->assertSame($expectedResult->describe(), $type->hasMethod($methodName)->describe());
	}

	public function testSorting(): void
	{
		$types = [
			new ConstantBooleanType(false),
			new ConstantBooleanType(true),
			new ConstantIntegerType(-1),
			new ConstantIntegerType(0),
			new ConstantIntegerType(1),
			new ConstantFloatType(-1.0),
			new ConstantFloatType(0.0),
			new ConstantFloatType(1.0),
			new ConstantStringType(''),
			new ConstantStringType('a'),
			new ConstantStringType('b'),
			new ConstantArrayType([], []),
			new ConstantArrayType([new ConstantStringType('')], [new ConstantStringType('')]),
			new IntegerType(),
			IntegerRangeType::fromInterval(10, 20),
			IntegerRangeType::fromInterval(30, 40),
			new FloatType(),
			new StringType(),
			new ClassStringType(),
			new MixedType(),
		];

		$type1 = new UnionType($types);
		$type2 = new UnionType(array_reverse($types));

		$this->assertSame(
			$type1->describe(VerbosityLevel::precise()),
			$type2->describe(VerbosityLevel::precise()),
			'UnionType sorting always produces the same order',
		);

		$this->assertTrue(
			$type1->equals($type2),
			'UnionType sorting always produces the same order',
		);
	}

	/**
	 * @dataProvider dataGetConstantArrays
	 * @param Type[] $types
	 * @param list<string> $expectedDescriptions
	 */
	public function testGetConstantArrays(
		array $types,
		array $expectedDescriptions,
	): void
	{
		$unionType = TypeCombinator::union(...$types);
		$constantArrays = $unionType->getConstantArrays();

		$actualDescriptions = [];
		foreach ($constantArrays as $constantArray) {
			$actualDescriptions[] = $constantArray->describe(VerbosityLevel::precise());
		}

		$this->assertSame($expectedDescriptions, $actualDescriptions);
	}

	public function dataGetConstantArrays(): iterable
	{
		yield from [
			[
				[
					TypeCombinator::intersect(
						new ConstantArrayType(
							[new ConstantIntegerType(1), new ConstantIntegerType(2)],
							[new IntegerType(), new StringType()],
							2,
							[0, 1],
						),
						new NonEmptyArrayType(),
					),
					new ConstantArrayType(
						[new ConstantIntegerType(0), new ConstantIntegerType(1)],
						[new ObjectType(Foo::class), new ObjectType(stdClass::class)],
						2,
					),
				],
				[
					'array{1?: int, 2?: string}',
					'array{RecursionCallable\Foo, stdClass}',
				],
			],
			[
				[
					TypeCombinator::intersect(
						new ConstantArrayType(
							[new ConstantIntegerType(1), new ConstantIntegerType(2)],
							[new IntegerType(), new StringType()],
							2,
							[0, 1],
						),
					),
					new IntegerType(),
				],
				[],
			],
		];
	}

	/**
	 * @dataProvider dataGetConstantStrings
	 * @param list<string> $expectedDescriptions
	 */
	public function testGetConstantStrings(
		Type $unionType,
		array $expectedDescriptions,
	): void
	{
		$constantStrings = $unionType->getConstantStrings();

		$actualDescriptions = [];
		foreach ($constantStrings as $constantString) {
			$actualDescriptions[] = $constantString->describe(VerbosityLevel::precise());
		}

		$this->assertSame($expectedDescriptions, $actualDescriptions);
	}

	public function dataGetConstantStrings(): iterable
	{
		yield from [
			[
				TypeCombinator::union(
					new ConstantStringType('hello'),
					new ConstantStringType('world'),
				),
				[
					"'hello'",
					"'world'",
				],
			],
			[
				TypeCombinator::union(
					new ConstantStringType(''),
					TypeCombinator::intersect(
						new StringType(),
						new AccessoryNumericStringType(),
					),
				),
				[],
			],
			[
				new UnionType([
					new IntersectionType(
						[
							new ConstantStringType('foo'),
							new AccessoryLiteralStringType(),
						],
					),
					new IntersectionType(
						[
							new ConstantStringType('bar'),
							new AccessoryLiteralStringType(),
						],
					),
				]),
				[
					"'foo'",
					"'bar'",
				],
			],
			[
				new BenevolentUnionType([
					new ConstantStringType('foo'),
					new NullType(),
				]),
				[
					"'foo'",
				],
			],
		];
	}

	/**
	 * @dataProvider dataGetObjectClassNames
	 * @param list<string> $expectedObjectClassNames
	 */
	public function testGetObjectClassNames(
		Type $unionType,
		array $expectedObjectClassNames,
	): void
	{
		$this->assertSame($expectedObjectClassNames, $unionType->getObjectClassNames());
	}

	public function dataGetObjectClassNames(): iterable
	{
		yield from [
			[
				TypeCombinator::union(
					new ObjectType(stdClass::class),
					new ObjectType(DateTimeImmutable::class),
				),
				[
					'stdClass',
					'DateTimeImmutable',
				],
			],
			[
				TypeCombinator::union(
					new ObjectType(stdClass::class),
					new NullType(),
				),
				[],
			],
			[
				TypeCombinator::union(
					new StringType(),
					new NullType(),
				),
				[],
			],
		];
	}

	/**
	 * @dataProvider dataGetArrays
	 * @param list<string> $expectedDescriptions
	 */
	public function testGetArrays(
		Type $unionType,
		array $expectedDescriptions,
	): void
	{
		$arrays = $unionType->getArrays();

		$actualDescriptions = [];
		foreach ($arrays as $arrayType) {
			$actualDescriptions[] = $arrayType->describe(VerbosityLevel::precise());
		}

		$this->assertSame($expectedDescriptions, $actualDescriptions);
	}

	public function dataGetArrays(): iterable
	{
		yield from [
			[
				TypeCombinator::union(
					new ConstantStringType('hello'),
					new ConstantStringType('world'),
				),
				[],
			],
			[
				TypeCombinator::union(
					TypeCombinator::intersect(
						new ConstantArrayType(
							[new ConstantIntegerType(1), new ConstantIntegerType(2)],
							[new IntegerType(), new StringType()],
							2,
							[0, 1],
						),
						new NonEmptyArrayType(),
					),
					new ConstantArrayType(
						[new ConstantIntegerType(0), new ConstantIntegerType(1)],
						[new ObjectType(Foo::class), new ObjectType(stdClass::class)],
						2,
					),
				),
				[
					'array{1?: int, 2?: string}',
					'array{RecursionCallable\Foo, stdClass}',
				],
			],
			[
				TypeCombinator::union(
					new ArrayType(new IntegerType(), new StringType()),
					new ConstantArrayType(
						[new ConstantIntegerType(1), new ConstantIntegerType(2)],
						[new IntegerType(), new StringType()],
						2,
						[0, 1],
					),
					new ConstantArrayType(
						[new ConstantIntegerType(0), new ConstantIntegerType(1)],
						[new ObjectType(Foo::class), new ObjectType(stdClass::class)],
						2,
					),
				),
				[
					'array<int, int|RecursionCallable\Foo|stdClass|string>',
				],
			],
		];
	}

}
