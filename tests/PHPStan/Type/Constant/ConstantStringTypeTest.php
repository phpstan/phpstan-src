<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PHPStan\Testing\TestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

class ConstantStringTypeTest extends TestCase
{

	public function dataIsSuperTypeOf(): array
	{
		return [
			[
				new ConstantStringType(\Exception::class),
				new GenericClassStringType(new ObjectType(\Exception::class)),
				TrinaryLogic::createMaybe(),
			],
			[
				new ConstantStringType(\Exception::class),
				new GenericClassStringType(new ObjectType(\Throwable::class)),
				TrinaryLogic::createMaybe(),
			],
			[
				new ConstantStringType(\Exception::class),
				new GenericClassStringType(new ObjectType(\InvalidArgumentException::class)),
				TrinaryLogic::createNo(),
			],
			[
				new ConstantStringType(\Exception::class),
				new GenericClassStringType(new ObjectType(\stdClass::class)),
				TrinaryLogic::createNo(),
			],
			[
				new ConstantStringType(\Exception::class),
				new ConstantStringType(\Exception::class),
				TrinaryLogic::createYes(),
			],
			[
				new ConstantStringType(\Exception::class),
				new ConstantStringType(\InvalidArgumentException::class),
				TrinaryLogic::createNo(),
			],
			[
				new ConstantStringType(\Exception::class),
				new GenericClassStringType(new ObjectType(\Exception::class)),
				TrinaryLogic::createMaybe(),
			],
			[
				new ConstantStringType(\Exception::class),
				new GenericClassStringType(new ObjectType(\stdClass::class)),
				TrinaryLogic::createNo(),
			],
			[
				new ConstantStringType(\Exception::class),
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					null,
					TemplateTypeVariance::createInvariant()
				)),
				TrinaryLogic::createMaybe(),
			],
			[
				new ConstantStringType(\Exception::class),
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new ObjectType(\Exception::class),
					TemplateTypeVariance::createInvariant()
				)),
				TrinaryLogic::createMaybe(),
			],
			[
				new ConstantStringType(\InvalidArgumentException::class),
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new ObjectType(\Exception::class),
					TemplateTypeVariance::createInvariant()
				)),
				TrinaryLogic::createMaybe(),
			],
			[
				new ConstantStringType(\Throwable::class),
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new ObjectType(\Exception::class),
					TemplateTypeVariance::createInvariant()
				)),
				TrinaryLogic::createMaybe(), // should be no
			],
			[
				new ConstantStringType(\stdClass::class),
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new ObjectType(\Exception::class),
					TemplateTypeVariance::createInvariant()
				)),
				TrinaryLogic::createNo(),
			],
			[
				new ConstantStringType(\Exception::class),
				new GenericClassStringType(new StaticType(\Exception::class)),
				TrinaryLogic::createMaybe(),
			],
			[
				new ConstantStringType(\Exception::class),
				new GenericClassStringType(new StaticType(\InvalidArgumentException::class)),
				TrinaryLogic::createNo(),
			],
			[
				new ConstantStringType(\Exception::class),
				new GenericClassStringType(new StaticType(\Throwable::class)),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	/**
	 * @dataProvider dataIsSuperTypeOf
	 */
	public function testIsSuperTypeOf(ConstantStringType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise()))
		);
	}

	public function testGeneralize(): void
	{
		$this->assertSame('string', (new ConstantStringType('NonexistentClass'))->generalize()->describe(VerbosityLevel::precise()));
		$this->assertSame('class-string', (new ConstantStringType(\stdClass::class))->generalize()->describe(VerbosityLevel::precise()));
	}

}
