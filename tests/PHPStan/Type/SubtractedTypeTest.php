<?php declare(strict_types = 1);

namespace PHPStan\Type;

use ClassWithProperty\ClassWithProperty;
use Generator;
use PHPStan\Testing\TestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantStringType;

class SubtractedTypeTest extends TestCase
{
	public function dataAccepts(): Generator
	{
		yield [
			new MixedType(),
			new NullType(),
			new NullType(),
			TrinaryLogic::createNo()
		];

		yield [
			new MixedType(),
			new NullType(),
			new StringType(),
			TrinaryLogic::createYes()
		];

		yield [
			new StringType(),
			new ConstantStringType('foo'),
			new ConstantStringType('bar'),
			TrinaryLogic::createYes()
		];

		yield [
			new StringType(),
			new ConstantStringType('foo'),
			new StringType(),
			TrinaryLogic::createYes()
		];
	}


	/**
	 * @dataProvider dataAccepts
	 */
	public function testAccepts(
		Type $original,
		Type $negative,
		Type $checkedType,
		TrinaryLogic $accepts
	): void {
		$subtractedType = SubtractedType::create(
			$original,
			$negative
		);
		// Sanity check so we only test the SubtractedType itself
		$this->assertInstanceOf(SubtractedType::class, $subtractedType);
		$this->assertTrue($subtractedType->accepts($checkedType, true)->equals($accepts));
	}

	public function dataCreate(): Generator
	{
		yield [
			new FloatType(),
			new ConstantFloatType(12.3),
			SubtractedType::class
		];

		yield [
			new ConstantFloatType(12.3),
			new FloatType(),
			NeverType::class
		];

		yield [
			new ConstantFloatType(12.3),
			new NullType(),
			ConstantFloatType::class
		];

		yield [
			new StringType(),
			new MixedType(),
			NeverType::class
		];
	}

	/**
	 * @dataProvider dataCreate
	 */
	public function testItCreates(Type $original, Type $subtracted, string $result): void
	{
		$this->assertInstanceOf($result, SubtractedType::create($original, $subtracted));
	}

	public function dataProperty(): Generator
	{
		yield [
			new MixedType(),
			new ObjectWithoutClassType(),
			'foo',
			TrinaryLogic::createNo()
		];

		yield [
			new MixedType(),
			new StringType(),
			'foo',
			TrinaryLogic::createYes()
		];

		yield [
			new ObjectWithoutClassType(),
			new ObjectType(ClassWithProperty::class),
			'prop',
			TrinaryLogic::createMaybe()
		];
	}

	/**
	 * @dataProvider dataProperty
	 */
	public function testItHasProperty(
		Type $original,
		Type $subtracted,
		string $property,
		TrinaryLogic $result
	): void {
		$subtractedType = SubtractedType::create(
			$original,
			$subtracted
		);
		$this->assertInstanceOf(SubtractedType::class, $subtractedType);
		$this->assertTrue($subtractedType->hasProperty($property)->equals($result));
	}
}
