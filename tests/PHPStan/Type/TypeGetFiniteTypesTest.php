<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;

class TypeGetFiniteTypesTest extends PHPStanTestCase
{

	public function dataGetFiniteTypes(): iterable
	{
		yield [
			IntegerRangeType::fromInterval(0, 5),
			[
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
				new ConstantIntegerType(2),
				new ConstantIntegerType(3),
				new ConstantIntegerType(4),
				new ConstantIntegerType(5),
			],
		];

		yield [
			IntegerRangeType::fromInterval(0, 300),
			[],
		];

		yield [
			new UnionType([
				new BooleanType(),
				IntegerRangeType::fromInterval(0, 2),
			]),
			[
				new ConstantBooleanType(true),
				new ConstantBooleanType(false),
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
				new ConstantIntegerType(2),
			],
		];

		yield [
			new UnionType([
				new BooleanType(),
				new IntegerType(),
			]),
			[],
		];

		yield [
			new UnionType([
				new BooleanType(),
				new BooleanType(),
			]),
			[
				new ConstantBooleanType(true),
				new ConstantBooleanType(false),
			],
		];

		yield [
			new IntersectionType([
				new BooleanType(),
				TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new BooleanType(),
					TemplateTypeVariance::createInvariant(),
				),
			]),
			[
				new ConstantBooleanType(true),
				new ConstantBooleanType(false),
			],
		];

		yield [
			new ConstantArrayType([
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
			], [
				new BooleanType(),
				new BooleanType(),
			], 2),
			[
				new ConstantArrayType([
					new ConstantIntegerType(0),
					new ConstantIntegerType(1),
				], [
					new ConstantBooleanType(true),
					new ConstantBooleanType(true),
				], 2, [], true),
				new ConstantArrayType([
					new ConstantIntegerType(0),
					new ConstantIntegerType(1),
				], [
					new ConstantBooleanType(true),
					new ConstantBooleanType(false),
				], 2, [], true),
				new ConstantArrayType([
					new ConstantIntegerType(0),
					new ConstantIntegerType(1),
				], [
					new ConstantBooleanType(false),
					new ConstantBooleanType(true),
				], 2, [], true),
				new ConstantArrayType([
					new ConstantIntegerType(0),
					new ConstantIntegerType(1),
				], [
					new ConstantBooleanType(false),
					new ConstantBooleanType(false),
				], 2, [], true),
			],
		];
	}

	/**
	 * @dataProvider dataGetFiniteTypes
	 * @param list<Type> $expectedTypes
	 */
	public function testGetFiniteTypes(
		Type $type,
		array $expectedTypes,
	): void
	{
		$this->assertEquals($expectedTypes, $type->getFiniteTypes());
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../../../conf/bleedingEdge.neon',
		];
	}

}
