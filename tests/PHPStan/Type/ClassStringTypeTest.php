<?php declare(strict_types = 1);

namespace PHPStan\Type;

use Exception;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use function sprintf;

class ClassStringTypeTest extends PHPStanTestCase
{

	public static function dataIsSuperTypeOf(): array
	{
		return [
			[
				new ClassStringType(),
				new GenericClassStringType(new ObjectType(Exception::class)),
				TrinaryLogic::createYes(),
			],
			[
				new ClassStringType(),
				new StringType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new ClassStringType(),
				new ConstantStringType(stdClass::class),
				TrinaryLogic::createYes(),
			],
			[
				new ClassStringType(),
				new ConstantStringType('Nonexistent'),
				TrinaryLogic::createNo(),
			],
		];
	}

	#[DataProvider('dataIsSuperTypeOf')]
	public function testIsSuperTypeOf(ClassStringType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	public static function dataAccepts(): iterable
	{
		yield [
			new ClassStringType(),
			new ClassStringType(),
			TrinaryLogic::createYes(),
		];

		yield [
			new ClassStringType(),
			new StringType(),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new ClassStringType(),
			new IntegerType(),
			TrinaryLogic::createNo(),
		];

		yield [
			new ClassStringType(),
			new ConstantStringType(stdClass::class),
			TrinaryLogic::createYes(),
		];

		yield [
			new ClassStringType(),
			new ConstantStringType('NonexistentClass'),
			TrinaryLogic::createNo(),
		];

		yield [
			new ClassStringType(),
			new UnionType([new ConstantStringType(stdClass::class), new ConstantStringType(self::class)]),
			TrinaryLogic::createYes(),
		];

		yield [
			new ClassStringType(),
			new UnionType([new ConstantStringType(stdClass::class), new ConstantStringType('Nonexistent')]),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new ClassStringType(),
			new UnionType([new ConstantStringType('Nonexistent'), new ConstantStringType('Nonexistent2')]),
			TrinaryLogic::createNo(),
		];
	}

	#[DataProvider('dataAccepts')]
	public function testAccepts(ClassStringType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->accepts($otherType, true)->result;
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> accepts(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	public static function dataEquals(): array
	{
		return [
			[
				new ClassStringType(),
				new ClassStringType(),
				true,
			],
			[
				new ClassStringType(),
				new StringType(),
				false,
			],
		];
	}

	#[DataProvider('dataEquals')]
	public function testEquals(ClassStringType $type, Type $otherType, bool $expectedResult): void
	{
		$actualResult = $type->equals($otherType);
		$this->assertSame(
			$expectedResult,
			$actualResult,
			sprintf('%s->equals(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

}
