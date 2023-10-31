<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use DateTime;
use Exception;
use InvalidArgumentException;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use stdClass;
use Throwable;
use function sprintf;

class GenericClassStringTypeTest extends PHPStanTestCase
{

	public function dataIsSuperTypeOf(): array
	{
		$reflectionProvider = $this->createReflectionProvider();

		return [
			0 => [
				new GenericClassStringType(new ObjectType(Exception::class)),
				new ClassStringType(),
				TrinaryLogic::createMaybe(),
			],
			1 => [
				new GenericClassStringType(new ObjectType(Exception::class)),
				new StringType(),
				TrinaryLogic::createMaybe(),
			],
			2 => [
				new GenericClassStringType(new ObjectType(Exception::class)),
				new GenericClassStringType(new ObjectType(Exception::class)),
				TrinaryLogic::createYes(),
			],
			3 => [
				new GenericClassStringType(new ObjectType(Exception::class)),
				new GenericClassStringType(new ObjectType(Throwable::class)),
				TrinaryLogic::createMaybe(),
			],
			4 => [
				new GenericClassStringType(new ObjectType(Exception::class)),
				new GenericClassStringType(new ObjectType(InvalidArgumentException::class)),
				TrinaryLogic::createYes(),
			],
			5 => [
				new GenericClassStringType(new ObjectType(Exception::class)),
				new GenericClassStringType(new ObjectType(stdClass::class)),
				TrinaryLogic::createNo(),
			],
			6 => [
				new GenericClassStringType(new ObjectType(Exception::class)),
				new ConstantStringType(Exception::class),
				TrinaryLogic::createYes(),
			],
			7 => [
				new GenericClassStringType(new ObjectType(Throwable::class)),
				new ConstantStringType(Exception::class),
				TrinaryLogic::createYes(),
			],
			8 => [
				new GenericClassStringType(new ObjectType(InvalidArgumentException::class)),
				new ConstantStringType(Exception::class),
				TrinaryLogic::createMaybe(),
			],
			9 => [
				new GenericClassStringType(new ObjectType(stdClass::class)),
				new ConstantStringType(Exception::class),
				TrinaryLogic::createNo(),
			],
			10 => [
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					null,
					TemplateTypeVariance::createInvariant(),
				)),
				new ConstantStringType(Exception::class),
				TrinaryLogic::createYes(),
			],
			11 => [
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new ObjectType(Exception::class),
					TemplateTypeVariance::createInvariant(),
				)),
				new ConstantStringType(Exception::class),
				TrinaryLogic::createYes(),
			],
			12 => [
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new ObjectType(Exception::class),
					TemplateTypeVariance::createInvariant(),
				)),
				new ConstantStringType(stdClass::class),
				TrinaryLogic::createNo(),
			],
			13 => [
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new ObjectType(Exception::class),
					TemplateTypeVariance::createInvariant(),
				)),
				new ConstantStringType(InvalidArgumentException::class),
				TrinaryLogic::createYes(),
			],
			14 => [
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new ObjectType(Exception::class),
					TemplateTypeVariance::createInvariant(),
				)),
				new ConstantStringType(Throwable::class),
				TrinaryLogic::createMaybe(),
			],
			15 => [
				new GenericClassStringType(new StaticType($reflectionProvider->getClass(Exception::class))),
				new ConstantStringType(Exception::class),
				TrinaryLogic::createYes(),
			],
			16 => [
				new GenericClassStringType(new StaticType($reflectionProvider->getClass(InvalidArgumentException::class))),
				new ConstantStringType(Exception::class),
				TrinaryLogic::createMaybe(),
			],
			17 => [
				new GenericClassStringType(new StaticType($reflectionProvider->getClass(Throwable::class))),
				new ConstantStringType(Exception::class),
				TrinaryLogic::createYes(),
			],
			18 => [
				new GenericClassStringType(new ObjectType(Type::class, new UnionType([
					new ObjectType(ConstantIntegerType::class),
					new ObjectType(IntegerRangeType::class),
				]))),
				new ConstantStringType(IntegerType::class),
				TrinaryLogic::createMaybe(),
			],
			19 => [
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					null,
					TemplateTypeVariance::createInvariant(),
				)),
				new ConstantStringType('array'),
				TrinaryLogic::createNo(),
			],
		];
	}

	/**
	 * @dataProvider dataIsSuperTypeOf
	 */
	public function testIsSuperTypeOf(GenericClassStringType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	public function dataAccepts(): array
	{
		return [
			0 => [
				new GenericClassStringType(new ObjectType(Exception::class)),
				new ConstantStringType(Throwable::class),
				TrinaryLogic::createNo(),
			],
			1 => [
				new GenericClassStringType(new ObjectType(Exception::class)),
				new ConstantStringType(Exception::class),
				TrinaryLogic::createYes(),
			],
			2 => [
				new GenericClassStringType(new ObjectType(Exception::class)),
				new ConstantStringType(InvalidArgumentException::class),
				TrinaryLogic::createYes(),
			],
			3 => [
				new GenericClassStringType(new ObjectType(Exception::class)),
				new StringType(),
				TrinaryLogic::createMaybe(),
			],
			4 => [
				new GenericClassStringType(new ObjectType(Exception::class)),
				new ObjectType(Exception::class),
				TrinaryLogic::createNo(),
			],
			5 => [
				new GenericClassStringType(new ObjectType(Exception::class)),
				new GenericClassStringType(new ObjectType(Exception::class)),
				TrinaryLogic::createYes(),
			],
			6 => [
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					null,
					TemplateTypeVariance::createInvariant(),
				)),
				new ConstantStringType('NonexistentClass'),
				TrinaryLogic::createNo(),
			],
			7 => [
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithClass('Foo'),
					'T',
					null,
					TemplateTypeVariance::createInvariant(),
				)),
				new UnionType([
					new ConstantStringType(DateTime::class),
					new ConstantStringType(Exception::class),
				]),
				TrinaryLogic::createYes(),
			],
			8 => [
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithClass('Foo'),
					'T',
					new ObjectWithoutClassType(),
					TemplateTypeVariance::createInvariant(),
				)),
				new ClassStringType(),
				TrinaryLogic::createYes(),
			],
			9 => [
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithClass('Foo'),
					'T',
					new ObjectWithoutClassType(),
					TemplateTypeVariance::createInvariant(),
				)),
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithClass('Boo'),
					'U',
					new ObjectWithoutClassType(),
					TemplateTypeVariance::createInvariant(),
				)),
				TrinaryLogic::createMaybe(),
			],
			10 => [
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithClass('Foo'),
					'T',
					new ObjectWithoutClassType(),
					TemplateTypeVariance::createInvariant(),
				)),
				new UnionType([new IntegerType(), new StringType()]),
				TrinaryLogic::createMaybe(),
			],
			11 => [
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithClass('Foo'),
					'T',
					new ObjectWithoutClassType(),
					TemplateTypeVariance::createInvariant(),
				)),
				new BenevolentUnionType([new IntegerType(), new StringType()]),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	/**
	 * @dataProvider dataAccepts
	 */
	public function testAccepts(
		GenericClassStringType $acceptingType,
		Type $acceptedType,
		TrinaryLogic $expectedResult,
	): void
	{
		$actualResult = $acceptingType->accepts($acceptedType, true);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> accepts(%s)', $acceptingType->describe(VerbosityLevel::precise()), $acceptedType->describe(VerbosityLevel::precise())),
		);
	}

	public function dataEquals(): array
	{
		$reflectionProvider = $this->createReflectionProvider();

		return [
			[
				new GenericClassStringType(new ObjectType(Exception::class)),
				new GenericClassStringType(new ObjectType(Exception::class)),
				true,
			],
			[
				new GenericClassStringType(new ObjectType(Exception::class)),
				new GenericClassStringType(new ObjectType(stdClass::class)),
				false,
			],
			[
				new GenericClassStringType(new StaticType($reflectionProvider->getClass(Exception::class))),
				new GenericClassStringType(new StaticType($reflectionProvider->getClass(Exception::class))),
				true,
			],
			[
				new GenericClassStringType(new StaticType($reflectionProvider->getClass(Exception::class))),
				new GenericClassStringType(new StaticType($reflectionProvider->getClass(stdClass::class))),
				false,
			],
		];
	}

	/**
	 * @dataProvider dataEquals
	 */
	public function testEquals(GenericClassStringType $type, Type $otherType, bool $expected): void
	{
		$verbosityLevel = VerbosityLevel::precise();
		$typeDescription = $type->describe($verbosityLevel);
		$otherTypeDescription = $otherType->describe($verbosityLevel);

		$actual = $type->equals($otherType);
		$this->assertSame(
			$expected,
			$actual,
			sprintf('%s -> equals(%s)', $typeDescription, $otherTypeDescription),
		);

		$actual = $otherType->equals($type);
		$this->assertSame(
			$expected,
			$actual,
			sprintf('%s -> equals(%s)', $otherTypeDescription, $typeDescription),
		);
	}

}
