<?php declare(strict_types = 1);

namespace PHPStan\Type;

use ArrayAccess;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use function sprintf;

class MixedTypeTest extends PHPStanTestCase
{

	public function dataIsSuperTypeOf(): array
	{
		return [
			0 => [
				new MixedType(),
				new MixedType(),
				TrinaryLogic::createYes(),
			],
			1 => [
				new MixedType(),
				new IntegerType(),
				TrinaryLogic::createYes(),
			],
			2 => [
				new MixedType(false, new IntegerType()),
				new IntegerType(),
				TrinaryLogic::createNo(),
			],
			3 => [
				new MixedType(false, new IntegerType()),
				new ConstantIntegerType(1),
				TrinaryLogic::createNo(),
			],
			4 => [
				new MixedType(false, new ConstantIntegerType(1)),
				new IntegerType(),
				TrinaryLogic::createMaybe(),
			],
			5 => [
				new MixedType(false, new ConstantIntegerType(1)),
				new MixedType(),
				TrinaryLogic::createMaybe(),
			],
			6 => [
				new MixedType(),
				new MixedType(false, new ConstantIntegerType(1)),
				TrinaryLogic::createYes(),
			],
			7 => [
				new MixedType(false, new ConstantIntegerType(1)),
				new MixedType(false, new ConstantIntegerType(1)),
				TrinaryLogic::createYes(),
			],
			8 => [
				new MixedType(false, new IntegerType()),
				new MixedType(false, new ConstantIntegerType(1)),
				TrinaryLogic::createMaybe(),
			],
			9 => [
				new MixedType(false, new ConstantIntegerType(1)),
				new MixedType(false, new IntegerType()),
				TrinaryLogic::createYes(),
			],
			10 => [
				new MixedType(false, new StringType()),
				new MixedType(false, new IntegerType()),
				TrinaryLogic::createMaybe(),
			],
			11 => [
				new MixedType(),
				new ObjectWithoutClassType(),
				TrinaryLogic::createYes(),
			],
			12 => [
				new MixedType(false, new ObjectWithoutClassType()),
				new ObjectWithoutClassType(),
				TrinaryLogic::createNo(),
			],
			13 => [
				new MixedType(false, new ObjectType('Exception')),
				new ObjectWithoutClassType(),
				TrinaryLogic::createMaybe(),
			],
			14 => [
				new MixedType(false, new ObjectType('Exception')),
				new ObjectWithoutClassType(new ObjectType('Exception')),
				TrinaryLogic::createYes(),
			],
			15 => [
				new MixedType(false, new ObjectType('Exception')),
				new ObjectWithoutClassType(new ObjectType('InvalidArgumentException')),
				TrinaryLogic::createMaybe(),
			],
			16 => [
				new MixedType(false, new ObjectType('InvalidArgumentException')),
				new ObjectWithoutClassType(new ObjectType('Exception')),
				TrinaryLogic::createYes(),
			],
			17 => [
				new MixedType(false, new ObjectType('Exception')),
				new ObjectType('Exception'),
				TrinaryLogic::createNo(),
			],
			18 => [
				new MixedType(false, new ObjectType('InvalidArgumentException')),
				new ObjectType('Exception'),
				TrinaryLogic::createMaybe(),
			],
			19 => [
				new MixedType(false, new ObjectType('Exception')),
				new ObjectType('InvalidArgumentException'),
				TrinaryLogic::createNo(),
			],
			20 => [
				new MixedType(false, new ObjectType('Exception')),
				new MixedType(),
				TrinaryLogic::createMaybe(),
			],
			21 => [
				new MixedType(false, new ObjectType('Exception')),
				new MixedType(false, new ObjectType('stdClass')),
				TrinaryLogic::createMaybe(),
			],
			22 => [
				new MixedType(),
				new NeverType(),
				TrinaryLogic::createYes(),
			],
			23 => [
				new MixedType(false, new NullType()),
				new NeverType(),
				TrinaryLogic::createYes(),
			],
			24 => [
				new MixedType(),
				new UnionType([new StringType(), new IntegerType()]),
				TrinaryLogic::createYes(),
			],
			25 => [
				new MixedType(false, new NullType()),
				new UnionType([new StringType(), new IntegerType()]),
				TrinaryLogic::createYes(),
			],
			26 => [
				new MixedType(),
				new StrictMixedType(),
				TrinaryLogic::createYes(),
			],
		];
	}

	/**
	 * @dataProvider dataIsSuperTypeOf
	 */
	public function testIsSuperTypeOf(MixedType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubtractedIsArray(): array
	{
		return [
			[
				new MixedType(),
				new ArrayType(new IntegerType(), new StringType()),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new ArrayType(new StringType(), new StringType()),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new ArrayType(new MixedType(), new MixedType()),
				TrinaryLogic::createNo(),
			],
			[
				new MixedType(),
				new ConstantArrayType(
					[new ConstantIntegerType(1)],
					[new ConstantStringType('hello')],
				),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new UnionType([new FloatType(), new ArrayType(new MixedType(), new MixedType())]),
				TrinaryLogic::createNo(),
			],
			[
				new MixedType(),
				new UnionType([new FloatType(), new ArrayType(new StringType(), new MixedType())]),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new UnionType([new FloatType(), new IntegerType()]),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new FloatType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(true),
				new FloatType(),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	/**
	 * @dataProvider dataSubtractedIsArray
	 */
	public function testSubtractedIsArray(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isArray();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isArray()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubtractedIsConstantArray(): array
	{
		return [
			[
				new MixedType(),
				new ArrayType(new IntegerType(), new StringType()),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new ArrayType(new StringType(), new StringType()),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new ArrayType(new MixedType(), new MixedType()),
				TrinaryLogic::createNo(),
			],
			[
				new MixedType(),
				new ConstantArrayType(
					[new ConstantIntegerType(1)],
					[new ConstantStringType('hello')],
				),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new ConstantArrayType([], []),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new UnionType([new FloatType(), new ArrayType(new MixedType(), new MixedType())]),
				TrinaryLogic::createNo(),
			],
			[
				new MixedType(),
				new UnionType([new FloatType(), new ArrayType(new StringType(), new MixedType())]),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new UnionType([new FloatType(), new IntegerType()]),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new FloatType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(true),
				new FloatType(),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	/**
	 * @dataProvider dataSubtractedIsConstantArray
	 */
	public function testSubtractedIsConstantArray(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isConstantArray();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isConstantArray()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubtractedIsString(): array
	{
		return [
			[
				new MixedType(),
				new StringType(),
				TrinaryLogic::createNo(),
			],
			[
				new MixedType(),
				TypeCombinator::intersect(
					new StringType(),
					new AccessoryNonEmptyStringType(),
				),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				TypeCombinator::intersect(
					new StringType(),
					new AccessoryNumericStringType(),
				),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new IntegerType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new ArrayType(new StringType(), new StringType()),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	/**
	 * @dataProvider dataSubtractedIsString
	 */
	public function testSubtractedIsString(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isString();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isString()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubtractedIsNumericString(): array
	{
		return [
			[
				new MixedType(),
				new StringType(),
				TrinaryLogic::createNo(),
			],
			[
				new MixedType(),
				TypeCombinator::intersect(
					new StringType(),
					new AccessoryNonEmptyStringType(),
				),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				TypeCombinator::intersect(
					new StringType(),
					new AccessoryNumericStringType(),
				),
				TrinaryLogic::createNo(),
			],
			[
				new MixedType(),
				new IntegerType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new ArrayType(new StringType(), new StringType()),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	/**
	 * @dataProvider dataSubtractedIsNumericString
	 */
	public function testSubtractedIsNumericString(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isNumericString();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isNumericString()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubtractedIsNonEmptyString(): array
	{
		return [
			[
				new MixedType(),
				new StringType(),
				TrinaryLogic::createNo(),
			],
			[
				new MixedType(),
				TypeCombinator::intersect(
					new StringType(),
					new AccessoryNonEmptyStringType(),
				),
				TrinaryLogic::createNo(),
			],
			[
				new MixedType(),
				TypeCombinator::intersect(
					new StringType(),
					new AccessoryNonFalsyStringType(),
				),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				TypeCombinator::intersect(
					new StringType(),
					new AccessoryNumericStringType(),
				),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new IntegerType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new ArrayType(new StringType(), new StringType()),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	/**
	 * @dataProvider dataSubtractedIsNonEmptyString
	 */
	public function testSubtractedIsNonEmptyString(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isNonEmptyString();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isNonEmptyString()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubtractedIsNonFalsyString(): array
	{
		return [
			[
				new MixedType(),
				new StringType(),
				TrinaryLogic::createNo(),
			],
			[
				new MixedType(),
				TypeCombinator::intersect(
					new StringType(),
					new AccessoryNonEmptyStringType(),
				),
				TrinaryLogic::createNo(),
			],
			[
				new MixedType(),
				TypeCombinator::intersect(
					new StringType(),
					new AccessoryNonFalsyStringType(),
				),
				TrinaryLogic::createNo(),
			],
			[
				new MixedType(),
				TypeCombinator::intersect(
					new StringType(),
					new AccessoryNumericStringType(),
				),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new IntegerType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new ArrayType(new StringType(), new StringType()),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	/**
	 * @dataProvider dataSubtractedIsNonFalsyString
	 */
	public function testSubtractedIsNonFalsyString(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isNonFalsyString();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isNonFalsyString()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubtractedIsLiteralString(): array
	{
		return [
			[
				new MixedType(),
				new StringType(),
				TrinaryLogic::createNo(),
			],
			[
				new MixedType(),
				TypeCombinator::intersect(
					new StringType(),
					new AccessoryLiteralStringType(),
				),
				TrinaryLogic::createNo(),
			],
			[
				new MixedType(),
				TypeCombinator::intersect(
					new StringType(),
					new AccessoryNonEmptyStringType(),
				),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				TypeCombinator::intersect(
					new StringType(),
					new AccessoryNonFalsyStringType(),
				),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				TypeCombinator::intersect(
					new StringType(),
					new AccessoryNumericStringType(),
				),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new IntegerType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new ArrayType(new StringType(), new StringType()),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	/**
	 * @dataProvider dataSubtractedIsClassString
	 */
	public function testSubtractedIsClassString(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isClassStringType();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isClassStringType()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubtractedIsClassString(): array
	{
		return [
			[
				new MixedType(),
				new StringType(),
				TrinaryLogic::createNo(),
			],
			[
				new MixedType(),
				new ClassStringType(),
				TrinaryLogic::createNo(),
			],
			[
				new MixedType(),
				new IntersectionType([
					new StringType(),
					new AccessoryLiteralStringType(),
				]),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new IntegerType(),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	/** @dataProvider dataSubtractedIsVoid */
	public function testSubtractedIsVoid(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isVoid();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isVoid()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubtractedIsVoid(): array
	{
		return [
			[
				new MixedType(),
				new VoidType(),
				TrinaryLogic::createNo(),
			],
			[
				new MixedType(),
				new StringType(),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	/** @dataProvider dataSubtractedIsScalar */
	public function testSubtractedIsScalar(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isScalar();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isScalar()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubtractedIsScalar(): array
	{
		return [
			[
				new MixedType(),
				new UnionType([new BooleanType(), new FloatType(), new IntegerType(), new StringType()]),
				TrinaryLogic::createNo(),
			],
			[
				new MixedType(),
				new StringType(),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	/**
	 * @dataProvider dataSubtractedIsLiteralString
	 */
	public function testSubtractedIsLiteralString(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isLiteralString();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isLiteralString()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubtractedIsIterable(): array
	{
		return [
			[
				new MixedType(),
				new StringType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				TypeCombinator::intersect(
					new StringType(),
					new AccessoryLiteralStringType(),
				),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new IntegerType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new ArrayType(new StringType(), new StringType()),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new IterableType(new MixedType(), new MixedType()),
				TrinaryLogic::createNo(),
			],
			[
				new MixedType(),
				new IterableType(new StringType(), new StringType()),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	/**
	 * @dataProvider dataSubtractedIsBoolean
	 */
	public function testSubtractedIsBoolean(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isBoolean();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isBoolean()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubtractedIsBoolean(): array
	{
		return [
			[
				new MixedType(),
				new IntegerType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new ConstantBooleanType(true),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new ConstantBooleanType(false),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new BooleanType(),
				TrinaryLogic::createNo(),
			],
		];
	}

	/**
	 * @dataProvider dataSubtractedIsFalse
	 */
	public function testSubtractedIsFalse(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isFalse();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isFalse()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubtractedIsFalse(): array
	{
		return [
			[
				new MixedType(),
				new IntegerType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new ConstantBooleanType(true),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new ConstantBooleanType(false),
				TrinaryLogic::createNo(),
			],
			[
				new MixedType(),
				new BooleanType(),
				TrinaryLogic::createNo(),
			],
		];
	}

	/**
	 * @dataProvider dataSubtractedIsNull
	 */
	public function testSubtractedIsNull(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isNull();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isNull()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubtractedIsNull(): array
	{
		return [
			[
				new MixedType(),
				new IntegerType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new ConstantBooleanType(true),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new ConstantBooleanType(false),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new BooleanType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new NullType(),
				TrinaryLogic::createNo(),
			],
		];
	}

	/**
	 * @dataProvider dataSubtractedIsTrue
	 */
	public function testSubtractedIsTrue(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isTrue();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isTrue()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubtractedIsTrue(): array
	{
		return [
			[
				new MixedType(),
				new IntegerType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new ConstantBooleanType(true),
				TrinaryLogic::createNo(),
			],
			[
				new MixedType(),
				new ConstantBooleanType(false),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new BooleanType(),
				TrinaryLogic::createNo(),
			],
		];
	}

	/**
	 * @dataProvider dataSubtractedIsFloat
	 */
	public function testSubtractedIsFloat(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isFloat();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isFloat()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubtractedIsFloat(): array
	{
		return [
			[
				new MixedType(),
				new IntegerType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				IntegerRangeType::fromInterval(-5, 5),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new FloatType(),
				TrinaryLogic::createNo(),
			],
		];
	}

	/**
	 * @dataProvider dataSubtractedIsInteger
	 */
	public function testSubtractedIsInteger(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isInteger();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isInteger()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubtractedIsInteger(): array
	{
		return [
			[
				new MixedType(),
				new IntegerType(),
				TrinaryLogic::createNo(),
			],
			[
				new MixedType(),
				IntegerRangeType::fromInterval(-5, 5),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new StringType(),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	/**
	 * @dataProvider dataSubtractedIsIterable
	 */
	public function testSubtractedIsIterable(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isIterable();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isIterable()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubtractedIsOffsetAccessible(): array
	{
		return [
			[
				new MixedType(),
				new ArrayType(new MixedType(), new MixedType()),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new StringType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new ObjectType(ArrayAccess::class),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new UnionType([
					new ArrayType(new MixedType(), new MixedType()),
					new StringType(),
					new ObjectType(ArrayAccess::class),
				]),
				TrinaryLogic::createNo(),
			],
			[
				new MixedType(),
				new UnionType([
					new ArrayType(new MixedType(), new MixedType()),
					new StringType(),
					new ObjectType(ArrayAccess::class),
					new FloatType(),
				]),
				TrinaryLogic::createNo(),
			],
		];
	}

	/**
	 * @dataProvider dataSubtractedIsOffsetAccessible
	 */
	public function testSubtractedIsOffsetAccessible(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isOffsetAccessible();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isOffsetAccessible()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubtractedHasOffsetValueType(): array
	{
		return [
			[
				new MixedType(),
				new ArrayType(new MixedType(), new MixedType()),
				new StringType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new StringType(),
				new StringType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new ObjectType(ArrayAccess::class),
				new StringType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new UnionType([
					new ArrayType(new MixedType(), new MixedType()),
					new StringType(),
					new ObjectType(ArrayAccess::class),
				]),
				new StringType(),
				TrinaryLogic::createNo(),
			],
			[
				new MixedType(),
				new UnionType([
					new ArrayType(new MixedType(), new MixedType()),
					new StringType(),
					new ObjectType(ArrayAccess::class),
					new FloatType(),
				]),
				new StringType(),
				TrinaryLogic::createNo(),
			],
		];
	}

	/** @dataProvider dataSubtractedHasOffsetValueType */
	public function testSubtractedHasOffsetValueType(MixedType $mixedType, Type $typeToSubtract, Type $offsetType, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->hasOffsetValueType($offsetType);

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> hasOffsetValueType()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

}
