<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantIntegerType;

class MixedTypeTest extends \PHPStan\Testing\PHPStanTestCase
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
		];
	}

	/**
	 * @dataProvider dataIsSuperTypeOf
	 * @param \PHPStan\Type\MixedType $type
	 * @param Type $otherType
	 * @param TrinaryLogic $expectedResult
	 */
	public function testIsSuperTypeOf(MixedType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise()))
		);
	}

}
