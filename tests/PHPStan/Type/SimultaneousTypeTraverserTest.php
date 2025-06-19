<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPUnit\Framework\Attributes\DataProvider;

class SimultaneousTypeTraverserTest extends PHPStanTestCase
{

	public static function dataChangeStringIntoNonEmptyString(): iterable
	{
		yield [
			new StringType(),
			new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()]),
			'non-empty-string',
		];
		yield [
			new ArrayType(new MixedType(), new StringType()),
			new ConstantArrayType(
				[new ConstantIntegerType(0)],
				[new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()])],
				[1],
			),
			'array<non-empty-string>',
		];
		yield [
			new ArrayType(new MixedType(), new IntegerType()),
			new ConstantArrayType(
				[new ConstantIntegerType(0)],
				[new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()])],
				[1],
			),
			'array<int>',
		];
		yield [
			new ArrayType(new MixedType(), new StringType()),
			new ConstantArrayType(
				[new ConstantIntegerType(0)],
				[new IntegerType()],
				[1],
			),
			'array<string>',
		];

		yield [
			new BenevolentUnionType([
				new StringType(),
				new IntegerType(),
			]),
			new UnionType([
				new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()]),
				new IntegerType(),
				new FloatType(),
			]),
			'(int|string)',
		];

		yield [
			new BenevolentUnionType([
				new StringType(),
				new IntegerType(),
			]),
			new UnionType([
				new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()]),
				new IntegerType(),
			]),
			'(int|non-empty-string)',
		];
	}

	#[DataProvider('dataChangeStringIntoNonEmptyString')]
	public function testChangeIntegerIntoString(Type $left, Type $right, string $expectedTypeDescription): void
	{
		$cb = static function (Type $left, Type $right, callable $traverse): Type {
			if (!$left->isString()->yes()) {
				return $traverse($left, $right);
			}
			if (!$right->isNonEmptyString()->yes()) {
				return $traverse($left, $right);
			}
			return $right;
		};
		$actualType = SimultaneousTypeTraverser::map($left, $right, $cb);
		$this->assertSame($expectedTypeDescription, $actualType->describe(VerbosityLevel::precise()));
	}

}
