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
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use StaticTypeTest\Base;
use StaticTypeTest\Child;
use StaticTypeTest\FinalChild;
use stdClass;
use Traversable;
use function sprintf;

class StaticTypeTest extends PHPStanTestCase
{

	public function dataIsIterable(): array
	{
		$reflectionProvider = $this->createReflectionProvider();

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

	public function dataIsCallable(): array
	{
		$reflectionProvider = $this->createReflectionProvider();

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

	public function dataIsSuperTypeOf(): array
	{
		$reflectionProvider = $this->createReflectionProvider();
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

	public function dataEquals(): array
	{
		$reflectionProvider = $this->createReflectionProvider();

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

}
