<?php declare(strict_types = 1);

namespace PHPStan\Type;

use ArrayAccess;
use ArrayObject;
use Closure;
use Countable;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Generator;
use InvalidArgumentException;
use Iterator;
use LogicException;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\HasMethodType;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use SimpleXMLElement;
use stdClass;
use Throwable;
use Traversable;
use function sprintf;

class ObjectTypeTest extends PHPStanTestCase
{

	public function dataIsIterable(): array
	{
		return [
			[new ObjectType('ArrayObject'), TrinaryLogic::createYes()],
			[new ObjectType('Traversable'), TrinaryLogic::createYes()],
			[new ObjectType('Unknown'), TrinaryLogic::createMaybe()],
			[new ObjectType('DateTime'), TrinaryLogic::createNo()],
		];
	}

	/**
	 * @dataProvider dataIsIterable
	 */
	public function testIsIterable(ObjectType $type, TrinaryLogic $expectedResult): void
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
		return [
			[new ObjectType('Closure'), TrinaryLogic::createYes()],
			[new ObjectType('Unknown'), TrinaryLogic::createMaybe()],
			[new ObjectType('DateTime'), TrinaryLogic::createMaybe()],
		];
	}

	/**
	 * @dataProvider dataIsCallable
	 */
	public function testIsCallable(ObjectType $type, TrinaryLogic $expectedResult): void
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
			0 => [
				new ObjectType('UnknownClassA'),
				new ObjectType('UnknownClassB'),
				TrinaryLogic::createMaybe(),
			],
			1 => [
				new ObjectType(ArrayAccess::class),
				new ObjectType(Traversable::class),
				TrinaryLogic::createMaybe(),
			],
			2 => [
				new ObjectType(Countable::class),
				new ObjectType(Countable::class),
				TrinaryLogic::createYes(),
			],
			3 => [
				new ObjectType(DateTimeImmutable::class),
				new ObjectType(DateTimeImmutable::class),
				TrinaryLogic::createYes(),
			],
			4 => [
				new ObjectType(Traversable::class),
				new ObjectType(ArrayObject::class),
				TrinaryLogic::createYes(),
			],
			5 => [
				new ObjectType(Traversable::class),
				new ObjectType(Iterator::class),
				TrinaryLogic::createYes(),
			],
			6 => [
				new ObjectType(ArrayObject::class),
				new ObjectType(Traversable::class),
				TrinaryLogic::createMaybe(),
			],
			7 => [
				new ObjectType(Iterator::class),
				new ObjectType(Traversable::class),
				TrinaryLogic::createMaybe(),
			],
			8 => [
				new ObjectType(ArrayObject::class),
				new ObjectType(DateTimeImmutable::class),
				TrinaryLogic::createNo(),
			],
			9 => [
				new ObjectType(DateTimeImmutable::class),
				new UnionType([
					new ObjectType(DateTimeImmutable::class),
					new StringType(),
				]),
				TrinaryLogic::createMaybe(),
			],
			10 => [
				new ObjectType(DateTimeImmutable::class),
				new UnionType([
					new ObjectType(ArrayObject::class),
					new StringType(),
				]),
				TrinaryLogic::createNo(),
			],
			11 => [
				new ObjectType(LogicException::class),
				new ObjectType(InvalidArgumentException::class),
				TrinaryLogic::createYes(),
			],
			12 => [
				new ObjectType(InvalidArgumentException::class),
				new ObjectType(LogicException::class),
				TrinaryLogic::createMaybe(),
			],
			13 => [
				new ObjectType(ArrayAccess::class),
				new StaticType($reflectionProvider->getClass(Traversable::class)),
				TrinaryLogic::createMaybe(),
			],
			14 => [
				new ObjectType(Countable::class),
				new StaticType($reflectionProvider->getClass(Countable::class)),
				TrinaryLogic::createYes(),
			],
			15 => [
				new ObjectType(DateTimeImmutable::class),
				new StaticType($reflectionProvider->getClass(DateTimeImmutable::class)),
				TrinaryLogic::createYes(),
			],
			16 => [
				new ObjectType(Traversable::class),
				new StaticType($reflectionProvider->getClass(ArrayObject::class)),
				TrinaryLogic::createYes(),
			],
			17 => [
				new ObjectType(Traversable::class),
				new StaticType($reflectionProvider->getClass(Iterator::class)),
				TrinaryLogic::createYes(),
			],
			18 => [
				new ObjectType(ArrayObject::class),
				new StaticType($reflectionProvider->getClass(Traversable::class)),
				TrinaryLogic::createMaybe(),
			],
			19 => [
				new ObjectType(Iterator::class),
				new StaticType($reflectionProvider->getClass(Traversable::class)),
				TrinaryLogic::createMaybe(),
			],
			20 => [
				new ObjectType(ArrayObject::class),
				new StaticType($reflectionProvider->getClass(DateTimeImmutable::class)),
				TrinaryLogic::createNo(),
			],
			21 => [
				new ObjectType(DateTimeImmutable::class),
				new UnionType([
					new StaticType($reflectionProvider->getClass(DateTimeImmutable::class)),
					new StringType(),
				]),
				TrinaryLogic::createMaybe(),
			],
			22 => [
				new ObjectType(DateTimeImmutable::class),
				new UnionType([
					new StaticType($reflectionProvider->getClass(ArrayObject::class)),
					new StringType(),
				]),
				TrinaryLogic::createNo(),
			],
			23 => [
				new ObjectType(LogicException::class),
				new StaticType($reflectionProvider->getClass(InvalidArgumentException::class)),
				TrinaryLogic::createYes(),
			],
			24 => [
				new ObjectType(InvalidArgumentException::class),
				new StaticType($reflectionProvider->getClass(LogicException::class)),
				TrinaryLogic::createMaybe(),
			],
			25 => [
				new ObjectType(stdClass::class),
				new ClosureType([], new MixedType(), false),
				TrinaryLogic::createNo(),
			],
			26 => [
				new ObjectType(Closure::class),
				new ClosureType([], new MixedType(), false),
				TrinaryLogic::createYes(),
			],
			27 => [
				new ObjectType(Countable::class),
				new IterableType(new MixedType(), new MixedType()),
				TrinaryLogic::createMaybe(),
			],
			28 => [
				new ObjectType(DateTimeImmutable::class),
				new HasMethodType('format'),
				TrinaryLogic::createMaybe(),
			],
			29 => [
				new ObjectType(Closure::class),
				new HasMethodType('format'),
				TrinaryLogic::createNo(),
			],
			30 => [
				new ObjectType(DateTimeImmutable::class),
				new UnionType([
					new HasMethodType('format'),
					new HasMethodType('getTimestamp'),
				]),
				TrinaryLogic::createMaybe(),
			],
			31 => [
				new ObjectType(DateInterval::class),
				new HasPropertyType('d'),
				TrinaryLogic::createMaybe(),
			],
			32 => [
				new ObjectType(Closure::class),
				new HasPropertyType('d'),
				TrinaryLogic::createMaybe(),
			],
			33 => [
				new ObjectType(DateInterval::class),
				new UnionType([
					new HasPropertyType('d'),
					new HasPropertyType('m'),
				]),
				TrinaryLogic::createMaybe(),
			],
			34 => [
				new ObjectType('Exception'),
				new ObjectWithoutClassType(),
				TrinaryLogic::createMaybe(),
			],
			35 => [
				new ObjectType('Exception'),
				new ObjectWithoutClassType(new ObjectType('Exception')),
				TrinaryLogic::createNo(),
			],
			36 => [
				new ObjectType('Exception'),
				new ObjectWithoutClassType(new ObjectType(InvalidArgumentException::class)),
				TrinaryLogic::createMaybe(),
			],
			37 => [
				new ObjectType(InvalidArgumentException::class),
				new ObjectWithoutClassType(new ObjectType('Exception')),
				TrinaryLogic::createNo(),
			],
			38 => [
				new ObjectType(Throwable::class, new ObjectType(InvalidArgumentException::class)),
				new ObjectType(InvalidArgumentException::class),
				TrinaryLogic::createNo(),
			],
			39 => [
				new ObjectType(Throwable::class, new ObjectType(InvalidArgumentException::class)),
				new ObjectType('Exception'),
				TrinaryLogic::createYes(),
			],
			40 => [
				new ObjectType(Throwable::class, new ObjectType('Exception')),
				new ObjectType(InvalidArgumentException::class),
				TrinaryLogic::createNo(),
			],
			41 => [
				new ObjectType(Throwable::class, new ObjectType('Exception')),
				new ObjectType('Exception'),
				TrinaryLogic::createNo(),
			],
			42 => [
				new ObjectType(Throwable::class, new ObjectType('Exception')),
				new ObjectType(Throwable::class),
				TrinaryLogic::createYes(),
			],
			43 => [
				new ObjectType(Throwable::class),
				new ObjectType(Throwable::class, new ObjectType('Exception')),
				TrinaryLogic::createYes(),
			],
			44 => [
				new ObjectType(Throwable::class),
				new ObjectType(Throwable::class, new ObjectType('Exception')),
				TrinaryLogic::createYes(),
			],
			45 => [
				new ObjectType('Exception'),
				new ObjectType(Throwable::class, new ObjectType('Exception')),
				TrinaryLogic::createNo(),
			],
			46 => [
				new ObjectType(DateTimeInterface::class),
				TemplateTypeFactory::create(
					TemplateTypeScope::createWithClass(DateTimeInterface::class),
					'T',
					new ObjectType(DateTimeInterface::class),
					TemplateTypeVariance::createInvariant(),
				),
				TrinaryLogic::createYes(),
			],
			47 => [
				new ObjectType(DateTimeInterface::class),
				TemplateTypeFactory::create(
					TemplateTypeScope::createWithClass(DateTime::class),
					'T',
					new ObjectType(DateTime::class),
					TemplateTypeVariance::createInvariant(),
				),
				TrinaryLogic::createYes(),
			],
			48 => [
				new ObjectType(DateTime::class),
				TemplateTypeFactory::create(
					TemplateTypeScope::createWithClass(DateTimeInterface::class),
					'T',
					new ObjectType(DateTimeInterface::class),
					TemplateTypeVariance::createInvariant(),
				),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	/**
	 * @dataProvider dataIsSuperTypeOf
	 */
	public function testIsSuperTypeOf(ObjectType $type, Type $otherType, TrinaryLogic $expectedResult): void
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
			[
				new ObjectType(SimpleXMLElement::class),
				new IntegerType(),
				TrinaryLogic::createNo(),
			],
			[
				new ObjectType(SimpleXMLElement::class),
				new ConstantStringType('foo'),
				TrinaryLogic::createNo(),
			],
			[
				new ObjectType(Traversable::class),
				new GenericObjectType(Traversable::class, [new MixedType(true), new ObjectType('DateTimeInteface')]),
				TrinaryLogic::createYes(),
			],
			[
				new ObjectType(DateTimeInterface::class),
				TemplateTypeFactory::create(
					TemplateTypeScope::createWithClass(DateTimeInterface::class),
					'T',
					new ObjectType(DateTimeInterface::class),
					TemplateTypeVariance::createInvariant(),
				),
				TrinaryLogic::createYes(),
			],
			[
				new ObjectType(DateTime::class),
				TemplateTypeFactory::create(
					TemplateTypeScope::createWithClass(DateTimeInterface::class),
					'T',
					new ObjectType(DateTimeInterface::class),
					TemplateTypeVariance::createInvariant(),
				),
				TrinaryLogic::createNo(),
			],
		];
	}

	/**
	 * @dataProvider dataAccepts
	 */
	public function testAccepts(
		ObjectType $type,
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

	public function testGetClassReflectionOfGenericClass(): void
	{
		$objectType = new ObjectType(Traversable::class);
		$classReflection = $objectType->getClassReflection();
		$this->assertNotNull($classReflection);
		$this->assertSame('Traversable<mixed,mixed>', $classReflection->getDisplayName());
	}

	public function dataHasOffsetValueType(): array
	{
		return [
			[
				new ObjectType(stdClass::class),
				new IntegerType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new ObjectType(Generator::class),
				new IntegerType(),
				TrinaryLogic::createNo(),
			],
			[
				new ObjectType(ArrayAccess::class),
				new IntegerType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new ObjectType(Countable::class),
				new IntegerType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new GenericObjectType(ArrayAccess::class, [new IntegerType(), new MixedType()]),
				new IntegerType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new GenericObjectType(ArrayAccess::class, [new IntegerType(), new MixedType()]),
				new MixedType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new GenericObjectType(ArrayAccess::class, [new IntegerType(), new MixedType()]),
				new StringType(),
				TrinaryLogic::createNo(),
			],
			[
				new GenericObjectType(ArrayAccess::class, [new ObjectType(DateTimeInterface::class), new MixedType()]),
				new ObjectType(DateTime::class),
				TrinaryLogic::createMaybe(),
			],
			[
				new GenericObjectType(ArrayAccess::class, [new ObjectType(DateTime::class), new MixedType()]),
				new ObjectType(DateTimeInterface::class),
				TrinaryLogic::createMaybe(),
			],
			[
				new GenericObjectType(ArrayAccess::class, [new ObjectType(DateTime::class), new MixedType()]),
				new ObjectType(stdClass::class),
				TrinaryLogic::createNo(),
			],
		];
	}

	/**
	 * @dataProvider dataHasOffsetValueType
	 */
	public function testHasOffsetValueType(
		ObjectType $type,
		Type $offsetType,
		TrinaryLogic $expectedResult,
	): void
	{
		$this->assertSame(
			$expectedResult->describe(),
			$type->hasOffsetValueType($offsetType)->describe(),
			sprintf('%s -> accepts(%s)', $type->describe(VerbosityLevel::precise()), $offsetType->describe(VerbosityLevel::precise())),
		);
	}

}
