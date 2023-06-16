<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use DateTime;
use DateTimeInterface;
use Exception;
use Iterator;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Test\A;
use PHPStan\Type\Test\B;
use PHPStan\Type\Test\C;
use PHPStan\Type\Test\D;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use ReflectionClass;
use stdClass;
use Traversable;
use function array_map;
use function sprintf;

class GenericObjectTypeTest extends PHPStanTestCase
{

	public function dataIsSuperTypeOf(): array
	{
		return [
			'equal type' => [
				new GenericObjectType(A\A::class, [new ObjectType('DateTime')]),
				new GenericObjectType(A\A::class, [new ObjectType('DateTime')]),
				TrinaryLogic::createYes(),
			],
			'sub-class with static @extends with same type args' => [
				new GenericObjectType(A\A::class, [new ObjectType('DateTime')]),
				new ObjectType(A\AOfDateTime::class),
				TrinaryLogic::createYes(),
			],
			'sub-class with @extends with same type args' => [
				new GenericObjectType(A\A::class, [new ObjectType('DateTime')]),
				new GenericObjectType(A\SubA::class, [new ObjectType('DateTime')]),
				TrinaryLogic::createYes(),
			],
			'same class, different type args' => [
				new GenericObjectType(A\A::class, [new ObjectType('DateTimeInterface')]),
				new GenericObjectType(A\A::class, [new ObjectType('DateTime')]),
				TrinaryLogic::createNo(),
			],
			'same class, one naked' => [
				new GenericObjectType(A\A::class, [new ObjectType('DateTimeInterface')]),
				new ObjectType(A\A::class),
				TrinaryLogic::createMaybe(),
			],
			'implementation with @extends with same type args' => [
				new GenericObjectType(B\I::class, [new ObjectType('DateTime')]),
				new GenericObjectType(B\IImpl::class, [new ObjectType('DateTime')]),
				TrinaryLogic::createYes(),
			],
			'implementation with @extends with different type args' => [
				new GenericObjectType(B\I::class, [new ObjectType('DateTimeInterface')]),
				new GenericObjectType(B\IImpl::class, [new ObjectType('DateTime')]),
				TrinaryLogic::createNo(),
			],
			'invariant with equals types' => [
				new GenericObjectType(C\Invariant::class, [new ObjectType('DateTime')]),
				new GenericObjectType(C\Invariant::class, [new ObjectType('DateTime')]),
				TrinaryLogic::createYes(),
			],
			'invariant with sub type' => [
				new GenericObjectType(C\Invariant::class, [new ObjectType('DateTimeInterface')]),
				new GenericObjectType(C\Invariant::class, [new ObjectType('DateTime')]),
				TrinaryLogic::createNo(),
			],
			'invariant with super type' => [
				new GenericObjectType(C\Invariant::class, [new ObjectType('DateTime')]),
				new GenericObjectType(C\Invariant::class, [new ObjectType('DateTimeInterface')]),
				TrinaryLogic::createNo(),
			],
			'covariant with equals types' => [
				new GenericObjectType(C\Covariant::class, [new ObjectType('DateTime')]),
				new GenericObjectType(C\Covariant::class, [new ObjectType('DateTime')]),
				TrinaryLogic::createYes(),
			],
			'covariant with sub type' => [
				new GenericObjectType(C\Covariant::class, [new ObjectType('DateTimeInterface')]),
				new GenericObjectType(C\Covariant::class, [new ObjectType('DateTime')]),
				TrinaryLogic::createYes(),
			],
			'covariant with super type' => [
				new GenericObjectType(C\Covariant::class, [new ObjectType('DateTime')]),
				new GenericObjectType(C\Covariant::class, [new ObjectType('DateTimeInterface')]),
				TrinaryLogic::createMaybe(),
			],
			'contravariant with equal types' => [
				new GenericObjectType(C\Contravariant::class, [new ObjectType('DateTime')]),
				new GenericObjectType(C\Contravariant::class, [new ObjectType('DateTime')]),
				TrinaryLogic::createYes(),
			],
			'contravariant with sub type' => [
				new GenericObjectType(C\Contravariant::class, [new ObjectType('DateTimeInterface')]),
				new GenericObjectType(C\Contravariant::class, [new ObjectType('DateTime')]),
				TrinaryLogic::createMaybe(),
			],
			'contravariant with super type' => [
				new GenericObjectType(C\Contravariant::class, [new ObjectType('DateTime')]),
				new GenericObjectType(C\Contravariant::class, [new ObjectType('DateTimeInterface')]),
				TrinaryLogic::createYes(),
			],
			[
				new ObjectType(ReflectionClass::class),
				new GenericObjectType(ReflectionClass::class, [
					new ObjectType(stdClass::class),
				]),
				TrinaryLogic::createYes(),
			],
			[
				new GenericObjectType(ReflectionClass::class, [
					new ObjectType(stdClass::class),
				]),
				new ObjectType(ReflectionClass::class),
				TrinaryLogic::createMaybe(),
			],
			[
				new GenericObjectType(ReflectionClass::class, [
					new ObjectWithoutClassType(),
				]),
				new GenericObjectType(ReflectionClass::class, [
					new ObjectType(stdClass::class),
				]),
				TrinaryLogic::createYes(),
			],
			[
				new GenericObjectType(ReflectionClass::class, [
					new ObjectType(stdClass::class),
				]),
				new GenericObjectType(ReflectionClass::class, [
					new ObjectWithoutClassType(),
				]),
				TrinaryLogic::createMaybe(),
			],
			[
				new GenericObjectType(ReflectionClass::class, [
					new ObjectType(Exception::class),
				]),
				new GenericObjectType(ReflectionClass::class, [
					new ObjectType(stdClass::class),
				]),
				TrinaryLogic::createNo(),
			],
		];
	}

	/**
	 * @dataProvider dataIsSuperTypeOf
	 */
	public function testIsSuperTypeOf(Type $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	public function dataAccepts(): array
	{
		return [
			'equal type' => [
				new GenericObjectType(A\A::class, [new ObjectType('DateTime')]),
				new GenericObjectType(A\A::class, [new ObjectType('DateTime')]),
				TrinaryLogic::createYes(),
			],
			'sub-class with static @extends with same type args' => [
				new GenericObjectType(A\A::class, [new ObjectType('DateTime')]),
				new ObjectType(A\AOfDateTime::class),
				TrinaryLogic::createYes(),
			],
			'sub-class with @extends with same type args' => [
				new GenericObjectType(A\A::class, [new ObjectType('DateTime')]),
				new GenericObjectType(A\SubA::class, [new ObjectType('DateTime')]),
				TrinaryLogic::createYes(),
			],
			'same class, different type args' => [
				new GenericObjectType(A\A::class, [new ObjectType('DateTimeInterface')]),
				new GenericObjectType(A\A::class, [new ObjectType('DateTime')]),
				TrinaryLogic::createNo(),
			],
			'same class, one naked' => [
				new GenericObjectType(A\A::class, [new ObjectType('DateTimeInterface')]),
				new ObjectType(A\A::class),
				TrinaryLogic::createYes(),
			],
			'implementation with @extends with same type args' => [
				new GenericObjectType(B\I::class, [new ObjectType('DateTime')]),
				new GenericObjectType(B\IImpl::class, [new ObjectType('DateTime')]),
				TrinaryLogic::createYes(),
			],
			'implementation with @extends with different type args' => [
				new GenericObjectType(B\I::class, [new ObjectType('DateTimeInterface')]),
				new GenericObjectType(B\IImpl::class, [new ObjectType('DateTime')]),
				TrinaryLogic::createNo(),
			],
			'generic object accepts normal object of same type' => [
				new GenericObjectType(Traversable::class, [new MixedType(true), new ObjectType('DateTimeInterface')]),
				new ObjectType(Traversable::class),
				TrinaryLogic::createYes(),
			],
			[
				new GenericObjectType(Iterator::class, [new MixedType(true), new MixedType(true)]),
				new ObjectType(Iterator::class),
				TrinaryLogic::createYes(),
			],
			[
				new GenericObjectType(Iterator::class, [new MixedType(true), new MixedType(true)]),
				new IntersectionType([new ObjectType(Iterator::class), new ObjectType(DateTimeInterface::class)]),
				TrinaryLogic::createYes(),
			],
		];
	}

	/**
	 * @dataProvider dataAccepts
	 */
	public function testAccepts(
		Type $acceptingType,
		Type $acceptedType,
		TrinaryLogic $expectedResult,
	): void
	{
		$actualResult = $acceptingType->accepts($acceptedType, true);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> accepts(%s)', $acceptingType->describe(VerbosityLevel::precise()), $acceptedType->describe(VerbosityLevel::precise())),
		);
	}

	/** @return array<string,array{Type,Type,array<string,string>}> */
	public function dataInferTemplateTypes(): array
	{
		$templateType = static fn (string $name, ?Type $bound = null): Type => TemplateTypeFactory::create(
			TemplateTypeScope::createWithFunction('a'),
			$name,
			$bound ?? new MixedType(),
			TemplateTypeVariance::createInvariant(),
		);

		return [
			'simple' => [
				new GenericObjectType(A\A::class, [
					new ObjectType(DateTime::class),
				]),
				new GenericObjectType(A\A::class, [
					$templateType('T'),
				]),
				['T' => 'DateTime'],
			],
			'two types' => [
				new GenericObjectType(A\A2::class, [
					new ObjectType(DateTime::class),
					new IntegerType(),
				]),
				new GenericObjectType(A\A2::class, [
					$templateType('K'),
					$templateType('V'),
				]),
				['K' => 'DateTime', 'V' => 'int'],
			],
			'union' => [
				new UnionType([
					new GenericObjectType(A\A2::class, [
						new ObjectType(DateTime::class),
						new IntegerType(),
					]),
					new GenericObjectType(A\A2::class, [
						new IntegerType(),
						new ObjectType(DateTime::class),
					]),
				]),
				new GenericObjectType(A\A2::class, [
					$templateType('K'),
					$templateType('V'),
				]),
				['K' => 'DateTime|int', 'V' => 'DateTime|int'],
			],
			'nested' => [
				new GenericObjectType(A\A::class, [
					new GenericObjectType(A\A2::class, [
						new ObjectType(DateTime::class),
						new IntegerType(),
					]),
				]),
				new GenericObjectType(A\A::class, [
					new GenericObjectType(A\A2::class, [
						$templateType('K'),
						$templateType('V'),
					]),
				]),
				['K' => 'DateTime', 'V' => 'int'],
			],
			'missing type' => [
				new GenericObjectType(A\A2::class, [
					new ObjectType(DateTime::class),
				]),
				new GenericObjectType(A\A2::class, [
					$templateType('K', new ObjectType(DateTimeInterface::class)),
					$templateType('V', new ObjectType(DateTimeInterface::class)),
				]),
				['K' => 'DateTime'],
			],
			'wrong class' => [
				new GenericObjectType(B\I::class, [
					new ObjectType(DateTime::class),
				]),
				new GenericObjectType(A\A::class, [
					$templateType('T', new ObjectType(DateTimeInterface::class)),
				]),
				[],
			],
			'wrong type' => [
				new IntegerType(),
				new GenericObjectType(A\A::class, [
					$templateType('T', new ObjectType(DateTimeInterface::class)),
				]),
				[],
			],
			'sub type' => [
				new ObjectType(A\AOfDateTime::class),
				new GenericObjectType(A\A::class, [
					$templateType('T'),
				]),
				['T' => 'DateTime'],
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

	/** @return array<array{TemplateTypeVariance,Type,bool,array<TemplateTypeReference>}> */
	public function dataGetReferencedTypeArguments(): array
	{
		$templateType = static fn (string $name, ?Type $bound = null): TemplateType => TemplateTypeFactory::create(
			TemplateTypeScope::createWithFunction('a'),
			$name,
			$bound ?? new MixedType(),
			TemplateTypeVariance::createInvariant(),
		);

		return [
			'param: Invariant<T>' => [
				TemplateTypeVariance::createContravariant(),
				new GenericObjectType(D\Invariant::class, [
					$templateType('T'),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createInvariant(),
					),
				],
			],
			'param: Out<T>' => [
				TemplateTypeVariance::createContravariant(),
				new GenericObjectType(D\Out::class, [
					$templateType('T'),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createContravariant(),
					),
				],
			],
			'param: Out<Out<T>>' => [
				TemplateTypeVariance::createContravariant(),
				new GenericObjectType(D\Out::class, [
					new GenericObjectType(D\Out::class, [
						$templateType('T'),
					]),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createContravariant(),
					),
				],
			],
			'param: Out<Out<Out<T>>>' => [
				TemplateTypeVariance::createContravariant(),
				new GenericObjectType(D\Out::class, [
					new GenericObjectType(D\Out::class, [
						new GenericObjectType(D\Out::class, [
							$templateType('T'),
						]),
					]),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createContravariant(),
					),
				],
			],
			'param: In<T>' => [
				TemplateTypeVariance::createContravariant(),
				new GenericObjectType(D\In::class, [
					$templateType('T'),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createCovariant(),
					),
				],
			],
			'param: In<In<T>>' => [
				TemplateTypeVariance::createContravariant(),
				new GenericObjectType(D\In::class, [
					new GenericObjectType(D\In::class, [
						$templateType('T'),
					]),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createContravariant(),
					),
				],
			],
			'param: In<In<In<T>>>' => [
				TemplateTypeVariance::createContravariant(),
				new GenericObjectType(D\In::class, [
					new GenericObjectType(D\In::class, [
						new GenericObjectType(D\In::class, [
							$templateType('T'),
						]),
					]),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createCovariant(),
					),
				],
			],
			'param: In<Out<T>>' => [
				TemplateTypeVariance::createContravariant(),
				new GenericObjectType(D\In::class, [
					new GenericObjectType(D\Out::class, [
						$templateType('T'),
					]),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createCovariant(),
					),
				],
			],
			'param: Out<In<T>>' => [
				TemplateTypeVariance::createContravariant(),
				new GenericObjectType(D\Out::class, [
					new GenericObjectType(D\In::class, [
						$templateType('T'),
					]),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createCovariant(),
					),
				],
			],
			'param: Out<Invariant<T>>' => [
				TemplateTypeVariance::createContravariant(),
				new GenericObjectType(D\Out::class, [
					new GenericObjectType(D\Invariant::class, [
						$templateType('T'),
					]),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createInvariant(),
					),
				],
			],
			'param: In<Invariant<T>>' => [
				TemplateTypeVariance::createContravariant(),
				new GenericObjectType(D\In::class, [
					new GenericObjectType(D\Invariant::class, [
						$templateType('T'),
					]),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createInvariant(),
					),
				],
			],
			'param: Invariant<Out<T>>' => [
				TemplateTypeVariance::createContravariant(),
				new GenericObjectType(D\Invariant::class, [
					new GenericObjectType(D\Out::class, [
						$templateType('T'),
					]),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createCovariant(),
					),
				],
			],
			'param: Invariant<In<T>>' => [
				TemplateTypeVariance::createContravariant(),
				new GenericObjectType(D\Invariant::class, [
					new GenericObjectType(D\In::class, [
						$templateType('T'),
					]),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createContravariant(),
					),
				],
			],
			'param: In<Invariant<Out<T>>>' => [
				TemplateTypeVariance::createContravariant(),
				new GenericObjectType(D\In::class, [
					new GenericObjectType(D\Invariant::class, [
						new GenericObjectType(D\Out::class, [
							$templateType('T'),
						]),
					]),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createCovariant(),
					),
				],
			],
			'param: Out<Invariant<In<T>>>' => [
				TemplateTypeVariance::createContravariant(),
				new GenericObjectType(D\Out::class, [
					new GenericObjectType(D\Invariant::class, [
						new GenericObjectType(D\In::class, [
							$templateType('T'),
						]),
					]),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createContravariant(),
					),
				],
			],
			'return: Invariant<T>' => [
				TemplateTypeVariance::createCovariant(),
				new GenericObjectType(D\Invariant::class, [
					$templateType('T'),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createInvariant(),
					),
				],
			],
			'return: Out<T>' => [
				TemplateTypeVariance::createCovariant(),
				new GenericObjectType(D\Out::class, [
					$templateType('T'),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createCovariant(),
					),
				],
			],
			'return: Out<Out<T>>' => [
				TemplateTypeVariance::createCovariant(),
				new GenericObjectType(D\Out::class, [
					new GenericObjectType(D\Out::class, [
						$templateType('T'),
					]),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createCovariant(),
					),
				],
			],
			'return: Out<Out<Out<T>>>' => [
				TemplateTypeVariance::createCovariant(),
				new GenericObjectType(D\Out::class, [
					new GenericObjectType(D\Out::class, [
						new GenericObjectType(D\Out::class, [
							$templateType('T'),
						]),
					]),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createCovariant(),
					),
				],
			],
			'return: In<T>' => [
				TemplateTypeVariance::createCovariant(),
				new GenericObjectType(D\In::class, [
					$templateType('T'),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createContravariant(),
					),
				],
			],
			'return: In<In<T>>' => [
				TemplateTypeVariance::createCovariant(),
				new GenericObjectType(D\In::class, [
					new GenericObjectType(D\In::class, [
						$templateType('T'),
					]),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createCovariant(),
					),
				],
			],
			'return: In<In<In<T>>>' => [
				TemplateTypeVariance::createCovariant(),
				new GenericObjectType(D\In::class, [
					new GenericObjectType(D\In::class, [
						new GenericObjectType(D\In::class, [
							$templateType('T'),
						]),
					]),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createContravariant(),
					),
				],
			],
			'return: In<Out<T>>' => [
				TemplateTypeVariance::createCovariant(),
				new GenericObjectType(D\In::class, [
					new GenericObjectType(D\Out::class, [
						$templateType('T'),
					]),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createContravariant(),
					),
				],
			],
			'return: Out<In<T>>' => [
				TemplateTypeVariance::createCovariant(),
				new GenericObjectType(D\Out::class, [
					new GenericObjectType(D\In::class, [
						$templateType('T'),
					]),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createContravariant(),
					),
				],
			],
			'return: Out<Invariant<T>>' => [
				TemplateTypeVariance::createCovariant(),
				new GenericObjectType(D\Out::class, [
					new GenericObjectType(D\Invariant::class, [
						$templateType('T'),
					]),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createInvariant(),
					),
				],
			],
			'return: In<Invariant<T>>' => [
				TemplateTypeVariance::createCovariant(),
				new GenericObjectType(D\In::class, [
					new GenericObjectType(D\Invariant::class, [
						$templateType('T'),
					]),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createInvariant(),
					),
				],
			],
			'return: Invariant<Out<T>>' => [
				TemplateTypeVariance::createCovariant(),
				new GenericObjectType(D\Invariant::class, [
					new GenericObjectType(D\Out::class, [
						$templateType('T'),
					]),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createCovariant(),
					),
				],
			],
			'return: Invariant<In<T>>' => [
				TemplateTypeVariance::createCovariant(),
				new GenericObjectType(D\Invariant::class, [
					new GenericObjectType(D\In::class, [
						$templateType('T'),
					]),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createContravariant(),
					),
				],
			],
			'return: In<Invariant<Out<T>>>' => [
				TemplateTypeVariance::createCovariant(),
				new GenericObjectType(D\In::class, [
					new GenericObjectType(D\Invariant::class, [
						new GenericObjectType(D\Out::class, [
							$templateType('T'),
						]),
					]),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createCovariant(),
					),
				],
			],
			'return: Out<Invariant<In<T>>>' => [
				TemplateTypeVariance::createCovariant(),
				new GenericObjectType(D\Out::class, [
					new GenericObjectType(D\Invariant::class, [
						new GenericObjectType(D\In::class, [
							$templateType('T'),
						]),
					]),
				]),
				false,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createContravariant(),
					),
				],
			],
			'param: Out<Invariant<T>> (with invariance composition)' => [
				TemplateTypeVariance::createContravariant(),
				new GenericObjectType(D\Out::class, [
					new GenericObjectType(D\Invariant::class, [
						$templateType('T'),
					]),
				]),
				true,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createInvariant(),
					),
				],
			],
			'param: In<Invariant<T>> (with invariance composition)' => [
				TemplateTypeVariance::createContravariant(),
				new GenericObjectType(D\In::class, [
					new GenericObjectType(D\Invariant::class, [
						$templateType('T'),
					]),
				]),
				true,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createInvariant(),
					),
				],
			],
			'param: Invariant<Out<T>> (with invariance composition)' => [
				TemplateTypeVariance::createContravariant(),
				new GenericObjectType(D\Invariant::class, [
					new GenericObjectType(D\Out::class, [
						$templateType('T'),
					]),
				]),
				true,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createInvariant(),
					),
				],
			],
			'param: Invariant<In<T>> (with invariance composition)' => [
				TemplateTypeVariance::createContravariant(),
				new GenericObjectType(D\Invariant::class, [
					new GenericObjectType(D\In::class, [
						$templateType('T'),
					]),
				]),
				true,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createInvariant(),
					),
				],
			],
			'param: In<Invariant<Out<T>>> (with invariance composition)' => [
				TemplateTypeVariance::createContravariant(),
				new GenericObjectType(D\In::class, [
					new GenericObjectType(D\Invariant::class, [
						new GenericObjectType(D\Out::class, [
							$templateType('T'),
						]),
					]),
				]),
				true,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createInvariant(),
					),
				],
			],
			'param: Out<Invariant<In<T>>> (with invariance composition)' => [
				TemplateTypeVariance::createContravariant(),
				new GenericObjectType(D\Out::class, [
					new GenericObjectType(D\Invariant::class, [
						new GenericObjectType(D\In::class, [
							$templateType('T'),
						]),
					]),
				]),
				true,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createInvariant(),
					),
				],
			],
			'return: Out<Invariant<T>> (with invariance composition)' => [
				TemplateTypeVariance::createCovariant(),
				new GenericObjectType(D\Out::class, [
					new GenericObjectType(D\Invariant::class, [
						$templateType('T'),
					]),
				]),
				true,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createInvariant(),
					),
				],
			],
			'return: In<Invariant<T>> (with invariance composition)' => [
				TemplateTypeVariance::createCovariant(),
				new GenericObjectType(D\In::class, [
					new GenericObjectType(D\Invariant::class, [
						$templateType('T'),
					]),
				]),
				true,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createInvariant(),
					),
				],
			],
			'return: Invariant<Out<T>> (with invariance composition)' => [
				TemplateTypeVariance::createCovariant(),
				new GenericObjectType(D\Invariant::class, [
					new GenericObjectType(D\Out::class, [
						$templateType('T'),
					]),
				]),
				true,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createInvariant(),
					),
				],
			],
			'return: Invariant<In<T>> (with invariance composition)' => [
				TemplateTypeVariance::createCovariant(),
				new GenericObjectType(D\Invariant::class, [
					new GenericObjectType(D\In::class, [
						$templateType('T'),
					]),
				]),
				true,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createInvariant(),
					),
				],
			],
			'return: In<Invariant<Out<T>>> (with invariance composition)' => [
				TemplateTypeVariance::createCovariant(),
				new GenericObjectType(D\In::class, [
					new GenericObjectType(D\Invariant::class, [
						new GenericObjectType(D\Out::class, [
							$templateType('T'),
						]),
					]),
				]),
				true,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createInvariant(),
					),
				],
			],
			'return: Out<Invariant<In<T>>> (with invariance composition)' => [
				TemplateTypeVariance::createCovariant(),
				new GenericObjectType(D\Out::class, [
					new GenericObjectType(D\Invariant::class, [
						new GenericObjectType(D\In::class, [
							$templateType('T'),
						]),
					]),
				]),
				true,
				[
					new TemplateTypeReference(
						$templateType('T'),
						TemplateTypeVariance::createInvariant(),
					),
				],
			],
		];
	}

	/**
	 * @dataProvider dataGetReferencedTypeArguments
	 *
	 * @param array<TemplateTypeReference> $expectedReferences
	 */
	public function testGetReferencedTypeArguments(TemplateTypeVariance $positionVariance, Type $type, bool $invarianceComposition, array $expectedReferences): void
	{
		TemplateTypeVariance::setInvarianceCompositionEnabled($invarianceComposition);

		$result = [];
		foreach ($type->getReferencedTemplateTypes($positionVariance) as $r) {
			$result[] = $r;
		}

		$comparableResult = array_map(static fn (TemplateTypeReference $ref): array => [
			'type' => $ref->getType()->describe(VerbosityLevel::typeOnly()),
			'positionVariance' => $ref->getPositionVariance()->describe(),
		], $result);

		$comparableExpect = array_map(static fn (TemplateTypeReference $ref): array => [
			'type' => $ref->getType()->describe(VerbosityLevel::typeOnly()),
			'positionVariance' => $ref->getPositionVariance()->describe(),
		], $expectedReferences);

		$this->assertSame($comparableExpect, $comparableResult);
	}

}
