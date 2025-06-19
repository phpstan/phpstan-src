<?php declare(strict_types = 1);

namespace PHPStan\Type;

use InvalidArgumentException;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPUnit\Framework\Attributes\DataProvider;
use function sprintf;

class ObjectWithoutClassTypeTest extends PHPStanTestCase
{

	public static function dataIsSuperTypeOf(): array
	{
		return [
			[
				new ObjectWithoutClassType(),
				new ObjectWithoutClassType(),
				TrinaryLogic::createYes(),
			],
			[
				new ObjectWithoutClassType(),
				new ObjectType('Exception'),
				TrinaryLogic::createYes(),
			],
			[
				new ObjectWithoutClassType(new ObjectType('Exception')),
				new ObjectType('Exception'),
				TrinaryLogic::createNo(),
			],
			[
				new ObjectWithoutClassType(new ObjectType(InvalidArgumentException::class)),
				new ObjectType('Exception'),
				TrinaryLogic::createMaybe(),
			],
			[
				new ObjectWithoutClassType(new ObjectType('Exception')),
				new ObjectType(InvalidArgumentException::class),
				TrinaryLogic::createNo(),
			],
			[
				new ObjectWithoutClassType(),
				new ObjectWithoutClassType(new ObjectType('Exception')),
				TrinaryLogic::createYes(),
			],
			[
				new ObjectWithoutClassType(new ObjectType('Exception')),
				new ObjectWithoutClassType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new ObjectWithoutClassType(new ObjectType(InvalidArgumentException::class)),
				new ObjectWithoutClassType(new ObjectType('Exception')),
				TrinaryLogic::createYes(),
			],
			[
				new ObjectWithoutClassType(new ObjectType('Exception')),
				new ObjectWithoutClassType(new ObjectType(InvalidArgumentException::class)),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	#[DataProvider('dataIsSuperTypeOf')]
	public function testIsSuperTypeOf(ObjectWithoutClassType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

}
