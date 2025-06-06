<?php declare(strict_types = 1);

namespace PHPStan\Type;

use ArrayAccess;
use ArrayObject;
use Countable;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use Iterator;
use LogicException;
use PHPStan\Generics\FunctionsAssertType\C;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\GenericStaticType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use StaticTypeTest\Base;
use StaticTypeTest\Child;
use StaticTypeTest\FinalChild;
use stdClass;
use Traversable;
use function sprintf;

class StaticTypeTest extends PHPStanTestCase
{

	public static function dataIsIterable(): array
	{
		$reflectionProvider = self::createReflectionProvider();

		return [
			[new StaticType($reflectionProvider->getClass('ArrayObject')), TrinaryLogic::createYes()],
			[new StaticType($reflectionProvider->getClass('Traversable')), TrinaryLogic::createYes()],
			[new StaticType($reflectionProvider->getClass('DateTime')), TrinaryLogic::createNo()],
		];
	}

	/**
	 * @dataProvider dataIsIterable
	 */
	public function testIsIterable(StaticType $type, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isIterable();
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isIterable()', $type->describe(VerbosityLevel::precise())),
		);
	}

	public static function dataIsCallable(): array
	{
		$reflectionProvider = self::createReflectionProvider();

		return [
			[new StaticType($reflectionProvider->getClass('Closure')), TrinaryLogic::createYes()],
			[new StaticType($reflectionProvider->getClass('DateTime')), TrinaryLogic::createMaybe()],
		];
	}

	/**
	 * @dataProvider dataIsCallable
	 */
	public function testIsCallable(StaticType $type, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isCallable();
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isCallable()', $type->describe(VerbosityLevel::precise())),
		);
	}

	public static function dataIsSuperTypeOf(): array
	{
		$reflectionProvider = self::createReflectionProvider();
		return [
			1 => [
				new StaticType($reflectionProvider->getClass(ArrayAccess::class)),
				new ObjectType(Traversable::class),
				TrinaryLogic::createMaybe(),
			],
			2 => [
				new StaticType($reflectionProvider->getClass(Countable::class)),
				new ObjectType(Countable::class),
				TrinaryLogic::createMaybe(),
			],
			3 => [
				new StaticType($reflectionProvider->getClass(DateTimeImmutable::class)),
				new ObjectType(DateTimeImmutable::class),
				TrinaryLogic::createMaybe(),
			],
			4 => [
				new StaticType($reflectionProvider->getClass(Traversable::class)),
				new ObjectType(ArrayObject::class),
				TrinaryLogic::createMaybe(),
			],
			5 => [
				new StaticType($reflectionProvider->getClass(Traversable::class)),
				new ObjectType(Iterator::class),
				TrinaryLogic::createMaybe(),
			],
			6 => [
				new StaticType($reflectionProvider->getClass(ArrayObject::class)),
				new ObjectType(Traversable::class),
				TrinaryLogic::createMaybe(),
			],
			7 => [
				new StaticType($reflectionProvider->getClass(Iterator::class)),
				new ObjectType(Traversable::class),
				TrinaryLogic::createMaybe(),
			],
			8 => [
				new StaticType($reflectionProvider->getClass(ArrayObject::class)),
				new ObjectType(DateTimeImmutable::class),
				TrinaryLogic::createNo(),
			],
			9 => [
				new StaticType($reflectionProvider->getClass(DateTimeImmutable::class)),
				new UnionType([
					new ObjectType(DateTimeImmutable::class),
					new StringType(),
				]),
				TrinaryLogic::createMaybe(),
			],
			10 => [
				new StaticType($reflectionProvider->getClass(DateTimeImmutable::class)),
				new UnionType([
					new ObjectType(ArrayObject::class),
					new StringType(),
				]),
				TrinaryLogic::createNo(),
			],
			11 => [
				new StaticType($reflectionProvider->getClass(LogicException::class)),
				new ObjectType(InvalidArgumentException::class),
				TrinaryLogic::createMaybe(),
			],
			12 => [
				new StaticType($reflectionProvider->getClass(InvalidArgumentException::class)),
				new ObjectType(LogicException::class),
				TrinaryLogic::createMaybe(),
			],
			13 => [
				new StaticType($reflectionProvider->getClass(ArrayAccess::class)),
				new StaticType($reflectionProvider->getClass(Traversable::class)),
				TrinaryLogic::createMaybe(),
			],
			14 => [
				new StaticType($reflectionProvider->getClass(Countable::class)),
				new StaticType($reflectionProvider->getClass(Countable::class)),
				TrinaryLogic::createYes(),
			],
			15 => [
				new StaticType($reflectionProvider->getClass(DateTimeImmutable::class)),
				new StaticType($reflectionProvider->getClass(DateTimeImmutable::class)),
				TrinaryLogic::createYes(),
			],
			16 => [
				new StaticType($reflectionProvider->getClass(Traversable::class)),
				new StaticType($reflectionProvider->getClass(ArrayObject::class)),
				TrinaryLogic::createYes(),
			],
			17 => [
				new StaticType($reflectionProvider->getClass(Traversable::class)),
				new StaticType($reflectionProvider->getClass(Iterator::class)),
				TrinaryLogic::createYes(),
			],
			18 => [
				new StaticType($reflectionProvider->getClass(ArrayObject::class)),
				new StaticType($reflectionProvider->getClass(Traversable::class)),
				TrinaryLogic::createMaybe(),
			],
			19 => [
				new StaticType($reflectionProvider->getClass(Iterator::class)),
				new StaticType($reflectionProvider->getClass(Traversable::class)),
				TrinaryLogic::createMaybe(),
			],
			20 => [
				new StaticType($reflectionProvider->getClass(ArrayObject::class)),
				new StaticType($reflectionProvider->getClass(DateTimeImmutable::class)),
				TrinaryLogic::createNo(),
			],
			21 => [
				new StaticType($reflectionProvider->getClass(DateTimeImmutable::class)),
				new UnionType([
					new StaticType($reflectionProvider->getClass(DateTimeImmutable::class)),
					new StaticType($reflectionProvider->getClass(DateTimeImmutable::class)),
				]),
				TrinaryLogic::createYes(),
			],
			22 => [
				new StaticType($reflectionProvider->getClass(DateTimeImmutable::class)),
				new UnionType([
					new StaticType($reflectionProvider->getClass(DateTimeImmutable::class)),
					new StringType(),
				]),
				TrinaryLogic::createMaybe(),
			],
			23 => [
				new StaticType($reflectionProvider->getClass(DateTimeImmutable::class)),
				new UnionType([
					new StaticType($reflectionProvider->getClass(ArrayObject::class)),
					new StringType(),
				]),
				TrinaryLogic::createNo(),
			],
			24 => [
				new StaticType($reflectionProvider->getClass(LogicException::class)),
				new StaticType($reflectionProvider->getClass(InvalidArgumentException::class)),
				TrinaryLogic::createYes(),
			],
			25 => [
				new StaticType($reflectionProvider->getClass(InvalidArgumentException::class)),
				new StaticType($reflectionProvider->getClass(LogicException::class)),
				TrinaryLogic::createMaybe(),
			],
			26 => [
				new StaticType($reflectionProvider->getClass(stdClass::class)),
				new ObjectWithoutClassType(),
				TrinaryLogic::createMaybe(),
			],
			27 => [
				new ObjectWithoutClassType(),
				new StaticType($reflectionProvider->getClass(stdClass::class)),
				TrinaryLogic::createYes(),
			],
			28 => [
				new ThisType($reflectionProvider->getClass(stdClass::class)),
				new ObjectWithoutClassType(),
				TrinaryLogic::createMaybe(),
			],
			29 => [
				new ObjectWithoutClassType(),
				new ThisType($reflectionProvider->getClass(stdClass::class)),
				TrinaryLogic::createYes(),
			],
			[
				new StaticType($reflectionProvider->getClass(Base::class)),
				new ObjectType(Child::class),
				TrinaryLogic::createMaybe(),
			],
			[
				new StaticType($reflectionProvider->getClass(Base::class)),
				new StaticType($reflectionProvider->getClass(FinalChild::class)),
				TrinaryLogic::createYes(),
			],
			[
				new StaticType($reflectionProvider->getClass(Base::class)),
				new StaticType($reflectionProvider->getClass(Child::class)),
				TrinaryLogic::createYes(),
			],
			[
				new StaticType($reflectionProvider->getClass(Base::class)),
				new ObjectType(FinalChild::class),
				TrinaryLogic::createYes(),
			],
			[
				new ThisType(
					$reflectionProvider->getClass(\ThisSubtractable\Foo::class), // phpcs:ignore
					new UnionType([new ObjectType(\ThisSubtractable\Bar::class), new ObjectType(\ThisSubtractable\Baz::class)]), // phpcs:ignore
				),
				new UnionType([
					new IntersectionType([
						new ThisType($reflectionProvider->getClass(\ThisSubtractable\Foo::class)), // phpcs:ignore
						new ObjectType(\ThisSubtractable\Bar::class), // phpcs:ignore
					]),
					new IntersectionType([
						new ThisType($reflectionProvider->getClass(\ThisSubtractable\Foo::class)), // phpcs:ignore
						new ObjectType(\ThisSubtractable\Baz::class), // phpcs:ignore
					]),
				]),
				TrinaryLogic::createNo(),
			],
			[
				new GenericStaticType(
					$reflectionProvider->getClass(\MethodSignatureGenericStaticType\Foo::class), // phpcs:ignore
					[new StringType()],
					null,
					[],
				),
				new GenericObjectType(\MethodSignatureGenericStaticType\FinalBar::class, [ // phpcs:ignore
					new IntegerType(),
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

	public static function dataEquals(): array
	{
		$reflectionProvider = self::createReflectionProvider();

		return [
			[
				new ThisType($reflectionProvider->getClass(Exception::class)),
				new ThisType($reflectionProvider->getClass(Exception::class)),
				true,
			],
			[
				new ThisType($reflectionProvider->getClass(Exception::class)),
				new ThisType($reflectionProvider->getClass(InvalidArgumentException::class)),
				false,
			],
			[
				new ThisType($reflectionProvider->getClass(Exception::class)),
				new StaticType($reflectionProvider->getClass(Exception::class)),
				false,
			],
			[
				new ThisType($reflectionProvider->getClass(Exception::class)),
				new StaticType($reflectionProvider->getClass(InvalidArgumentException::class)),
				false,
			],
		];
	}

	/**
	 * @dataProvider dataEquals
	 */
	public function testEquals(StaticType $type, StaticType $otherType, bool $expected): void
	{
		$this->assertSame($expected, $type->equals($otherType));
		$this->assertSame($expected, $otherType->equals($type));
	}

	public static function dataAccepts(): iterable
	{
		$reflectionProvider = self::createReflectionProvider();
		$c = $reflectionProvider->getClass(C::class);

		yield [
			new StaticType($c),
			new StaticType($c),
			TrinaryLogic::createYes(),
		];

		yield [
			// static !== static<int>
			new StaticType($c),
			new GenericStaticType($c, [new IntegerType()], null, []),
			TrinaryLogic::createNo(),
		];

		yield [
			// static !== static<covariant int>
			new StaticType($c),
			new GenericStaticType($c, [new IntegerType()], null, [
				TemplateTypeVariance::createCovariant(),
			]),
			TrinaryLogic::createNo(),
		];

		yield [
			// static === static<T>
			new StaticType($c),
			new GenericStaticType($c, [
				TemplateTypeFactory::create(TemplateTypeScope::createWithClass($c->getName()), 'T', null, TemplateTypeVariance::createInvariant()),
			], null, []),
			TrinaryLogic::createYes(),
		];

		yield [
			// static<T> !== static
			new GenericStaticType($c, [
				TemplateTypeFactory::create(TemplateTypeScope::createWithClass($c->getName()), 'T', null, TemplateTypeVariance::createInvariant()),
			], null, []),
			new StaticType($c),
			TrinaryLogic::createNo(), // could be Yes
		];

		yield [
			new GenericStaticType($c, [new IntegerType()], null, []),
			new GenericStaticType($c, [new IntegerType()], null, []),
			TrinaryLogic::createYes(),
		];

		yield [
			new GenericStaticType($c, [new UnionType([
				new IntegerType(),
				new StringType(),
			])], null, []),
			new GenericStaticType($c, [new IntegerType()], null, []),
			TrinaryLogic::createNo(),
		];

		yield [
			new GenericStaticType($c, [new UnionType([
				new IntegerType(),
				new StringType(),
			])], null, [TemplateTypeVariance::createCovariant()]),
			new GenericStaticType($c, [new IntegerType()], null, []),
			TrinaryLogic::createYes(),
		];

		yield [
			new GenericStaticType($c, [new IntegerType()], null, []),
			new GenericStaticType($c, [new UnionType([
				new IntegerType(),
				new StringType(),
			])], null, []),
			TrinaryLogic::createNo(),
		];

		yield [
			new GenericStaticType($c, [new IntegerType()], null, [
				TemplateTypeVariance::createContravariant(),
			]),
			new GenericStaticType($c, [new UnionType([
				new IntegerType(),
				new StringType(),
			])], null, []),
			TrinaryLogic::createYes(),
		];

		yield [
			new GenericStaticType($c, [new IntegerType()], null, []),
			new ObjectType($c->getName()),
			TrinaryLogic::createNo(),
		];

		yield [
			new GenericStaticType($c, [new IntegerType()], null, []),
			new GenericObjectType($c->getName(), [new IntegerType()], null),
			TrinaryLogic::createNo(),
		];

		yield [
			new GenericStaticType($c, [new IntegerType()], null, []),
			new ObjectWithoutClassType(),
			TrinaryLogic::createNo(),
		];

		yield [
			new GenericStaticType($c, [new IntegerType()], null, []),
			new ObjectWithoutClassType(),
			TrinaryLogic::createNo(),
		];
	}

	/**
	 * @dataProvider dataAccepts
	 */
	public function testAccepts(StaticType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->accepts($otherType, true);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->result->describe(),
			sprintf('%s -> accepts(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

}
