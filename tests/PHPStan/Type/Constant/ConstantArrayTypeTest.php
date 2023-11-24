<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use Closure;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ArrayType;
use PHPStan\Type\CallableType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function array_map;
use function sprintf;

class ConstantArrayTypeTest extends PHPStanTestCase
{

	public function dataAccepts(): iterable
	{
		yield [
			new ConstantArrayType([], []),
			new ConstantArrayType([], []),
			TrinaryLogic::createYes(),
		];

		yield [
			new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
			new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
			TrinaryLogic::createYes(),
		];

		yield [
			new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
			new ConstantArrayType([], []),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantArrayType([], []),
			new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
			new ConstantArrayType([new ConstantIntegerType(7)], [new ConstantIntegerType(2)]),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
			new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(7)]),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
			new ArrayType(new IntegerType(), new IntegerType()),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
			new ArrayType(new StringType(), new StringType()),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
			new ArrayType(new MixedType(), new MixedType()),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
			new IterableType(new MixedType(), new IntegerType()),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
			new ConstantArrayType([], []),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantArrayType([new ConstantStringType('foo')], [new CallableType()]),
			new ConstantArrayType([], []),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantArrayType([new ConstantStringType('foo')], [new StringType()]),
			new ConstantArrayType([new ConstantStringType('foo'), new ConstantStringType('bar')], [new StringType(), new StringType()]),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantArrayType([new ConstantStringType('foo')], [new StringType()]),
			new ConstantArrayType([new ConstantStringType('bar')], [new StringType()]),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantArrayType([new ConstantStringType('foo')], [new StringType()]),
			new ConstantArrayType([new ConstantStringType('foo')], [new ConstantStringType('bar')]),
			TrinaryLogic::createYes(),
		];

		yield [
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
			TrinaryLogic::createNo(),
		];

		yield [
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
		];

		yield [
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
		];

		yield [
			new ConstantArrayType([
				new ConstantStringType('sorton'),
				new ConstantStringType('limit'),
			], [
				new StringType(),
				new IntegerType(),
			], 0, [0, 1]),
			new ConstantArrayType([
				new ConstantStringType('sorton'),
				new ConstantStringType('limit'),
			], [
				new ConstantStringType('test'),
				new ConstantStringType('true'),
			]),
			TrinaryLogic::createNo(),
		];

		yield [
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
		];

		yield [
			new ConstantArrayType([
				new ConstantStringType('sorton'),
				new ConstantStringType('limit'),
			], [
				new StringType(),
				new IntegerType(),
			], 0, [1]),
			new ConstantArrayType([
				new ConstantStringType('sorton'),
				new ConstantStringType('limit'),
			], [
				new ConstantStringType('test'),
				new ConstantStringType('true'),
			]),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantArrayType([
				new ConstantStringType('limit'),
			], [
				new IntegerType(),
			], 0, [0]),
			new ConstantArrayType([
				new ConstantStringType('limit'),
			], [
				new ConstantStringType('true'),
			]),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantArrayType([
				new ConstantStringType('limit'),
			], [
				new IntegerType(),
			], 0),
			new ConstantArrayType([
				new ConstantStringType('limit'),
			], [
				new ConstantStringType('true'),
			]),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantArrayType([
				new ConstantStringType('sorton'),
				new ConstantStringType('limit'),
			], [
				new StringType(),
				new StringType(),
			], 0, [0, 1]),
			new ConstantArrayType([
				new ConstantStringType('sorton'),
				new ConstantStringType('limit'),
			], [
				new ConstantStringType('test'),
				new ConstantStringType('true'),
			]),
			TrinaryLogic::createYes(),
		];

		yield [
			new ConstantArrayType([
				new ConstantStringType('name'),
				new ConstantStringType('color'),
			], [
				new StringType(),
				new StringType(),
			], 0, [0, 1]),
			new ConstantArrayType([
				new ConstantStringType('color'),
			], [
				new ConstantStringType('test'),
			]),
			TrinaryLogic::createYes(),
		];

		yield [
			new ConstantArrayType([
				new ConstantStringType('name'),
				new ConstantStringType('color'),
			], [
				new StringType(),
				new StringType(),
			], 0, [0, 1]),
			new ConstantArrayType([
				new ConstantStringType('sound'),
			], [
				new ConstantStringType('test'),
			]),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantArrayType([
				new ConstantStringType('foo'),
				new ConstantStringType('bar'),
			], [
				new StringType(),
				new StringType(),
			], 0, [0, 1]),
			new ConstantArrayType([
				new ConstantStringType('foo'),
				new ConstantStringType('bar'),
			], [
				new ConstantStringType('s'),
				new ConstantStringType('m'),
			], 0, [0, 1]),
			TrinaryLogic::createYes(),
		];

		yield [
			new ConstantArrayType([
				new ConstantStringType('sorton'),
				new ConstantStringType('limit'),
			], [
				new StringType(),
				new IntegerType(),
			], 0, [0, 1]),
			new ConstantArrayType([
				new ConstantStringType('sorton'),
				new ConstantStringType('limit'),
			], [
				new ConstantStringType('test'),
				new ConstantStringType('true'),
			]),
			TrinaryLogic::createNo(),
		];
	}

	/**
	 * @dataProvider dataAccepts
	 */
	public function testAccepts(Type $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->accepts($otherType, true);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> accepts(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	public function dataIsSuperTypeOf(): iterable
	{
		yield [
			new ConstantArrayType([], []),
			new ConstantArrayType([], []),
			TrinaryLogic::createYes(),
		];

		yield [
			new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
			new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
			TrinaryLogic::createYes(),
		];

		yield [
			new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
			new ConstantArrayType([], []),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantArrayType([], []),
			new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
			new ConstantArrayType([new ConstantIntegerType(7)], [new ConstantIntegerType(2)]),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
			new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(7)]),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
			new ArrayType(new IntegerType(), new IntegerType()),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
			new ArrayType(new StringType(), new StringType()),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantArrayType([new ConstantIntegerType(1)], [new ConstantIntegerType(2)]),
			new ArrayType(new MixedType(), new MixedType()),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new ConstantArrayType([], []),
			new IterableType(new MixedType(false), new MixedType(true)),
			TrinaryLogic::createMaybe(),
		];

		yield [
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
		];

		yield [
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
		];

		yield [
			new ConstantArrayType([
				new ConstantStringType('foo'),
				new ConstantStringType('bar'),
			], [
				new IntegerType(),
				new IntegerType(),
			], 2),
			new ConstantArrayType([], []),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantArrayType([
				new ConstantStringType('foo'),
				new ConstantStringType('bar'),
			], [
				new IntegerType(),
				new IntegerType(),
			], 2, [0]),
			new ConstantArrayType([], []),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantArrayType([
				new ConstantStringType('foo'),
				new ConstantStringType('bar'),
			], [
				new IntegerType(),
				new IntegerType(),
			], 2, [0, 1]),
			new ConstantArrayType([], []),
			TrinaryLogic::createYes(),
		];

		yield [
			new ConstantArrayType([
				new ConstantStringType('foo'),
				new ConstantStringType('bar'),
			], [
				new IntegerType(),
				new IntegerType(),
			], 2, [0, 1]),
			new ConstantArrayType([
				new ConstantStringType('foo'),
			], [
				new IntegerType(),
			], 1, [0]),
			TrinaryLogic::createYes(),
		];

		yield [
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
			], 2, [0, 1]),
			TrinaryLogic::createMaybe(),
		];

		yield [
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
			], 2, [0, 1]),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantArrayType([
				new ConstantStringType('foo'),
				new ConstantStringType('bar'),
			], [
				new IntegerType(),
				new IntegerType(),
			], 2, [0, 1]),
			new ConstantArrayType([
				new ConstantStringType('foo'),
			], [
				new StringType(),
			]),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantArrayType([], []),
			new ConstantArrayType([
				new ConstantStringType('foo'),
				new ConstantStringType('bar'),
			], [
				new IntegerType(),
				new IntegerType(),
			], 2, [0, 1]),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new ConstantArrayType([
				new ConstantStringType('foo'),
			], [
				new IntegerType(),
			], 1, [0]),
			new ConstantArrayType([
				new ConstantStringType('foo'),
			], [
				new IntegerType(),
			]),
			TrinaryLogic::createYes(),
		];

		yield [
			new ConstantArrayType([
				new ConstantStringType('foo'),
			], [
				new IntegerType(),
			]),
			new ConstantArrayType([
				new ConstantStringType('foo'),
			], [
				new IntegerType(),
			], 1, [0]),
			TrinaryLogic::createMaybe(),
		];
	}

	/**
	 * @dataProvider dataIsSuperTypeOf
	 */
	public function testIsSuperTypeOf(ConstantArrayType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	public function dataInferTemplateTypes(): array
	{
		$templateType = static fn (string $name): Type => TemplateTypeFactory::create(
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
	 * @dataProvider dataInferTemplateTypes
	 * @param array<string,string> $expectedTypes
	 */
	public function testResolveTemplateTypes(Type $received, Type $template, array $expectedTypes): void
	{
		$result = $template->inferTemplateTypes($received);

		$this->assertSame(
			$expectedTypes,
			array_map(static fn (Type $type): string => $type->describe(VerbosityLevel::precise()), $result->getTypes()),
		);
	}

	/**
	 * @dataProvider dataIsCallable
	 */
	public function testIsCallable(ConstantArrayType $type, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isCallable();
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isCallable()', $type->describe(VerbosityLevel::precise())),
		);
	}

	public function dataIsCallable(): iterable
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
	}

	public function dataValuesArray(): iterable
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
			], [20], [], false),
			new ConstantArrayType([
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
			], [
				new ConstantStringType('a'),
				new ConstantStringType('b'),
			], [2], [], true),
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
			], [15], [1, 3], false),
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
			], [3, 4, 5], [3, 4], true),
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
			], [15], [0, 2, 4], false),
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
			], [2, 3, 4, 5], [2, 3, 4], true),
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
			], [11, 12, 13], [1, 2], true),
			new ConstantArrayType([
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
				new ConstantIntegerType(2),
			], [
				new ConstantStringType('a'),
				new ConstantStringType('b'),
				new ConstantStringType('c'),
			], [1, 2, 3], [1, 2], true),
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
			], [11, 12, 13], [1, 2], false),
			new ConstantArrayType([
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
				new ConstantIntegerType(2),
			], [
				new ConstantStringType('a'),
				new UnionType([new ConstantStringType('b'), new ConstantStringType('c')]),
				new ConstantStringType('c'),
			], [1, 2, 3], [1, 2], true),
		];
	}

	/**
	 * @dataProvider dataValuesArray
	 */
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

}
