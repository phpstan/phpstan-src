<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;

class ConstantFloatTypeTest extends PHPStanTestCase
{

	public static function dataDescribe(): array
	{
		return [
			[
				new ConstantFloatType(2.0),
				'2.0',
			],
			[
				new ConstantFloatType(2.0123),
				'2.0123',
			],
			[
				new ConstantFloatType(1.2000000992884E-10),
				'1.2000000992884E-10',
			],
			[
				new ConstantFloatType(-1.200000099288476E+10),
				'-12000000992.88476',
			],
			[
				new ConstantFloatType(-1.200000099288476E+20),
				'-1.200000099288476E+20',
			],
			[
				new ConstantFloatType(1.2 * 1.4),
				'1.68',
			],
		];
	}

	#[DataProvider('dataDescribe')]
	public function testDescribe(
		ConstantFloatType $type,
		string $expectedDescription,
	): void
	{
		$this->assertSame($expectedDescription, $type->describe(VerbosityLevel::precise()));
	}

}
