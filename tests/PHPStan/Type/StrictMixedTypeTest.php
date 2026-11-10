<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateStrictMixedType;
use PHPStan\Type\Generic\TemplateTypeParameterStrategy;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPUnit\Framework\Attributes\DataProvider;
use function sprintf;

class StrictMixedTypeTest extends PHPStanTestCase
{

	public static function dataIsSubTypeOf(): array
	{
		return [
			[
				new StrictMixedType(),
				new StrictMixedType(),
				TrinaryLogic::createYes(),
			],
			[
				new StrictMixedType(),
				new TemplateStrictMixedType(
					scope: TemplateTypeScope::createWithFunction('identity'),
					templateTypeStrategy: new TemplateTypeParameterStrategy(),
					templateTypeVariance: TemplateTypeVariance::createInvariant(),
					name: 'A',
					bound: new StrictMixedType(),
					default: null,
				),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	#[DataProvider('dataIsSubTypeOf')]
	public function testIsSubTypeOf(StrictMixedType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSubTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSubTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	#[DataProvider('dataIsSubTypeOf')]
	public function testIsSubTypeOfInversed(StrictMixedType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $otherType->isSuperTypeOf($type);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $otherType->describe(VerbosityLevel::precise()), $type->describe(VerbosityLevel::precise())),
		);
	}

}
