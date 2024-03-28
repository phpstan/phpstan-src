<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use Exception;
use InvalidArgumentException;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ErrorType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use stdClass;
use Throwable;
use function sprintf;

class ConstantStringTypeTest extends PHPStanTestCase
{

	public function dataIsSuperTypeOf(): array
	{
		$reflectionProvider = $this->createReflectionProvider();
		return [
			0 => [
				new ConstantStringType(Exception::class),
				new GenericClassStringType(new ObjectType(Exception::class)),
				TrinaryLogic::createMaybe(),
			],
			1 => [
				new ConstantStringType(Exception::class),
				new GenericClassStringType(new ObjectType(Throwable::class)),
				TrinaryLogic::createMaybe(),
			],
			2 => [
				new ConstantStringType(Exception::class),
				new GenericClassStringType(new ObjectType(InvalidArgumentException::class)),
				TrinaryLogic::createNo(),
			],
			3 => [
				new ConstantStringType(Exception::class),
				new GenericClassStringType(new ObjectType(stdClass::class)),
				TrinaryLogic::createNo(),
			],
			4 => [
				new ConstantStringType(Exception::class),
				new ConstantStringType(Exception::class),
				TrinaryLogic::createYes(),
			],
			5 => [
				new ConstantStringType(Exception::class),
				new ConstantStringType(InvalidArgumentException::class),
				TrinaryLogic::createNo(),
			],
			6 => [
				new ConstantStringType(Exception::class),
				new GenericClassStringType(new ObjectType(Exception::class)),
				TrinaryLogic::createMaybe(),
			],
			7 => [
				new ConstantStringType(Exception::class),
				new GenericClassStringType(new ObjectType(stdClass::class)),
				TrinaryLogic::createNo(),
			],
			8 => [
				new ConstantStringType(Exception::class),
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					null,
					TemplateTypeVariance::createInvariant(),
				)),
				TrinaryLogic::createMaybe(),
			],
			9 => [
				new ConstantStringType(Exception::class),
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new ObjectType(Exception::class),
					TemplateTypeVariance::createInvariant(),
				)),
				TrinaryLogic::createMaybe(),
			],
			10 => [
				new ConstantStringType(InvalidArgumentException::class),
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new ObjectType(Exception::class),
					TemplateTypeVariance::createInvariant(),
				)),
				TrinaryLogic::createMaybe(),
			],
			11 => [
				new ConstantStringType(Throwable::class),
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new ObjectType(Exception::class),
					TemplateTypeVariance::createInvariant(),
				)),
				TrinaryLogic::createNo(),
			],
			12 => [
				new ConstantStringType(stdClass::class),
				new GenericClassStringType(TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('foo'),
					'T',
					new ObjectType(Exception::class),
					TemplateTypeVariance::createInvariant(),
				)),
				TrinaryLogic::createNo(),
			],
			13 => [
				new ConstantStringType(Exception::class),
				new GenericClassStringType(new StaticType($reflectionProvider->getClass(Exception::class))),
				TrinaryLogic::createMaybe(),
			],
			14 => [
				new ConstantStringType(Exception::class),
				new GenericClassStringType(new StaticType($reflectionProvider->getClass(InvalidArgumentException::class))),
				TrinaryLogic::createNo(),
			],
			15 => [
				new ConstantStringType(Exception::class),
				new GenericClassStringType(new StaticType($reflectionProvider->getClass(Throwable::class))),
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
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	public function testGeneralize(): void
	{
		$this->assertSame('literal-string&non-falsy-string', (new ConstantStringType('NonexistentClass'))->generalize(GeneralizePrecision::moreSpecific())->describe(VerbosityLevel::precise()));
		$this->assertSame('literal-string', (new ConstantStringType(''))->generalize(GeneralizePrecision::moreSpecific())->describe(VerbosityLevel::precise()));
		$this->assertSame('literal-string&non-falsy-string', (new ConstantStringType('a'))->generalize(GeneralizePrecision::moreSpecific())->describe(VerbosityLevel::precise()));
		$this->assertSame('literal-string&non-empty-string&numeric-string', (new ConstantStringType('0'))->generalize(GeneralizePrecision::moreSpecific())->describe(VerbosityLevel::precise()));
		$this->assertSame('literal-string&non-falsy-string&numeric-string', (new ConstantStringType('1.123'))->generalize(GeneralizePrecision::moreSpecific())->describe(VerbosityLevel::precise()));
		$this->assertSame('string', (new ConstantStringType(''))->generalize(GeneralizePrecision::lessSpecific())->describe(VerbosityLevel::precise()));
		$this->assertSame('string', (new ConstantStringType('a'))->generalize(GeneralizePrecision::lessSpecific())->describe(VerbosityLevel::precise()));
		$this->assertSame('literal-string&non-falsy-string', (new ConstantStringType(stdClass::class))->generalize(GeneralizePrecision::moreSpecific())->describe(VerbosityLevel::precise()));
		$this->assertSame('class-string', (new ConstantStringType(stdClass::class, true))->generalize(GeneralizePrecision::moreSpecific())->describe(VerbosityLevel::precise()));
		$this->assertSame('class-string', (new ConstantStringType('NonexistentClass', true))->generalize(GeneralizePrecision::moreSpecific())->describe(VerbosityLevel::precise()));
	}

	public function testTextInvalidEncoding(): void
	{
		$this->assertSame("'\xc3Lorem ipsum dolor s\u{2026}'", (new ConstantStringType("\xc3Lorem ipsum dolor sit"))->describe(VerbosityLevel::value()));
	}

	public function testShortTextInvalidEncoding(): void
	{
		$this->assertSame("'\xc3Lorem ipsum dolor'", (new ConstantStringType("\xc3Lorem ipsum dolor"))->describe(VerbosityLevel::value()));
	}

	public function testSetInvalidValue(): void
	{
		$string = new ConstantStringType('internal:/node/add');
		$result = $string->setOffsetValueType(new ConstantIntegerType(0), new NullType());
		$this->assertInstanceOf(ErrorType::class, $result);
	}

}
