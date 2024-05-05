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

	public function dataSubstractedIsArray(): array
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
	 * @dataProvider dataSubstractedIsArray
	 */
	public function testSubstractedIsArray(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isArray();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isArray()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubstractedIsConstantArray(): array
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
	 * @dataProvider dataSubstractedIsConstantArray
	 */
	public function testSubstractedIsConstantArray(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isConstantArray();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isConstantArray()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubstractedIsString(): array
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
	 * @dataProvider dataSubstractedIsString
	 */
	public function testSubstractedIsString(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isString();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isString()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubstractedIsNumericString(): array
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
	 * @dataProvider dataSubstractedIsNumericString
	 */
	public function testSubstractedIsNumericString(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isNumericString();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isNumericString()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubstractedIsNonEmptyString(): array
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
	 * @dataProvider dataSubstractedIsNonEmptyString
	 */
	public function testSubstractedIsNonEmptyString(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isNonEmptyString();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isNonEmptyString()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubstractedIsNonFalsyString(): array
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
	 * @dataProvider dataSubstractedIsNonFalsyString
	 */
	public function testSubstractedIsNonFalsyString(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isNonFalsyString();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isNonFalsyString()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubstractedIsLiteralString(): array
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
	 * @dataProvider dataSubstractedIsClassString
	 */
	public function testSubstractedIsClassString(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isClassStringType();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isClassStringType()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubstractedIsClassString(): array
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
	 * @dataProvider dataSubstractedIsLiteralString
	 */
	public function testSubstractedIsLiteralString(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isLiteralString();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isLiteralString()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubstractedIsIterable(): array
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
	 * @dataProvider dataSubstractedIsBoolean
	 */
	public function testSubstractedIsBoolean(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isBoolean();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isBoolean()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubstractedIsBoolean(): array
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
	 * @dataProvider dataSubstractedIsFalse
	 */
	public function testSubstractedIsFalse(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isFalse();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isFalse()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubstractedIsFalse(): array
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
	 * @dataProvider dataSubstractedIsNull
	 */
	public function testSubstractedIsNull(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isNull();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isNull()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubstractedIsNull(): array
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
	 * @dataProvider dataSubstractedIsTrue
	 */
	public function testSubstractedIsTrue(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isTrue();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isTrue()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubstractedIsTrue(): array
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
	 * @dataProvider dataSubstractedIsFloat
	 */
	public function testSubstractedIsFloat(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isFloat();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isFloat()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubstractedIsFloat(): array
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
	 * @dataProvider dataSubstractedIsInteger
	 */
	public function testSubstractedIsInteger(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isInteger();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isInteger()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubstractedIsInteger(): array
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
	 * @dataProvider dataSubstractedIsIterable
	 */
	public function testSubstractedIsIterable(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isIterable();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isIterable()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubstractedIsOffsetAccessible(): array
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
	 * @dataProvider dataSubstractedIsOffsetAccessible
	 */
	public function testSubstractedIsOffsetAccessible(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isOffsetAccessible();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isOffsetAccessible()', $subtracted->describe(VerbosityLevel::precise())),
		);
	}

	public function dataSubstractedIsOffsetLegal(): array
	{
		return [
			[
				new MixedType(),
				new ArrayType(new MixedType(), new MixedType()),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new IntersectionType([
					new ObjectWithoutClassType(),
					new ObjectType(ArrayAccess::class),
				]),
				TrinaryLogic::createMaybe(),
			],
			[
				new MixedType(),
				new ObjectWithoutClassType(),
				TrinaryLogic::createYes(),
			],
			[
				new MixedType(),
				new UnionType([
					new ObjectWithoutClassType(),
					new StringType(),
				]),
				TrinaryLogic::createYes(),
			],
		];
	}

	/**
	 * @dataProvider dataSubstractedIsOffsetLegal
	 */
	public function testSubstractedIsOffsetLegal(MixedType $mixedType, Type $typeToSubtract, TrinaryLogic $expectedResult): void
	{
		$subtracted = $mixedType->subtract($typeToSubtract);
		$actualResult = $subtracted->isOffsetAccessLegal();

		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isOffsetAccessLegal()', $subtracted->describe(VerbosityLevel::precise())),
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
