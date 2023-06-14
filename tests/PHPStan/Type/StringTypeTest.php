<?php declare(strict_types = 1);

namespace PHPStan\Type;

use Exception;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use stdClass;
use Test\ClassWithToString;
use function sprintf;

class StringTypeTest extends PHPStanTestCase
{

	public function dataIsSuperTypeOf(): array
	{
		return [
			[
				new StringType(),
				new GenericClassStringType(new ObjectType(Exception::class)),
				TrinaryLogic::createYes(),
			],
			[
				new StringType(),
				TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new StringType(),
					TemplateTypeVariance::createInvariant(),
				),
				TrinaryLogic::createYes(),
			],
			[
				TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new StringType(),
					TemplateTypeVariance::createInvariant(),
				),
				new StringType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new ClassStringType(),
				TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new StringType(),
					TemplateTypeVariance::createInvariant(),
				),
				TrinaryLogic::createMaybe(),
			],
			[
				TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new StringType(),
					TemplateTypeVariance::createInvariant(),
				),
				new ClassStringType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new GenericClassStringType(new ObjectType(stdClass::class)),
				TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new StringType(),
					TemplateTypeVariance::createInvariant(),
				),
				TrinaryLogic::createMaybe(),
			],
			[
				TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new StringType(),
					TemplateTypeVariance::createInvariant(),
				),
				new GenericClassStringType(new ObjectType(stdClass::class)),
				TrinaryLogic::createMaybe(),
			],
			[
				new StringAlwaysAcceptingObjectWithToStringType(),
				new ObjectType(ClassWithToString::class),
				TrinaryLogic::createYes(),
			],
		];
	}

	/**
	 * @dataProvider dataIsSuperTypeOf
	 */
	public function testIsSuperTypeOf(StringType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	public function dataAccepts(): iterable
	{
		yield [
			new StringType(),
			new IntersectionType([
				new ObjectType(ClassWithToString::class),
				new HasPropertyType('foo'),
			]),
			TrinaryLogic::createNo(),
		];

		yield [
			new StringType(),
			new ClassStringType(),
			TrinaryLogic::createYes(),
		];

		yield [
			new StringType(),
			TemplateTypeFactory::create(
				TemplateTypeScope::createWithFunction('foo'),
				'T',
				new StringType(),
				TemplateTypeVariance::createInvariant(),
			),
			TrinaryLogic::createYes(),
		];

		yield [
			TemplateTypeFactory::create(
				TemplateTypeScope::createWithFunction('foo'),
				'T',
				new StringType(),
				TemplateTypeVariance::createInvariant(),
			),
			new StringType(),
			TrinaryLogic::createYes(),
		];

		yield [
			TemplateTypeFactory::create(
				TemplateTypeScope::createWithFunction('foo'),
				'T',
				new StringType(),
				TemplateTypeVariance::createInvariant(),
			)->toArgument(),
			new StringType(),
			TrinaryLogic::createMaybe(),
		];

		yield [
			TemplateTypeFactory::create(
				TemplateTypeScope::createWithFunction('foo'),
				'T',
				new StringType(),
				TemplateTypeVariance::createInvariant(),
			)->toArgument(),
			TemplateTypeFactory::create(
				TemplateTypeScope::createWithFunction('foo'),
				'T',
				new StringType(),
				TemplateTypeVariance::createInvariant(),
			)->toArgument(),
			TrinaryLogic::createYes(),
		];
	}

	/**
	 * @dataProvider dataAccepts
	 */
	public function testAccepts(StringType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->accepts($otherType, true);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> accepts(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	public function dataEquals(): array
	{
		return [
			[
				new StringType(),
				new StringType(),
				true,
			],
			[
				new ConstantStringType('foo'),
				new ConstantStringType('foo'),
				true,
			],
			[
				new ConstantStringType('foo'),
				new ConstantStringType('bar'),
				false,
			],
			[
				new StringType(),
				new ConstantStringType(''),
				false,
			],
			[
				new ConstantStringType(''),
				new StringType(),
				false,
			],
			[
				new StringType(),
				new ClassStringType(),
				false,
			],
		];
	}

	/**
	 * @dataProvider dataEquals
	 */
	public function testEquals(StringType $type, Type $otherType, bool $expectedResult): void
	{
		$actualResult = $type->equals($otherType);
		$this->assertSame(
			$expectedResult,
			$actualResult,
			sprintf('%s->equals(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

}
