<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use function sprintf;

class NeverTypeTest extends PHPStanTestCase
{

	public function dataAccepts(): array
	{
		return [
			0 => [
				new NeverType(),
				new NeverType(),
				TrinaryLogic::createYes(),
			],
			1 => [
				new NeverType(true),
				new NeverType(),
				TrinaryLogic::createYes(),
			],
			2 => [
				new NeverType(),
				new NeverType(true),
				TrinaryLogic::createYes(),
			],
			3 => [
				new NeverType(true),
				new NeverType(true),
				TrinaryLogic::createYes(),
			],
			4 => [
				new NeverType(),
				new MixedType(),
				TrinaryLogic::createYes(),
			],
			5 => [
				new NeverType(true),
				new MixedType(),
				TrinaryLogic::createNo(),
			],
		];
	}

	/**
	 * @dataProvider dataAccepts
	 */
	public function testAccepts(NeverType $type, Type $acceptedType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->accepts($acceptedType, true);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> accepts(%s)', $type->describe(VerbosityLevel::precise()), $acceptedType->describe(VerbosityLevel::precise())),
		);
	}

}
