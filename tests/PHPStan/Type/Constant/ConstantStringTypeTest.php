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
			0 => [
				new ConstantStringType(\Exception::class),
				new GenericClassStringType(new ObjectType(\Exception::class)),
				TrinaryLogic::createMaybe(),
			],
			1 => [
				new ConstantStringType(\Exception::class),
				new GenericClassStringType(new ObjectType(\Throwable::class)),
				TrinaryLogic::createMaybe(),
			],
			2 => [
				new ConstantStringType(\Exception::class),
				new GenericClassStringType(new ObjectType(\InvalidArgumentException::class)),
				TrinaryLogic::createNo(),
			],
			3 => [
				new ConstantStringType(\Exception::class),
				new GenericClassStringType(new ObjectType(\stdClass::class)),
				TrinaryLogic::createNo(),
			],
			4 => [
				new ConstantStringType(\Exception::class),
				new ConstantStringType(\Exception::class),
				TrinaryLogic::createYes(),
			],
			5 => [
				new ConstantStringType(\Exception::class),
				new ConstantStringType(\InvalidArgumentException::class),
				TrinaryLogic::createNo(),
			],
			6 => [
				new ConstantStringType(\Exception::class),
				new GenericClassStringType(new ObjectType(\Exception::class)),
				TrinaryLogic::createMaybe(),
			],
			7 => [
				new ConstantStringType(\Exception::class),
				new GenericClassStringType(new ObjectType(\stdClass::class)),
				TrinaryLogic::createNo(),
			],
			8 => [
				new ConstantStringType(\Exception::class),
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					null,
					TemplateTypeVariance::createInvariant()
				)),
				TrinaryLogic::createMaybe(),
			],
			9 => [
				new ConstantStringType(\Exception::class),
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new ObjectType(\Exception::class),
					TemplateTypeVariance::createInvariant()
				)),
				TrinaryLogic::createMaybe(),
			],
			10 => [
				new ConstantStringType(\InvalidArgumentException::class),
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new ObjectType(\Exception::class),
					TemplateTypeVariance::createInvariant()
				)),
				TrinaryLogic::createMaybe(),
			],
			11 => [
				new ConstantStringType(\Throwable::class),
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new ObjectType(\Exception::class),
					TemplateTypeVariance::createInvariant()
				)),
				TrinaryLogic::createNo(),
			],
			12 => [
				new ConstantStringType(\stdClass::class),
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new ObjectType(\Exception::class),
					TemplateTypeVariance::createInvariant()
				)),
				TrinaryLogic::createNo(),
			],
			13 => [
				new ConstantStringType(\Exception::class),
				new GenericClassStringType(new StaticType(\Exception::class)),
				TrinaryLogic::createMaybe(),
			],
			14 => [
				new ConstantStringType(\Exception::class),
				new GenericClassStringType(new StaticType(\InvalidArgumentException::class)),
				TrinaryLogic::createNo(),
			],
			15 => [
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
		$this->assertSame('string', (new ConstantStringType(\stdClass::class))->generalize()->describe(VerbosityLevel::precise()));
		$this->assertSame('class-string', (new ConstantStringType(\stdClass::class, true))->generalize()->describe(VerbosityLevel::precise()));
		$this->assertSame('class-string', (new ConstantStringType('NonexistentClass', true))->generalize()->describe(VerbosityLevel::precise()));
	}

	public function testTextInvalidEncoding(): void
	{
		$this->assertSame("'\xc3Lorem ipsum dolor s\u{2026}'", (new ConstantStringType("\xc3Lorem ipsum dolor sit"))->describe(VerbosityLevel::value()));
	}

	public function testShortTextInvalidEncoding(): void
	{
		$this->assertSame("'\xc3Lorem ipsum dolor'", (new ConstantStringType("\xc3Lorem ipsum dolor"))->describe(VerbosityLevel::value()));
	}

}
