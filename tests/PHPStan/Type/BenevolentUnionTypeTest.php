<?php declare(strict_types = 1);

namespace PHPStan\Type;

use DateTimeImmutable;
use Iterator;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use function sprintf;

class BenevolentUnionTypeTest extends PHPStanTestCase
{

	public function dataCanAccessProperties(): Iterator
	{
		yield [
			new BenevolentUnionType([new ObjectWithoutClassType(), new ObjectWithoutClassType()]),
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([new ObjectWithoutClassType(), new NullType()]),
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([new NullType(), new NullType()]),
			TrinaryLogic::createNo(),
		];
	}

	/** @dataProvider dataCanAccessProperties */
	public function testCanAccessProperties(BenevolentUnionType $type, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->canAccessProperties();
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> canAccessProperties()', $type->describe(VerbosityLevel::precise())),
		);
	}

	public function dataHasProperty(): Iterator
	{
		yield [
			new BenevolentUnionType([
				new IntersectionType([new ObjectWithoutClassType(), new HasPropertyType('foo')]),
				new IntersectionType([new ObjectWithoutClassType(), new HasPropertyType('foo')]),
			]),
			'foo',
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([
				new IntersectionType([new ObjectWithoutClassType(), new HasPropertyType('foo')]),
				new NullType(),
			]),
			'foo',
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([new NullType(), new NullType()]),
			'foo',
			TrinaryLogic::createNo(),
		];
	}

	/** @dataProvider dataHasProperty */
	public function testHasProperty(BenevolentUnionType $type, string $propertyName, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->hasProperty($propertyName);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> hasProperty()', $type->describe(VerbosityLevel::precise())),
		);
	}

	public function dataCanCallMethods(): Iterator
	{
		yield [
			new BenevolentUnionType([new ObjectWithoutClassType(), new ObjectWithoutClassType()]),
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([new ObjectWithoutClassType(), new NullType()]),
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([new NullType(), new NullType()]),
			TrinaryLogic::createNo(),
		];
	}

	/** @dataProvider dataCanCallMethods */
	public function testCanCanCallMethods(BenevolentUnionType $type, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->canCallMethods();
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> canCallMethods()', $type->describe(VerbosityLevel::precise())),
		);
	}

	public function dataHasMethod(): Iterator
	{
		yield [
			new BenevolentUnionType([
				new ObjectType(DateTimeImmutable::class),
				new ObjectType(DateTimeImmutable::class),
			]),
			'format',
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([new ObjectType(DateTimeImmutable::class), new NullType()]),
			'format',
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([new NullType(), new NullType()]),
			'format',
			TrinaryLogic::createNo(),
		];
	}

	/** @dataProvider dataHasMethod */
	public function testHasMethod(BenevolentUnionType $type, string $methodName, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->hasMethod($methodName);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> hasMethod()', $type->describe(VerbosityLevel::precise())),
		);
	}

	public function dataCanAccessConstants(): Iterator
	{
		yield [
			new BenevolentUnionType([new ObjectWithoutClassType(), new ObjectWithoutClassType()]),
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([new ObjectWithoutClassType(), new NullType()]),
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([new NullType(), new NullType()]),
			TrinaryLogic::createNo(),
		];
	}

	/** @dataProvider dataCanAccessConstants */
	public function testCanAccessConstants(BenevolentUnionType $type, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->canAccessConstants();
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> canAccessConstants()', $type->describe(VerbosityLevel::precise())),
		);
	}

	public function dataIsIterable(): Iterator
	{
		yield [
			new BenevolentUnionType([
				new ArrayType(new MixedType(), new MixedType()),
				new ArrayType(new MixedType(), new MixedType()),
			]),
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([
				new ArrayType(new MixedType(), new MixedType()),
				new NullType(),
			]),
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([new NullType(), new NullType()]),
			TrinaryLogic::createNo(),
		];
	}

	/** @dataProvider dataIsIterable */
	public function testIsIterable(BenevolentUnionType $type, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isIterable();
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isIterable()', $type->describe(VerbosityLevel::precise())),
		);
	}

	public function dataIsIterableAtLeastOnce(): Iterator
	{
		yield [
			new BenevolentUnionType([
				new IntersectionType([new ArrayType(new MixedType(), new MixedType()), new NonEmptyArrayType()]),
				new IntersectionType([new ArrayType(new MixedType(), new MixedType()), new NonEmptyArrayType()]),
			]),
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([
				new IntersectionType([new ArrayType(new MixedType(), new MixedType()), new NonEmptyArrayType()]),
				new NullType(),
			]),
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([new NullType(), new NullType()]),
			TrinaryLogic::createNo(),
		];
	}

	/** @dataProvider dataIsIterableAtLeastOnce */
	public function testIsIterableAtLeastOnce(BenevolentUnionType $type, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isIterableAtLeastOnce();
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isIterableAtLeastOnce()', $type->describe(VerbosityLevel::precise())),
		);
	}

	public function dataIsArray(): Iterator
	{
		yield [
			new BenevolentUnionType([new ArrayType(new MixedType(), new MixedType()), new ConstantArrayType([], [])]),
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([new ArrayType(new MixedType(), new MixedType()), new NullType()]),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new BenevolentUnionType([new NullType(), new NullType()]),
			TrinaryLogic::createNo(),
		];
	}

	/** @dataProvider dataIsArray */
	public function testIsArray(BenevolentUnionType $type, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isArray();
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isArray()', $type->describe(VerbosityLevel::precise())),
		);
	}

	public function dataIsString(): Iterator
	{
		yield [
			new BenevolentUnionType([
				new StringType(),
				new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()]),
			]),
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([new IntegerType(), new StringType()]),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new BenevolentUnionType([new IntegerType(), new IntegerType()]),
			TrinaryLogic::createNo(),
		];
	}

	/** @dataProvider dataIsString */
	public function testIsString(BenevolentUnionType $type, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isString();
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isString()', $type->describe(VerbosityLevel::precise())),
		);
	}

	public function dataIsNumericString(): Iterator
	{
		yield [
			new BenevolentUnionType([
				new IntersectionType([new StringType(), new AccessoryNumericStringType()]),
				new IntersectionType([new StringType(), new AccessoryNumericStringType()])]),
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([new IntegerType(), new StringType()]),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new BenevolentUnionType([new IntegerType(), new IntegerType()]),
			TrinaryLogic::createNo(),
		];
	}

	/** @dataProvider dataIsNumericString */
	public function testIsNumericString(BenevolentUnionType $type, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isNumericString();
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isNumericString()', $type->describe(VerbosityLevel::precise())),
		);
	}

	public function dataIsNonFalsyString(): Iterator
	{
		yield [
			new BenevolentUnionType([
				new IntersectionType([new StringType(), new AccessoryNonFalsyStringType()]),
				new IntersectionType([new StringType(), new AccessoryNonFalsyStringType()])]),
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([new IntegerType(), new StringType()]),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new BenevolentUnionType([new IntegerType(), new IntegerType()]),
			TrinaryLogic::createNo(),
		];
	}

	/** @dataProvider dataIsNonFalsyString */
	public function testIsNonFalsyString(BenevolentUnionType $type, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isNonFalsyString();
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isNonFalsyString()', $type->describe(VerbosityLevel::precise())),
		);
	}

	public function dataIsLiteralString(): Iterator
	{
		yield [
			new BenevolentUnionType([
				new IntersectionType([new StringType(), new AccessoryLiteralStringType()]),
				new IntersectionType([new StringType(), new AccessoryLiteralStringType()])]),
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([new IntegerType(), new StringType()]),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new BenevolentUnionType([new IntegerType(), new IntegerType()]),
			TrinaryLogic::createNo(),
		];
	}

	/** @dataProvider dataIsLiteralString */
	public function testIsLiteralString(BenevolentUnionType $type, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isLiteralString();
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isLiteralString()', $type->describe(VerbosityLevel::precise())),
		);
	}

	public function dataIsOffsetAccessible(): Iterator
	{
		yield [
			new BenevolentUnionType([
				new ArrayType(new MixedType(), new MixedType()),
				new ArrayType(new MixedType(), new MixedType()),
			]),
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([
				new ArrayType(new MixedType(), new MixedType()),
				new StrictMixedType(),
			]),
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([new StrictMixedType(), new StrictMixedType()]),
			TrinaryLogic::createNo(),
		];
	}

	/** @dataProvider dataIsOffsetAccessible */
	public function testIsOffsetAccessible(BenevolentUnionType $type, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isOffsetAccessible();
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isOffsetAccessible()', $type->describe(VerbosityLevel::precise())),
		);
	}

	public function dataHasOffsetValueType(): Iterator
	{
		yield [
			new BenevolentUnionType([
				new ConstantArrayType([new ConstantStringType('foo')], [new MixedType()]),
				new ConstantArrayType([new ConstantStringType('foo')], [new MixedType()]),
			]),
			new ConstantStringType('foo'),
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([
				new ConstantArrayType([new ConstantStringType('foo')], [new MixedType()]),
				new NullType(),
			]),
			new ConstantStringType('foo'),
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([new NullType(), new NullType()]),
			new ConstantStringType('foo'),
			TrinaryLogic::createNo(),
		];
	}

	/** @dataProvider dataHasOffsetValueType */
	public function testHasOffsetValue(BenevolentUnionType $type, Type $offsetType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->hasOffsetValueType($offsetType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> hasOffsetValueType()', $type->describe(VerbosityLevel::precise())),
		);
	}

	public function dataIsCallable(): Iterator
	{
		yield [
			new BenevolentUnionType([new CallableType(), new CallableType()]),
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([new CallableType(), new NullType()]),
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([new NullType(), new NullType()]),
			TrinaryLogic::createNo(),
		];
	}

	/** @dataProvider dataIsCallable */
	public function testIsCallable(BenevolentUnionType $type, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isCallable();
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isCallable()', $type->describe(VerbosityLevel::precise())),
		);
	}

	public function dataIsCloneable(): Iterator
	{
		yield [
			new BenevolentUnionType([new ObjectWithoutClassType(), new ObjectWithoutClassType()]),
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([new ObjectWithoutClassType(), new NullType()]),
			TrinaryLogic::createYes(),
		];

		yield [
			new BenevolentUnionType([new NullType(), new NullType()]),
			TrinaryLogic::createNo(),
		];
	}

	/** @dataProvider dataIsCloneable */
	public function testIsCloneable(BenevolentUnionType $type, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isCloneable();
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isCloneable()', $type->describe(VerbosityLevel::precise())),
		);
	}

}
