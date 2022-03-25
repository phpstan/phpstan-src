<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Constant\ConstantBooleanType;

class ConditionalTypeTest extends PHPStanTestCase
{

	public function dataCreate(): array
	{
		return [
			[
				ConditionalType::create(
					new IntegerType(),
					new IntegerType(),
					new ConstantBooleanType(true),
					new ConstantBooleanType(false),
					false,
				),
				'true',
			],
			[
				ConditionalType::create(
					new IntegerType(),
					new MixedType(),
					new ConstantBooleanType(true),
					new ConstantBooleanType(false),
					false,
				),
				'true',
			],
			[
				ConditionalType::create(
					new MixedType(),
					new IntegerType(),
					new ConstantBooleanType(true),
					new ConstantBooleanType(false),
					false,
				),
				'bool',
			],
		];
	}

	/**
	 * @dataProvider dataCreate
	 */
	public function testCreate(Type $type, string $expected): void
	{
		$this->assertSame($expected, $type->describe(VerbosityLevel::precise()));
	}

}
