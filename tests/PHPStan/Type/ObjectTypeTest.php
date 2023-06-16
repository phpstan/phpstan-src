<?php declare(strict_types = 1);

namespace PHPStan\Type;

use ArrayAccess;
use ArrayObject;
use Bug4008\BaseModel;
use Bug4008\ChildGenericGenericClass;
use Bug4008\GenericClass;
use Bug4008\Model;
use Bug8850\UserInSessionInRoleEndpointExtension;
use Bug9006\TestInterface;
use Closure;
use Countable;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use ExtendsThrowable\ExtendsThrowable;
use Generator;
use InvalidArgumentException;
use Iterator;
use LogicException;
use ObjectTypeEnums\FooEnum;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\HasMethodType;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\ConstantNumericComparisonTypeTrait;
use SimpleXMLElement;
use stdClass;
use Throwable;
use ThrowPoints\TryCatch\MyInvalidArgumentException;
use Traversable;
use function count;
use function sprintf;
use const PHP_VERSION_ID;

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
				PHP_VERSION_ID < 80200 ? TrinaryLogic::createMaybe() : TrinaryLogic::createNo(),
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
				TrinaryLogic::createMaybe(),
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
				TrinaryLogic::createMaybe(),
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
			49 => [
				new ObjectType(Exception::class, new ObjectType(InvalidArgumentException::class)),
				new ObjectType(InvalidArgumentException::class),
				TrinaryLogic::createNo(),
			],
			50 => [
				new ObjectType(Exception::class, new ObjectType(InvalidArgumentException::class)),
				new ObjectType(MyInvalidArgumentException::class),
				TrinaryLogic::createNo(),
			],
			51 => [
				new ObjectType(Exception::class, new ObjectType(InvalidArgumentException::class)),
				new ObjectType(LogicException::class),
				TrinaryLogic::createMaybe(),
			],
			52 => [
				new ObjectType(InvalidArgumentException::class, new ObjectType(MyInvalidArgumentException::class)),
				new ObjectType(Exception::class),
				TrinaryLogic::createMaybe(),
			],
			53 => [
				new ObjectType(InvalidArgumentException::class, new ObjectType(MyInvalidArgumentException::class)),
				new ObjectType(Exception::class, new ObjectType(InvalidArgumentException::class)),
				TrinaryLogic::createNo(),
			],
			54 => [
				new ObjectType(InvalidArgumentException::class),
				new ObjectType(Exception::class, new ObjectType(InvalidArgumentException::class)),
				TrinaryLogic::createNo(),
			],
			55 => [
				new ObjectType(stdClass::class, new ObjectType(Throwable::class)),
				new ObjectType(Throwable::class),
				TrinaryLogic::createNo(),
			],
			56 => [
				new ObjectType(Type::class, new UnionType([
					new ObjectType(ConstantIntegerType::class),
					new ObjectType(IntegerRangeType::class),
				])),
				new ObjectType(IntegerType::class),
				TrinaryLogic::createMaybe(),
			],
			57 => [
				new ObjectType(Throwable::class),
				new ObjectType(ExtendsThrowable::class),
				TrinaryLogic::createYes(),
			],
			58 => [
				new ObjectType(Throwable::class, new ObjectType(InvalidArgumentException::class)),
				new ObjectType(ExtendsThrowable::class),
				TrinaryLogic::createMaybe(),
			],
			59 => [
				new ObjectType(DateTime::class),
				new ObjectType(ConstantNumericComparisonTypeTrait::class),
				TrinaryLogic::createNo(),
			],
			60 => [
				new ObjectType(ConstantNumericComparisonTypeTrait::class),
				new ObjectType(DateTime::class),
				TrinaryLogic::createNo(),
			],
			61 => [
				new ObjectType(UserInSessionInRoleEndpointExtension::class),
				new ThisType($reflectionProvider->getClass(UserInSessionInRoleEndpointExtension::class)),
				TrinaryLogic::createYes(),
			],
			62 => [
				new ObjectType(TestInterface::class),
				new ClosureType([], new MixedType(), false),
				TrinaryLogic::createNo(),
			],
			63 => [
				new ObjectType(TestInterface::class),
				new ObjectType(Closure::class),
				TrinaryLogic::createNo(),
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
				new GenericObjectType(Traversable::class, [new MixedType(true), new ObjectType('DateTimeInterface')]),
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
			[
				new ObjectType(TestInterface::class),
				new ClosureType([], new MixedType(), false),
				TrinaryLogic::createNo(),
			],
			63 => [
				new ObjectType(TestInterface::class),
				new ObjectType(Closure::class),
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

	public function dataGetEnumCases(): iterable
	{
		yield [
			new ObjectType(stdClass::class),
			[],
		];

		yield [
			new ObjectType(FooEnum::class),
			[
				new EnumCaseObjectType(FooEnum::class, 'FOO'),
				new EnumCaseObjectType(FooEnum::class, 'BAR'),
				new EnumCaseObjectType(FooEnum::class, 'BAZ'),
			],
		];

		yield [
			new ObjectType(FooEnum::class, new EnumCaseObjectType(FooEnum::class, 'FOO')),
			[
				new EnumCaseObjectType(FooEnum::class, 'BAR'),
				new EnumCaseObjectType(FooEnum::class, 'BAZ'),
			],
		];

		yield [
			new ObjectType(FooEnum::class, new UnionType([new EnumCaseObjectType(FooEnum::class, 'FOO'), new EnumCaseObjectType(FooEnum::class, 'BAR')])),
			[
				new EnumCaseObjectType(FooEnum::class, 'BAZ'),
			],
		];
	}

	/**
	 * @dataProvider dataGetEnumCases
	 * @param list<EnumCaseObjectType> $expectedEnumCases
	 */
	public function testGetEnumCases(
		ObjectType $type,
		array $expectedEnumCases,
	): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$enumCases = $type->getEnumCases();
		$this->assertCount(count($expectedEnumCases), $enumCases);
		foreach ($enumCases as $i => $enumCase) {
			$expectedEnumCase = $expectedEnumCases[$i];
			$this->assertTrue($expectedEnumCase->equals($enumCase), sprintf('%s->equals(%s)', $expectedEnumCase->describe(VerbosityLevel::precise()), $enumCase->describe(VerbosityLevel::precise())));
		}
	}

	public function testClassReflectionWithTemplateBound(): void
	{
		$type = new ObjectType(GenericClass::class);
		$classReflection = $type->getClassReflection();
		$this->assertNotNull($classReflection);
		$tModlel = $classReflection->getActiveTemplateTypeMap()->getType('TModlel');
		$this->assertNotNull($tModlel);
		$this->assertSame(BaseModel::class, $tModlel->describe(VerbosityLevel::precise()));
	}

	public function testClassReflectionParentWithTemplateBound(): void
	{
		$type = new ObjectType(ChildGenericGenericClass::class);
		$classReflection = $type->getClassReflection();
		$this->assertNotNull($classReflection);
		$ancestor = $classReflection->getAncestorWithClassName(GenericClass::class);
		$this->assertNotNull($ancestor);
		$tModlel = $ancestor->getActiveTemplateTypeMap()->getType('TModlel');
		$this->assertNotNull($tModlel);
		$this->assertSame(Model::class, $tModlel->describe(VerbosityLevel::precise()));
	}

}
