<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
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
			'(int|non-empty-string)',
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

	public static function dataDescriptionBased(): iterable
	{
		$chooseScalarSubtype = static function (Type $left, Type $right, callable $traverse): Type {
			$scalar = new UnionType([new IntegerType(), new FloatType(), new StringType(), new BooleanType(), new NullType()]);
			if (!$scalar->isSuperTypeOf($left)->yes() || $left instanceof BenevolentUnionType) {
				return $traverse($left, $right);
			}
			if (!$scalar->isSuperTypeOf($right)->yes() || $right instanceof BenevolentUnionType) {
				return $traverse($left, $right);
			}
			if (!$left->isSuperTypeOf($right)->yes()) {
				return $traverse($left, $right);
			}

			return $right;
		};

		yield [
			'__benevolent<object|int>',
			'1|2|3',
			$chooseScalarSubtype,
			'(1|2|3|object)',
		];

		yield [
			'object|int',
			'1|2|3',
			$chooseScalarSubtype,
			'1|2|3|object',
		];

		yield [
			'Foo|non-empty-string',
			"'aaa'",
			$chooseScalarSubtype,
			"'aaa'|Foo",
		];

		yield [
			'list<string|null>',
			'array<string>',
			$chooseScalarSubtype,
			'list<string>',
		];

		yield [
			'list<string|null>',
			'list<string>',
			$chooseScalarSubtype,
			'list<string>',
		];
	}

	/**
	 * @param callable(Type $left, Type $right, callable(Type, Type): Type $traverse): Type $callback
	 */
	#[DataProvider('dataDescriptionBased')]
	public function testDescriptionBased(string $left, string $right, callable $callback, string $expectedTypeDescription): void
	{
		$typeStringResolver = self::getContainer()->getByType(TypeStringResolver::class);
		$leftType = $typeStringResolver->resolve($left);
		$rightType = $typeStringResolver->resolve($right);
		$actualType = SimultaneousTypeTraverser::map($leftType, $rightType, $callback);
		$this->assertSame($expectedTypeDescription, $actualType->describe(VerbosityLevel::precise()));
	}

}
