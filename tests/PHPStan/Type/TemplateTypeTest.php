<?php declare(strict_types = 1);

namespace PHPStan\Type;

use DateTime;
use DateTimeInterface;
use Exception;
use Iterator;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use stdClass;
use Throwable;
use Traversable;
use function array_map;
use function assert;
use function sprintf;

class TemplateTypeTest extends PHPStanTestCase
{

	public function dataAccepts(): array
	{
		$templateType = static fn (string $name, ?Type $bound, ?string $functionName = null): Type => TemplateTypeFactory::create(
			TemplateTypeScope::createWithFunction($functionName ?? '_'),
			$name,
			$bound,
			TemplateTypeVariance::createInvariant(),
		);

		return [
			0 => [
				$templateType('T', new ObjectType('DateTime')),
				new ObjectType('DateTime'),
				TrinaryLogic::createYes(),
				TrinaryLogic::createMaybe(),
			],
			1 => [
				$templateType('T', new ObjectType('DateTimeInterface')),
				new ObjectType('DateTime'),
				TrinaryLogic::createYes(),
				TrinaryLogic::createMaybe(),
			],
			2 => [
				$templateType('T', new ObjectType('DateTime')),
				$templateType('T', new ObjectType('DateTime')),
				TrinaryLogic::createYes(),
				TrinaryLogic::createYes(),
			],
			3 => [
				$templateType('T', new ObjectType('DateTime'), 'a'),
				$templateType('T', new ObjectType('DateTime'), 'b'),
				TrinaryLogic::createMaybe(),
				TrinaryLogic::createMaybe(),
			],
			4 => [
				$templateType('T', null),
				new MixedType(),
				TrinaryLogic::createYes(),
				TrinaryLogic::createYes(),
			],
			5 => [
				$templateType('T', null),
				new IntersectionType([
					new ObjectWithoutClassType(),
					$templateType('T', null),
				]),
				TrinaryLogic::createYes(),
				TrinaryLogic::createYes(),
			],
			'accepts itself with a sub-type union bound' => [
				$templateType('T', new UnionType([
					new IntegerType(),
					new StringType(),
				])),
				$templateType('T', new IntegerType()),
				TrinaryLogic::createYes(),
				TrinaryLogic::createYes(),
			],
			'accepts itself with a sub-type object bound' => [
				$templateType('T', new ObjectWithoutClassType()),
				$templateType('T', new ObjectType('stdClass')),
				TrinaryLogic::createYes(),
				TrinaryLogic::createYes(),
			],
			'does not accept ObjectType that is a super type of bound' => [
				$templateType('T', new ObjectType(Iterator::class)),
				new ObjectType(Traversable::class),
				TrinaryLogic::createNo(),
				TrinaryLogic::createNo(),
			],
		];
	}

	/**
	 * @dataProvider dataAccepts
	 */
	public function testAccepts(
		Type $type,
		Type $otherType,
		TrinaryLogic $expectedAccept,
		TrinaryLogic $expectedAcceptArg,
	): void
	{
		assert($type instanceof TemplateType);

		$actualResult = $type->accepts($otherType, true);
		$this->assertSame(
			$expectedAccept->describe(),
			$actualResult->describe(),
			sprintf('%s -> accepts(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);

		$type = $type->toArgument();

		$actualResult = $type->accepts($otherType, true);
		$this->assertSame(
			$expectedAcceptArg->describe(),
			$actualResult->describe(),
			sprintf('%s -> accepts(%s) (Argument strategy)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	public function dataIsSuperTypeOf(): array
	{
		$templateType = static fn (string $name, ?Type $bound, ?string $functionName = null): Type => TemplateTypeFactory::create(
			TemplateTypeScope::createWithFunction($functionName ?? '_'),
			$name,
			$bound,
			TemplateTypeVariance::createInvariant(),
		);

		return [
			0 => [
				$templateType('T', new ObjectType('DateTime')),
				new ObjectType('DateTime'),
				TrinaryLogic::createMaybe(), // (T of DateTime) isSuperTypeTo DateTime
				TrinaryLogic::createYes(), // DateTime isSuperTypeTo (T of DateTime)
			],
			1 => [
				$templateType('T', new ObjectType('DateTime')),
				$templateType('T', new ObjectType('DateTime')),
				TrinaryLogic::createYes(),
				TrinaryLogic::createYes(),
			],
			2 => [
				$templateType('T', new ObjectType('DateTime'), 'a'),
				$templateType('T', new ObjectType('DateTime'), 'b'),
				TrinaryLogic::createMaybe(),
				TrinaryLogic::createMaybe(),
			],
			3 => [
				$templateType('T', new ObjectType('DateTime')),
				new StringType(),
				TrinaryLogic::createNo(), // (T of DateTime) isSuperTypeTo string
				TrinaryLogic::createNo(), // string isSuperTypeTo (T of DateTime)
			],
			4 => [
				$templateType('T', new ObjectType('DateTime')),
				new ObjectType('DateTimeInterface'),
				TrinaryLogic::createMaybe(), // (T of DateTime) isSuperTypeTo DateTimeInterface
				TrinaryLogic::createYes(), // DateTimeInterface isSuperTypeTo (T of DateTime)
			],
			5 => [
				$templateType('T', new ObjectType('DateTime')),
				$templateType('T', new ObjectType('DateTimeInterface')),
				TrinaryLogic::createMaybe(), // (T of DateTime) isSuperTypeTo (T of DateTimeInterface)
				TrinaryLogic::createYes(), // (T of DateTimeInterface) isSuperTypeTo (T of DateTime)
			],
			6 => [
				$templateType('T', new ObjectType('DateTime')),
				new NullType(),
				TrinaryLogic::createNo(), // (T of DateTime) isSuperTypeTo null
				TrinaryLogic::createNo(), // null isSuperTypeTo (T of DateTime)
			],
			7 => [
				$templateType('T', new ObjectType('DateTime')),
				new UnionType([
					new NullType(),
					new ObjectType('DateTime'),
				]),
				TrinaryLogic::createMaybe(), // (T of DateTime) isSuperTypeTo (DateTime|null)
				TrinaryLogic::createYes(), // (DateTime|null) isSuperTypeTo (T of DateTime)
			],
			8 => [
				$templateType('T', new ObjectType('DateTimeInterface')),
				new UnionType([
					new NullType(),
					new ObjectType('DateTime'),
				]),
				TrinaryLogic::createMaybe(), // (T of DateTimeInterface) isSuperTypeTo (DateTime|null)
				TrinaryLogic::createMaybe(), // (DateTime|null) isSuperTypeTo (T of DateTimeInterface)
			],
			9 => [
				$templateType('T', new ObjectType('DateTime')),
				new UnionType([
					new NullType(),
					new ObjectType('DateTimeInterface'),
				]),
				TrinaryLogic::createMaybe(), // (T of DateTime) isSuperTypeTo (DateTimeInterface|null)
				TrinaryLogic::createYes(), // (DateTimeInterface|null) isSuperTypeTo (T of DateTime)
			],
			10 => [
				$templateType('T', null),
				new MixedType(true),
				TrinaryLogic::createMaybe(), // T isSuperTypeTo mixed
				TrinaryLogic::createYes(), // mixed isSuperTypeTo T
			],
			11 => [
				$templateType('T', null),
				new ObjectType(DateTimeInterface::class),
				TrinaryLogic::createMaybe(), // T isSuperTypeTo DateTimeInterface
				TrinaryLogic::createMaybe(), // DateTimeInterface isSuperTypeTo T
			],
			12 => [
				$templateType('T', new ObjectWithoutClassType()),
				new ObjectWithoutClassType(),
				TrinaryLogic::createMaybe(),
				TrinaryLogic::createYes(),
			],
			13 => [
				$templateType('T', new ObjectWithoutClassType()),
				new ObjectType(DateTimeInterface::class),
				TrinaryLogic::createMaybe(),
				TrinaryLogic::createMaybe(),
			],
			14 => [
				$templateType('T', new ObjectType(Throwable::class)),
				new ObjectType(Exception::class),
				TrinaryLogic::createMaybe(),
				TrinaryLogic::createMaybe(),
			],
			15 => [
				$templateType('T', new MixedType(true)),
				$templateType('U', new UnionType([new IntegerType(), new StringType()])),
				TrinaryLogic::createMaybe(),
				TrinaryLogic::createMaybe(),
			],
			16 => [
				$templateType('T', new MixedType(true)),
				$templateType('U', new BenevolentUnionType([new IntegerType(), new StringType()])),
				TrinaryLogic::createMaybe(),
				TrinaryLogic::createMaybe(),
			],
			17 => [
				$templateType('T', new ObjectType(stdClass::class)),
				$templateType('U', new BenevolentUnionType([new IntegerType(), new StringType()])),
				TrinaryLogic::createNo(),
				TrinaryLogic::createNo(),
			],
			18 => [
				$templateType('T', new BenevolentUnionType([new IntegerType(), new StringType()])),
				$templateType('T', new BenevolentUnionType([new IntegerType(), new StringType()])),
				TrinaryLogic::createYes(),
				TrinaryLogic::createYes(),
			],
			19 => [
				$templateType('T', new UnionType([new IntegerType(), new StringType()])),
				$templateType('T', new UnionType([new IntegerType(), new StringType()])),
				TrinaryLogic::createYes(),
				TrinaryLogic::createYes(),
			],
			20 => [
				$templateType('T', new UnionType([new IntegerType(), new StringType()])),
				$templateType('T', new BenevolentUnionType([new IntegerType(), new StringType()])),
				TrinaryLogic::createYes(),
				TrinaryLogic::createYes(),
			],
			21 => [
				$templateType('T', new UnionType([new IntegerType(), new StringType()])),
				$templateType('T', new IntegerType()),
				TrinaryLogic::createYes(),
				TrinaryLogic::createMaybe(),
			],
			22 => [
				$templateType('T', new BenevolentUnionType([new IntegerType(), new StringType()])),
				new UnionType([new BooleanType(), new FloatType(), new IntegerType(), new StringType(), new NullType()]),
				TrinaryLogic::createMaybe(),
				TrinaryLogic::createYes(),
			],
		];
	}

	/**
	 * @dataProvider dataIsSuperTypeOf
	 */
	public function testIsSuperTypeOf(
		Type $type,
		Type $otherType,
		TrinaryLogic $expectedIsSuperType,
		TrinaryLogic $expectedIsSuperTypeInverse,
	): void
	{
		assert($type instanceof TemplateType);

		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedIsSuperType->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);

		$actualResult = $otherType->isSuperTypeOf($type);
		$this->assertSame(
			$expectedIsSuperTypeInverse->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $otherType->describe(VerbosityLevel::precise()), $type->describe(VerbosityLevel::precise())),
		);
	}

	/** @return array<string,array{Type,Type,array<string,string>}> */
	public function dataInferTemplateTypes(): array
	{
		$templateType = static fn (string $name, ?Type $bound = null, ?string $functionName = null): Type => TemplateTypeFactory::create(
			TemplateTypeScope::createWithFunction($functionName ?? '_'),
			$name,
			$bound,
			TemplateTypeVariance::createInvariant(),
		);

		return [
			'simple' => [
				new IntegerType(),
				$templateType('T'),
				['T' => 'int'],
			],
			'object' => [
				new ObjectType(DateTime::class),
				$templateType('T'),
				['T' => 'DateTime'],
			],
			'object with bound' => [
				new ObjectType(DateTime::class),
				$templateType('T', new ObjectType(DateTimeInterface::class)),
				['T' => 'DateTime'],
			],
			'wrong object with bound' => [
				new ObjectType(stdClass::class),
				$templateType('T', new ObjectType(DateTimeInterface::class)),
				[],
			],
			'template type' => [
				TemplateTypeHelper::toArgument($templateType('T', new ObjectType(DateTimeInterface::class))),
				$templateType('T', new ObjectType(DateTimeInterface::class)),
				['T' => 'T of DateTimeInterface (function _(), argument)'],
			],
			'foreign template type' => [
				TemplateTypeHelper::toArgument($templateType('T', new ObjectType(DateTimeInterface::class), 'a')),
				$templateType('T', new ObjectType(DateTimeInterface::class), 'b'),
				['T' => 'T of DateTimeInterface (function a(), argument)'],
			],
			'foreign template type, incompatible bound' => [
				TemplateTypeHelper::toArgument($templateType('T', new ObjectType(stdClass::class), 'a')),
				$templateType('T', new ObjectType(DateTime::class), 'b'),
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

}
