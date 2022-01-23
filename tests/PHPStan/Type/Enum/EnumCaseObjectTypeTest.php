<?php declare(strict_types = 1);

namespace PHPStan\Type\Enum;

use PHPStan\Fixture\FinalClass;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
use const PHP_VERSION_ID;

class EnumCaseObjectTypeTest extends PHPStanTestCase
{

	public function dataIsSuperTypeOf(): iterable
	{
		yield [
			new ObjectType('PHPStan\Fixture\TestEnum'),
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			TrinaryLogic::createYes(),
		];
		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			TrinaryLogic::createYes(),
		];

		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'TWO'),
			TrinaryLogic::createNo(),
		];
		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new ObjectType('PHPStan\Fixture\TestEnum'),
			TrinaryLogic::createMaybe(),
		];
		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new ObjectType('PHPStan\Fixture\TestEnumInterface'),
			TrinaryLogic::createMaybe(),
		];
		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new ObjectType('stdClass'),
			TrinaryLogic::createNo(),
		];
		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new ObjectType(FinalClass::class),
			TrinaryLogic::createNo(),
		];
		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new ObjectType('Stringable'),
			TrinaryLogic::createNo(),
		];
		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new ObjectWithoutClassType(),
			TrinaryLogic::createMaybe(),
		];
		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new ObjectWithoutClassType(new ObjectType('PHPStan\Fixture\TestEnum')),
			TrinaryLogic::createNo(),
		];
		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new ObjectWithoutClassType(new ObjectType('PHPStan\Fixture\TestEnumInterface')),
			TrinaryLogic::createNo(),
		];
		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new ObjectType('PHPStan\Fixture\TestEnumInterface', new ObjectType('PHPStan\Fixture\TestEnum')),
			TrinaryLogic::createNo(),
		];
		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new ObjectWithoutClassType(new ObjectType('PHPStan\Fixture\AnotherTestEnum')),
			TrinaryLogic::createMaybe(),
		];
		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new UnionType([
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'TWO'),
			]),
			TrinaryLogic::createMaybe(),
		];
		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new UnionType([
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'TWO'),
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'THREE'),
			]),
			TrinaryLogic::createNo(),
		];
	}

	/**
	 * @dataProvider dataIsSuperTypeOf
	 */
	public function testIsSuperTypeOf(Type $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}
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
			new ObjectType('PHPStan\Fixture\TestEnum'),
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			TrinaryLogic::createYes(),
		];
		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			TrinaryLogic::createYes(),
		];

		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'TWO'),
			TrinaryLogic::createNo(),
		];
		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new ObjectType('PHPStan\Fixture\TestEnum'),
			TrinaryLogic::createMaybe(),
		];
		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new ObjectType('PHPStan\Fixture\TestEnumInterface'),
			TrinaryLogic::createMaybe(),
		];
		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new ObjectType('stdClass'),
			TrinaryLogic::createNo(),
		];
		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new ObjectType(FinalClass::class),
			TrinaryLogic::createNo(),
		];
		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new ObjectType('Stringable'),
			TrinaryLogic::createNo(),
		];
		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new ObjectWithoutClassType(),
			TrinaryLogic::createMaybe(),
		];
		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new ObjectWithoutClassType(new ObjectType('PHPStan\Fixture\TestEnum')),
			TrinaryLogic::createNo(),
		];
		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new ObjectWithoutClassType(new ObjectType('PHPStan\Fixture\TestEnumInterface')),
			TrinaryLogic::createNo(),
		];
		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new ObjectType('PHPStan\Fixture\TestEnumInterface', new ObjectType('PHPStan\Fixture\TestEnum')),
			TrinaryLogic::createNo(),
		];
		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new ObjectWithoutClassType(new ObjectType('PHPStan\Fixture\AnotherTestEnum')),
			TrinaryLogic::createMaybe(),
		];
		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new UnionType([
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'TWO'),
			]),
			TrinaryLogic::createMaybe(),
		];
		yield [
			new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'ONE'),
			new UnionType([
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'TWO'),
				new EnumCaseObjectType('PHPStan\Fixture\TestEnum', 'THREE'),
			]),
			TrinaryLogic::createNo(),
		];
	}

	/**
	 * @dataProvider dataAccepts
	 */
	public function testAccepts(
		Type $type,
		Type $acceptedType,
		TrinaryLogic $expectedResult,
	): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->assertSame(
			$expectedResult->describe(),
			$type->accepts($acceptedType, true)->describe(),
			sprintf('%s -> accepts(%s)', $type->describe(VerbosityLevel::precise()), $acceptedType->describe(VerbosityLevel::precise())),
		);
	}

}
