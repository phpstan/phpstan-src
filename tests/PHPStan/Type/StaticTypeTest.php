<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Broker\Broker;
use PHPStan\TrinaryLogic;
use StaticTypeTest\Base;
use StaticTypeTest\Child;
use StaticTypeTest\FinalChild;

class StaticTypeTest extends \PHPStan\Testing\BaseTestCase
{

	public function dataIsIterable(): array
	{
		return [
			[new StaticType('ArrayObject'), TrinaryLogic::createYes()],
			[new StaticType('Traversable'), TrinaryLogic::createYes()],
			[new StaticType('Unknown'), TrinaryLogic::createMaybe()],
			[new StaticType('DateTime'), TrinaryLogic::createNo()],
		];
	}

	/**
	 * @dataProvider dataIsIterable
	 * @param StaticType $type
	 * @param TrinaryLogic $expectedResult
	 */
	public function testIsIterable(StaticType $type, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isIterable();
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isIterable()', $type->describe(VerbosityLevel::precise()))
		);
	}

	public function dataIsCallable(): array
	{
		return [
			[new StaticType('Closure'), TrinaryLogic::createYes()],
			[new StaticType('Unknown'), TrinaryLogic::createMaybe()],
			[new StaticType('DateTime'), TrinaryLogic::createMaybe()],
		];
	}

	/**
	 * @dataProvider dataIsCallable
	 * @param StaticType $type
	 * @param TrinaryLogic $expectedResult
	 */
	public function testIsCallable(StaticType $type, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isCallable();
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isCallable()', $type->describe(VerbosityLevel::precise()))
		);
	}

	public function dataIsSuperTypeOf(): array
	{
		$broker = $this->createBroker();
		return [
			0 => [
				new StaticType('UnknownClassA'),
				new ObjectType('UnknownClassB'),
				TrinaryLogic::createMaybe(),
			],
			1 => [
				new StaticType(\ArrayAccess::class),
				new ObjectType(\Traversable::class),
				TrinaryLogic::createMaybe(),
			],
			2 => [
				new StaticType(\Countable::class),
				new ObjectType(\Countable::class),
				TrinaryLogic::createMaybe(),
			],
			3 => [
				new StaticType(\DateTimeImmutable::class),
				new ObjectType(\DateTimeImmutable::class),
				TrinaryLogic::createMaybe(),
			],
			4 => [
				new StaticType(\Traversable::class),
				new ObjectType(\ArrayObject::class),
				TrinaryLogic::createMaybe(),
			],
			5 => [
				new StaticType(\Traversable::class),
				new ObjectType(\Iterator::class),
				TrinaryLogic::createMaybe(),
			],
			6 => [
				new StaticType(\ArrayObject::class),
				new ObjectType(\Traversable::class),
				TrinaryLogic::createMaybe(),
			],
			7 => [
				new StaticType(\Iterator::class),
				new ObjectType(\Traversable::class),
				TrinaryLogic::createMaybe(),
			],
			8 => [
				new StaticType(\ArrayObject::class),
				new ObjectType(\DateTimeImmutable::class),
				TrinaryLogic::createNo(),
			],
			9 => [
				new StaticType(\DateTimeImmutable::class),
				new UnionType([
					new ObjectType(\DateTimeImmutable::class),
					new StringType(),
				]),
				TrinaryLogic::createMaybe(),
			],
			10 => [
				new StaticType(\DateTimeImmutable::class),
				new UnionType([
					new ObjectType(\ArrayObject::class),
					new StringType(),
				]),
				TrinaryLogic::createNo(),
			],
			11 => [
				new StaticType(\LogicException::class),
				new ObjectType(\InvalidArgumentException::class),
				TrinaryLogic::createMaybe(),
			],
			12 => [
				new StaticType(\InvalidArgumentException::class),
				new ObjectType(\LogicException::class),
				TrinaryLogic::createMaybe(),
			],
			13 => [
				new StaticType(\ArrayAccess::class),
				new StaticType(\Traversable::class),
				TrinaryLogic::createMaybe(),
			],
			14 => [
				new StaticType(\Countable::class),
				new StaticType(\Countable::class),
				TrinaryLogic::createYes(),
			],
			15 => [
				new StaticType(\DateTimeImmutable::class),
				new StaticType(\DateTimeImmutable::class),
				TrinaryLogic::createYes(),
			],
			16 => [
				new StaticType(\Traversable::class),
				new StaticType(\ArrayObject::class),
				TrinaryLogic::createYes(),
			],
			17 => [
				new StaticType(\Traversable::class),
				new StaticType(\Iterator::class),
				TrinaryLogic::createYes(),
			],
			18 => [
				new StaticType(\ArrayObject::class),
				new StaticType(\Traversable::class),
				TrinaryLogic::createMaybe(),
			],
			19 => [
				new StaticType(\Iterator::class),
				new StaticType(\Traversable::class),
				TrinaryLogic::createMaybe(),
			],
			20 => [
				new StaticType(\ArrayObject::class),
				new StaticType(\DateTimeImmutable::class),
				TrinaryLogic::createNo(),
			],
			21 => [
				new StaticType(\DateTimeImmutable::class),
				new UnionType([
					new StaticType(\DateTimeImmutable::class),
					new StaticType(\DateTimeImmutable::class),
				]),
				TrinaryLogic::createYes(),
			],
			22 => [
				new StaticType(\DateTimeImmutable::class),
				new UnionType([
					new StaticType(\DateTimeImmutable::class),
					new StringType(),
				]),
				TrinaryLogic::createMaybe(),
			],
			23 => [
				new StaticType(\DateTimeImmutable::class),
				new UnionType([
					new StaticType(\ArrayObject::class),
					new StringType(),
				]),
				TrinaryLogic::createNo(),
			],
			24 => [
				new StaticType(\LogicException::class),
				new StaticType(\InvalidArgumentException::class),
				TrinaryLogic::createYes(),
			],
			25 => [
				new StaticType(\InvalidArgumentException::class),
				new StaticType(\LogicException::class),
				TrinaryLogic::createMaybe(),
			],
			26 => [
				new StaticType(\stdClass::class),
				new ObjectWithoutClassType(),
				TrinaryLogic::createMaybe(),
			],
			27 => [
				new ObjectWithoutClassType(),
				new StaticType(\stdClass::class),
				TrinaryLogic::createYes(),
			],
			28 => [
				new ThisType($broker->getClass(\stdClass::class)),
				new ObjectWithoutClassType(),
				TrinaryLogic::createMaybe(),
			],
			29 => [
				new ObjectWithoutClassType(),
				new ThisType($broker->getClass(\stdClass::class)),
				TrinaryLogic::createYes(),
			],
			[
				new StaticType(Base::class),
				new ObjectType(Child::class),
				TrinaryLogic::createMaybe(),
			],
			[
				new StaticType(Base::class),
				new StaticType(FinalChild::class),
				TrinaryLogic::createYes(),
			],
			[
				new StaticType(Base::class),
				new StaticType(Child::class),
				TrinaryLogic::createYes(),
			],
			[
				new StaticType(Base::class),
				new ObjectType(FinalChild::class),
				TrinaryLogic::createYes(),
			],
		];
	}

	/**
	 * @dataProvider dataIsSuperTypeOf
	 * @param Type $type
	 * @param Type $otherType
	 * @param TrinaryLogic $expectedResult
	 */
	public function testIsSuperTypeOf(Type $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise()))
		);
	}

	public function dataEquals(): array
	{
		$reflectionProvider = Broker::getInstance();

		return [
			[
				new ThisType($reflectionProvider->getClass(\Exception::class)),
				new ThisType($reflectionProvider->getClass(\Exception::class)),
				true,
			],
			[
				new ThisType($reflectionProvider->getClass(\Exception::class)),
				new ThisType($reflectionProvider->getClass(\InvalidArgumentException::class)),
				false,
			],
			[
				new ThisType($reflectionProvider->getClass(\Exception::class)),
				new StaticType(\Exception::class),
				false,
			],
			[
				new ThisType($reflectionProvider->getClass(\Exception::class)),
				new StaticType(\InvalidArgumentException::class),
				false,
			],
		];
	}

	/**
	 * @dataProvider dataEquals
	 * @param StaticType $type
	 * @param StaticType $otherType
	 * @param bool $expected
	 */
	public function testEquals(StaticType $type, StaticType $otherType, bool $expected): void
	{
		$this->assertSame($expected, $type->equals($otherType));
		$this->assertSame($expected, $otherType->equals($type));
	}

}
