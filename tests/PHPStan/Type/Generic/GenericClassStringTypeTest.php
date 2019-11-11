<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\TrinaryLogic;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

class GenericClassStringTypeTest extends \PHPStan\Testing\TestCase
{

	public function dataIsSuperTypeOf(): array
	{
		return [
			[
				new GenericClassStringType(new ObjectType(\Exception::class)),
				new ClassStringType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new GenericClassStringType(new ObjectType(\Exception::class)),
				new StringType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new GenericClassStringType(new ObjectType(\Exception::class)),
				new GenericClassStringType(new ObjectType(\Exception::class)),
				TrinaryLogic::createYes(),
			],
			[
				new GenericClassStringType(new ObjectType(\Exception::class)),
				new GenericClassStringType(new ObjectType(\Throwable::class)),
				TrinaryLogic::createMaybe(),
			],
			[
				new GenericClassStringType(new ObjectType(\Exception::class)),
				new GenericClassStringType(new ObjectType(\InvalidArgumentException::class)),
				TrinaryLogic::createYes(),
			],
			[
				new GenericClassStringType(new ObjectType(\Exception::class)),
				new GenericClassStringType(new ObjectType(\stdClass::class)),
				TrinaryLogic::createNo(),
			],
			[
				new GenericClassStringType(new ObjectType(\Exception::class)),
				new ConstantStringType(\Exception::class),
				TrinaryLogic::createYes(),
			],
			[
				new GenericClassStringType(new ObjectType(\Throwable::class)),
				new ConstantStringType(\Exception::class),
				TrinaryLogic::createYes(),
			],
			[
				new GenericClassStringType(new ObjectType(\InvalidArgumentException::class)),
				new ConstantStringType(\Exception::class),
				TrinaryLogic::createNo(),
			],
			[
				new GenericClassStringType(new ObjectType(\Exception::class)),
				new ConstantStringType(\Exception::class),
				TrinaryLogic::createYes(),
			],
			[
				new GenericClassStringType(new ObjectType(\stdClass::class)),
				new ConstantStringType(\Exception::class),
				TrinaryLogic::createNo(),
			],
			[
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					null,
					TemplateTypeVariance::createInvariant()
				)),
				new ConstantStringType(\Exception::class),
				TrinaryLogic::createYes(),
			],
			[
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new ObjectType(\Exception::class),
					TemplateTypeVariance::createInvariant()
				)),
				new ConstantStringType(\Exception::class),
				TrinaryLogic::createYes(),
			],
			[
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new ObjectType(\Exception::class),
					TemplateTypeVariance::createInvariant()
				)),
				new ConstantStringType(\stdClass::class),
				TrinaryLogic::createNo(),
			],
			[
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new ObjectType(\Exception::class),
					TemplateTypeVariance::createInvariant()
				)),
				new ConstantStringType(\InvalidArgumentException::class),
				TrinaryLogic::createYes(),
			],
			[
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new ObjectType(\Exception::class),
					TemplateTypeVariance::createInvariant()
				)),
				new ConstantStringType(\Throwable::class),
				TrinaryLogic::createYes(), // should be no
			],
			[
				new GenericClassStringType(new StaticType(\Exception::class)),
				new ConstantStringType(\Exception::class),
				TrinaryLogic::createYes(),
			],
			[
				new GenericClassStringType(new StaticType(\InvalidArgumentException::class)),
				new ConstantStringType(\Exception::class),
				TrinaryLogic::createNo(),
			],
			[
				new GenericClassStringType(new StaticType(\Throwable::class)),
				new ConstantStringType(\Exception::class),
				TrinaryLogic::createYes(),
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
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise()))
		);
	}

	public function dataAccepts(): array
	{
		return [
			[
				new GenericClassStringType(new ObjectType(\Exception::class)),
				new ConstantStringType(\Throwable::class),
				TrinaryLogic::createNo(),
			],
			[
				new GenericClassStringType(new ObjectType(\Exception::class)),
				new ConstantStringType(\Exception::class),
				TrinaryLogic::createYes(),
			],
			[
				new GenericClassStringType(new ObjectType(\Exception::class)),
				new ConstantStringType(\InvalidArgumentException::class),
				TrinaryLogic::createYes(),
			],
			[
				new GenericClassStringType(new ObjectType(\Exception::class)),
				new StringType(),
				TrinaryLogic::createNo(),
			],
			[
				new GenericClassStringType(new ObjectType(\Exception::class)),
				new ObjectType(\Exception::class),
				TrinaryLogic::createNo(),
			],
			[
				new GenericClassStringType(new ObjectType(\Exception::class)),
				new GenericClassStringType(new ObjectType(\Exception::class)),
				TrinaryLogic::createYes(),
			],
			[
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					null,
					TemplateTypeVariance::createInvariant()
				)),
				new ConstantStringType('NonexistentClass'),
				TrinaryLogic::createNo(),
			],
		];
	}

	/**
	 * @dataProvider dataAccepts
	 */
	public function testAccepts(
		GenericClassStringType $acceptingType,
		Type $acceptedType,
		TrinaryLogic $expectedResult
	): void
	{
		$actualResult = $acceptingType->accepts($acceptedType, true);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> accepts(%s)', $acceptingType->describe(VerbosityLevel::precise()), $acceptedType->describe(VerbosityLevel::precise()))
		);
	}

}
