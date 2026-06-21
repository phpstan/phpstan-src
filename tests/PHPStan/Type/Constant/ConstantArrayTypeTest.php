<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use Closure;
use PHPStan\DependencyInjection\BleedingEdgeToggle;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CallableType;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use function array_map;
use function is_string;
use function sprintf;

class ConstantArrayTypeTest extends PHPStanTestCase
{

	public static function dataAccepts(): iterable
	{
		// Build the legacy (unsealed) array shapes under an explicit toggle value. These
		// data sets must not depend on the ambient global BleedingEdgeToggle: a previously
		// created bleeding-edge container in the same worker may have left it set, which
		// would seal these shapes at construction time and intermittently flip results.
		yield from BleedingEdgeToggle::withBleedingEdge(false, static fn (): array => [
			[
				new ConstantArrayType([], []),
				new ConstantArrayType([], []),
				TrinaryLogic::createYes(),
			],

			[
				new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
				new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
				TrinaryLogic::createYes(),
			],

			[
				new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
				new ConstantArrayType([], []),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([], []),
				new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
				new ConstantArrayType([new ConstantIntegerType(7)], [new ConstantIntegerType(2)]),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
				new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(7)]),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
				new ArrayType(new IntegerType(), new IntegerType()),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
				new ArrayType(new StringType(), new StringType()),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
				new ArrayType(new MixedType(), new MixedType()),
				TrinaryLogic::createMaybe(),
			],

			[
				new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
				new IterableType(new MixedType(), new IntegerType()),
				TrinaryLogic::createMaybe(),
			],

			[
				new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
				new ConstantArrayType([], []),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([new ConstantStringType('foo')], [new CallableType()]),
				new ConstantArrayType([], []),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([new ConstantStringType('foo')], [new StringType()]),
				new ConstantArrayType([new ConstantStringType('foo'), new ConstantStringType('bar')], [new StringType(), new StringType()]),
				TrinaryLogic::createYes(),
			],

			[
				new ConstantArrayType([new ConstantStringType('foo')], [new StringType()]),
				new ConstantArrayType([new ConstantStringType('bar')], [new StringType()]),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([new ConstantStringType('foo')], [new StringType()]),
				new ConstantArrayType([new ConstantStringType('foo')], [new ConstantStringType('bar')]),
				TrinaryLogic::createYes(),
			],

			[
				TypeCombinator::union(
					new ConstantArrayType([
						new ConstantStringType('name'),
					], [
						new StringType(),
					]),
					new ConstantArrayType([
						new ConstantStringType('name'),
						new ConstantStringType('color'),
					], [
						new StringType(),
						new StringType(),
					]),
				),
				new ConstantArrayType([
					new ConstantStringType('name'),
					new ConstantStringType('color'),
					new ConstantStringType('year'),
				], [
					new StringType(),
					new StringType(),
					new IntegerType(),
				]),
				TrinaryLogic::createYes(),
			],

			[
				new ConstantArrayType([
					new ConstantStringType('name'),
					new ConstantStringType('color'),
					new ConstantStringType('year'),
				], [
					new StringType(),
					new StringType(),
					new IntegerType(),
				]),
				new MixedType(),
				TrinaryLogic::createYes(),
			],

			[
				TypeCombinator::union(
					new ConstantArrayType([], []),
					new ConstantArrayType([
						new ConstantStringType('name'),
						new ConstantStringType('color'),
					], [
						new StringType(),
						new StringType(),
					]),
				),
				new ConstantArrayType([
					new ConstantStringType('surname'),
				], [
					new StringType(),
				]),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([
					new ConstantStringType('sorton'),
					new ConstantStringType('limit'),
				], [
					new StringType(),
					new IntegerType(),
				], optionalKeys: [0, 1]),
				new ConstantArrayType([
					new ConstantStringType('sorton'),
					new ConstantStringType('limit'),
				], [
					new ConstantStringType('test'),
					new ConstantStringType('true'),
				]),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([
					new ConstantStringType('sorton'),
					new ConstantStringType('limit'),
				], [
					new StringType(),
					new IntegerType(),
				]),
				new ConstantArrayType([
					new ConstantStringType('sorton'),
					new ConstantStringType('limit'),
				], [
					new ConstantStringType('test'),
					new ConstantStringType('true'),
				]),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([
					new ConstantStringType('sorton'),
					new ConstantStringType('limit'),
				], [
					new StringType(),
					new IntegerType(),
				], optionalKeys: [1]),
				new ConstantArrayType([
					new ConstantStringType('sorton'),
					new ConstantStringType('limit'),
				], [
					new ConstantStringType('test'),
					new ConstantStringType('true'),
				]),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([
					new ConstantStringType('limit'),
				], [
					new IntegerType(),
				], optionalKeys: [0]),
				new ConstantArrayType([
					new ConstantStringType('limit'),
				], [
					new ConstantStringType('true'),
				]),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([
					new ConstantStringType('limit'),
				], [
					new IntegerType(),
				], [0]),
				new ConstantArrayType([
					new ConstantStringType('limit'),
				], [
					new ConstantStringType('true'),
				]),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([
					new ConstantStringType('sorton'),
					new ConstantStringType('limit'),
				], [
					new StringType(),
					new StringType(),
				], optionalKeys: [0, 1]),
				new ConstantArrayType([
					new ConstantStringType('sorton'),
					new ConstantStringType('limit'),
				], [
					new ConstantStringType('test'),
					new ConstantStringType('true'),
				]),
				TrinaryLogic::createYes(),
			],

			[
				new ConstantArrayType([
					new ConstantStringType('name'),
					new ConstantStringType('color'),
				], [
					new StringType(),
					new StringType(),
				], optionalKeys: [0, 1]),
				new ConstantArrayType([
					new ConstantStringType('color'),
				], [
					new ConstantStringType('test'),
				]),
				TrinaryLogic::createYes(),
			],

			[
				new ConstantArrayType([
					new ConstantStringType('name'),
					new ConstantStringType('color'),
				], [
					new StringType(),
					new StringType(),
				], optionalKeys: [0, 1]),
				new ConstantArrayType([
					new ConstantStringType('sound'),
				], [
					new ConstantStringType('test'),
				]),
				TrinaryLogic::createYes(),
			],

			[
				new ConstantArrayType([
					new ConstantStringType('foo'),
					new ConstantStringType('bar'),
				], [
					new StringType(),
					new StringType(),
				], optionalKeys: [0, 1]),
				new ConstantArrayType([
					new ConstantStringType('foo'),
					new ConstantStringType('bar'),
				], [
					new ConstantStringType('s'),
					new ConstantStringType('m'),
				], optionalKeys: [0, 1]),
				TrinaryLogic::createYes(),
			],

			[
				new ConstantArrayType([
					new ConstantStringType('sorton'),
					new ConstantStringType('limit'),
				], [
					new StringType(),
					new IntegerType(),
				], optionalKeys: [0, 1]),
				new ConstantArrayType([
					new ConstantStringType('sorton'),
					new ConstantStringType('limit'),
				], [
					new ConstantStringType('test'),
					new ConstantStringType('true'),
				]),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([], []),
				new NeverType(),
				TrinaryLogic::createYes(),
			],

			[
				new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
				new NeverType(),
				TrinaryLogic::createYes(),
			],

			[
				new ConstantArrayType([new ConstantStringType('test')], [new MixedType()]),
				new IntersectionType([
					new ArrayType(new MixedType(), new MixedType()),
					new HasOffsetType(new ConstantStringType('test')),
				]),
				TrinaryLogic::createYes(),
			],

			[
				new ConstantArrayType([new ConstantStringType('test')], [new StringType()]),
				new IntersectionType([
					new ArrayType(new MixedType(), new MixedType()),
					new HasOffsetValueType(new ConstantStringType('test'), new StringType()),
				]),
				TrinaryLogic::createYes(),
			],

			[
				new ConstantArrayType([new ConstantStringType('test')], [new MixedType()]),
				new UnionType([
					new ArrayType(new MixedType(), new MixedType()),
					new HasOffsetType(new ConstantStringType('test')),
				]),
				TrinaryLogic::createMaybe(),
			],

			[
				new ConstantArrayType([new ConstantStringType('test')], [new StringType()]),
				new UnionType([
					new ArrayType(new MixedType(), new MixedType()),
					new HasOffsetValueType(new ConstantStringType('test'), new StringType()),
				]),
				TrinaryLogic::createMaybe(),
			],

			[
				new ConstantArrayType([new ConstantStringType('test')], [new MixedType()]),
				new IntersectionType([
					new UnionType([new ArrayType(new MixedType(), new MixedType()), new IterableType(new MixedType(), new MixedType())]),
					new HasOffsetType(new ConstantStringType('test')),
				]),
				TrinaryLogic::createMaybe(),
			],

			[
				new ConstantArrayType([], []),
				new ConstantArrayType([], []),
				TrinaryLogic::createYes(),
			],

			// empty array (with unknown sealedness) does not accept extra keys
			[
				new ConstantArrayType([], []),
				new ConstantArrayType([new ConstantStringType('a')], [new StringType()]),
				TrinaryLogic::createNo(),
				[],
			],

			// non-empty array (with unknown sealedness) accepts extra keys
			[
				new ConstantArrayType([new ConstantStringType('a')], [new StringType()]),
				new ConstantArrayType([
					new ConstantStringType('a'),
					new ConstantStringType('b'),
				], [
					new StringType(),
					new IntegerType(),
				]),
				TrinaryLogic::createYes(),
				[],
			],
		]);

		yield from BleedingEdgeToggle::withBleedingEdge(true, static fn (): array => [
			// empty array (sealed) does not accept extra keys
			[
				new ConstantArrayType([], []),
				new ConstantArrayType([new ConstantStringType('a')], [new StringType()]),
				TrinaryLogic::createNo(),
				[],
			],

			// non-empty array (sealed) does not accept extra keys
			[
				new ConstantArrayType([new ConstantStringType('a')], [new StringType()]),
				new ConstantArrayType([
					new ConstantStringType('a'),
					new ConstantStringType('b'),
				], [
					new StringType(),
					new IntegerType(),
				]),
				TrinaryLogic::createNo(),
				['Sealed array shape does not accept array with extra key \'b\'.'],
			],

			// sealed array does not accept general array
			[
				new ConstantArrayType([new ConstantStringType('a')], [new StringType()]),
				new ArrayType(new StringType(), new StringType()),
				TrinaryLogic::createNo(),
				['Sealed array shape can only accept a constant array. Extra keys are not allowed.'],
			],

			// sealed array does not accept unsealed array
			[
				new ConstantArrayType([new ConstantStringType('a')], [new StringType()]),
				new ConstantArrayType([new ConstantStringType('a')], [new StringType()], unsealed: [new StringType(), new ObjectType(stdClass::class)]),
				TrinaryLogic::createNo(),
				['Sealed array shape does not accept unsealed array shape.'],
			],

			// unsealed array accepts compatible general array
			[
				new ConstantArrayType([new ConstantStringType('a')], [new StringType()], unsealed: [new StringType(), new StringType()]),
				new IntersectionType([
					new ArrayType(new StringType(), new StringType()),
					new HasOffsetValueType(new ConstantStringType('a'), new StringType()),
				]),
				TrinaryLogic::createYes(),
				[],
			],

			// unsealed array does not accept incompatible general array (the error is in the keys already)
			[
				new ConstantArrayType([new ConstantStringType('a')], [new IntegerType()], unsealed: [new StringType(), new StringType()]),
				new IntersectionType([
					new ArrayType(new StringType(), new StringType()),
					new HasOffsetValueType(new ConstantStringType('a'), new StringType()),
				]),
				TrinaryLogic::createNo(),
				[],
			],

			// unsealed array does not accept incompatible general array (integer vs. string unsealed values)
			[
				new ConstantArrayType([new ConstantStringType('a')], [new StringType()], unsealed: [new StringType(), new IntegerType()]),
				new IntersectionType([
					new ArrayType(new StringType(), new StringType()),
					new HasOffsetValueType(new ConstantStringType('a'), new StringType()),
				]),
				TrinaryLogic::createNo(),
				[],
			],

			// unsealed array must check extra keys against its own unsealed types
			[
				new ConstantArrayType([new ConstantStringType('a')], [new StringType()], unsealed: [new StringType(), new StringType()]),
				new ConstantArrayType([
					new ConstantStringType('a'),
					new ConstantStringType('b'),
				], [
					new StringType(),
					new StringType(),
				]),
				TrinaryLogic::createYes(),
				[],
			],

			[
				new ConstantArrayType([new ConstantStringType('a')], [new StringType()], unsealed: [new IntegerType(), new StringType()]),
				new ConstantArrayType([
					new ConstantStringType('a'),
					new ConstantIntegerType(10),
				], [
					new StringType(),
					new StringType(),
				]),
				TrinaryLogic::createYes(),
				[],
			],

			[
				new ConstantArrayType([new ConstantStringType('a')], [new StringType()], unsealed: [new IntegerType(), new StringType()]),
				new ConstantArrayType([
					new ConstantStringType('a'),
					new ConstantStringType('b'),
				], [
					new StringType(),
					new StringType(),
				]),
				TrinaryLogic::createNo(),
				[
					'Unsealed array key type int does not accept extra key type \'b\'.',
				],
			],

			[
				new ConstantArrayType([new ConstantStringType('a')], [new StringType()], unsealed: [new StringType(), new IntegerType()]),
				new ConstantArrayType([
					new ConstantStringType('a'),
					new ConstantStringType('b'),
				], [
					new StringType(),
					new StringType(),
				]),
				TrinaryLogic::createNo(),
				[
					'Unsealed array value type int does not accept extra offset \'b\' with value type string.',
				],
			],

			// unsealed array must check the other array unsealed types
			[
				new ConstantArrayType([new ConstantStringType('a')], [new StringType()], unsealed: [new StringType(), new StringType()]),
				new ConstantArrayType([new ConstantStringType('a')], [new StringType()], unsealed: [new StringType(), new StringType()]),
				TrinaryLogic::createYes(),
				[],
			],

			[
				new ConstantArrayType([new ConstantStringType('a')], [new StringType()], unsealed: [new StringType(), new StringType()]),
				new ConstantArrayType([new ConstantStringType('a')], [new StringType()], unsealed: [new IntegerType(), new StringType()]),
				TrinaryLogic::createNo(),
				[
					'Unsealed array key type string does not accept unsealed array key type int.',
				],
			],

			[
				new ConstantArrayType([new ConstantStringType('a')], [new StringType()], unsealed: [new StringType(), new StringType()]),
				new ConstantArrayType([new ConstantStringType('a')], [new StringType()], unsealed: [new StringType(), new IntegerType()]),
				TrinaryLogic::createNo(),
				[
					'Unsealed array value type string does not accept unsealed array value type int.',
				],
			],

			[
				new ConstantArrayType([new ConstantStringType('a')], [new StringType()]),
				new UnionType([
					new ConstantArrayType([new ConstantStringType('a')], [new StringType()]),
					new StringType(),
				]),
				TrinaryLogic::createMaybe(),
				[],
			],
		]);
	}

	/**
	 * @param array<string>|null $reasons
	 */
	#[DataProvider('dataAccepts')]
	public function testAccepts(Type $type, Type $otherType, TrinaryLogic $expectedResult, ?array $reasons = null): void
	{
		$actualResult = $type->accepts($otherType, true);
		$testDescription = sprintf('%s -> accepts(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise()));
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->result->describe(),
			$testDescription,
		);
		if ($reasons === null) {
			return;
		}

		$this->assertSame($reasons, $actualResult->reasons, $testDescription);
	}

	public static function dataIsSuperTypeOf(): iterable
	{
		// Build the legacy (unsealed) array shapes under an explicit toggle value. These
		// data sets must not depend on the ambient global BleedingEdgeToggle: a previously
		// created bleeding-edge container in the same worker may have left it set, which
		// would seal these shapes at construction time and intermittently flip results.
		yield from BleedingEdgeToggle::withBleedingEdge(false, static fn (): array => [
			[
				new ConstantArrayType([], []),
				new ConstantArrayType([], []),
				TrinaryLogic::createYes(),
			],

			[
				new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
				new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
				TrinaryLogic::createYes(),
			],

			[
				new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
				new ConstantArrayType([], []),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([], []),
				new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
				new ConstantArrayType([new ConstantIntegerType(7)], [new ConstantIntegerType(2)]),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
				new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(7)]),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
				new ArrayType(new IntegerType(), new IntegerType()),
				TrinaryLogic::createMaybe(),
			],

			[
				new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
				new ArrayType(new StringType(), new StringType()),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
				new ArrayType(new MixedType(), new MixedType()),
				TrinaryLogic::createMaybe(),
			],

			[
				new ConstantArrayType([], []),
				new IterableType(new MixedType(false), new MixedType(true)),
				TrinaryLogic::createMaybe(),
			],

			[
				new ConstantArrayType([
					new ConstantStringType('foo'),
				], [
					new IntegerType(),
				]),
				new ConstantArrayType([
					new ConstantStringType('foo'),
					new ConstantStringType('bar'),
				], [
					new IntegerType(),
					new IntegerType(),
				]),
				TrinaryLogic::createYes(),
			],

			[
				new ConstantArrayType([
					new ConstantStringType('foo'),
					new ConstantStringType('bar'),
				], [
					new IntegerType(),
					new IntegerType(),
				]),
				new ConstantArrayType([
					new ConstantStringType('foo'),
				], [
					new IntegerType(),
				]),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([
					new ConstantStringType('foo'),
					new ConstantStringType('bar'),
				], [
					new IntegerType(),
					new IntegerType(),
				], [2]),
				new ConstantArrayType([], []),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([
					new ConstantStringType('foo'),
					new ConstantStringType('bar'),
				], [
					new IntegerType(),
					new IntegerType(),
				], [2], [0]),
				new ConstantArrayType([], []),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([
					new ConstantStringType('foo'),
					new ConstantStringType('bar'),
				], [
					new IntegerType(),
					new IntegerType(),
				], [2], [0, 1]),
				new ConstantArrayType([], []),
				TrinaryLogic::createYes(),
			],

			[
				new ConstantArrayType([
					new ConstantStringType('foo'),
					new ConstantStringType('bar'),
				], [
					new IntegerType(),
					new IntegerType(),
				], [2], [0, 1]),
				new ConstantArrayType([
					new ConstantStringType('foo'),
				], [
					new IntegerType(),
				], [1], [0]),
				TrinaryLogic::createYes(),
			],

			[
				new ConstantArrayType([
					new ConstantStringType('foo'),
				], [
					new IntegerType(),
				]),
				new ConstantArrayType([
					new ConstantStringType('foo'),
					new ConstantStringType('bar'),
				], [
					new IntegerType(),
					new IntegerType(),
				], [2], [0, 1]),
				TrinaryLogic::createMaybe(),
			],

			[
				new ConstantArrayType([
					new ConstantStringType('foo'),
				], [
					new StringType(),
				]),
				new ConstantArrayType([
					new ConstantStringType('foo'),
					new ConstantStringType('bar'),
				], [
					new IntegerType(),
					new IntegerType(),
				], [2], [0, 1]),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([
					new ConstantStringType('foo'),
					new ConstantStringType('bar'),
				], [
					new IntegerType(),
					new IntegerType(),
				], [2], [0, 1]),
				new ConstantArrayType([
					new ConstantStringType('foo'),
				], [
					new StringType(),
				]),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([], []),
				new ConstantArrayType([
					new ConstantStringType('foo'),
					new ConstantStringType('bar'),
				], [
					new IntegerType(),
					new IntegerType(),
				], [2], [0, 1]),
				TrinaryLogic::createMaybe(),
			],

			[
				new ConstantArrayType([
					new ConstantStringType('foo'),
				], [
					new IntegerType(),
				], [1], [0]),
				new ConstantArrayType([
					new ConstantStringType('foo'),
				], [
					new IntegerType(),
				]),
				TrinaryLogic::createYes(),
			],

			[
				new ConstantArrayType([
					new ConstantStringType('foo'),
				], [
					new IntegerType(),
				]),
				new ConstantArrayType([
					new ConstantStringType('foo'),
				], [
					new IntegerType(),
				], [1], [0]),
				TrinaryLogic::createMaybe(),
			],

			[
				new ConstantArrayType([
					new ConstantStringType('a'),
					new ConstantStringType('b'),
				], [
					new IntegerType(),
					new UnionType([new IntegerType(), new NullType()]),
				]),
				new ArrayType(new StringType(), new MixedType()),
				TrinaryLogic::createMaybe(),
			],

			[
				new ConstantArrayType([
					new ConstantStringType('a'),
					new ConstantStringType('b'),
				], [
					new IntegerType(),
					new UnionType([new IntegerType(), new NullType()]),
				]),
				new ArrayType(new StringType(), new StringType()),
				TrinaryLogic::createNo(),
			],

			[
				new ConstantArrayType([
					new ConstantIntegerType(1),
					new ConstantIntegerType(2),
				], [
					new IntegerType(),
					new UnionType([new IntegerType(), new NullType()]),
				]),
				new ArrayType(new StringType(), new MixedType()),
				TrinaryLogic::createNo(),
			],

			// empty array (with unknown sealedness) does not accept extra keys
			[
				new ConstantArrayType([], []),
				new ConstantArrayType([new ConstantStringType('a')], [new StringType()]),
				TrinaryLogic::createNo(),
			],

			// non-empty array (with unknown sealedness) accepts extra keys
			[
				new ConstantArrayType([new ConstantStringType('a')], [new StringType()]),
				new ConstantArrayType([
					new ConstantStringType('a'),
					new ConstantStringType('b'),
				], [
					new StringType(),
					new IntegerType(),
				]),
				TrinaryLogic::createYes(),
			],
		]);

		// definite sealedness tests (bleeding edge)

		// both sealed, same keys, compatible values
		yield ['array{a: int, b: string}', 'array{a: int, b: string}', TrinaryLogic::createYes()];

		// both sealed, bigger vs smaller (subset) — sealed requires exact keys
		yield ['array{a: int, b: string}', 'array{a: int}', TrinaryLogic::createNo()];
		yield ['array{a: int}', 'array{a: int, b: string}', TrinaryLogic::createNo()];

		// both sealed, narrower value
		yield ['array{a: int}', 'array{a: int<0, max>}', TrinaryLogic::createYes()];
		yield ['array{a: int<0, max>}', 'array{a: int}', TrinaryLogic::createMaybe()];

		// both sealed, optional key in left only
		yield ['array{a: int, b?: string}', 'array{a: int}', TrinaryLogic::createYes()];
		yield ['array{a: int, b?: string}', 'array{a: int, b: string}', TrinaryLogic::createYes()];

		// both unsealed, compatible known keys + compatible unsealed
		yield ['array{a: int, ...}', 'array{a: int<0, max>, ...}', TrinaryLogic::createYes()];
		yield ['array{a: int<0, max>, ...}', 'array{a: int, ...}', TrinaryLogic::createMaybe()];

		// both unsealed, bigger known on right (right's extra fits left's unsealed extras)
		yield ['array{a: int, ...}', 'array{a: int, b: string, ...}', TrinaryLogic::createYes()];

		// both unsealed, right has known key left doesn't require; left's unsealed must cover
		yield ['array{a: int, ...<string, string>}', 'array{a: int, b: int, ...<string, string>}', TrinaryLogic::createNo()];
		yield ['array{a: int, ...<string, string>}', 'array{a: int, b: non-empty-string, ...<string, string>}', TrinaryLogic::createYes()];

		// both unsealed, narrower unsealed value on right
		yield ['array{a: int, ...<string, string>}', 'array{a: int, ...<string, non-empty-string>}', TrinaryLogic::createYes()];
		yield ['array{a: int, ...<string, non-empty-string>}', 'array{a: int, ...<string, string>}', TrinaryLogic::createMaybe()];

		// both unsealed, narrower unsealed key on right (array-key ⊃ string)
		yield ['array{a: int, ...<array-key, string>}', 'array{a: int, ...<string, string>}', TrinaryLogic::createYes()];
		yield ['array{a: int, ...<string, string>}', 'array{a: int, ...<array-key, string>}', TrinaryLogic::createMaybe()];

		// both unsealed, incompatible unsealed key types
		yield ['array{...<int, string>}', 'array{...<string, string>}', TrinaryLogic::createNo()];

		// both unsealed, incompatible unsealed value types
		yield ['array{...<int, string>}', 'array{...<int, int>}', TrinaryLogic::createNo()];

		// unsealed vs sealed — sealed's extras must fit unsealed's unsealed
		yield ['array{a: int, ...}', 'array{a: int, b: string}', TrinaryLogic::createYes()];
		yield ['array{a: int, ...<string, int>}', 'array{a: int, b: string}', TrinaryLogic::createNo()];

		// sealed vs unsealed — unsealed might have extras sealed doesn't allow
		yield ['array{a: int}', 'array{a: int, ...}', TrinaryLogic::createMaybe()];
		yield ['array{a: int, b: string}', 'array{a: int<0, max>, ...}', TrinaryLogic::createMaybe()];

		// sealed vs unsealed where sealed's keys can't be in unsealed's extras
		yield ['array{a: int}', 'array{...<int, int>}', TrinaryLogic::createNo()];

		// sealed vs unsealed where sealed fits unsealed's extras
		yield ['array{a: int}', 'array{...<string, int>}', TrinaryLogic::createMaybe()];
	}

	/**
	 * @param ConstantArrayType|string $type
	 * @param Type|string $otherType
	 */
	#[DataProvider('dataIsSuperTypeOf')]
	public function testIsSuperTypeOf($type, $otherType, TrinaryLogic $expectedResult): void
	{
		[$type, $otherType] = BleedingEdgeToggle::withBleedingEdge(true, static function () use ($type, $otherType): array {
			$resolver = self::getContainer()->getByType(TypeStringResolver::class);
			if (is_string($type)) {
				$type = $resolver->resolve($type, null);
			}
			if (is_string($otherType)) {
				$otherType = $resolver->resolve($otherType, null);
			}

			return [$type, $otherType];
		});

		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	public static function dataInferTemplateTypes(): array
	{
		$templateType = static fn ($name): Type => TemplateTypeFactory::create(
			TemplateTypeScope::createWithFunction('a'),
			$name,
			new MixedType(),
			TemplateTypeVariance::createInvariant(),
		);

		return [
			'receive constant array' => [
				new ConstantArrayType(
					[
						new ConstantStringType('a'),
						new ConstantStringType('b'),
					],
					[
						new StringType(),
						new IntegerType(),
					],
				),
				new ConstantArrayType(
					[
						new ConstantStringType('a'),
						new ConstantStringType('b'),
					],
					[
						$templateType('T'),
						$templateType('U'),
					],
				),
				['T' => 'string', 'U' => 'int'],
			],
			'receive constant array int' => [
				new ConstantArrayType(
					[
						new ConstantIntegerType(0),
						new ConstantIntegerType(1),
					],
					[
						new StringType(),
						new IntegerType(),
					],
				),
				new ConstantArrayType(
					[
						new ConstantIntegerType(0),
						new ConstantIntegerType(1),
					],
					[
						$templateType('T'),
						$templateType('U'),
					],
				),
				['T' => 'string', 'U' => 'int'],
			],
			'receive incompatible constant array' => [
				new ConstantArrayType(
					[
						new ConstantStringType('c'),
					],
					[
						new StringType(),
					],
				),
				new ConstantArrayType(
					[
						new ConstantStringType('a'),
						new ConstantStringType('b'),
					],
					[
						$templateType('T'),
						$templateType('U'),
					],
				),
				[],
			],
			'receive mixed' => [
				new MixedType(),
				new ConstantArrayType(
					[
						new ConstantStringType('a'),
					],
					[
						$templateType('T'),
					],
				),
				[],
			],
			'receive array' => [
				new ArrayType(new MixedType(), new StringType()),
				new ConstantArrayType(
					[
						new ConstantStringType('a'),
					],
					[
						$templateType('T'),
					],
				),
				['T' => 'string'],
			],
		];
	}

	/**
	 * @param array<string,string> $expectedTypes
	 */
	#[DataProvider('dataInferTemplateTypes')]
	public function testResolveTemplateTypes(Type $received, Type $template, array $expectedTypes): void
	{
		$result = $template->inferTemplateTypes($received);

		$this->assertSame(
			$expectedTypes,
			array_map(static fn (Type $type): string => $type->describe(VerbosityLevel::precise()), $result->getTypes()),
		);
	}

	#[DataProvider('dataIsCallable')]
	public function testIsCallable(ConstantArrayType $type, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isCallable();
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isCallable()', $type->describe(VerbosityLevel::precise())),
		);
	}

	public static function dataIsCallable(): iterable
	{
		yield 'zero items' => [
			new ConstantArrayType([], []),
			TrinaryLogic::createNo(),
		];

		yield 'function name' => [
			new ConstantArrayType([
				new ConstantIntegerType(0),
			], [
				new ConstantStringType('strlen'),
			]),
			TrinaryLogic::createNo(),
		];

		yield 'existing static method' => [
			new ConstantArrayType([
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
			], [
				new ConstantStringType(Closure::class, true),
				new ConstantStringType('bind'),
			]),
			TrinaryLogic::createYes(),
		];

		yield 'non-existing static method' => [
			new ConstantArrayType([
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
			], [
				new ConstantStringType(Closure::class, true),
				new ConstantStringType('foobar'),
			]),
			TrinaryLogic::createNo(),
		];

		yield 'existing static method but not a class string' => [
			new ConstantArrayType([
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
			], [
				new ConstantStringType('Closure'),
				new ConstantStringType('bind'),
			]),
			TrinaryLogic::createYes(),
		];

		yield 'existing static method but with string keys' => [
			new ConstantArrayType([
				new ConstantStringType('a'),
				new ConstantStringType('b'),
			], [
				new ConstantStringType(Closure::class, true),
				new ConstantStringType('bind'),
			]),
			TrinaryLogic::createNo(),
		];

		yield 'class-string' => [
			new ConstantArrayType([
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
			], [
				new GenericClassStringType(new ObjectType(Closure::class)),
				new ConstantStringType('bind'),
			]),
			TrinaryLogic::createYes(),
		];

		yield [
			new ConstantArrayType([
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
			], [
				new ClassStringType(),
				new StringType(),
			]),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new ConstantArrayType([
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
			], [
				new ObjectWithoutClassType(),
				new StringType(),
			]),
			TrinaryLogic::createMaybe(),
		];

		$never = new NeverType(true);
		$sealed = [$never, $never];

		yield [
			new ConstantArrayType([
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
			], [
				new ObjectWithoutClassType(),
				new StringType(),
			], unsealed: $sealed),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new ConstantArrayType([
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
			], [
				new ObjectWithoutClassType(),
				new StringType(),
			], unsealed: [new IntegerType(), new StringType()]),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new ConstantArrayType([
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
			], [
				new GenericClassStringType(new ObjectType(Closure::class)),
				new ConstantStringType('bind'),
			], unsealed: [new IntegerType(), new StringType()]),
			TrinaryLogic::createMaybe(), // extra keys would void the callable-ness
		];

		yield [
			new ConstantArrayType([
				new ConstantIntegerType(0),
			], [
				new ObjectWithoutClassType(),
			], unsealed: [new IntegerType(), new StringType()]),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new ConstantArrayType([
				new ConstantIntegerType(0),
			], [
				new ObjectWithoutClassType(),
			], unsealed: $sealed),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantArrayType([
				new ConstantIntegerType(0),
			], [
				new ObjectWithoutClassType(),
			], unsealed: [IntegerRangeType::createAllGreaterThanOrEqualTo(2), new StringType()]),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantArrayType([
				new ConstantIntegerType(0),
			], [
				new ObjectWithoutClassType(),
			], unsealed: [new StringType(), new StringType()]),
			TrinaryLogic::createNo(),
		];

		// Only key 0 explicit, value at key 1 from unsealed can never be
		// a non-falsy-string (int → not a string at all).
		yield [
			new ConstantArrayType([
				new ConstantIntegerType(0),
			], [
				new ObjectWithoutClassType(),
			], unsealed: [new IntegerType(), new IntegerType()]),
			TrinaryLogic::createNo(),
		];

		// Only key 1 explicit, value at key 0 from unsealed must be
		// object|class-string; int can never be that.
		yield [
			new ConstantArrayType([
				new ConstantIntegerType(1),
			], [
				new ConstantStringType('bind'),
			], unsealed: [new IntegerType(), new IntegerType()]),
			TrinaryLogic::createNo(),
		];

		// Only key 1 explicit, value at key 0 from unsealed is a plain
		// string — `string ∩ (object|class-string) = class-string`, so
		// it could line up.
		yield [
			new ConstantArrayType([
				new ConstantIntegerType(1),
			], [
				new ConstantStringType('bind'),
			], unsealed: [new IntegerType(), new StringType()]),
			TrinaryLogic::createMaybe(),
		];

		// Sealed three-element array is never a callable (callable
		// shape has exactly two slots).
		yield [
			new ConstantArrayType([
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
				new ConstantIntegerType(2),
			], [
				new GenericClassStringType(new ObjectType(Closure::class)),
				new ConstantStringType('bind'),
				new ConstantStringType('extra'),
			]),
			TrinaryLogic::createNo(),
		];

		// Sealed two-element array with a stray non-callable key
		// position is never a callable.
		yield [
			new ConstantArrayType([
				new ConstantIntegerType(0),
				new ConstantIntegerType(5),
			], [
				new GenericClassStringType(new ObjectType(Closure::class)),
				new ConstantStringType('bind'),
			]),
			TrinaryLogic::createNo(),
		];

		// Fully open `array{...<mixed, mixed>}`: callable iff actual
		// extras happen to land on `[0 => object|class-string,
		// 1 => non-falsy-string]` — uncertain by construction.
		yield [
			new ConstantArrayType([], [], unsealed: [new MixedType(), new MixedType()]),
			TrinaryLogic::createMaybe(),
		];

		// Empty value, no explicit keys, sealed → empty array → No.
		// (Already covered by the 'zero items' case above; included here
		// as a foil for the open-shape variant.)
	}

	public static function dataValuesArray(): iterable
	{
		yield 'empty' => [
			new ConstantArrayType([], []),
			new ConstantArrayType([], []),
		];

		yield 'non-optional' => [
			new ConstantArrayType([
				new ConstantIntegerType(10),
				new ConstantIntegerType(11),
			], [
				new ConstantStringType('a'),
				new ConstantStringType('b'),
			], [20], isList: TrinaryLogic::createNo()),
			new ConstantArrayType([
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
			], [
				new ConstantStringType('a'),
				new ConstantStringType('b'),
			], [2], isList: TrinaryLogic::createYes()),
		];

		yield 'optional-1' => [
			new ConstantArrayType([
				new ConstantIntegerType(10),
				new ConstantIntegerType(11),
				new ConstantIntegerType(12),
				new ConstantIntegerType(13),
				new ConstantIntegerType(14),
			], [
				new ConstantStringType('a'),
				new ConstantStringType('b'),
				new ConstantStringType('c'),
				new ConstantStringType('d'),
				new ConstantStringType('e'),
			], [15], [1, 3], TrinaryLogic::createNo()),
			new ConstantArrayType([
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
				new ConstantIntegerType(2),
				new ConstantIntegerType(3),
				new ConstantIntegerType(4),
			], [
				new ConstantStringType('a'),
				new UnionType([new ConstantStringType('b'), new ConstantStringType('c')]),
				new UnionType([new ConstantStringType('c'), new ConstantStringType('d'), new ConstantStringType('e')]),
				new UnionType([new ConstantStringType('d'), new ConstantStringType('e')]),
				new ConstantStringType('e'),
			], [3, 4, 5], [3, 4], TrinaryLogic::createYes()),
		];

		yield 'optional-2' => [
			new ConstantArrayType([
				new ConstantIntegerType(10),
				new ConstantIntegerType(11),
				new ConstantIntegerType(12),
				new ConstantIntegerType(13),
				new ConstantIntegerType(14),
			], [
				new ConstantStringType('a'),
				new ConstantStringType('b'),
				new ConstantStringType('c'),
				new ConstantStringType('d'),
				new ConstantStringType('e'),
			], [15], [0, 2, 4], TrinaryLogic::createNo()),
			new ConstantArrayType([
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
				new ConstantIntegerType(2),
				new ConstantIntegerType(3),
				new ConstantIntegerType(4),
			], [
				new UnionType([new ConstantStringType('a'), new ConstantStringType('b')]),
				new UnionType([new ConstantStringType('b'), new ConstantStringType('c'), new ConstantStringType('d')]),
				new UnionType([new ConstantStringType('c'), new ConstantStringType('d'), new ConstantStringType('e')]),
				new UnionType([new ConstantStringType('d'), new ConstantStringType('e')]),
				new ConstantStringType('e'),
			], [2, 3, 4, 5], [2, 3, 4], TrinaryLogic::createYes()),
		];

		yield 'optional-at-end-and-list' => [
			new ConstantArrayType([
				new ConstantIntegerType(10),
				new ConstantIntegerType(11),
				new ConstantIntegerType(12),
			], [
				new ConstantStringType('a'),
				new ConstantStringType('b'),
				new ConstantStringType('c'),
			], [11, 12, 13], [1, 2], TrinaryLogic::createYes()),
			new ConstantArrayType([
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
				new ConstantIntegerType(2),
			], [
				new ConstantStringType('a'),
				new ConstantStringType('b'),
				new ConstantStringType('c'),
			], [1, 2, 3], [1, 2], TrinaryLogic::createYes()),
		];

		yield 'optional-at-end-but-not-list' => [
			new ConstantArrayType([
				new ConstantIntegerType(10),
				new ConstantIntegerType(11),
				new ConstantIntegerType(12),
			], [
				new ConstantStringType('a'),
				new ConstantStringType('b'),
				new ConstantStringType('c'),
			], [11, 12, 13], [1, 2], TrinaryLogic::createNo()),
			new ConstantArrayType([
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
				new ConstantIntegerType(2),
			], [
				new ConstantStringType('a'),
				new UnionType([new ConstantStringType('b'), new ConstantStringType('c')]),
				new ConstantStringType('c'),
			], [1, 2, 3], [1, 2], TrinaryLogic::createYes()),
		];
	}

	#[DataProvider('dataValuesArray')]
	public function testValuesArray(ConstantArrayType $type, ConstantArrayType $expectedType): void
	{
		$actualType = $type->getValuesArray();
		$message = sprintf(
			'Values array of %s is %s, but should be %s',
			$type->describe(VerbosityLevel::precise()),
			$actualType->describe(VerbosityLevel::precise()),
			$expectedType->describe(VerbosityLevel::precise()),
		);
		$this->assertTrue($expectedType->equals($actualType), $message);
		$this->assertSame($expectedType->isList(), $actualType->isList());
		$this->assertSame($expectedType->getNextAutoIndexes(), $actualType->getNextAutoIndexes());
	}

	public static function dataHasOffsetValueType(): array
	{
		return [
			[
				new ConstantArrayType([new ConstantIntegerType(0)], [new ConstantStringType('a')]),
				new ConstantArrayType([new ConstantIntegerType(0)], [new ConstantStringType('a')]),
				TrinaryLogic::createNo(),
			],
		];
	}

	#[DataProvider('dataHasOffsetValueType')]
	public function testHasOffsetValueType(
		ConstantArrayType $type,
		Type $offsetType,
		TrinaryLogic $expectedResult,
	): void
	{
		$actualResult = $type->hasOffsetValueType($offsetType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> hasOffsetValueType(%s)', $type->describe(VerbosityLevel::precise()), $offsetType->describe(VerbosityLevel::precise())),
		);
	}

	public function testEqualsTreatsLegacyNullAndSealedMarkerAsEqual(): void
	{
		$bleedingEdgeBackup = BleedingEdgeToggle::isBleedingEdge();

		try {
			// Pre-bleeding-edge construction leaves the unsealed slot null
			// (`isUnsealed()` answers `Maybe`).
			BleedingEdgeToggle::setBleedingEdge(false);
			$legacyNull = new ConstantArrayType([new ConstantStringType('a')], [new IntegerType()]);

			// Bleeding-edge construction seeds the `[NeverType, NeverType]`
			// sealed marker (`isUnsealed()` answers `No`).
			BleedingEdgeToggle::setBleedingEdge(true);
			$sealedMarker = new ConstantArrayType([new ConstantStringType('a')], [new IntegerType()]);

			// Both represent the same sealed shape, so they must compare
			// equal in both directions — this mismatch is what made the
			// `TypeToPhpDocNode` round-trip fail under old PHPUnit (data
			// providers run before the container enables bleeding edge).
			$this->assertTrue($legacyNull->equals($sealedMarker), 'legacy-null should equal sealed-marker');
			$this->assertTrue($sealedMarker->equals($legacyNull), 'sealed-marker should equal legacy-null');
		} finally {
			BleedingEdgeToggle::setBleedingEdge($bleedingEdgeBackup);
		}
	}

	public function testSealedness(): void
	{
		$bleedingEdgeBackup = BleedingEdgeToggle::isBleedingEdge();

		BleedingEdgeToggle::setBleedingEdge(false);

		try {
			$builder = ConstantArrayTypeBuilder::createEmpty();
			$array = $builder->getArray();
			$this->assertInstanceOf(ConstantArrayType::class, $array);
			$this->assertSame(TrinaryLogic::createMaybe()->describe(), $array->isSealed()->describe());
			$this->assertSame(TrinaryLogic::createMaybe()->describe(), $array->isUnsealed()->describe());

			BleedingEdgeToggle::setBleedingEdge(true);
			$builder = ConstantArrayTypeBuilder::createEmpty();
			$array = $builder->getArray();
			$this->assertInstanceOf(ConstantArrayType::class, $array);
			$this->assertSame(TrinaryLogic::createYes()->describe(), $array->isSealed()->describe());
			$this->assertSame(TrinaryLogic::createNo()->describe(), $array->isUnsealed()->describe());

			$builder = ConstantArrayTypeBuilder::createEmpty();
			$builder->makeUnsealed(new IntegerType(), new StringType());
			$array = $builder->getArray();
			// No known keys + real unsealed extras now collapses to a general ArrayType
			// (see ConstantArrayTypeBuilder::getArray).
			$this->assertInstanceOf(ArrayType::class, $array);
			$this->assertSame('array<int, string>', $array->describe(VerbosityLevel::precise()));
		} finally {
			BleedingEdgeToggle::setBleedingEdge($bleedingEdgeBackup);
		}
	}

	public static function dataGetArraySize(): iterable
	{
		foreach ([false, true] as $bleedingEdge) {
			// Build the toggle-dependent data sets eagerly and restore the global
			// BleedingEdgeToggle before yielding, so it never leaks across a yield
			// boundary into unrelated tests when this provider is abandoned early.
			yield from BleedingEdgeToggle::withBleedingEdge($bleedingEdge, static function (): array {
				$cases = [];

				$cases[] = [
					new ConstantArrayType([], []),
					new ConstantIntegerType(0),
				];

				$builder = ConstantArrayTypeBuilder::createEmpty();
				$cases[] = [
					$builder->getArray(),
					new ConstantIntegerType(0),
				];

				$builder->makeUnsealed(new IntegerType(), new ObjectType(stdClass::class));
				$cases[] = [
					$builder->getArray(),
					IntegerRangeType::createAllGreaterThanOrEqualTo(0),
				];

				$builder->setOffsetValueType(new ConstantIntegerType(0), new ObjectType(stdClass::class));
				$cases[] = [
					$builder->getArray(),
					IntegerRangeType::createAllGreaterThanOrEqualTo(1),
				];

				$builder->setOffsetValueType(new ConstantIntegerType(1), new ObjectType(stdClass::class), true);
				$cases[] = [
					$builder->getArray(),
					IntegerRangeType::createAllGreaterThanOrEqualTo(1),
				];

				return $cases;
			});
		}

		$builder = ConstantArrayTypeBuilder::createEmpty();
		$builder->makeUnsealed(new IntegerType(), new ObjectType(stdClass::class));
		yield [
			$builder->getArray(),
			IntegerRangeType::createAllGreaterThanOrEqualTo(0),
		];
		$builder->setOffsetValueType(new ConstantIntegerType(0), new ObjectType(stdClass::class));
		yield [
			$builder->getArray(),
			IntegerRangeType::createAllGreaterThanOrEqualTo(1),
		];

		$builder->setOffsetValueType(new ConstantIntegerType(1), new ObjectType(stdClass::class), true);
		yield [
			$builder->getArray(),
			IntegerRangeType::createAllGreaterThanOrEqualTo(1),
		];
	}

	#[DataProvider('dataGetArraySize')]
	public function testGetArraySize(Type $constantArray, Type $expectedSize): void
	{
		$this->assertSame($expectedSize->describe(VerbosityLevel::precise()), $constantArray->getArraySize()->describe(VerbosityLevel::precise()));
	}

	public static function dataGetFiniteTypes(): iterable
	{
		yield 'empty array' => [
			new ConstantArrayType([], []),
			['array{}'],
		];

		yield 'single key with single finite value' => [
			new ConstantArrayType(
				[new ConstantStringType('a')],
				[new ConstantStringType('foo')],
			),
			["array{a: 'foo'}"],
		];

		yield 'multiple finite-only values' => [
			new ConstantArrayType(
				[
					new ConstantStringType('a'),
					new ConstantStringType('b'),
				],
				[
					new ConstantIntegerType(1),
					new ConstantStringType('foo'),
				],
			),
			["array{a: 1, b: 'foo'}"],
		];

		yield 'union value expands to cartesian product' => [
			new ConstantArrayType(
				[new ConstantStringType('a')],
				[new UnionType([
					new ConstantIntegerType(1),
					new ConstantIntegerType(2),
				])],
			),
			['array{a: 1}', 'array{a: 2}'],
		];

		yield 'two union values expand to full cartesian product' => [
			new ConstantArrayType(
				[
					new ConstantStringType('a'),
					new ConstantStringType('b'),
				],
				[
					new UnionType([
						new ConstantIntegerType(1),
						new ConstantIntegerType(2),
					]),
					new UnionType([
						new ConstantStringType('x'),
						new ConstantStringType('y'),
					]),
				],
			),
			[
				"array{a: 1, b: 'x'}",
				"array{a: 1, b: 'y'}",
				"array{a: 2, b: 'x'}",
				"array{a: 2, b: 'y'}",
			],
		];

		yield 'bool value expands to true/false' => [
			new ConstantArrayType(
				[new ConstantStringType('flag')],
				[new BooleanType()],
			),
			['array{flag: true}', 'array{flag: false}'],
		];

		yield 'non-finite value yields no finite types' => [
			new ConstantArrayType(
				[new ConstantStringType('a')],
				[new IntegerType()],
			),
			[],
		];

		yield 'mixed finite and non-finite values yield no finite types' => [
			new ConstantArrayType(
				[
					new ConstantStringType('a'),
					new ConstantStringType('b'),
				],
				[
					new ConstantIntegerType(1),
					new IntegerType(),
				],
			),
			[],
		];

		yield 'optional key forks with-without' => [
			new ConstantArrayType(
				[
					new ConstantStringType('a'),
					new ConstantStringType('b'),
				],
				[
					new ConstantIntegerType(1),
					new ConstantStringType('foo'),
				],
				[0],
				[0],
			),
			[
				"array{b: 'foo'}",
				"array{a: 1, b: 'foo'}",
			],
		];

		yield 'all optional keys' => [
			new ConstantArrayType(
				[
					new ConstantStringType('a'),
					new ConstantStringType('b'),
				],
				[
					new ConstantIntegerType(1),
					new ConstantStringType('foo'),
				],
				[0],
				[0, 1],
			),
			[
				'array{}',
				"array{b: 'foo'}",
				'array{a: 1}',
				"array{a: 1, b: 'foo'}",
			],
		];

		yield 'optional key combined with union value' => [
			new ConstantArrayType(
				[
					new ConstantStringType('a'),
					new ConstantStringType('b'),
				],
				[
					new UnionType([
						new ConstantIntegerType(1),
						new ConstantIntegerType(2),
					]),
					new ConstantStringType('foo'),
				],
				[2],
				[0],
			),
			[
				"array{b: 'foo'}",
				"array{a: 1, b: 'foo'}",
				"array{a: 2, b: 'foo'}",
			],
		];

		yield 'exceeding CALCULATE_SCALARS_LIMIT bails out' => [
			(static function (): ConstantArrayType {
				$keyTypes = [];
				$valueTypes = [];
				// 8 keys × 2 = 256 combinations, well above the 128 limit.
				for ($i = 0; $i < 8; $i++) {
					$keyTypes[] = new ConstantIntegerType($i);
					$valueTypes[] = new UnionType([
						new ConstantStringType('a'),
						new ConstantStringType('b'),
					]);
				}
				return new ConstantArrayType($keyTypes, $valueTypes);
			})(),
			[],
		];

		$never = new NeverType(true);
		$sealed = [$never, $never];

		yield 'sealed is finite' => [
			new ConstantArrayType(
				[new ConstantStringType('a')],
				[new ConstantStringType('foo')],
				unsealed: $sealed,
			),
			["array{a: 'foo'}"],
		];

		yield 'unsealed is finite' => [
			new ConstantArrayType(
				[new ConstantStringType('a')],
				[new ConstantStringType('foo')],
				unsealed: [new IntegerType(), new StringType()],
			),
			[],
		];
	}

	/**
	 * @param list<string> $expectedDescriptions
	 */
	#[DataProvider('dataGetFiniteTypes')]
	public function testGetFiniteTypes(ConstantArrayType $type, array $expectedDescriptions): void
	{
		$actual = array_map(
			static fn (Type $finite): string => $finite->describe(VerbosityLevel::precise()),
			$type->getFiniteTypes(),
		);

		$this->assertSame(
			$expectedDescriptions,
			$actual,
			sprintf('%s -> getFiniteTypes()', $type->describe(VerbosityLevel::precise())),
		);
	}

	public static function dataGeneralize(): iterable
	{
		$never = new NeverType(true);
		$sealedMarker = [$never, $never];

		yield 'sealed empty (legacy null unsealed)' => [
			new ConstantArrayType([], []),
			GeneralizePrecision::lessSpecific(),
			'array{}',
		];

		yield 'sealed empty (bleeding-edge NeverType marker)' => [
			new ConstantArrayType([], [], unsealed: $sealedMarker),
			GeneralizePrecision::lessSpecific(),
			'array{}',
		];

		yield 'sealed single explicit key' => [
			new ConstantArrayType(
				[new ConstantStringType('a')],
				[new ConstantIntegerType(1)],
				unsealed: $sealedMarker,
			),
			GeneralizePrecision::lessSpecific(),
			'non-empty-array<string, int>',
		];

		yield 'sealed two explicit keys, lessSpecific' => [
			new ConstantArrayType(
				[new ConstantStringType('a'), new ConstantStringType('b')],
				[new ConstantIntegerType(1), new ConstantStringType('x')],
				unsealed: $sealedMarker,
			),
			GeneralizePrecision::lessSpecific(),
			'non-empty-array<string, int|string>',
		];

		yield 'sealed two explicit keys, moreSpecific' => [
			new ConstantArrayType(
				[new ConstantStringType('a'), new ConstantStringType('b')],
				[new ConstantIntegerType(1), new ConstantStringType('x')],
				unsealed: $sealedMarker,
			),
			GeneralizePrecision::moreSpecific(),
			"non-empty-array<literal-string&lowercase-string&non-falsy-string, int|(literal-string&lowercase-string&non-falsy-string)>&hasOffsetValue('a', int)&hasOffsetValue('b', literal-string&lowercase-string&non-falsy-string)",
		];

		yield 'sealed list, lessSpecific' => [
			new ConstantArrayType(
				[new ConstantIntegerType(0), new ConstantIntegerType(1)],
				[new ConstantIntegerType(1), new ConstantIntegerType(2)],
				unsealed: $sealedMarker,
				isList: TrinaryLogic::createYes(),
			),
			GeneralizePrecision::lessSpecific(),
			'non-empty-list<int>',
		];

		yield 'sealed only-optional keys' => [
			new ConstantArrayType(
				[new ConstantStringType('a')],
				[new ConstantIntegerType(1)],
				optionalKeys: [0],
				unsealed: $sealedMarker,
			),
			GeneralizePrecision::lessSpecific(),
			'array<string, int>',
		];

		yield 'unsealed only, lessSpecific' => [
			new ConstantArrayType([], [], unsealed: [new IntegerType(), new ConstantStringType('foo')]),
			GeneralizePrecision::lessSpecific(),
			// No explicit keys but real unsealed extras — generalize
			// has to broaden the unsealed value (`'foo'` → `string`)
			// and degrade to a plain `ArrayType`. The size is uncertain
			// (zero-or-more extras), so no `NonEmptyArrayType`.
			'array<int, string>',
		];

		yield 'unsealed only with non-falsy-string key, moreSpecific' => [
			new ConstantArrayType([], [], unsealed: [new IntegerType(), new ConstantStringType('foo')]),
			GeneralizePrecision::moreSpecific(),
			'array<int, literal-string&lowercase-string&non-falsy-string>',
		];

		yield 'unsealed with explicit key, lessSpecific' => [
			new ConstantArrayType(
				[new ConstantStringType('a')],
				[new ConstantIntegerType(1)],
				unsealed: [new IntegerType(), new StringType()],
			),
			GeneralizePrecision::lessSpecific(),
			'non-empty-array<int|string, int|string>',
		];

		yield 'unsealed with explicit key, moreSpecific' => [
			new ConstantArrayType(
				[new ConstantStringType('a')],
				[new ConstantIntegerType(1)],
				unsealed: [new IntegerType(), new StringType()],
			),
			GeneralizePrecision::moreSpecific(),
			"non-empty-array<int|(literal-string&lowercase-string&non-falsy-string), int|string>&hasOffsetValue('a', int)",
		];

		yield 'unsealed with optional explicit key' => [
			new ConstantArrayType(
				[new ConstantStringType('a')],
				[new ConstantIntegerType(1)],
				optionalKeys: [0],
				unsealed: [new IntegerType(), new StringType()],
			),
			GeneralizePrecision::lessSpecific(),
			'array<int|string, int|string>',
		];

		yield 'templateArgument routes through traverse' => [
			new ConstantArrayType(
				[new ConstantStringType('a')],
				[new ConstantIntegerType(1)],
				unsealed: [new IntegerType(), new ConstantStringType('foo')],
			),
			GeneralizePrecision::templateArgument(),
			// `traverse` recurses into both explicit and unsealed values
			// (see commit history c. unsealed-aware traverse): `1` →
			// `int`, `'foo'` → `string`.
			'array{a: int, ...<int, string>}',
		];
	}

	#[DataProvider('dataGeneralize')]
	public function testGeneralize(ConstantArrayType $type, GeneralizePrecision $precision, string $expectedDescription): void
	{
		$this->assertSame(
			$expectedDescription,
			$type->generalize($precision)->describe(VerbosityLevel::precise()),
		);
	}

	public function testGeneralizeValuesAlsoBroadensUnsealedValue(): void
	{
		$type = new ConstantArrayType(
			[new ConstantStringType('a')],
			[new ConstantIntegerType(1)],
			unsealed: [new IntegerType(), new ConstantStringType('foo')],
		);

		$this->assertSame(
			'array{a: int, ...<int, string>}',
			$type->generalizeValues()->describe(VerbosityLevel::precise()),
		);
	}

	public function testTraverseSimultaneouslyVisitsUnsealedValue(): void
	{
		$left = new ConstantArrayType(
			[new ConstantStringType('a')],
			[new IntegerType()],
			unsealed: [new IntegerType(), new IntegerType()],
		);
		$right = new ConstantArrayType(
			[new ConstantStringType('a')],
			[new StringType()],
			unsealed: [new IntegerType(), new StringType()],
		);

		$visited = [];
		$result = $left->traverseSimultaneously($right, static function (Type $l, Type $r) use (&$visited): Type {
			$visited[] = [
				$l->describe(VerbosityLevel::precise()),
				$r->describe(VerbosityLevel::precise()),
			];
			return new MixedType();
		});

		$this->assertSame(
			[
				['int', 'string'],
				['int', 'string'],
			],
			$visited,
		);

		$this->assertSame(
			'array{a: mixed, ...<int, mixed>}',
			$result->describe(VerbosityLevel::precise()),
		);
	}

}
