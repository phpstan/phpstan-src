<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPUnit\Framework\Attributes\DataProvider;
use function sprintf;

class ObjectShapeTypeTest extends PHPStanTestCase
{

	public static function dataIsSuperTypeOf(): iterable
	{
		// Same properties, same types
		yield [
			new ObjectShapeType(['foo' => new IntegerType()], []),
			new ObjectShapeType(['foo' => new IntegerType()], []),
			TrinaryLogic::createYes(),
		];

		// Wider property type is supertype
		yield [
			new ObjectShapeType(['foo' => new IntegerType()], []),
			new ObjectShapeType(['foo' => new ConstantIntegerType(1)], []),
			TrinaryLogic::createYes(),
		];

		// Narrower property type is maybe supertype
		yield [
			new ObjectShapeType(['foo' => new ConstantIntegerType(1)], []),
			new ObjectShapeType(['foo' => new IntegerType()], []),
			TrinaryLogic::createMaybe(),
		];

		// Incompatible property types
		yield [
			new ObjectShapeType(['foo' => new IntegerType()], []),
			new ObjectShapeType(['foo' => new StringType()], []),
			TrinaryLogic::createNo(),
		];

		// Disjoint properties - object shapes are open types
		yield [
			new ObjectShapeType(['foo' => new IntegerType()], []),
			new ObjectShapeType(['bar' => new StringType()], []),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new ObjectShapeType(['bar' => new StringType()], []),
			new ObjectShapeType(['foo' => new IntegerType()], []),
			TrinaryLogic::createMaybe(),
		];

		// Required vs optional: optional is supertype of required
		yield [
			new ObjectShapeType(['foo' => new IntegerType()], ['foo']),
			new ObjectShapeType(['foo' => new IntegerType()], []),
			TrinaryLogic::createYes(),
		];

		// Required vs optional: required is maybe supertype of optional
		yield [
			new ObjectShapeType(['foo' => new IntegerType()], []),
			new ObjectShapeType(['foo' => new IntegerType()], ['foo']),
			TrinaryLogic::createMaybe(),
		];

		// Wider type with required property
		yield [
			new ObjectShapeType(['foo' => TypeCombinator::union(new IntegerType(), new NullType())], []),
			new ObjectShapeType(['foo' => new IntegerType()], []),
			TrinaryLogic::createYes(),
		];

		// Narrower type checking wider
		yield [
			new ObjectShapeType(['foo' => new IntegerType()], []),
			new ObjectShapeType(['foo' => TypeCombinator::union(new IntegerType(), new NullType())], []),
			TrinaryLogic::createMaybe(),
		];

		// Optional wider type vs required narrower
		yield [
			new ObjectShapeType(['foo' => TypeCombinator::union(new IntegerType(), new NullType())], ['foo']),
			new ObjectShapeType(['foo' => new IntegerType()], []),
			TrinaryLogic::createYes(),
		];

		// Required narrower vs optional wider
		yield [
			new ObjectShapeType(['foo' => new IntegerType()], []),
			new ObjectShapeType(['foo' => TypeCombinator::union(new IntegerType(), new NullType())], ['foo']),
			TrinaryLogic::createMaybe(),
		];

		// Disjoint with optional property
		yield [
			new ObjectShapeType(['foo' => new IntegerType()], []),
			new ObjectShapeType(['bar' => new IntegerType()], ['bar']),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new ObjectShapeType(['bar' => new IntegerType()], ['bar']),
			new ObjectShapeType(['foo' => new IntegerType()], []),
			TrinaryLogic::createMaybe(),
		];

		// Optional property with incompatible types
		yield [
			new ObjectShapeType(['foo' => new IntegerType()], []),
			new ObjectShapeType(['foo' => new StringType()], ['foo']),
			TrinaryLogic::createMaybe(),
		];

		// Superset has extra required property - maybe because shapes are open
		yield [
			new ObjectShapeType(['foo' => new IntegerType(), 'bar' => new StringType()], []),
			new ObjectShapeType(['foo' => new IntegerType()], []),
			TrinaryLogic::createMaybe(),
		];

		// Subset is supertype
		yield [
			new ObjectShapeType(['foo' => new IntegerType()], []),
			new ObjectShapeType(['foo' => new IntegerType(), 'bar' => new StringType()], []),
			TrinaryLogic::createYes(),
		];

		// Empty shape is supertype of any shape
		yield [
			new ObjectShapeType([], []),
			new ObjectShapeType(['foo' => new IntegerType()], []),
			TrinaryLogic::createYes(),
		];

		// Any shape is maybe supertype of empty shape
		yield [
			new ObjectShapeType(['foo' => new IntegerType()], []),
			new ObjectShapeType([], []),
			TrinaryLogic::createMaybe(),
		];
	}

	#[DataProvider('dataIsSuperTypeOf')]
	public function testIsSuperTypeOf(ObjectShapeType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

}
