<?php declare(strict_types = 1);

namespace PHPStan\Type;

use Closure;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\HasMethodType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use function array_map;
use function sprintf;

class CallableTypeTest extends PHPStanTestCase
{

	public function dataIsSuperTypeOf(): array
	{
		return [
			[
				new CallableType(),
				new ClosureType([], new MixedType(), false),
				TrinaryLogic::createYes(),
			],
			[
				new CallableType(),
				new HasMethodType('format'),
				TrinaryLogic::createMaybe(),
			],
			[
				new CallableType(),
				new HasMethodType('__invoke'),
				TrinaryLogic::createYes(),
			],
			[
				new CallableType([new NativeParameterReflection('foo', false, new MixedType(), PassedByReference::createNo(), false, null)], new MixedType(), false),
				new CallableType([new NativeParameterReflection('foo', false, new IntegerType(), PassedByReference::createNo(), false, null)], new MixedType(), false),
				TrinaryLogic::createMaybe(),
			],
			[
				new CallableType([new NativeParameterReflection('foo', false, new IntegerType(), PassedByReference::createNo(), false, null)], new MixedType(), false),
				new CallableType([new NativeParameterReflection('foo', false, new MixedType(), PassedByReference::createNo(), false, null)], new MixedType(), false),
				TrinaryLogic::createYes(),
			],
			[
				new CallableType([
					new NativeParameterReflection('foo', false, new StringType(), PassedByReference::createNo(), false, null),
					new NativeParameterReflection('bar', false, new StringType(), PassedByReference::createNo(), false, null),
				], new MixedType(), false),
				new CallableType([new NativeParameterReflection('foo', false, new StringType(), PassedByReference::createNo(), false, null)], new MixedType(), false),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	/**
	 * @dataProvider dataIsSuperTypeOf
	 */
	public function testIsSuperTypeOf(CallableType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	public function dataIsSubTypeOf(): array
	{
		return [
			[
				new CallableType(),
				new CallableType(),
				TrinaryLogic::createYes(),
			],
			[
				new CallableType(),
				new StringType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new CallableType(),
				new IntegerType(),
				TrinaryLogic::createNo(),
			],
			[
				new CallableType(),
				new UnionType([new CallableType(), new NullType()]),
				TrinaryLogic::createYes(),
			],
			[
				new CallableType(),
				new UnionType([new StringType(), new NullType()]),
				TrinaryLogic::createMaybe(),
			],
			[
				new CallableType(),
				new UnionType([new IntegerType(), new NullType()]),
				TrinaryLogic::createNo(),
			],
			[
				new CallableType(),
				new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()]),
				TrinaryLogic::createMaybe(),
			],
			[
				new CallableType(),
				new IntegerType(),
				TrinaryLogic::createNo(),
			],
			[
				new CallableType(),
				new IntersectionType([new CallableType(), new StringType()]),
				TrinaryLogic::createMaybe(),
			],
			[
				new CallableType(),
				new IntersectionType([new CallableType(), new ObjectType('Unknown')]),
				TrinaryLogic::createMaybe(),
			],
			[
				new CallableType(),
				new HasMethodType('foo'),
				TrinaryLogic::createMaybe(),
			],
			[
				new CallableType(),
				new HasMethodType('__invoke'),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	/**
	 * @dataProvider dataIsSubTypeOf
	 */
	public function testIsSubTypeOf(CallableType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSubTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSubTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	/**
	 * @dataProvider dataIsSubTypeOf
	 */
	public function testIsSubTypeOfInversed(CallableType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $otherType->isSuperTypeOf($type);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $otherType->describe(VerbosityLevel::precise()), $type->describe(VerbosityLevel::precise())),
		);
	}

	public function dataInferTemplateTypes(): array
	{
		$param = static fn (Type $type): NativeParameterReflection => new NativeParameterReflection(
			'',
			false,
			$type,
			PassedByReference::createNo(),
			false,
			null,
		);

		$templateType = static function (string $name): Type {
			/** @var non-empty-string $name */
			return TemplateTypeFactory::create(
				TemplateTypeScope::createWithFunction('a'),
				$name,
				new MixedType(),
				TemplateTypeVariance::createInvariant(),
			);
		};

		return [
			'template param' => [
				new CallableType(
					[
						$param(new StringType()),
					],
					new IntegerType(),
				),
				new CallableType(
					[
						$param($templateType('T')),
					],
					new IntegerType(),
				),
				['T' => 'string'],
			],
			'template return' => [
				new CallableType(
					[
						$param(new StringType()),
					],
					new IntegerType(),
				),
				new CallableType(
					[
						$param(new StringType()),
					],
					$templateType('T'),
				),
				['T' => 'int'],
			],
			'multiple templates' => [
				new CallableType(
					[
						$param(new StringType()),
						$param(new ObjectType('DateTime')),
					],
					new IntegerType(),
				),
				new CallableType(
					[
						$param(new StringType()),
						$param($templateType('A')),
					],
					$templateType('B'),
				),
				['B' => 'int', 'A' => 'DateTime'],
			],
			'receive union' => [
				new UnionType([
					new NullType(),
					new CallableType(
						[
							$param(new StringType()),
							$param(new ObjectType('DateTime')),
						],
						new IntegerType(),
					),
				]),
				new CallableType(
					[
						$param(new StringType()),
						$param($templateType('A')),
					],
					$templateType('B'),
				),
				['B' => 'int', 'A' => 'DateTime'],
			],
			'receive non-accepted' => [
				new NullType(),
				new CallableType(
					[
						$param(new StringType()),
						$param($templateType('A')),
					],
					$templateType('B'),
				),
				[],
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

	public function dataAccepts(): array
	{
		return [
			[
				new CallableType([new NativeParameterReflection('foo', false, new MixedType(), PassedByReference::createNo(), false, null)], new MixedType(), false),
				new CallableType([new NativeParameterReflection('foo', false, new IntegerType(), PassedByReference::createNo(), false, null)], new MixedType(), false),
				TrinaryLogic::createYes(),
			],
			[
				new CallableType([new NativeParameterReflection('foo', false, new IntegerType(), PassedByReference::createNo(), false, null)], new MixedType(), false),
				new CallableType([new NativeParameterReflection('foo', false, new MixedType(), PassedByReference::createNo(), false, null)], new MixedType(), false),
				TrinaryLogic::createYes(),
			],
			[
				new CallableType([
					new NativeParameterReflection('foo', false, new IntegerType(), PassedByReference::createNo(), false, null),
				], new MixedType(), false),
				new CallableType([
					new NativeParameterReflection('foo', false, new IntegerType(), PassedByReference::createNo(), false, null),
					new NativeParameterReflection('bar', true, new IntegerType(), PassedByReference::createNo(), false, null),
					new NativeParameterReflection('bar', true, new IntegerType(), PassedByReference::createNo(), false, null),
				], new MixedType(), false),
				TrinaryLogic::createYes(),
			],
			[
				new CallableType([
					new NativeParameterReflection('foo', false, new IntegerType(), PassedByReference::createNo(), false, null),
					new NativeParameterReflection('bar', false, new StringType(), PassedByReference::createNo(), false, null),
				], new MixedType(), false),
				new CallableType([
					new NativeParameterReflection('foo', false, new IntegerType(), PassedByReference::createNo(), false, null),
					new NativeParameterReflection('bar', true, new IntegerType(), PassedByReference::createNo(), false, null),
				], new MixedType(), false),
				TrinaryLogic::createNo(),
			],
			[
				new CallableType([], new MixedType(), false),
				new CallableType([], new MixedType(), false),
				TrinaryLogic::createYes(),
			],
			[
				new CallableType([], new IntegerType(), false),
				new CallableType([], new MixedType(), false),
				TrinaryLogic::createYes(),
			],
			[
				new CallableType([], new MixedType(), false),
				new CallableType([], new IntegerType(), false),
				TrinaryLogic::createYes(),
			],
			[
				new CallableType(),
				TypeCombinator::intersect(new ArrayType(new MixedType(), new MixedType()), new CallableType()),
				TrinaryLogic::createYes(),
			],
			[
				new CallableType(),
				new ConstantArrayType([
					new ConstantIntegerType(0),
					new ConstantIntegerType(1),
				], [
					new GenericClassStringType(new ObjectType(Closure::class)),
					new ConstantStringType('bind'),
				]),
				TrinaryLogic::createYes(),
			],
			[
				new CallableType([
					new NativeParameterReflection('foo', false, new StringType(), PassedByReference::createNo(), false, null),
					new NativeParameterReflection('bar', false, new StringType(), PassedByReference::createNo(), false, null),
				], new MixedType(), false),
				new CallableType([new NativeParameterReflection('foo', false, new StringType(), PassedByReference::createNo(), false, null)], new MixedType(), false),
				TrinaryLogic::createYes(),
			],
			[
				new CallableType(null, null, true, null, null, [], TrinaryLogic::createNo()),
				new CallableType(null, null, true, null, null, [], TrinaryLogic::createNo()),
				TrinaryLogic::createYes(),
			],
			[
				new CallableType(null, null, true, null, null, [], TrinaryLogic::createNo()),
				new CallableType(null, null, true, null, null, [], TrinaryLogic::createYes()),
				TrinaryLogic::createYes(),
			],
			[
				new CallableType(null, null, true, null, null, [], TrinaryLogic::createYes()),
				new CallableType(null, null, true, null, null, [], TrinaryLogic::createYes()),
				TrinaryLogic::createYes(),
			],
			[
				new CallableType(null, null, true, null, null, [], TrinaryLogic::createYes()),
				new CallableType(null, null, true, null, null, [], TrinaryLogic::createNo()),
				TrinaryLogic::createNo(),
			],
			[
				new CallableType([], new VoidType(), false, null, null, [], TrinaryLogic::createNo()),
				new CallableType([], new VoidType(), false, null, null, [], TrinaryLogic::createNo()),
				TrinaryLogic::createYes(),
			],
			[
				new CallableType([], new VoidType(), false, null, null, [], TrinaryLogic::createNo()),
				new CallableType([], new VoidType(), false, null, null, [], TrinaryLogic::createYes()),
				TrinaryLogic::createNo(),
			],
			[
				new CallableType([], new VoidType(), false, null, null, [], TrinaryLogic::createMaybe()),
				new CallableType([], new VoidType(), false, null, null, [], TrinaryLogic::createYes()),
				TrinaryLogic::createYes(),
			],
			[
				new CallableType([], new VoidType(), false, null, null, [], TrinaryLogic::createMaybe()),
				new CallableType([], new VoidType(), false, null, null, [], TrinaryLogic::createNo()),
				TrinaryLogic::createYes(),
			],
			[
				new CallableType([], new VoidType(), false, null, null, [], TrinaryLogic::createMaybe()),
				new CallableType([], new VoidType(), false, null, null, [], TrinaryLogic::createMaybe()),
				TrinaryLogic::createYes(),
			],
			[
				new CallableType([], new VoidType(), false, null, null, [], TrinaryLogic::createYes()),
				new CallableType([], new VoidType(), false, null, null, [], TrinaryLogic::createYes()),
				TrinaryLogic::createYes(),
			],
			[
				new CallableType([], new VoidType(), false, null, null, [], TrinaryLogic::createYes()),
				new CallableType([], new VoidType(), false, null, null, [], TrinaryLogic::createNo()),
				TrinaryLogic::createNo(),
			],
		];
	}

	/**
	 * @dataProvider dataAccepts
	 */
	public function testAccepts(
		CallableType $type,
		Type $acceptedType,
		TrinaryLogic $expectedResult,
	): void
	{
		$this->assertSame(
			$expectedResult->describe(),
			$type->accepts($acceptedType, true)->describe(),
			sprintf('%s -> accepts(%s)', $type->describe(VerbosityLevel::precise()), $acceptedType->describe(VerbosityLevel::precise())),
		);
	}

}
