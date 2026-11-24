<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use function array_map;

class UnpackedArgumentTypesTest extends PHPStanTestCase
{

	/**
	 * @return iterable<string, array{ConstantArrayType, list<string>, string|null}>
	 */
	public static function dataExpand(): iterable
	{
		$keyTypes = [new ConstantIntegerType(0), new ConstantIntegerType(1)];
		$valueTypes = [new ConstantIntegerType(1), new ConstantIntegerType(2)];
		$never = new NeverType(true);

		yield 'list' => [
			new ConstantArrayType($keyTypes, $valueTypes, [2], isList: TrinaryLogic::createYes()),
			['1', '2'],
			null,
		];

		yield 'sealed list' => [
			new ConstantArrayType($keyTypes, $valueTypes, [2], isList: TrinaryLogic::createYes(), unsealed: [$never, $never]),
			['1', '2'],
			null,
		];

		yield 'maybe a list' => [
			new ConstantArrayType($keyTypes, $valueTypes, [2], isList: TrinaryLogic::createMaybe()),
			[],
			'1|2',
		];

		yield 'unsealed list' => [
			new ConstantArrayType($keyTypes, $valueTypes, [2], isList: TrinaryLogic::createYes(), unsealed: [new IntegerType(), new ConstantIntegerType(3)]),
			[],
			'1|2|3',
		];
	}

	/**
	 * @param list<string> $expectedTypes
	 */
	#[DataProvider('dataExpand')]
	public function testExpand(ConstantArrayType $array, array $expectedTypes, ?string $expectedRestType): void
	{
		[$types, $restType] = UnpackedArgumentTypes::expand([$array], [true]);

		$this->assertSame($expectedTypes, array_map(static fn (Type $type) => $type->describe(VerbosityLevel::precise()), $types));
		$this->assertSame($expectedRestType, $restType?->describe(VerbosityLevel::precise()));
	}

}
