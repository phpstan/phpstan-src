<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use Stringable;

class IllegalOffsetTypeHelperTest extends PHPStanTestCase
{

	/**
	 * @return iterable<array{Type, bool}>
	 */
	public static function dataMayOffsetThrowTypeError(): iterable
	{
		yield [new IntegerType(), false];
		yield [new StringType(), false];
		yield [new ConstantStringType('foo'), false];
		yield [TypeCombinator::union(new FloatType(), new BooleanType(), new NullType()), false];
		yield [new ResourceType(), false];
		yield [new ArrayType(new MixedType(), new MixedType()), true];
		yield [new ObjectType(stdClass::class), true];
		yield [new ObjectType(Stringable::class), true];
		yield [new ObjectWithoutClassType(), true];
		yield [new MixedType(), true];
		yield [TypeCombinator::union(new IntegerType(), new ArrayType(new MixedType(), new MixedType())), true];
		yield [TypeCombinator::union(new IntegerType(), new ObjectType(stdClass::class)), true];
		yield [TypeCombinator::addNull(new ObjectType(stdClass::class)), true];
	}

	#[DataProvider('dataMayOffsetThrowTypeError')]
	public function testMayOffsetThrowTypeError(Type $offsetType, bool $expected): void
	{
		$this->assertSame(
			$expected,
			IllegalOffsetTypeHelper::mayOffsetThrowTypeError($offsetType),
			$offsetType->describe(VerbosityLevel::precise()),
		);
	}

}
